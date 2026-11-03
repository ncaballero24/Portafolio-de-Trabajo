<?php
require_once 'includes/inicio.php';
$id = max(1, (int) ($_GET['id'] ?? 0));
$stmtNoticia = $conn->prepare('SELECT * FROM noticias WHERE id = ? LIMIT 1');
$stmtNoticia->bind_param('i', $id);
$stmtNoticia->execute();
$noticia = $stmtNoticia->get_result()->fetch_assoc();
if (!$noticia) {
    http_response_code(404);
    exit('Noticia no encontrada.');
}
if (($_SESSION['admin_rol'] ?? '') === 'Redactor' && $noticia['autor'] !== ($_SESSION['admin_nombre'] ?? '')) {
    http_response_code(403);
    exit('Los redactores sólo pueden editar sus propias noticias.');
}
$categorias = categorias_activas($conn);
$mensaje = '';
$mediosNoticia = medios_noticia($conn, $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $titulo = trim($_POST['titulo'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $contenido = sanitizar_html($_POST['contenido'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $estado = $_POST['estado'] ?? 'borrador';
    $destacada = isset($_POST['destacada']) ? 1 : 0;
    if (($_SESSION['admin_rol'] ?? '') === 'Redactor') {
        $estado = in_array($estado, ['publicada', 'borrador'], true) ? $estado : 'borrador';
        $destacada = 0;
    }
    $fechaProgramada = !empty($_POST['fecha_programada']) ? str_replace('T', ' ', $_POST['fecha_programada']) : null;
    $imagenAlt = trim($_POST['imagen_alt'] ?? '');
    $imagenPie = trim($_POST['imagen_pie'] ?? '');
    $autorPublico = trim($_POST['autor_publico'] ?? '');
    $tipoAutoria = $_POST['tipo_autoria'] ?? 'escrito_por';
    $accion = $_POST['accion'] ?? 'guardar';

    if ($accion === 'preview') {
        $_SESSION['preview_noticia'] = compact('titulo', 'resumen', 'contenido', 'categoria', 'imagenAlt', 'imagenPie', 'autorPublico', 'tipoAutoria');
        $_SESSION['preview_noticia']['imagen'] = $noticia['imagen'];
        $_SESSION['preview_noticia']['autorImagen'] = $noticia['autor_imagen'] ?? '';
        $_SESSION['preview_noticia']['video'] = $noticia['video'] ?? '';
        $_SESSION['preview_noticia']['videoMime'] = $noticia['video_mime'] ?? '';
        $_SESSION['preview_noticia']['medios'] = $mediosNoticia;
        header('Location: vista_previa.php');
        exit;
    }

    $nombresCategorias = array_column($categorias, 'nombre');
    if ($titulo === '' || mb_strlen($autorPublico) > 150 || $resumen === '' || $contenido === '' || !in_array($categoria, $nombresCategorias, true) || !in_array($estado, ['publicada', 'borrador', 'programada'], true) || !in_array($tipoAutoria, ['escrito_por', 'tomado_de'], true)) {
        $mensaje = 'Revisa los campos obligatorios.';
    } elseif ($tipoAutoria === 'tomado_de' && $autorPublico === '') {
        $mensaje = 'Indica el nombre de la fuente cuando selecciones “Tomado de:”.';
    } elseif ($estado === 'programada' && !$fechaProgramada) {
        $mensaje = 'Indica una fecha para la publicación programada.';
    } else {
        try {
            $stmtRevision = $conn->prepare('INSERT INTO revisiones_noticias (noticia_id, titulo, resumen, contenido, editado_por) VALUES (?, ?, ?, ?, ?)');
            $editor = $_SESSION['admin_nombre'];
            $stmtRevision->bind_param('issss', $id, $noticia['titulo'], $noticia['resumen'], $noticia['contenido'], $editor);
            $stmtRevision->execute();

            $mediosEliminar = array_values(array_filter(array_map('intval', $_POST['eliminar_medios'] ?? [])));
            if ($mediosEliminar) {
                $placeholders = implode(',', array_fill(0, count($mediosEliminar), '?'));
                $tipos = str_repeat('i', count($mediosEliminar) + 1);
                $params = array_merge([$id], $mediosEliminar);
                $stmtBuscarMedios = $conn->prepare("SELECT archivo FROM noticia_medios WHERE noticia_id=? AND id IN ($placeholders)");
                $stmtBuscarMedios->bind_param($tipos, ...$params);
                $stmtBuscarMedios->execute();
                $archivosEliminar = $stmtBuscarMedios->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmtEliminarMedios = $conn->prepare("DELETE FROM noticia_medios WHERE noticia_id=? AND id IN ($placeholders)");
                $stmtEliminarMedios->bind_param($tipos, ...$params);
                $stmtEliminarMedios->execute();
                foreach ($archivosEliminar as $medioEliminar) {
                    eliminar_archivo_local($medioEliminar['archivo'] ?? null);
                }
            }

            $imagenRuta = $noticia['imagen'];
            $imagen = guardar_imagen_segura($_FILES['imagen'] ?? [], 'noticias');
            if ($imagen) {
                $imagenRuta = $imagen['ruta'];
            }
            $videoAnterior = $noticia['video'] ?? '';
            $videoRuta = $videoAnterior;
            $videoMime = $noticia['video_mime'] ?? '';
            if (isset($_POST['eliminar_video'])) {
                $videoRuta = '';
                $videoMime = '';
            }
            $video = guardar_video_seguro($_FILES['video'] ?? []);
            if ($video) {
                $videoRuta = $video['ruta'];
                $videoMime = $video['mime'];
            }
            $autorImagenAnterior = $noticia['autor_imagen'] ?? '';
            $autorImagenRuta = $autorImagenAnterior;
            if (isset($_POST['eliminar_autor_imagen'])) {
                $autorImagenRuta = '';
            }
            $autorImagen = guardar_imagen_segura($_FILES['autor_imagen'] ?? [], 'autores');
            if ($autorImagen) {
                $autorImagenRuta = $autorImagen['ruta'];
            }
            $slug = slug_unico($conn, $titulo, $id);
            $stmt = $conn->prepare('UPDATE noticias SET titulo=?, slug=?, resumen=?, contenido=?, imagen=?, imagen_alt=?, imagen_pie=?, video=?, video_mime=?, categoria=?, autor_publico=?, tipo_autoria=?, autor_imagen=?, destacada=?, estado=?, fecha_programada=?, actualizado_en=NOW() WHERE id=?');
            $stmt->bind_param('sssssssssssssissi', $titulo, $slug, $resumen, $contenido, $imagenRuta, $imagenAlt, $imagenPie, $videoRuta, $videoMime, $categoria, $autorPublico, $tipoAutoria, $autorImagenRuta, $destacada, $estado, $fechaProgramada, $id);
            if (!$stmt->execute()) {
                if ($video && $videoAnterior !== $videoRuta) {
                    eliminar_archivo_local($videoRuta);
                }
                throw new RuntimeException('No fue posible actualizar la noticia.');
            }
            if ($imagen && $noticia['imagen'] !== $imagenRuta) {
                eliminar_archivo_local($noticia['imagen']);
            }
            if ($videoAnterior && $videoAnterior !== $videoRuta) {
                eliminar_archivo_local($videoAnterior);
            }
            if ($autorImagenAnterior && $autorImagenAnterior !== $autorImagenRuta) {
                eliminar_archivo_local($autorImagenAnterior);
            }
            guardar_medios_noticia($conn, $id, $_FILES['medios'] ?? []);
            registrar_bitacora($conn, 'noticia_editada', 'noticia', $id, $titulo . ' [' . $estado . ']');
            header('Location: noticias.php?actualizada=1');
            exit;
        } catch (Throwable $e) {
            $mensaje = $e->getMessage();
        }
    }
}

$admin_titulo = 'Editar noticia';
$admin_extra_head = '<script src="../tinymce/js/tinymce/tinymce.min.js"></script>';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <section class="noticia-detalle">
    <header class="encabezado-admin"><span>Editor</span><h1>Editar noticia</h1><a href="revisiones.php?id=<?= $id ?>">Ver historial de cambios</a></header>
    <?php if ($mensaje): ?><p class="mensaje-error"><?= e($mensaje) ?></p><?php endif; ?>
    <?php include 'includes/form_noticia.php'; ?>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
