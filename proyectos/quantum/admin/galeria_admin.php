<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador', 'Editor']);
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? 'agregar';
    $titulo = trim($_POST['titulo'] ?? '');
    try {
        if ($titulo === '' || mb_strlen($titulo) > 255) {
            throw new RuntimeException('Escribe un título válido.');
        }
        if ($accion === 'editar') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new RuntimeException('La imagen seleccionada no es válida.');
            }
            $stmt = $conn->prepare('UPDATE galeria SET titulo = ? WHERE id = ?');
            $stmt->bind_param('si', $titulo, $id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                $existe = $conn->prepare('SELECT id FROM galeria WHERE id = ?');
                $existe->bind_param('i', $id);
                $existe->execute();
                if (!$existe->get_result()->fetch_assoc()) {
                    throw new RuntimeException('La imagen ya no existe.');
                }
            }
            $mensaje = 'Nombre de la imagen actualizado.';
        } elseif ($accion === 'agregar') {
            $imagen = guardar_imagen_segura($_FILES['imagen'] ?? [], 'galeria');
            if (!$imagen) {
                throw new RuntimeException('Selecciona una imagen.');
            }
            $stmt = $conn->prepare('INSERT INTO galeria (titulo, imagen) VALUES (?, ?)');
            $stmt->bind_param('ss', $titulo, $imagen['ruta']);
            $stmt->execute();
            $mensaje = 'Imagen agregada a la Galería ININDETEC.';
        } else {
            throw new RuntimeException('La acción solicitada no es válida.');
        }
    } catch (Throwable $e) {
        $mensaje = $e->getMessage();
    }
}
$pagina = pagina_actual();
$porPagina = 12;
$total = (int) $conn->query('SELECT COUNT(*) AS total FROM galeria')->fetch_assoc()['total'];
$offset = ($pagina-1)*$porPagina;
$stmt = $conn->prepare('SELECT * FROM galeria ORDER BY fecha DESC LIMIT ? OFFSET ?');
$stmt->bind_param('ii', $porPagina, $offset);
$stmt->execute();
$imagenes = $stmt->get_result();
$admin_titulo = 'Galería ININDETEC';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <section class="noticia-detalle">
    <h1>Agregar imagen a Galería ININDETEC</h1><?php if ($mensaje): ?><p class="mensaje-formulario"><?= e($mensaje) ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="admin-form-grid"><?= csrf_input() ?>
      <input type="hidden" name="accion" value="agregar">
      <label>Título<input class="admin-input" name="titulo" maxlength="255" required></label>
      <label>Imagen<input class="admin-input" type="file" name="imagen" accept="image/jpeg,image/png,image/webp" required><small>JPG, PNG o WebP. Máximo <?= LIMITE_IMAGEN_MB ?> MB.</small></label>
      <button class="btn" type="submit">Subir imagen</button>
    </form>
  </section>
  <section class="galeria-grid admin-galeria">
    <?php while ($row=$imagenes->fetch_assoc()): ?>
      <article class="galeria-card">
        <img src="../<?= e($row['imagen']) ?>" alt="<?= e($row['titulo']) ?>" loading="lazy">
        <h3><?= e($row['titulo']) ?></h3>
        <div class="admin-galeria-acciones">
          <button class="btn btn-secundario" type="button" data-editar-galeria aria-expanded="false" aria-controls="editar-galeria-<?= (int)$row['id'] ?>">Editar nombre</button>
          <form id="editar-galeria-<?= (int)$row['id'] ?>" class="editar-galeria-form" method="POST" hidden>
            <?= csrf_input() ?>
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <label for="titulo-galeria-<?= (int)$row['id'] ?>">Nombre de la imagen</label>
            <input id="titulo-galeria-<?= (int)$row['id'] ?>" class="admin-input" name="titulo" maxlength="255" value="<?= e($row['titulo']) ?>" required>
            <div class="editar-galeria-botones">
              <button class="btn" type="submit">Guardar</button>
              <button class="btn btn-secundario" type="button" data-cancelar-edicion-galeria>Cancelar</button>
            </div>
          </form>
          <form method="POST" action="eliminar_imagen.php" data-confirmar="¿Eliminar esta imagen?">
            <?= csrf_input() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <button class="btn btn-danger" type="submit">Eliminar</button>
          </form>
        </div>
      </article>
    <?php endwhile; ?>
  </section>
  <?= render_paginacion($pagina,max(1,(int)ceil($total/$porPagina))) ?>
</main>
<?php include 'includes/footer.php'; ?>
