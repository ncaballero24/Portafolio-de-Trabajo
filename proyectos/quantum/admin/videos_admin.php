<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador', 'Editor']);

$mensaje = '';
$editar = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    $id = max(0, (int) ($_POST['id'] ?? 0));
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $publicado = isset($_POST['publicado']) ? 1 : 0;
    $enInicio = isset($_POST['en_inicio']) ? 1 : 0;
    if ($enInicio) {
        $publicado = 1;
    }

    try {
        if (in_array($accion, ['subir', 'editar'], true)) {
            if ($titulo === '' || mb_strlen($titulo) > 255) {
                throw new RuntimeException('Escribe un título válido de hasta 255 caracteres.');
            }
            if (mb_strlen($descripcion) > 1000) {
                throw new RuntimeException('La descripción no puede superar 1000 caracteres.');
            }
        }

        if ($accion === 'subir') {
            $video = guardar_video_seguro($_FILES['video'] ?? []);
            if (!$video) {
                throw new RuntimeException('Selecciona un video.');
            }
            if ($enInicio) {
                $conn->query('UPDATE videos SET en_inicio=0 WHERE en_inicio=1');
            }
            $stmt = $conn->prepare('INSERT INTO videos (titulo, descripcion, archivo, mime, publicado, en_inicio) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssii', $titulo, $descripcion, $video['ruta'], $video['mime'], $publicado, $enInicio);
            if (!$stmt->execute()) {
                eliminar_archivo_local($video['ruta']);
                throw new RuntimeException('No se pudo registrar el video.');
            }
            $mensaje = 'Video subido correctamente.';
        } elseif ($accion === 'editar' && $id) {
            if ($enInicio) {
                $conn->query('UPDATE videos SET en_inicio=0 WHERE en_inicio=1');
            }
            $stmt = $conn->prepare('UPDATE videos SET titulo=?, descripcion=?, publicado=?, en_inicio=?, actualizado_en=NOW() WHERE id=?');
            $stmt->bind_param('ssiii', $titulo, $descripcion, $publicado, $enInicio, $id);
            $stmt->execute();
            $mensaje = 'Información del video actualizada.';
        } elseif ($accion === 'alternar' && $id) {
            $stmt = $conn->prepare('UPDATE videos SET publicado=1-publicado, en_inicio=IF(publicado=1, 0, en_inicio), actualizado_en=NOW() WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $mensaje = 'Visibilidad del video actualizada.';
        } elseif ($accion === 'inicio' && $id) {
            $conn->query('UPDATE videos SET en_inicio=0 WHERE en_inicio=1');
            $stmt = $conn->prepare('UPDATE videos SET publicado=1, en_inicio=1, actualizado_en=NOW() WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $mensaje = 'Video configurado como inicio.';
        } elseif ($accion === 'eliminar' && $id) {
            $stmt = $conn->prepare('SELECT archivo FROM videos WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $video = $stmt->get_result()->fetch_assoc();
            if (!$video) {
                throw new RuntimeException('El video ya no existe.');
            }
            $stmt = $conn->prepare('DELETE FROM videos WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            eliminar_archivo_local($video['archivo']);
            $mensaje = 'Video eliminado.';
        } else {
            throw new RuntimeException('La acción solicitada no es válida.');
        }
    } catch (Throwable $e) {
        $mensaje = $e->getMessage();
    }
}

if (!empty($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $conn->prepare('SELECT * FROM videos WHERE id=?');
    $stmt->bind_param('i', $idEditar);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

$pagina = pagina_actual();
$porPagina = 9;
$total = (int) $conn->query('SELECT COUNT(*) AS total FROM videos')->fetch_assoc()['total'];
$offset = ($pagina - 1) * $porPagina;
$stmt = $conn->prepare('SELECT * FROM videos ORDER BY fecha DESC, id DESC LIMIT ? OFFSET ?');
$stmt->bind_param('ii', $porPagina, $offset);
$stmt->execute();
$videos = $stmt->get_result();

$admin_titulo = 'Videos';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <section class="noticia-detalle">
    <h1><?= $editar ? 'Editar video' : 'Subir video' ?></h1>
    <?php if ($mensaje): ?><p class="mensaje-formulario"><?= e($mensaje) ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="admin-form-grid">
      <?= csrf_input() ?>
      <input type="hidden" name="accion" value="<?= $editar ? 'editar' : 'subir' ?>">
      <input type="hidden" name="id" value="<?= (int) ($editar['id'] ?? 0) ?>">
      <label>Título
        <input class="admin-input" name="titulo" maxlength="255" value="<?= e($editar['titulo'] ?? '') ?>" required>
      </label>
      <?php if (!$editar): ?>
        <label>Archivo de video
          <input class="admin-input" type="file" name="video" accept="video/mp4,video/webm,video/quicktime,video/x-m4v,.m4v" required>
          <small>MP4, WebM, MOV o M4V. Máximo <?= LIMITE_VIDEO_MB ?> MB.</small>
        </label>
      <?php endif; ?>
      <label class="admin-form-full">Descripción <small>Opcional</small>
        <textarea class="admin-input" name="descripcion" maxlength="1000" rows="4"><?= e($editar['descripcion'] ?? '') ?></textarea>
      </label>
      <label class="checkbox-admin">
        <input type="checkbox" name="publicado" <?= !isset($editar['publicado']) || $editar['publicado'] ? 'checked' : '' ?>>
        Visible en la videoteca
      </label>
      <label class="checkbox-admin">
        <input type="checkbox" name="en_inicio" <?= !empty($editar['en_inicio']) ? 'checked' : '' ?>>
        Mostrar en inicio
      </label>
      <div class="newsletter-actions">
        <button class="btn" type="submit"><?= $editar ? 'Guardar cambios' : 'Subir video' ?></button>
        <?php if ($editar): ?><a class="btn btn-secundario" href="videos_admin.php">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </section>

  <?php if ($videos->num_rows): ?>
    <section class="admin-videos-grid" aria-label="Videos administrados">
      <?php while ($video = $videos->fetch_assoc()): ?>
        <article class="admin-video-card">
          <div class="video-frame">
            <video controls preload="metadata">
              <source src="../<?= e($video['archivo']) ?>" type="<?= e($video['mime']) ?>">
            </video>
          </div>
          <div class="admin-video-card-body">
            <span class="estado-video <?= $video['publicado'] ? 'publicado' : 'oculto' ?>"><?= $video['publicado'] ? 'Publicado' : 'Oculto' ?></span>
            <?php if (!empty($video['en_inicio'])): ?><span class="estado-video publicado">Inicio</span><?php endif; ?>
            <h2><?= e($video['titulo']) ?></h2>
            <?php if ($video['descripcion']): ?><p><?= e($video['descripcion']) ?></p><?php endif; ?>
            <div class="admin-video-acciones">
              <a class="btn btn-secundario" href="?editar=<?= (int) $video['id'] ?>">Editar</a>
              <?php if (empty($video['en_inicio'])): ?>
                <form method="POST">
                  <?= csrf_input() ?><input type="hidden" name="accion" value="inicio"><input type="hidden" name="id" value="<?= (int) $video['id'] ?>">
                  <button class="btn btn-secundario" type="submit">Poner en inicio</button>
                </form>
              <?php endif; ?>
              <form method="POST">
                <?= csrf_input() ?><input type="hidden" name="accion" value="alternar"><input type="hidden" name="id" value="<?= (int) $video['id'] ?>">
                <button class="btn btn-secundario" type="submit"><?= $video['publicado'] ? 'Ocultar' : 'Publicar' ?></button>
              </form>
              <form method="POST" data-confirmar="¿Eliminar este video y su archivo?">
                <?= csrf_input() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int) $video['id'] ?>">
                <button class="btn btn-danger" type="submit">Eliminar</button>
              </form>
            </div>
          </div>
        </article>
      <?php endwhile; ?>
    </section>
    <?= render_paginacion($pagina, max(1, (int) ceil($total / $porPagina))) ?>
  <?php else: ?>
    <section class="estado-vacio"><h2>No hay videos registrados</h2><p>Sube el primer archivo desde el formulario superior.</p></section>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
