<?php
require_once '../conexion.php';
require_once '../includes/funciones.php';
cabeceras_seguridad();
iniciar_sesion_segura();

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'local');
$stmtBloqueo = $conn->prepare("SELECT COUNT(*) AS total FROM intentos_login WHERE ip_hash = ? AND exitoso = 0 AND fecha >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$stmtBloqueo->bind_param('s', $ipHash);
$stmtBloqueo->execute();
$intentos = (int) $stmtBloqueo->get_result()->fetch_assoc()['total'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    if ($intentos >= 5) {
        $error = 'Demasiados intentos. Espera 15 minutos antes de volver a intentar.';
    } else {
        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $conn->prepare('SELECT id, nombre, usuario, password, rol FROM usuarios_admin WHERE usuario = ? LIMIT 1');
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $exitoso = $admin && password_verify($password, $admin['password']);
        $valorExitoso = $exitoso ? 1 : 0;
        $stmtRegistro = $conn->prepare('INSERT INTO intentos_login (ip_hash, exitoso) VALUES (?, ?)');
        $stmtRegistro->bind_param('si', $ipHash, $valorExitoso);
        $stmtRegistro->execute();

        if ($exitoso) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            $_SESSION['admin_rol'] = $admin['rol'];
            unset($_SESSION['csrf_token']);
            registrar_bitacora($conn, 'inicio_sesion', 'sesion', null, 'Inicio de sesión correcto');
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>Acceso administrativo | Quántum ININDETEC</title>
  <link rel="stylesheet" href="<?= e(asset_url('css/estilos.css', '../')) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/quantum.css', '../')) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/quantum-dark.css', '../')) ?>">
</head>
<body class="admin-login-body">
<main class="admin-login">
  <section class="noticia-detalle">
    <div class="login-brand"><img src="<?= e(asset_url('assets/quantum-logo-vertical.png', '../')) ?>" alt=""><strong>QUÁNTUM</strong><span>ININDETEC · GACETA OFICIAL</span></div>
    <span class="categoria">Acceso restringido</span>
    <h1>Panel Administrativo</h1>
    <p>Ingresa tus credenciales para continuar.</p>
    <?php if ($error): ?><p class="mensaje-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="POST">
      <?= csrf_input() ?>
      <label>Usuario<input class="admin-input" type="text" name="usuario" autocomplete="username" maxlength="100" required autofocus></label>
      <label>Contraseña<input class="admin-input" type="password" name="password" autocomplete="current-password" required></label>
      <button class="btn" type="submit" <?= $intentos >= 5 ? 'disabled' : '' ?>>Ingresar</button>
    </form>
    <a href="../index.php">Volver al sitio</a>
  </section>
</main>
</body>
</html>
