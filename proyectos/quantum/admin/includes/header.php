<?php
$admin_titulo = $admin_titulo ?? 'Panel Administrativo';
$admin_descripcion = $admin_descripcion ?? 'Gestión editorial';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($admin_titulo) ?> | Administración</title>
  <link rel="stylesheet" href="<?= e(asset_url('css/estilos.css', '../')) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/quantum.css', '../')) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/quantum-dark.css', '../')) ?>">
  <?= $admin_extra_head ?? '' ?>
</head>
<body class="admin-body">
<a class="skip-link" href="#contenido-admin">Saltar al contenido</a>
<?php include __DIR__ . '/nav.php'; ?>
