<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador', 'Editor']);
$estado = trim($_GET['estado'] ?? '');
$q = trim($_GET['q'] ?? '');
$qLike = '%' . $q . '%';
$pagina = pagina_actual();
$porPagina = 20;
$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM comentarios c LEFT JOIN noticias n ON c.noticia_id=n.id WHERE (?='' OR c.estado=?) AND (?='' OR c.nombre LIKE ? OR c.comentario LIKE ? OR n.titulo LIKE ?)");
$stmtTotal->bind_param('ssssss', $estado, $estado, $q, $qLike, $qLike, $qLike);
$stmtTotal->execute();
$total = (int) $stmtTotal->get_result()->fetch_assoc()['total'];
$offset = ($pagina - 1) * $porPagina;
$stmt = $conn->prepare("SELECT c.*, n.titulo FROM comentarios c LEFT JOIN noticias n ON c.noticia_id=n.id WHERE (?='' OR c.estado=?) AND (?='' OR c.nombre LIKE ? OR c.comentario LIKE ? OR n.titulo LIKE ?) ORDER BY c.fecha DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ssssssii', $estado, $estado, $q, $qLike, $qLike, $qLike, $porPagina, $offset);
$stmt->execute();
$comentarios = $stmt->get_result();
$admin_titulo = 'Comentarios';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <header class="encabezado-listado"><span>Moderación</span><h1>Comentarios</h1><p><?= $total ?> registros</p></header>
  <form method="GET" class="filtros-admin">
    <input class="admin-input" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar comentario, autor o noticia">
    <select class="admin-input" name="estado"><option value="">Todos</option><?php foreach (['pendiente','aprobado','rechazado'] as $e): ?><option <?= $estado===$e?'selected':'' ?>><?= $e ?></option><?php endforeach; ?></select>
    <button class="btn" type="submit">Filtrar</button><a class="btn btn-secundario" href="comentarios.php">Limpiar</a>
  </form>
  <div class="tabla-admin"><table><thead><tr><th>Noticia</th><th>Autor</th><th>Comentario</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>
  <?php while ($c = $comentarios->fetch_assoc()): ?><tr>
    <td><?= e($c['titulo'] ?: 'Noticia eliminada') ?></td><td><?= e($c['nombre']) ?></td><td class="celda-comentario"><?= e($c['comentario']) ?></td><td><span class="estado-newsletter <?= e($c['estado']) ?>"><?= e($c['estado']) ?></span></td><td><?= e($c['fecha']) ?></td>
    <td class="acciones-tabla">
      <form method="POST" action="aprobar_comentario.php"><?= csrf_input() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn" type="submit">Aprobar</button></form>
      <form method="POST" action="rechazar_comentario.php"><?= csrf_input() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-secundario" type="submit">Rechazar</button></form>
      <form method="POST" action="eliminar_comentario.php" data-confirmar="¿Eliminar definitivamente este comentario?"><?= csrf_input() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-danger" type="submit">Eliminar</button></form>
    </td>
  </tr><?php endwhile; ?>
  </tbody></table></div>
  <?= render_paginacion($pagina, max(1, (int) ceil($total/$porPagina))) ?>
</main>
<?php include 'includes/footer.php'; ?>
