<?php
require_once 'includes/inicio.php';
$categorias = categorias_activas($conn);
$mensaje = '';

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
        header('Location: vista_previa.php');
        exit;
    }

    $nombresCategorias = array_column($categorias, 'nombre');
    if ($titulo === '' || mb_strlen($titulo) > 255 || mb_strlen($autorPublico) > 150 || $resumen === '' || $contenido === '' || !in_array($categoria, $nombresCategorias, true) || !in_array($estado, ['publicada', 'borrador', 'programada'], true) || !in_array($tipoAutoria, ['escrito_por', 'tomado_de'], true)) {
        $mensaje = 'Revisa los campos obligatorios y vuelve a intentarlo.';
    } elseif ($tipoAutoria === 'tomado_de' && $autorPublico === '') {
        $mensaje = 'Indica el nombre de la fuente cuando selecciones “Tomado de:”.';
    } elseif ($estado === 'programada' && !$fechaProgramada) {
        $mensaje = 'Indica una fecha para la publicación programada.';
    } else {
        try {
            $imagen = guardar_imagen_segura($_FILES['imagen'] ?? [], 'noticias');
            $imagenRuta = $imagen['ruta'] ?? '';
            $video = guardar_video_seguro($_FILES['video'] ?? []);
            $videoRuta = $video['ruta'] ?? '';
            $videoMime = $video['mime'] ?? '';
            $autorImagen = guardar_imagen_segura($_FILES['autor_imagen'] ?? [], 'autores');
            $autorImagenRuta = $autorImagen['ruta'] ?? '';
            $slug = slug_unico($conn, $titulo);
            $autor = $_SESSION['admin_nombre'];
            $stmt = $conn->prepare('INSERT INTO noticias (titulo, slug, resumen, contenido, imagen, imagen_alt, imagen_pie, video, video_mime, categoria, autor, autor_publico, tipo_autoria, autor_imagen, destacada, estado, fecha_programada, actualizado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->bind_param('ssssssssssssssiss', $titulo, $slug, $resumen, $contenido, $imagenRuta, $imagenAlt, $imagenPie, $videoRuta, $videoMime, $categoria, $autor, $autorPublico, $tipoAutoria, $autorImagenRuta, $destacada, $estado, $fechaProgramada);
            if (!$stmt->execute()) {
                eliminar_archivo_local($imagenRuta);
                eliminar_archivo_local($videoRuta);
                eliminar_archivo_local($autorImagenRuta);
                throw new RuntimeException('No fue posible guardar la noticia.');
            }
            $noticiaId = (int) $conn->insert_id;
            guardar_medios_noticia($conn, $noticiaId, $_FILES['medios'] ?? []);
            registrar_bitacora($conn, 'noticia_creada', 'noticia', $noticiaId, $titulo . ' [' . $estado . ']');
            header('Location: noticias.php?creada=1');
            exit;
        } catch (Throwable $e) {
            $mensaje = $e->getMessage();
        }
    }
}

$admin_titulo = 'Nueva noticia';
$admin_extra_head = '<script src="../tinymce/js/tinymce/tinymce.min.js"></script>';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <section class="noticia-detalle">
    <header class="encabezado-admin"><span>Editor</span><h1>Crear noticia</h1></header>
    <?php if ($mensaje): ?><p class="mensaje-error"><?= e($mensaje) ?></p><?php endif; ?>
    <?php include 'includes/form_noticia.php'; ?>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
