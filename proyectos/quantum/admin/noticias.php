<?php
require_once 'includes/inicio.php';
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'eliminar') {
        requerir_admin(['Administrador']);
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT imagen, video FROM noticias WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $noticia = $stmt->get_result()->fetch_assoc();
        $medios = medios_noticia($conn, $id);
        $stmt = $conn->prepare('DELETE FROM noticias WHERE id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            eliminar_archivo_local($noticia['imagen'] ?? null);
            eliminar_archivo_local($noticia['video'] ?? null);
            foreach ($medios as $medio) {
                eliminar_archivo_local($medio['archivo'] ?? null);
            }
            $mensaje = 'Noticia eliminada.';
        }
    } elseif ($accion === 'lote') {
        requerir_admin(['Administrador', 'Editor']);
        $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
        $estadoLote = $_POST['estado_lote'] ?? '';
        if ($ids && in_array($estadoLote, ['publicada', 'borrador'], true)) {
            $lista = implode(',', $ids);
            $stmt = $conn->prepare("UPDATE noticias SET estado=?, actualizado_en=NOW() WHERE id IN ($lista)");
            $stmt->bind_param('s', $estadoLote);
            $stmt->execute();
            $mensaje = 'Noticias actualizadas: ' . $stmt->affected_rows . '.';
        }
    }
}

$q = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$pagina = pagina_actual();
$porPagina = 20;
$qLike = '%' . $q . '%';
$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM noticias WHERE (?='' OR titulo LIKE ?) AND (?='' OR estado=?) AND (?='' OR categoria=?)");
$stmtTotal->bind_param('ssssss', $q, $qLike, $estado, $estado, $categoria, $categoria);
$stmtTotal->execute();
$total = (int) $stmtTotal->get_result()->fetch_assoc()['total'];
$offset = ($pagina - 1) * $porPagina;
$stmt = $conn->prepare("SELECT id,titulo,slug,imagen,categoria,autor,estado,destacada,visitas,fecha FROM noticias WHERE (?='' OR titulo LIKE ?) AND (?='' OR estado=?) AND (?='' OR categoria=?) ORDER BY fecha DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ssssssii', $q, $qLike, $estado, $estado, $categoria, $categoria, $porPagina, $offset);
$stmt->execute();
$noticias = $stmt->get_result();
$categorias = categorias_activas($conn);
$admin_titulo = 'Administrar noticias';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <header class="encabezado-listado"><span>Archivo editorial</span><h1>Noticias</h1><p><?= $total ?> registros</p><a class="btn" href="nueva_noticia.php">Nueva noticia</a></header>
  <?php if ($mensaje): ?><p class="mensaje-formulario"><?= e($mensaje) ?></p><?php endif; ?>
  <form method="GET" class="filtros-admin">
    <input class="admin-input" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título">
    <select class="admin-input" name="estado"><option value="">Todos los estados</option><?php foreach (['publicada','borrador','programada'] as $e): ?><option <?= $estado===$e?'selected':'' ?>><?= $e ?></option><?php endforeach; ?></select>
    <select class="admin-input" name="categoria"><option value="">Todas las categorías</option><?php foreach ($categorias as $cat): ?><option value="<?= e($cat['nombre']) ?>" <?= $categoria===$cat['nombre']?'selected':'' ?>><?= e($cat['nombre']) ?></option><?php endforeach; ?></select>
    <button class="btn" type="submit">Filtrar</button><a class="btn btn-secundario" href="noticias.php">Limpiar</a>
  </form>
  <form method="POST">
    <?= csrf_input() ?><input type="hidden" name="accion" value="lote">
    <?php if (in_array($_SESSION['admin_rol'], ['Administrador', 'Editor'], true)): ?>
      <div class="acciones-lote"><select class="admin-input" name="estado_lote"><option value="publicada">Publicar seleccionadas</option><option value="borrador">Mover a borrador</option></select><button class="btn" type="submit">Aplicar</button></div>
    <?php endif; ?>
    <div class="tabla-admin"><table><thead><tr><th></th><th>Imagen</th><th>Título</th><th>Categoría</th><th>Estado</th><th>Visitas</th><th>Acciones</th></tr></thead><tbody>
    <?php while ($row = $noticias->fetch_assoc()): ?><tr>
      <td><input type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>" aria-label="Seleccionar <?= e($row['titulo']) ?>"></td>
      <td><?php if ($row['imagen']): ?><img src="<?= e(url_imagen($row['imagen'], '../')) ?>" class="tabla-img" alt=""><?php endif; ?></td>
      <td><strong><?= e($row['titulo']) ?></strong><?php if ($row['destacada']): ?><small>Destacada</small><?php endif; ?></td>
      <td><?= e($row['categoria']) ?></td><td><span class="estado-newsletter <?= e($row['estado']) ?>"><?= e($row['estado']) ?></span></td><td><?= (int) $row['visitas'] ?></td>
      <td class="acciones-tabla"><a class="btn btn-secundario" href="../<?= e(url_noticia($row)) ?>" target="_blank">Ver</a>
      <?php if ($_SESSION['admin_rol'] !== 'Redactor' || $row['autor'] === $_SESSION['admin_nombre']): ?><a class="btn" href="editar_noticia.php?id=<?= (int) $row['id'] ?>">Editar</a><?php endif; ?>
      <?php if ($_SESSION['admin_rol']==='Administrador'): ?><button class="btn btn-danger" type="submit" formaction="eliminar_noticia.php" name="id" value="<?= (int) $row['id'] ?>" onclick="return confirm('¿Eliminar esta noticia?')">Eliminar</button><?php endif; ?></td>
    </tr><?php endwhile; ?>
    </tbody></table></div>
  </form>
  <?= render_paginacion($pagina, max(1, (int) ceil($total/$porPagina))) ?>
</main>
<?php include 'includes/footer.php'; ?>
