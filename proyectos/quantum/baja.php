<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';
$token = trim($_GET['token'] ?? '');
$actualizado = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = $conn->prepare('UPDATE suscriptores SET activo=0 WHERE token_baja=?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $actualizado = $stmt->affected_rows > 0;
}
$page_title = 'Cancelar suscripción | Quántum ININDETEC';
$page_description = 'Administración de la suscripción al boletín.';
$canonical = url_base() . '/baja.php';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-principal"><section class="estado-vacio"><h1><?= $actualizado ? 'Suscripción cancelada' : 'Enlace no válido' ?></h1><p><?= $actualizado ? 'Ya no recibirás nuevos boletines. Puedes volver a suscribirte cuando quieras.' : 'El enlace de cancelación no existe o ya fue utilizado.' ?></p><a class="btn" href="index.php">Volver al inicio</a></section></main>
<?php include 'includes/footer.php'; ?>
