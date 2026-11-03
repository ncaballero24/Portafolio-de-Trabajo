<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

$videos = $conn->query("SELECT titulo, descripcion, archivo, mime, fecha FROM videos WHERE publicado=1 ORDER BY fecha DESC, id DESC");
$totalVideos = $videos ? $videos->num_rows : 0;

$page_title = 'Videos | Quántum ININDETEC';
$page_description = 'Videoteca institucional de Quántum ININDETEC.';
$canonical = url_base() . '/videos.php';
$body_class = 'quantum-listado-page quantum-videos-page';
include 'includes/header.php';
?>
<main class="detalle videos-page" id="contenido-principal">
  <header class="encabezado-listado videos-encabezado">
    <span>Contenido audiovisual</span>
    <h1>Videoteca Quántum</h1>
    <p><?= $totalVideos ?> video<?= $totalVideos === 1 ? '' : 's' ?> disponible<?= $totalVideos === 1 ? '' : 's' ?></p>
  </header>

  <?php if ($totalVideos): ?>
    <section class="videos-grid" aria-label="Videos publicados">
      <?php while ($video = $videos->fetch_assoc()): ?>
        <article class="video-card">
          <div class="video-frame">
            <video controls preload="metadata" playsinline>
              <source src="<?= e($video['archivo']) ?>" type="<?= e($video['mime']) ?>">
              Tu navegador no puede reproducir este video.
            </video>
            <button class="video-play-overlay" type="button" aria-label="Reproducir <?= e($video['titulo']) ?>">
              <span aria-hidden="true"></span>
            </button>
          </div>
          <div class="video-card-body">
            <span>Quántum Multimedia</span>
            <h2><?= e($video['titulo']) ?></h2>
            <?php if ($video['descripcion']): ?><p><?= e($video['descripcion']) ?></p><?php endif; ?>
          </div>
        </article>
      <?php endwhile; ?>
    </section>
  <?php else: ?>
    <section class="estado-vacio videos-vacio">
      <span class="videos-vacio-icono" aria-hidden="true">▶</span>
      <h2>Próximamente</h2>
      <p>La videoteca institucional se actualizará con nuevos contenidos.</p>
    </section>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
