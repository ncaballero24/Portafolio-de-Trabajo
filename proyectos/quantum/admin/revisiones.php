<?php
require_once 'includes/inicio.php';
$id = max(1, (int) ($_GET['id'] ?? 0));
$stmt = $conn->prepare('SELECT titulo, resumen, contenido, editado_por, fecha FROM revisiones_noticias WHERE noticia_id = ? ORDER BY fecha DESC LIMIT 30');
$stmt->bind_param('i', $id);
$stmt->execute();
$revisiones = $stmt->get_result();
$admin_titulo = 'Historial de cambios';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <header class="encabezado-listado"><span>Auditoría editorial</span><h1>Revisiones de la noticia</h1><a class="btn" href="editar_noticia.php?id=<?= $id ?>">Volver al editor</a></header>
  <div class="revisiones-lista">
    <?php while ($revision = $revisiones->fetch_assoc()): ?>
      <details class="noticia-detalle"><summary><strong><?= e($revision['titulo']) ?></strong><span><?= e($revision['editado_por']) ?> · <?= e($revision['fecha']) ?></span></summary><p><?= e($revision['resumen']) ?></p><div class="contenido-noticia"><?= sanitizar_html($revision['contenido']) ?></div></details>
    <?php endwhile; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
