<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';
iniciar_sesion_segura();

$slug = trim($_GET['slug'] ?? '');
$id = max(0, (int) ($_GET['id'] ?? 0));

if ($slug !== '') {
    $stmt = $conn->prepare("SELECT * FROM noticias WHERE slug = ? AND estado = 'publicada' LIMIT 1");
    $stmt->bind_param('s', $slug);
} else {
    $stmt = $conn->prepare("SELECT * FROM noticias WHERE id = ? AND estado = 'publicada' LIMIT 1");
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$noticia = $stmt->get_result()->fetch_assoc();

if (!$noticia) {
    http_response_code(404);
    exit('La noticia no existe o aún no está disponible.');
}
$id = (int) $noticia['id'];

$visitanteHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
$stmtVisita = $conn->prepare('INSERT IGNORE INTO visitas_noticias (noticia_id, visitante_hash, dia) VALUES (?, ?, CURDATE())');
$stmtVisita->bind_param('is', $id, $visitanteHash);
$stmtVisita->execute();
if ($stmtVisita->affected_rows > 0) {
    $stmtSuma = $conn->prepare('UPDATE noticias SET visitas = visitas + 1 WHERE id = ?');
    $stmtSuma->bind_param('i', $id);
    $stmtSuma->execute();
    $noticia['visitas']++;
}

$mensajeComentario = $_SESSION['mensaje_comentario'] ?? '';
unset($_SESSION['mensaje_comentario']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_comentario'])) {
    verificar_csrf();
    $nombre = trim($_POST['nombre'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');
    $trampa = trim($_POST['sitio_web'] ?? '');
    $ultimo = (int) ($_SESSION['ultimo_comentario'] ?? 0);

    if ($trampa !== '') {
        $_SESSION['mensaje_comentario'] = 'No fue posible registrar el comentario.';
    } elseif (time() - $ultimo < 30) {
        $_SESSION['mensaje_comentario'] = 'Espera unos segundos antes de enviar otro comentario.';
    } elseif (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100 || mb_strlen($comentario) < 5 || mb_strlen($comentario) > 2000) {
        $_SESSION['mensaje_comentario'] = 'Revisa el nombre y el comentario. El comentario admite hasta 2,000 caracteres.';
    } else {
        $stmtComentario = $conn->prepare('INSERT INTO comentarios (noticia_id, nombre, comentario) VALUES (?, ?, ?)');
        $stmtComentario->bind_param('iss', $id, $nombre, $comentario);
        $stmtComentario->execute();
        $_SESSION['ultimo_comentario'] = time();
        $_SESSION['mensaje_comentario'] = 'Comentario enviado. Aparecerá después de ser revisado.';
    }
    header('Location: ' . url_noticia($noticia) . '#comentarios');
    exit;
}

$autorReferencia = $noticia['autor_publico'] ?: ($noticia['autor'] ?: '');
$stmtRelacionadas = $conn->prepare("SELECT id, titulo, slug, resumen, imagen, imagen_alt, categoria, fecha, visitas FROM noticias WHERE id != ? AND estado = 'publicada' ORDER BY (categoria = ?) DESC, (COALESCE(NULLIF(autor_publico,''), autor, '') = ?) DESC, destacada DESC, visitas DESC, fecha DESC LIMIT 3");
$stmtRelacionadas->bind_param('iss', $id, $noticia['categoria'], $autorReferencia);
$stmtRelacionadas->execute();
$relacionadas = $stmtRelacionadas->get_result();

$stmtAnterior = $conn->prepare("SELECT id, titulo, slug FROM noticias WHERE estado='publicada' AND fecha < ? ORDER BY fecha DESC LIMIT 1");
$stmtAnterior->bind_param('s', $noticia['fecha']);
$stmtAnterior->execute();
$anterior = $stmtAnterior->get_result()->fetch_assoc();
$stmtSiguiente = $conn->prepare("SELECT id, titulo, slug FROM noticias WHERE estado='publicada' AND fecha > ? ORDER BY fecha ASC LIMIT 1");
$stmtSiguiente->bind_param('s', $noticia['fecha']);
$stmtSiguiente->execute();
$siguiente = $stmtSiguiente->get_result()->fetch_assoc();

$mediosNoticia = medios_noticia($conn, $id);

$descripcion = $noticia['resumen'] ?: mb_substr(strip_tags($noticia['contenido']), 0, 155);
$canonical = url_base() . '/noticia.php?slug=' . rawurlencode($noticia['slug'] ?: (string) $id);
$imagenAbsoluta = $noticia['imagen'] ? url_imagen($noticia['imagen'], url_base() . '/') : '';
$page_title = $noticia['titulo'] . ' | Quántum ININDETEC';
$page_description = $descripcion;
$og_type = 'article';
$og_image = $imagenAbsoluta;
$nombreAutor = $noticia['autor_publico'] ?: ($noticia['autor'] ?: 'Redacción');
$tipoAutoria = ($noticia['tipo_autoria'] ?? 'escrito_por') === 'tomado_de' ? 'tomado_de' : 'escrito_por';
$etiquetaAutoria = $tipoAutoria === 'tomado_de' ? 'Tomado de:' : 'Escrito por:';
$autorImagenAbsoluta = !empty($noticia['autor_imagen']) ? url_imagen($noticia['autor_imagen'], url_base() . '/') : '';
$autorEstructurado = $tipoAutoria === 'tomado_de'
    ? ['@type' => 'Organization', 'name' => 'Quántum ININDETEC']
    : ['@type' => 'Person', 'name' => $nombreAutor];
if ($autorImagenAbsoluta && $tipoAutoria === 'escrito_por') {
    $autorEstructurado['image'] = $autorImagenAbsoluta;
}
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $noticia['titulo'],
    'description' => $descripcion,
    'datePublished' => date(DATE_ATOM, strtotime($noticia['fecha'])),
    'dateModified' => date(DATE_ATOM, strtotime($noticia['actualizado_en'] ?: $noticia['fecha'])),
    'author' => $autorEstructurado,
    'publisher' => ['@type' => 'Organization', 'name' => 'Quántum ININDETEC'],
    'mainEntityOfPage' => $canonical,
];
if ($tipoAutoria === 'tomado_de') {
    $jsonLd['citation'] = $nombreAutor;
}
if ($imagenAbsoluta) {
    $jsonLd['image'] = [$imagenAbsoluta];
}
$extra_head = '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
include 'includes/header.php';

$contenidoPlano = trim($noticia['contenido']) === trim(strip_tags($noticia['contenido']));
$contenidoRender = $contenidoPlano ? nl2br(e($noticia['contenido'])) : sanitizar_html($noticia['contenido']);
?>

<div class="progreso-lectura" aria-hidden="true"><span data-progreso-lectura></span></div>
<main class="detalle" id="contenido-principal">
  <nav class="breadcrumbs" aria-label="Migas de pan">
    <a href="index.php">Inicio</a><span>/</span>
    <a href="categoria.php?cat=<?= rawurlencode($noticia['categoria']) ?>"><?= e($noticia['categoria']) ?></a><span>/</span>
    <span aria-current="page"><?= e($noticia['titulo']) ?></span>
  </nav>

  <article class="noticia-detalle" data-noticia-actual data-id="<?= $id ?>" data-titulo="<?= e($noticia['titulo']) ?>" data-url="<?= e($canonical) ?>" data-imagen="<?= e($imagenAbsoluta) ?>" data-resumen="<?= e($descripcion) ?>" data-categoria="<?= e($noticia['categoria']) ?>">
    <?php if ($noticia['imagen']): ?>
      <figure class="noticia-portada noticia-portada-titulada">
        <img src="<?= e(url_imagen($noticia['imagen'])) ?>" alt="<?= e($noticia['imagen_alt'] ?: $noticia['titulo']) ?>" fetchpriority="high">
        <div class="noticia-portada-titulo">
          <span class="categoria"><?= e($noticia['categoria']) ?></span>
          <h1><?= e($noticia['titulo']) ?></h1>
        </div>
        <?php if ($noticia['imagen_pie']): ?><figcaption><?= e($noticia['imagen_pie']) ?></figcaption><?php endif; ?>
      </figure>
    <?php else: ?>
      <span class="categoria"><?= e($noticia['categoria']) ?></span>
      <h1 class="noticia-titulo-sin-portada"><?= e($noticia['titulo']) ?></h1>
    <?php endif; ?>

    <div class="firma-noticia">
      <?php if (!empty($noticia['autor_imagen'])): ?>
        <img src="<?= e(url_imagen($noticia['autor_imagen'])) ?>" alt="<?= e($nombreAutor) ?>">
      <?php endif; ?>
      <div>
        <span><?= e($etiquetaAutoria) ?></span>
        <strong><?= e($nombreAutor) ?></strong>
        <p><?= e(date('d/m/Y H:i', strtotime($noticia['fecha']))) ?> · <?= (int) $noticia['visitas'] ?> lecturas · <?= tiempo_lectura($noticia['contenido']) ?> min</p>
      </div>
    </div>

    <div class="barra-herramientas-lectura" aria-label="Herramientas de lectura">
      <div><strong><?= tiempo_lectura($noticia['contenido']) ?> min</strong><span>de lectura estimada</span></div>
      <button type="button" data-modo-lectura aria-pressed="false">Modo lectura</button>
      <span class="controles-tipografia" aria-label="Tamaño de letra">
        <button type="button" data-texto-menos aria-label="Reducir tamaño de letra">A−</button>
        <button type="button" data-texto-restablecer aria-label="Restablecer tamaño de letra">A</button>
        <button type="button" data-texto-mas aria-label="Aumentar tamaño de letra">A+</button>
      </span>
      <button type="button" class="guardar-noticia-boton guardar-noticia-principal" data-guardar-noticia>Guardar para después</button>
    </div>

    <?php if (!empty($noticia['video'])): ?>
      <figure class="noticia-video">
        <video controls preload="metadata" playsinline>
          <source src="<?= e($noticia['video']) ?>" type="<?= e($noticia['video_mime'] ?: 'video/mp4') ?>">
          Tu navegador no puede reproducir este video.
        </video>
      </figure>
    <?php endif; ?>

    <?php if ($mediosNoticia): ?>
      <section class="noticia-medios" aria-label="Galería multimedia de la noticia">
        <header><span>Contenido adicional</span><h2>Experiencia multimedia</h2><p><?= count($mediosNoticia) ?> recurso<?= count($mediosNoticia) === 1 ? '' : 's' ?> para ampliar la publicación.</p></header>
        <?php foreach ($mediosNoticia as $medio): ?>
          <figure class="noticia-medio <?= ($medio['tipo'] ?? '') === 'video' ? 'noticia-medio-video' : 'noticia-medio-imagen' ?>">
            <?php if (($medio['tipo'] ?? '') === 'video'): ?>
              <video controls preload="metadata" playsinline>
                <source src="<?= e($medio['archivo']) ?>" type="<?= e($medio['mime'] ?: 'video/mp4') ?>">
                Tu navegador no puede reproducir este video.
              </video>
            <?php else: ?>
              <button type="button" class="noticia-medio-abrir" data-multimedia-imagen="<?= e(url_imagen($medio['archivo'])) ?>" data-multimedia-titulo="<?= e($noticia['titulo']) ?>">
                <img src="<?= e(url_imagen($medio['archivo'])) ?>" alt="<?= e($noticia['titulo']) ?>" loading="lazy" decoding="async">
                <span>Ampliar imagen</span>
              </button>
            <?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

    <div class="compartir-noticia" aria-label="Compartir noticia">
      <strong>Compartir</strong>
      <a href="https://wa.me/?text=<?= rawurlencode($noticia['titulo'] . ' ' . $canonical) ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($canonical) ?>" target="_blank" rel="noopener">Facebook</a>
      <button type="button" data-copiar-url="<?= e($canonical) ?>">Copiar enlace</button>
    </div>
    <div class="contenido-noticia" data-contenido-lectura><?= $contenidoRender ?></div>
  </article>

  <div class="modal-multimedia" data-modal-multimedia role="dialog" aria-modal="true" aria-label="Visor multimedia" hidden>
    <button type="button" class="modal-multimedia-cerrar" data-cerrar-multimedia aria-label="Cerrar visor">×</button>
    <button type="button" class="modal-multimedia-nav" data-multimedia-anterior aria-label="Imagen anterior">‹</button>
    <figure><img data-multimedia-grande alt=""><figcaption data-multimedia-caption></figcaption></figure>
    <button type="button" class="modal-multimedia-nav" data-multimedia-siguiente aria-label="Imagen siguiente">›</button>
  </div>

  <nav class="navegacion-noticias" aria-label="Noticias anterior y siguiente">
    <?php if ($anterior): ?><a href="<?= e(url_noticia($anterior)) ?>"><span>Anterior</span><?= e($anterior['titulo']) ?></a><?php endif; ?>
    <?php if ($siguiente): ?><a href="<?= e(url_noticia($siguiente)) ?>"><span>Siguiente</span><?= e($siguiente['titulo']) ?></a><?php endif; ?>
  </nav>

  <section class="comentarios" id="comentarios">
    <h2>Comentarios</h2>
    <?php if ($mensajeComentario): ?><p class="mensaje-formulario"><?= e($mensajeComentario) ?></p><?php endif; ?>
    <form method="POST">
      <?= csrf_input() ?>
      <input class="campo-trampa" type="text" name="sitio_web" tabindex="-1" autocomplete="off" aria-hidden="true">
      <label>Nombre<input class="admin-input" type="text" name="nombre" maxlength="100" required></label>
      <label>Comentario<textarea class="admin-input" name="comentario" rows="5" maxlength="2000" required></textarea></label>
      <button class="btn" type="submit" name="nuevo_comentario">Enviar a revisión</button>
    </form>
    <?php
    $stmtComentarios = $conn->prepare("SELECT nombre, comentario, fecha FROM comentarios WHERE noticia_id = ? AND estado = 'aprobado' ORDER BY fecha DESC");
    $stmtComentarios->bind_param('i', $id);
    $stmtComentarios->execute();
    $comentarios = $stmtComentarios->get_result();
    ?>
    <?php if ($comentarios->num_rows): ?>
      <?php while ($c = $comentarios->fetch_assoc()): ?>
        <article class="comentario-card"><strong><?= e($c['nombre']) ?></strong><small><?= e(date('d/m/Y H:i', strtotime($c['fecha']))) ?></small><p><?= nl2br(e($c['comentario'])) ?></p></article>
      <?php endwhile; ?>
    <?php else: ?><p>Aún no hay comentarios publicados.</p><?php endif; ?>
  </section>

  <?php if ($relacionadas->num_rows): ?>
    <section class="relacionadas">
      <h2>Noticias relacionadas</h2>
      <div class="noticias-grid">
        <?php while ($row = $relacionadas->fetch_assoc()): ?>
          <article class="card">
            <?php if ($row['imagen']): ?><img src="<?= e(url_imagen($row['imagen'])) ?>" alt="<?= e($row['imagen_alt'] ?: $row['titulo']) ?>" loading="lazy"><?php endif; ?>
            <div class="card-body"><h3><?= e($row['titulo']) ?></h3><p><?= e($row['resumen']) ?></p><a class="btn" href="<?= e(url_noticia($row)) ?>">Leer más</a></div>
          </article>
        <?php endwhile; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
