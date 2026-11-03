<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

$slug = strtolower(trim($_GET['dir'] ?? ''));
$direccion = obtener_direccion_inindetec($slug);
if (!$direccion) {
    http_response_code(404);
    exit('La Dirección solicitada no existe.');
}

$stmt = $conn->prepare("SELECT id, titulo, slug, resumen, imagen, imagen_alt, categoria, fecha
    FROM noticias
    WHERE estado='publicada' AND (categoria=? OR categoria=?)
    ORDER BY fecha DESC LIMIT 6");
$stmt->bind_param('ss', $direccion['nombre'], $direccion['sigla']);
$stmt->execute();
$noticiasDireccion = $stmt->get_result();

$page_title = $direccion['nombre'] . ' | Quántum ININDETEC';
$page_description = $direccion['descripcion'];
$canonical = url_base() . '/direccion.php?dir=' . rawurlencode($slug);
$og_image = url_base() . '/' . $direccion['logo'];
$body_class = 'quantum-direcciones-page';
include 'includes/header.php';
?>
<main class="detalle direccion-detalle-page" id="contenido-principal">
  <nav class="direccion-migas" aria-label="Ruta de navegación">
    <a href="direcciones.php">Direcciones</a><span aria-hidden="true">/</span><span><?= e($direccion['sigla']) ?></span>
  </nav>

  <header class="direccion-perfil">
    <div class="direccion-perfil-emblema">
      <img src="<?= e($direccion['logo']) ?>" alt="Emblema de <?= e($direccion['nombre']) ?>">
    </div>
    <div class="direccion-perfil-contenido">
      <span><?= e($direccion['sigla']) ?> · ININDETEC</span>
      <h1><?= e($direccion['nombre']) ?></h1>
      <p><?= e($direccion['descripcion']) ?></p>
    </div>
  </header>

  <section class="direccion-publicaciones" aria-labelledby="publicaciones-direccion">
    <header>
      <span>Actualidad institucional</span>
      <h2 id="publicaciones-direccion">Publicaciones de la Dirección</h2>
    </header>
    <?php if ($noticiasDireccion->num_rows): ?>
      <div class="noticias-grid">
        <?php while ($row = $noticiasDireccion->fetch_assoc()): ?>
          <article class="card">
            <?php if ($row['imagen']): ?><img src="<?= e(url_imagen($row['imagen'])) ?>" alt="<?= e($row['imagen_alt'] ?: $row['titulo']) ?>" loading="lazy" decoding="async"><?php endif; ?>
            <div class="card-body">
              <span class="categoria"><?= e($row['categoria']) ?></span>
              <h3><?= e($row['titulo']) ?></h3>
              <p><?= e($row['resumen']) ?></p>
              <a class="btn" href="<?= e(url_noticia($row)) ?>">Leer noticia</a>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="estado-vacio">
        <h3>Próximamente</h3>
        <p>Esta Dirección está lista para recibir publicaciones institucionales.</p>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
