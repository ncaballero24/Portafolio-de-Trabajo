<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

$direcciones = direcciones_inindetec();
$page_title = 'Direcciones | Quántum ININDETEC';
$page_description = 'Directorio de las áreas especializadas de ININDETEC.';
$canonical = url_base() . '/direcciones.php';
$body_class = 'quantum-direcciones-page';
include 'includes/header.php';
?>
<main class="detalle direcciones-page" id="contenido-principal">
  <header class="encabezado-listado direcciones-encabezado">
    <span>Organización institucional</span>
    <h1>Direcciones</h1>
    <p>Selecciona un emblema para consultar su Dirección.</p>
  </header>

  <section class="direcciones-grid" aria-label="Direcciones de ININDETEC">
    <?php foreach ($direcciones as $slug => $direccion): ?>
      <a class="direccion-card" href="direccion.php?dir=<?= rawurlencode($slug) ?>">
        <span class="direccion-logo">
          <img src="<?= e($direccion['logo']) ?>" alt="Emblema de <?= e($direccion['nombre']) ?>" loading="lazy" decoding="async">
        </span>
        <span class="direccion-card-contenido">
          <small><?= e($direccion['sigla']) ?></small>
          <strong><?= e($direccion['nombre']) ?></strong>
          <span>Consultar Dirección</span>
        </span>
      </a>
    <?php endforeach; ?>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
