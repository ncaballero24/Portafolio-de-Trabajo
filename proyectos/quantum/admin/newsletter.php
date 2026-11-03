<?php
require_once 'includes/inicio.php';
require_once '../includes/correo.php';
requerir_admin(['Administrador', 'Editor']);

$mensajeAlerta = '';
$tipoAlerta = 'mensaje-formulario';
$preview = null;
$asuntoValor = '';
$mensajeValor = '';
$correoPrueba = trim((string) (getenv('MAIL_REPLY_TO') ?: ''));
$pruebaConfirmada = (int) ($_SESSION['newsletter_prueba_ok_hasta'] ?? 0) >= time()
    && ($_SESSION['newsletter_prueba_transporte'] ?? '') === (string) getenv('MAIL_TRANSPORT');
if (!$pruebaConfirmada) {
    unset($_SESSION['newsletter_prueba_ok_hasta']);
}

if (!empty($_GET['cargar'])) {
    $idCargar = (int) $_GET['cargar'];
    $stmt = $conn->prepare("SELECT asunto, mensaje FROM newsletters WHERE id=? AND estado='borrador'");
    $stmt->bind_param('i', $idCargar);
    $stmt->execute();
    if ($borrador = $stmt->get_result()->fetch_assoc()) {
        $asuntoValor = $borrador['asunto'];
        $mensajeValor = $borrador['mensaje'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = sanitizar_html($_POST['mensaje'] ?? '');
    $accion = $_POST['accion'] ?? 'preview';
    $correoPrueba = trim($_POST['correo_prueba'] ?? $correoPrueba);
    $asuntoValor = $asunto;
    $mensajeValor = $mensaje;

    if ($asunto === '' || mb_strlen($asunto) > 255 || $mensaje === '') {
        $mensajeAlerta = 'Completa el asunto y el contenido del boletín.';
        $tipoAlerta = 'mensaje-error';
    } elseif ($accion === 'preview') {
        $preview = ['asunto' => $asunto, 'mensaje' => $mensaje];
    } elseif ($accion === 'borrador') {
        $estado = 'borrador';
        $autor = $_SESSION['admin_nombre'];
        $stmt = $conn->prepare('INSERT INTO newsletters(asunto,mensaje,enviado_por,estado,enviados,fallidos) VALUES(?,?,?,?,0,0)');
        $stmt->bind_param('ssss', $asunto, $mensaje, $autor, $estado);
        $stmt->execute();
        registrar_bitacora($conn, 'newsletter_borrador', 'newsletter', (int) $conn->insert_id, $asunto);
        $mensajeAlerta = 'Newsletter guardado como borrador.';
        $asuntoValor = '';
        $mensajeValor = '';
    } elseif ($accion === 'prueba') {
        if (!filter_var($correoPrueba, FILTER_VALIDATE_EMAIL)) {
            $mensajeAlerta = 'Escribe una dirección válida para la prueba.';
            $tipoAlerta = 'mensaje-error';
        } else {
            try {
                $messageId = enviar_correo($correoPrueba, '[PRUEBA] ' . $asunto, cuerpo_boletin($mensaje), trim(strip_tags($mensaje)));
                $_SESSION['newsletter_prueba_ok_hasta'] = time() + 1800;
                $_SESSION['newsletter_prueba_transporte'] = (string) getenv('MAIL_TRANSPORT');
                $pruebaConfirmada = true;
                registrar_bitacora($conn, 'newsletter_prueba', 'newsletter', null, 'Prueba enviada a ' . $correoPrueba);
                $mensajeAlerta = 'Proveedor aceptó la prueba para ' . $correoPrueba . '. Confirma su recepción antes del envío masivo. Referencia: ' . $messageId;
            } catch (Throwable $e) {
                unset($_SESSION['newsletter_prueba_ok_hasta']);
                $pruebaConfirmada = false;
                error_log('Fallo de prueba de correo: ' . $e->getMessage());
                $mensajeAlerta = getenv('MAIL_TRANSPORT') === 'brevo_api' && $e instanceof RuntimeException
                    ? $e->getMessage() : 'No se pudo enviar la prueba. Revisa la configuración de correo y el registro de errores.';
                $tipoAlerta = 'mensaje-error';
            }
        }
    } elseif ($accion === 'enviar') {
        if (!$pruebaConfirmada) {
            $mensajeAlerta = 'Antes del envío masivo debes completar correctamente un envío de prueba en esta sesión.';
            $tipoAlerta = 'mensaje-error';
        } elseif (!correo_configurado()) {
            $mensajeAlerta = 'Configura primero las credenciales de correo y envía una prueba.';
            $tipoAlerta = 'mensaje-error';
        } else {
            set_time_limit(0);
            $suscriptores = $conn->query('SELECT correo, token_baja FROM suscriptores WHERE activo=1 ORDER BY id');
            $enviados = 0;
            $fallidos = 0;
            try {
                while ($s = $suscriptores->fetch_assoc()) {
                    $baja = url_publica_aplicacion() . '/baja.php?token=' . rawurlencode($s['token_baja']);
                    try {
                        enviar_correo($s['correo'], $asunto, cuerpo_boletin($mensaje, $baja), trim(strip_tags($mensaje)) . "\n\nCancelar suscripción: " . $baja, $baja);
                        $enviados++;
                    } catch (Throwable $e) {
                        $fallidos++;
                        error_log('Fallo de correo para un suscriptor: ' . $e->getMessage());
                    }
                }
            } catch (Throwable $e) {
                error_log('No se pudo iniciar el correo: ' . $e->getMessage());
                $fallidos += max(0, (int) $suscriptores->num_rows - $enviados);
            }

            $estado = $fallidos > 0 ? 'fallido' : 'enviado';
            $autor = $_SESSION['admin_nombre'];
            $stmt = $conn->prepare('INSERT INTO newsletters(asunto,mensaje,enviado_por,estado,enviados,fallidos) VALUES(?,?,?,?,?,?)');
            $stmt->bind_param('ssssii', $asunto, $mensaje, $autor, $estado, $enviados, $fallidos);
            $stmt->execute();
            registrar_bitacora($conn, 'newsletter_envio', 'newsletter', (int) $conn->insert_id, "Asunto: $asunto; enviados: $enviados; fallidos: $fallidos");
            unset($_SESSION['newsletter_prueba_ok_hasta']);
            $pruebaConfirmada = false;
            $mensajeAlerta = "Proceso terminado. Aceptados por el proveedor: $enviados. No confirmados: $fallidos. La entrega final se consulta en Brevo.";
            $tipoAlerta = $fallidos > 0 ? 'mensaje-error' : 'mensaje-formulario';
            if ($fallidos === 0) {
                $asuntoValor = '';
                $mensajeValor = '';
            }
        }
    }
}

$totalActivos = (int) $conn->query('SELECT COUNT(*) total FROM suscriptores WHERE activo=1')->fetch_assoc()['total'];
$historial = $conn->query('SELECT * FROM newsletters ORDER BY fecha DESC LIMIT 20');
$admin_titulo = 'Newsletter';
$admin_extra_head = '<script src="../tinymce/js/tinymce/tinymce.min.js"></script>';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <header class="encabezado-listado"><span>Boletín</span><h1>Newsletter</h1><p><?= $totalActivos ?> destinatarios activos</p><a class="btn btn-secundario" href="suscriptores.php">Administrar audiencia</a></header>
  <section class="noticia-detalle">
    <p><strong>Remitente:</strong> <?= e((string) (getenv('MAIL_FROM_ADDRESS') ?: '')) ?> · <strong><?= getenv('MAIL_TRANSPORT') === 'brevo_api' ? 'API Brevo' : 'SMTP' ?>:</strong> <?= correo_configurado() ? 'configurado' : 'pendiente' ?></p>
    <?php if ($mensajeAlerta): ?><p class="<?= e($tipoAlerta) ?>" role="status"><?= e($mensajeAlerta) ?></p><?php endif; ?>
    <form method="POST" data-editor-form data-autosave-key="newsletter-editor"><?= csrf_input() ?>
      <label>Asunto<input class="admin-input" name="asunto" maxlength="255" value="<?= e($asuntoValor) ?>" required></label>
      <label>Contenido<textarea class="editor-html" name="mensaje" required><?= e($mensajeValor) ?></textarea></label>
      <p data-autosave-status>El borrador local se guarda automáticamente.</p>
      <div class="admin-form-grid"><label>Enviar prueba a<input class="admin-input" type="email" name="correo_prueba" value="<?= e($correoPrueba) ?>"></label></div>
      <p><strong>Envío masivo:</strong> <?= $pruebaConfirmada ? 'habilitado durante 30 minutos' : 'bloqueado hasta completar una prueba' ?>.</p>
      <div class="newsletter-actions"><button class="btn btn-secundario" name="accion" value="preview">Vista previa</button><button class="btn btn-secundario" name="accion" value="borrador">Guardar borrador</button><button class="btn btn-secundario" name="accion" value="prueba">Enviar prueba</button><button class="btn" name="accion" value="enviar" <?= $pruebaConfirmada ? '' : 'disabled title="Completa primero un envío de prueba"' ?> onclick="return confirm('¿Enviar a todos los suscriptores activos? Esta acción no se puede deshacer.')">Enviar newsletter</button></div>
    </form>
  </section>
  <?php if ($preview): ?><section class="newsletter-preview noticia-detalle"><span>Vista previa</span><h1><?= e($preview['asunto']) ?></h1><div class="contenido-noticia"><?= $preview['mensaje'] ?></div></section><?php endif; ?>
  <div class="tabla-admin"><table><thead><tr><th>Asunto</th><th>Estado</th><th>Enviados</th><th>Fallidos</th><th>Autor</th><th>Fecha</th><th></th></tr></thead><tbody>
  <?php while ($row = $historial->fetch_assoc()): ?><tr><td><?= e($row['asunto']) ?></td><td><span class="estado-newsletter <?= e($row['estado']) ?>"><?= e($row['estado']) ?></span></td><td><?= (int) $row['enviados'] ?></td><td><?= (int) $row['fallidos'] ?></td><td><?= e($row['enviado_por']) ?></td><td><?= e($row['fecha']) ?></td><td><?php if ($row['estado'] === 'borrador'): ?><a class="btn btn-secundario" href="?cargar=<?= (int) $row['id'] ?>">Cargar</a><?php endif; ?></td></tr><?php endwhile; ?>
  </tbody></table></div>
</main>
<?php include 'includes/footer.php'; ?>
