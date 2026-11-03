<?php
require_once 'includes/inicio.php';
$preview = $_SESSION['preview_noticia'] ?? null;
if (!$preview) {
    exit('No hay una vista previa disponible.');
}
$admin_titulo = 'Vista previa';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <article class="noticia-detalle preview-editorial">
    <span class="categoria"><?= e($preview['categoria'] ?? 'Sin categoría') ?></span>
    <h1><?= e($preview['titulo'] ?? 'Sin título') ?></h1>
    <?php if (!empty($preview['autorPublico'])): ?>
      <div class="firma-noticia firma-preview">
        <?php if (!empty($preview['autorImagen'])): ?><img src="<?= e(url_imagen($preview['autorImagen'], '../')) ?>" alt=""><?php endif; ?>
        <div><span><?= ($preview['tipoAutoria'] ?? 'escrito_por') === 'tomado_de' ? 'Tomado de:' : 'Escrito por:' ?></span><strong><?= e($preview['autorPublico']) ?></strong></div>
      </div>
    <?php endif; ?>
    <p class="lead"><?= e($preview['resumen'] ?? '') ?></p>
    <?php if (!empty($preview['imagen'])): ?><img src="<?= e(url_imagen($preview['imagen'], '../')) ?>" alt="<?= e($preview['imagenAlt'] ?? '') ?>"><?php endif; ?>
    <?php if (!empty($preview['video'])): ?>
      <figure class="noticia-video">
        <video controls preload="metadata" playsinline>
          <source src="../<?= e($preview['video']) ?>" type="<?= e($preview['videoMime'] ?? 'video/mp4') ?>">
        </video>
      </figure>
    <?php endif; ?>
    <?php if (!empty($preview['medios'])): ?>
      <section class="noticia-medios" aria-label="Galería multimedia de la noticia">
        <?php foreach ($preview['medios'] as $medio): ?>
          <figure class="noticia-medio <?= ($medio['tipo'] ?? '') === 'video' ? 'noticia-medio-video' : 'noticia-medio-imagen' ?>">
            <?php if (($medio['tipo'] ?? '') === 'video'): ?>
              <video controls preload="metadata" playsinline>
                <source src="../<?= e($medio['archivo']) ?>" type="<?= e($medio['mime'] ?: 'video/mp4') ?>">
              </video>
            <?php else: ?>
              <img src="<?= e(url_imagen($medio['archivo'], '../')) ?>" alt="">
            <?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
    <div class="contenido-noticia"><?= sanitizar_html($preview['contenido'] ?? '') ?></div>
  </article>
</main>
<?php include 'includes/footer.php'; ?>
