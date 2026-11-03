<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador', 'Editor']);
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    $id = max(0, (int) ($_POST['id'] ?? 0));
    if ($accion === 'guardar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $orden = (int) ($_POST['orden'] ?? 0);
        $activa = isset($_POST['activa']) ? 1 : 0;
        $slug = slugify($nombre);
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $mensaje = 'Escribe un nombre de categoría válido.';
        } elseif ($id) {
            $stmtAnterior = $conn->prepare('SELECT nombre FROM categorias WHERE id=?');
            $stmtAnterior->bind_param('i', $id);
            $stmtAnterior->execute();
            $anterior = $stmtAnterior->get_result()->fetch_assoc();
            if (!$anterior) {
                $mensaje = 'La categoría ya no existe.';
            } else {
                $conn->begin_transaction();
                $stmt = $conn->prepare('UPDATE categorias SET nombre=?, slug=?, orden=?, activa=? WHERE id=?');
                $stmt->bind_param('ssiii', $nombre, $slug, $orden, $activa, $id);
                $okCategoria = $stmt->execute();
                $stmtNoticias = $conn->prepare('UPDATE noticias SET categoria=? WHERE categoria=?');
                $stmtNoticias->bind_param('ss', $nombre, $anterior['nombre']);
                $okNoticias = $stmtNoticias->execute();
                if ($okCategoria && $okNoticias) {
                    $conn->commit();
                    $mensaje = 'Categoría y noticias relacionadas actualizadas.';
                } else {
                    $conn->rollback();
                    $mensaje = 'No fue posible actualizar la categoría. Revisa que el nombre no esté repetido.';
                }
            }
        } else {
            $stmt = $conn->prepare('INSERT INTO categorias (nombre, slug, orden, activa) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssii', $nombre, $slug, $orden, $activa);
            $mensaje = $stmt->execute() ? 'Categoría creada.' : 'La categoría ya existe.';
        }
    } elseif ($accion === 'alternar' && $id) {
        $stmt = $conn->prepare('UPDATE categorias SET activa = 1 - activa WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $mensaje = 'Visibilidad actualizada.';
    } elseif ($accion === 'eliminar' && $id) {
        $stmtUso = $conn->prepare('SELECT COUNT(*) AS total FROM noticias n JOIN categorias c ON n.categoria=c.nombre WHERE c.id=?');
        $stmtUso->bind_param('i', $id);
        $stmtUso->execute();
        if ((int) $stmtUso->get_result()->fetch_assoc()['total'] > 0) {
            $mensaje = 'No se puede eliminar una categoría que contiene noticias. Puedes desactivarla.';
        } else {
            $stmt = $conn->prepare('DELETE FROM categorias WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $mensaje = 'Categoría eliminada.';
        }
    }
}

$editar = null;
if (!empty($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $conn->prepare('SELECT * FROM categorias WHERE id=?');
    $stmt->bind_param('i', $idEditar);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}
$categorias = $conn->query('SELECT c.*, (SELECT COUNT(*) FROM noticias n WHERE n.categoria=c.nombre) AS noticias FROM categorias c ORDER BY orden, nombre');
$admin_titulo = 'Categorías';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <section class="noticia-detalle">
    <h1><?= $editar ? 'Editar categoría' : 'Nueva categoría' ?></h1>
    <?php if ($mensaje): ?><p class="mensaje-formulario"><?= e($mensaje) ?></p><?php endif; ?>
    <form method="POST" class="admin-form-grid">
      <?= csrf_input() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int) ($editar['id'] ?? 0) ?>">
      <label>Nombre<input class="admin-input" name="nombre" maxlength="100" value="<?= e($editar['nombre'] ?? '') ?>" required></label>
      <label>Orden<input class="admin-input" type="number" name="orden" value="<?= (int) ($editar['orden'] ?? 100) ?>"></label>
      <label class="checkbox-admin"><input type="checkbox" name="activa" <?= !isset($editar['activa']) || $editar['activa'] ? 'checked' : '' ?>> Visible en el sitio</label>
      <button class="btn" type="submit">Guardar categoría</button>
    </form>
  </section>
  <div class="tabla-admin">
    <table><thead><tr><th>Orden</th><th>Nombre</th><th>Noticias</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
      <?php while ($categoria = $categorias->fetch_assoc()): ?>
        <tr><td><?= (int) $categoria['orden'] ?></td><td><?= e($categoria['nombre']) ?></td><td><?= (int) $categoria['noticias'] ?></td><td><?= $categoria['activa'] ? 'Visible' : 'Oculta' ?></td>
        <td class="acciones-tabla">
          <a class="btn btn-secundario" href="?editar=<?= (int) $categoria['id'] ?>">Editar</a>
          <form method="POST"><?= csrf_input() ?><input type="hidden" name="accion" value="alternar"><input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>"><button class="btn btn-secundario" type="submit"><?= $categoria['activa'] ? 'Ocultar' : 'Mostrar' ?></button></form>
          <form method="POST" data-confirmar="¿Eliminar esta categoría?"><?= csrf_input() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>"><button class="btn btn-danger" type="submit">Eliminar</button></form>
        </td></tr>
      <?php endwhile; ?>
    </tbody></table>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
