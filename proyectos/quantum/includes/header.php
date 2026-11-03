<?php
require_once __DIR__ . '/funciones.php';
cabeceras_seguridad();

$prefijo = $prefijo ?? '';
$page_title = $page_title ?? 'Quántum ININDETEC';
$page_description = $page_description ?? 'Gaceta oficial de innovación, tecnología, defensa y conocimiento aplicado.';
$canonical = $canonical ?? url_base() . '/';
$og_image = $og_image ?? (url_base() . '/assets/quantum-logo-horizontal.png');
$categorias_menu = isset($conn) ? categorias_activas($conn) : [];
$pagina_menu = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#071b55">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Quántum">
  <title><?= e($page_title) ?></title>
  <meta name="description" content="<?= e($page_description) ?>">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="alternate" type="application/rss+xml" title="Quántum ININDETEC" href="<?= e(url_base() . '/rss.php') ?>">
  <meta property="og:title" content="<?= e($page_title) ?>">
  <meta property="og:description" content="<?= e($page_description) ?>">
  <meta property="og:type" content="<?= e($og_type ?? 'website') ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <?php if ($og_image): ?><meta property="og:image" content="<?= e($og_image) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" type="image/png" href="<?= e(asset_url('assets/quantum-logo-q.png', $prefijo)) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset_url('assets/pwa/apple-touch-icon.png', $prefijo)) ?>">
  <link rel="manifest" href="<?= e(asset_url('manifest.webmanifest', $prefijo)) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800;900&family=Source+Serif+4:opsz,wght@8..60,400;8..60,500;8..60,600;8..60,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset_url('css/estilos.css', $prefijo)) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/quantum.css', $prefijo)) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/quantum-dark.css', $prefijo)) ?>">
  <?= $extra_head ?? '' ?>
</head>
<body class="<?= e($body_class ?? '') ?>">
<a class="skip-link" href="#contenido-principal">Saltar al contenido</a>
<header class="site-header">
  <div class="top-header">
    <div class="quantum-brand-zone">
      <a class="marina-emblem" href="<?= e($prefijo) ?>index.php" aria-label="Unidad de Investigación y Desarrollo Tecnológico">
        <img src="<?= e(asset_url('assets/logo-holografico-80-animado.gif', $prefijo)) ?>" alt="Holograma animado de la Unidad de Investigación y Desarrollo Tecnológico">
      </a>
    </div>
    <a class="quantum-wordmark" href="<?= e($prefijo) ?>index.php" aria-label="Quántum ININDETEC, ir al inicio">
      <img src="<?= e(asset_url('assets/quantum-logo-horizontal.png', $prefijo)) ?>" alt="Quántum ININDETEC, Gaceta Oficial">
    </a>
    <div class="header-right">
      <form action="<?= e($prefijo) ?>buscar.php" method="GET" class="search-box" role="search">
        <label class="sr-only" for="busqueda-header">Buscar en la gaceta</label>
        <span class="campo-sugerencias-wrap">
          <input id="busqueda-header" type="search" name="q" placeholder="Buscar en la gaceta..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" data-busqueda-sugerencias data-endpoint="<?= e($prefijo) ?>api/buscar_sugerencias.php" required>
          <span class="sugerencias-busqueda sugerencias-header" role="listbox" hidden></span>
        </span>
      </form>
      <div class="menu">
        <button class="menu-btn" type="button" aria-label="Abrir menú" aria-expanded="false">
          <span class="menu-icon" aria-hidden="true"></span>
        </button>
        <div class="menu-content">
          <a href="<?= e($prefijo) ?>index.php"<?= $pagina_menu === 'index.php' ? ' aria-current="page"' : '' ?>>Inicio</a>
          <a class="nav-simposium-destacado" href="<?= e($prefijo) ?>simposium.php"<?= $pagina_menu === 'simposium.php' ? ' aria-current="page"' : '' ?>>SIMPOSIUM 2026</a>
          <?php foreach ($categorias_menu as $categoria): ?>
            <a href="<?= e($prefijo) ?>categoria.php?cat=<?= rawurlencode($categoria['nombre']) ?>"><?= e($categoria['nombre']) ?></a>
          <?php endforeach; ?>
          <a href="<?= e($prefijo) ?>direcciones.php"<?= $pagina_menu === 'direcciones.php' || $pagina_menu === 'direccion.php' ? ' aria-current="page"' : '' ?>>Direcciones</a>
          <a href="<?= e($prefijo) ?>videos.php"<?= $pagina_menu === 'videos.php' ? ' aria-current="page"' : '' ?>>Videos</a>
          <a href="<?= e($prefijo) ?>galeria/galeria.php"<?= $pagina_menu === 'galeria.php' ? ' aria-current="page"' : '' ?>>Galería ININDETEC</a>
          <a href="<?= e($prefijo) ?>guardados.php"<?= $pagina_menu === 'guardados.php' ? ' aria-current="page"' : '' ?>>Mis lecturas <span class="contador-guardados" data-contador-guardados>0</span></a>
        </div>
      </div>
    </div>
  </div>
  <nav class="nav-principal" aria-label="Navegación principal">
    <a href="<?= e($prefijo) ?>index.php"<?= $pagina_menu === 'index.php' ? ' aria-current="page"' : '' ?>>Inicio</a>
    <a class="nav-simposium-destacado" href="<?= e($prefijo) ?>simposium.php"<?= $pagina_menu === 'simposium.php' ? ' aria-current="page"' : '' ?>>SIMPOSIUM 2026</a>
    <a href="<?= e($prefijo) ?>categoria.php?cat=<?= rawurlencode('Tecnología') ?>"<?= $pagina_menu === 'categoria.php' && ($_GET['cat'] ?? '') === 'Tecnología' ? ' aria-current="page"' : '' ?>>Tecnología</a>
    <a href="<?= e($prefijo) ?>categoria.php?cat=<?= rawurlencode('Los pasantes opinan') ?>"<?= $pagina_menu === 'categoria.php' && ($_GET['cat'] ?? '') === 'Los pasantes opinan' ? ' aria-current="page"' : '' ?>>Los pasantes opinan</a>
    <a href="<?= e($prefijo) ?>direcciones.php"<?= $pagina_menu === 'direcciones.php' || $pagina_menu === 'direccion.php' ? ' aria-current="page"' : '' ?>>Direcciones</a>
    <a href="<?= e($prefijo) ?>videos.php"<?= $pagina_menu === 'videos.php' ? ' aria-current="page"' : '' ?>>Videos</a>
    <a href="<?= e($prefijo) ?>galeria/galeria.php"<?= $pagina_menu === 'galeria.php' ? ' aria-current="page"' : '' ?>>Galería ININDETEC</a>
    <a href="<?= e($prefijo) ?>guardados.php"<?= $pagina_menu === 'guardados.php' ? ' aria-current="page"' : '' ?>>Mis lecturas <span class="contador-guardados" data-contador-guardados>0</span></a>
  </nav>
</header>
