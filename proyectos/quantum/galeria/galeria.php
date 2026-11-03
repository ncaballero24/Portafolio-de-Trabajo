<?php
require_once '../conexion.php';
require_once '../includes/funciones.php';
$pagina = pagina_actual();
$porPagina = 12;
$total = (int) $conn->query('SELECT COUNT(*) AS total FROM galeria')->fetch_assoc()['total'];
$offset = ($pagina - 1) * $porPagina;
$stmt = $conn->prepare('SELECT id, titulo, imagen, fecha FROM galeria ORDER BY fecha DESC LIMIT ? OFFSET ?');
$stmt->bind_param('ii', $porPagina, $offset);
$stmt->execute();
$imagenes = $stmt->get_result();
$prefijo = '../';
$page_title = 'Galería ININDETEC | Quántum ININDETEC';
$page_description = 'Galería ININDETEC de imágenes institucionales, marítimas y navales.';
$canonical = url_base() . '/galeria/galeria.php';
$body_class = 'quantum-listado-page quantum-galeria-page';
include '../includes/header.php';
?>
<main class="detalle" id="contenido-principal">
  <header class="encabezado-listado"><span>Archivo visual</span><h1>Galería ININDETEC</h1><p><?= $total ?> imagen<?= $total === 1 ? '' : 'es' ?></p></header>
  <?php if ($imagenes->num_rows): ?>
    <section class="galeria-grid">
      <?php while ($row = $imagenes->fetch_assoc()): ?>
        <button class="galeria-card" type="button" data-imagen="../<?= e($row['imagen']) ?>" data-titulo="<?= e($row['titulo']) ?>">
          <img src="../<?= e($row['imagen']) ?>" alt="<?= e($row['titulo']) ?>" loading="lazy" decoding="async">
          <span><?= e($row['titulo']) ?></span>
        </button>
      <?php endwhile; ?>
    </section>
    <?= render_paginacion($pagina, max(1, (int) ceil($total / $porPagina))) ?>
  <?php else: ?><div class="estado-vacio"><h2>Aún no hay imágenes</h2><p>La galería se actualizará próximamente.</p></div><?php endif; ?>
</main>
<div id="modalGaleria" class="modal-galeria" role="dialog" aria-modal="true" aria-label="Visor de galería" hidden>
  <button class="cerrar-galeria" type="button" aria-label="Cerrar">×</button>
  <button class="navegar-galeria navegar-galeria-anterior" type="button" aria-label="Imagen anterior">
    <span aria-hidden="true">‹</span>
  </button>
  <figure class="visor-holografico">
    <div class="marco-holografico">
      <img id="imagenGrande" alt="">
      <span class="holograma-reticula" aria-hidden="true"></span>
      <span class="holograma-particulas" aria-hidden="true"></span>
      <span class="holograma-esquinas" aria-hidden="true"></span>
      <span class="barrido-holografico" aria-hidden="true"></span>
    </div>
    <div class="holograma-lectura"><span aria-hidden="true">VISOR QUANTUM</span><figcaption id="tituloImagenGrande"></figcaption><p class="contador-galeria" id="contadorGaleria" aria-live="polite"></p></div>
  </figure>
  <button class="navegar-galeria navegar-galeria-siguiente" type="button" aria-label="Imagen siguiente">
    <span aria-hidden="true">›</span>
  </button>
</div>
<?php include '../includes/footer.php'; ?>
