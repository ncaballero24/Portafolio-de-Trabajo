<?php $admin_titulo = $admin_titulo ?? 'Panel Administrativo'; ?>
<header class="admin-header">
  <div class="top-header">
    <div class="logo quantum-admin-brand">
      <img src="<?= e(asset_url('assets/quantum-logo-q.png', '../')) ?>" alt="Logotipo Quantum">
      <span>
        <strong>QUÁNTUM</strong>
        <small><?= e($admin_titulo) ?> · <?= e($_SESSION['admin_rol'] ?? '') ?></small>
      </span>
    </div>
    <button class="admin-menu-toggle" type="button" aria-label="Abrir navegación" aria-expanded="false">☰</button>
  </div>
  <nav class="admin-nav" aria-label="Administración">
    <a href="dashboard.php">Dashboard</a>
    <?php if (($_SESSION['admin_rol'] ?? '') === 'Administrador'): ?><a href="simposium.php">SIMPOSIUM</a><?php endif; ?>
    <a href="nueva_noticia.php">Nueva noticia</a>
    <a href="noticias.php">Noticias</a>
    <?php if (in_array($_SESSION['admin_rol'] ?? '', ['Administrador', 'Editor'], true)): ?>
      <a href="categorias.php">Categorías</a>
      <a href="ultima_hora.php">Última hora</a>
      <a href="galeria_admin.php">Galería ININDETEC</a>
      <a href="videos_admin.php">Videos</a>
      <a href="comentarios.php">Comentarios</a>
      <a href="newsletter.php">Newsletter</a>
      <a href="suscriptores.php">Suscriptores</a>
    <?php endif; ?>
    <?php if (($_SESSION['admin_rol'] ?? '') === 'Administrador'): ?><a href="usuarios.php">Usuarios</a><a href="bitacora.php">Bitácora</a><?php endif; ?>
    <a href="../index.php" target="_blank" rel="noopener">Ver sitio</a>
    <form method="POST" action="logout.php" class="admin-logout-form"><?= csrf_input() ?><button type="submit">Salir</button></form>
  </nav>
</header>
