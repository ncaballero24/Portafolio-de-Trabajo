<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

function correo_configurado(): bool
{
    return getenv('MAIL_TRANSPORT') === 'brevo_api'
        ? trim((string) getenv('BREVO_API_KEY')) !== ''
        : smtp_configurado();
}

function brevo_interpretar_respuesta(int $estado, string $respuesta): string
{
    $datos = json_decode($respuesta, true);
    if ($estado === 201 && is_array($datos) && !empty($datos['messageId'])) {
        return (string) $datos['messageId'];
    }
    $mensaje = match ($estado) {
        401 => 'Brevo rechazó la clave API. Usa una clave API, no una clave SMTP.',
        403 => 'Brevo bloqueó la solicitud. Revisa la activación de la cuenta, los permisos de la clave y las IP autorizadas.',
        400 => 'Brevo rechazó los datos. Revisa que el remitente esté verificado y la cuenta habilitada para envíos.',
        402 => 'Brevo indica que no hay créditos disponibles.',
        429 => 'Se alcanzó un límite de Brevo. No repitas el envío inmediatamente.',
        default => 'Brevo no confirmó el envío (HTTP ' . $estado . '). Revisa el historial de Brevo antes de reintentar.',
    };
    // No se muestra ni registra el cuerpo remoto: puede contener datos personales.
    throw new RuntimeException($mensaje);
}

function enviar_correo(string $destinatario, string $asunto, string $html, string $texto, ?string $baja = null): string
{
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('La dirección del destinatario no es válida.');
    }
    if (getenv('MAIL_TRANSPORT') !== 'brevo_api') {
        $correo = crear_cliente_smtp();
        try {
            $correo->addAddress($destinatario);
            $correo->Subject = $asunto;
            $correo->Body = $html;
            $correo->AltBody = $texto;
            if ($baja !== null) {
                $correo->addCustomHeader('List-Unsubscribe', '<' . $baja . '>');
            }
            $correo->send();
            return $correo->getLastMessageID();
        } finally {
            $correo->smtpClose();
        }
    }
    $clave = trim((string) getenv('BREVO_API_KEY'));
    if ($clave === '' || preg_match('/[\r\n]/', $clave)) {
        throw new RuntimeException('Falta configurar una clave API válida de Brevo.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP necesita la extensión cURL para conectar con Brevo.');
    }
    $remitente = trim((string) getenv('MAIL_FROM_ADDRESS'));
    $respuesta = trim((string) (getenv('MAIL_REPLY_TO') ?: $remitente));
    if (!filter_var($remitente, FILTER_VALIDATE_EMAIL) || !filter_var($respuesta, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Configura un remitente y una dirección de respuesta válidos.');
    }
    $payload = [
        'sender' => ['email' => $remitente, 'name' => (string) (getenv('MAIL_FROM_NAME') ?: 'Quántum ININDETEC')],
        'to' => [['email' => $destinatario]],
        'replyTo' => ['email' => $respuesta],
        'subject' => $asunto,
        'htmlContent' => $html,
        'textContent' => $texto,
    ];
    if ($baja !== null) {
        $payload['headers'] = ['List-Unsubscribe' => '<' . $baja . '>'];
    }
    $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => ['accept: application/json', 'content-type: application/json', 'api-key: ' . $clave],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    if (getenv('BREVO_FORCE_IPV6') === '1') {
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6);
    }
    try {
        $body = curl_exec($ch);
        if ($body === false) {
            throw new RuntimeException('No se pudo confirmar el envío con Brevo (red/TLS, código ' . curl_errno($ch) . '). Revisa el historial de Brevo antes de reintentar.');
        }
        return brevo_interpretar_respuesta((int) curl_getinfo($ch, CURLINFO_HTTP_CODE), $body);
    } finally {
        curl_close($ch);
    }
}

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

function smtp_configurado(): bool
{
    return trim((string) getenv('SMTP_USER')) !== '' && trim((string) getenv('SMTP_PASS')) !== '';
}

function crear_cliente_smtp(): PHPMailer
{
    if (!smtp_configurado()) {
        throw new RuntimeException('El correo SMTP todavía no está configurado.');
    }
    $correo = new PHPMailer(true);
    $correo->isSMTP();
    $correo->Host = trim((string) (getenv('SMTP_HOST') ?: 'smtp.gmail.com'));
    $correo->Port = max(1, (int) (getenv('SMTP_PORT') ?: 587));
    $correo->SMTPAuth = true;
    $correo->Username = trim((string) getenv('SMTP_USER'));
    $correo->Password = (string) getenv('SMTP_PASS');
    $correo->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $correo->CharSet = PHPMailer::CHARSET_UTF8;
    $correo->Encoding = PHPMailer::ENCODING_BASE64;
    $correo->Timeout = 25;
    $correo->SMTPKeepAlive = true;
    $correo->isHTML(true);
    $remitente = trim((string) (getenv('MAIL_FROM_ADDRESS') ?: $correo->Username));
    $nombre = trim((string) (getenv('MAIL_FROM_NAME') ?: 'Quántum ININDETEC'));
    $respuesta = trim((string) (getenv('MAIL_REPLY_TO') ?: $remitente));
    $correo->setFrom($remitente, $nombre);
    $correo->addReplyTo($respuesta, $nombre);
    return $correo;
}

function url_publica_aplicacion(): string
{
    $configurada = trim((string) getenv('APP_URL'));
    return rtrim($configurada !== '' ? $configurada : url_base(), '/');
}

function cuerpo_boletin(string $contenido, ?string $urlBaja = null): string
{
    $pie = $urlBaja === null ? '' : '<p style="font-size:12px;color:#64748b">Recibes este correo porque te suscribiste al boletín. <a href="' . e($urlBaja) . '">Cancelar suscripción</a>.</p>';
    return '<div style="font-family:Arial,sans-serif;max-width:680px;margin:auto;color:#1e293b"><h1 style="color:#013d6d">QUÁNTUM ININDETEC</h1><p style="color:#167da9">Gaceta Oficial</p>' . $contenido . '<hr style="border:0;border-top:3px solid #40e0d0">' . $pie . '</div>';
}
