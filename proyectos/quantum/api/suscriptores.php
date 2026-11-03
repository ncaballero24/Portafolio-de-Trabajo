<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
cabeceras_seguridad();
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!empty($_POST['sitio_web'])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo procesar la solicitud.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$intentos = $_SESSION['intentos_suscripcion'] ?? [];
$intentos = array_values(array_filter($intentos, fn($tiempo) => $tiempo > time() - 3600));
if (count($intentos) >= 5) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'mensaje' => 'Demasiados intentos. Prueba más tarde.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$intentos[] = time();
$_SESSION['intentos_suscripcion'] = $intentos;
$correo = mb_strtolower(trim($_POST['correo'] ?? ''));
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 255) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Ingresa un correo electrónico válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$token = bin2hex(random_bytes(32));
$stmt = $conn->prepare('INSERT INTO suscriptores (correo, token_baja, activo) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE activo=1, token_baja=COALESCE(token_baja, VALUES(token_baja))');
$stmt->bind_param('ss', $correo, $token);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo guardar tu suscripción.'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['ok' => true, 'mensaje' => $stmt->affected_rows === 1 ? 'Suscripción registrada correctamente.' : 'Tu suscripción ya está activa.'], JSON_UNESCAPED_UNICODE);
