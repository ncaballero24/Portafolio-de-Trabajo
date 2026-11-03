<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

$cat = trim($_GET['cat'] ?? '');
$stmtCategoria = $conn->prepare('SELECT nombre, slug FROM categorias WHERE nombre = ? AND activa = 1 LIMIT 1');
$stmtCategoria->bind_param('s', $cat);
$stmtCategoria->execute();
$categoria = $stmtCategoria->get_result()->fetch_assoc();
if (!$categoria) {
    http_response_code(404);
    exit('La categoría solicitada no existe.');
}

$pagina = pagina_actual();
$porPagina = 9;
$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM noticias WHERE categoria = ? AND estado='publicada'");
$stmtTotal->bind_param('s', $cat);
$stmtTotal->execute();
$total = (int) $stmtTotal->get_result()->fetch_assoc()['total'];
$offset = ($pagina - 1) * $porPagina;
$stmt = $conn->prepare("SELECT id, titulo, slug, resumen, imagen, imagen_alt, categoria, fecha FROM noticias WHERE categoria = ? AND estado='publicada' ORDER BY fecha DESC LIMIT ? OFFSET ?");
$stmt->bind_param('sii', $cat, $porPagina, $offset);
$stmt->execute();
$resultado = $stmt->get_result();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$page_title = $cat . ' | Quántum ININDETEC';
$page_description = 'Noticias y publicaciones de la categoría ' . $cat . '.';
$canonical = url_base() . '/categoria.php?cat=' . rawurlencode($cat);
$body_class = 'quantum-listado-page quantum-categoria-page';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-principal">
  <header class="encabezado-listado"><span>Categoría</span><h1><?= e($cat) ?></h1><p><?= $total ?> publicación<?= $total === 1 ? '' : 'es' ?></p></header>
  <?php if ($resultado->num_rows): ?>
    <section class="noticias-grid">
      <?php while ($row = $resultado->fetch_assoc()): ?>
        <article class="card">
          <?php if ($row['imagen']): ?><img src="<?= e(url_imagen($row['imagen'])) ?>" alt="<?= e($row['imagen_alt'] ?: $row['titulo']) ?>" loading="lazy" decoding="async"><?php endif; ?>
          <div class="card-body"><span class="categoria"><?= e($row['categoria']) ?></span><h2><?= e($row['titulo']) ?></h2><p><?= e($row['resumen']) ?></p><a class="btn" href="<?= e(url_noticia($row)) ?>">Leer noticia</a></div>
        </article>
      <?php endwhile; ?>
    </section>
    <?= render_paginacion($pagina, $totalPaginas) ?>
  <?php else: ?><div class="estado-vacio"><h2>Aún no hay publicaciones</h2><p>Esta categoría está lista para recibir contenido.</p></div><?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
