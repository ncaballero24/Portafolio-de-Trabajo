<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

$ultimaHoraResultado = $conn->query("
    SELECT mensaje, tipo
    FROM ultima_hora
    WHERE activo = 1
      AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
      AND (fecha_fin IS NULL OR fecha_fin >= NOW())
    ORDER BY fecha DESC
    LIMIT 1
");
$ultima_hora = $ultimaHoraResultado ? $ultimaHoraResultado->fetch_assoc() : null;

$destacadasResultado = $conn->query("SELECT id, titulo, slug, resumen, imagen, imagen_alt, categoria, fecha, visitas FROM noticias WHERE destacada = 1 AND estado = 'publicada' ORDER BY fecha DESC LIMIT 5");
$destacadas = $destacadasResultado->fetch_all(MYSQLI_ASSOC);
$noticias = $conn->query("SELECT id, titulo, slug, resumen, imagen, imagen_alt, categoria, fecha FROM noticias WHERE estado = 'publicada' ORDER BY fecha DESC LIMIT 6");
$pasantes = $conn->query("SELECT id, titulo, slug, resumen, imagen, imagen_alt FROM noticias WHERE categoria = 'Los pasantes opinan' AND estado = 'publicada' ORDER BY fecha DESC LIMIT 3");
$recientes = $conn->query("SELECT id, titulo, slug, categoria FROM noticias WHERE estado = 'publicada' ORDER BY fecha DESC LIMIT 5");
$mas_leidas = $conn->query("SELECT id, titulo, slug, visitas FROM noticias WHERE estado = 'publicada' ORDER BY visitas DESC, fecha DESC LIMIT 5");
$portadaVideoResultado = $conn->query("SELECT titulo, descripcion, archivo, mime FROM videos WHERE publicado = 1 AND en_inicio = 1 ORDER BY fecha DESC, id DESC LIMIT 1");
$portadaVideo = $portadaVideoResultado ? $portadaVideoResultado->fetch_assoc() : null;

$estadisticasResultado = $conn->query("
    SELECT
      (SELECT COUNT(*) FROM noticias WHERE estado = 'publicada') AS noticias,
      (SELECT COUNT(*) FROM videos WHERE publicado = 1) AS videos,
      (SELECT COUNT(*) FROM galeria) AS imagenes,
      (SELECT COALESCE(SUM(visitas), 0) FROM noticias WHERE estado = 'publicada') AS lecturas
");
$estadisticas = $estadisticasResultado ? $estadisticasResultado->fetch_assoc() : ['noticias' => 0, 'videos' => 0, 'imagenes' => 0, 'lecturas' => 0];

$exploraRecientesResultado = $conn->query("SELECT id, titulo, slug, categoria, fecha FROM noticias WHERE estado='publicada' ORDER BY fecha DESC LIMIT 4");
$exploraLeidasResultado = $conn->query("SELECT id, titulo, slug, categoria, visitas FROM noticias WHERE estado='publicada' ORDER BY visitas DESC, fecha DESC LIMIT 4");
$exploraRecomendadasResultado = $conn->query("SELECT id, titulo, slug, categoria, fecha FROM noticias WHERE estado='publicada' ORDER BY destacada DESC, visitas DESC, fecha DESC LIMIT 4");
$exploraRecientes = $exploraRecientesResultado ? $exploraRecientesResultado->fetch_all(MYSQLI_ASSOC) : [];
$exploraLeidas = $exploraLeidasResultado ? $exploraLeidasResultado->fetch_all(MYSQLI_ASSOC) : [];
$exploraRecomendadas = $exploraRecomendadasResultado ? $exploraRecomendadasResultado->fetch_all(MYSQLI_ASSOC) : [];

$lineaTiempoResultado = $conn->query("SELECT id, titulo, slug, resumen, categoria, fecha FROM noticias WHERE estado='publicada' AND (categoria='Tecnología' OR titulo LIKE '%tecnolog%' OR resumen LIKE '%tecnolog%') ORDER BY fecha DESC LIMIT 8");
$lineaTiempo = $lineaTiempoResultado ? $lineaTiempoResultado->fetch_all(MYSQLI_ASSOC) : [];

$page_title = 'Quántum ININDETEC | Gaceta Oficial';
$page_description = 'Gaceta oficial de ININDETEC con información sobre innovación, tecnología, defensa, ciberespacio y conocimiento aplicado.';
$body_class = 'quantum-home-page';
$canonical = url_base() . '/';
$extra_head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">'
    . '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>';
$extra_scripts = '<script src="' . e(asset_url('js/mapa_naval.js')) . '"></script>';
include 'includes/header.php';
?>

<div id="popup" class="popup-overlay" role="dialog" aria-modal="true" aria-labelledby="popup-titulo" hidden>
  <div class="popup-box">
    <button class="close-btn" type="button" onclick="cerrarPopup()" aria-label="Cerrar">&times;</button>
    <h2 id="popup-titulo">Mantente al frente de la transformación</h2>
    <p>Recibe publicaciones sobre innovación, tecnología, defensa y conocimiento aplicado.</p>
    <form onsubmit="suscribirse(event)">
      <label class="sr-only" for="correo-popup">Correo electrónico</label>
      <input type="email" id="correo-popup" name="correo" placeholder="tu_correo@ejemplo.com" autocomplete="email" required>
      <button type="submit">Suscribirme</button>
    </form>
  </div>
</div>

<?php if ($ultima_hora): ?>
<section class="breaking-news breaking-<?= e($ultima_hora['tipo'] ?? 'alerta') ?>" aria-label="Última hora">
  <div class="breaking-news-inner">
    <div class="breaking-label"><span class="breaking-dot"></span><strong>Última hora</strong></div>
    <div class="breaking-marquee"><p><?= e($ultima_hora['mensaje']) ?></p></div>
    <span class="breaking-source">Sala de redacción</span>
  </div>
</section>
<?php endif; ?>

<main class="contenedor" id="contenido-principal">
  <section class="quantum-edicion" aria-labelledby="titulo-edicion">
    <header class="quantum-edicion-header">
      <span>Primera edición</span>
      <h1 id="titulo-edicion">Descubre Quántum</h1>
    </header>

    <?php if ($portadaVideo): ?>
      <article class="quantum-feature quantum-feature-video">
        <video controls preload="metadata" playsinline aria-label="<?= e($portadaVideo['titulo']) ?>">
          <source src="<?= e($portadaVideo['archivo']) ?>" type="<?= e($portadaVideo['mime']) ?>">
          Tu navegador no puede reproducir este video.
        </video>
        <button class="video-play-overlay" type="button" aria-label="Reproducir <?= e($portadaVideo['titulo']) ?>">
          <span aria-hidden="true"></span>
        </button>
        <div class="quantum-video-caption">
          <span>Quántum Multimedia</span>
          <h2><?= e($portadaVideo['titulo']) ?></h2>
        </div>
      </article>
    <?php elseif (!empty($destacadas[0])): $principal = $destacadas[0]; ?>
      <article class="quantum-feature">
        <img src="<?= e(url_imagen($principal['imagen'])) ?>" alt="<?= e($principal['imagen_alt'] ?: $principal['titulo']) ?>" fetchpriority="high">
        <div class="quantum-feature-shade"></div>
        <img class="quantum-feature-mark" src="<?= e(asset_url('assets/quantum-logo-vertical.png')) ?>" alt="">
        <div class="quantum-feature-caption">
          <span><?= e($principal['categoria']) ?></span>
          <h2><?= e($principal['titulo']) ?></h2>
          <?php if (!empty($principal['resumen'])): ?><p class="quantum-feature-summary"><?= e($principal['resumen']) ?></p><?php endif; ?>
          <a class="quantum-feature-action" href="<?= e(url_noticia($principal)) ?>">Ver edición</a>
        </div>
        <div class="quantum-feature-meta" aria-label="Datos de la publicación">
          <span><b>Fecha</b><?= e(date('d/m/Y', strtotime($principal['fecha']))) ?></span>
          <span><b>Lecturas</b><?= number_format((int) $principal['visitas']) ?></span>
        </div>
      </article>
    <?php endif; ?>
  </section>

  <?php if (count($destacadas) > 1): ?>
    <section class="quantum-destacadas" id="noticias-destacadas" aria-labelledby="titulo-destacadas">
      <header><h2 id="titulo-destacadas">Noticias destacadas</h2><a href="#noticias-recientes">Ver todas las noticias</a></header>
      <div class="quantum-destacadas-grid">
        <?php foreach (array_slice($destacadas, 1, 2) as $row): ?>
          <article class="quantum-destacada-card">
            <?php if ($row['imagen']): ?><img src="<?= e(url_imagen($row['imagen'])) ?>" alt="<?= e($row['imagen_alt'] ?: $row['titulo']) ?>" loading="lazy" decoding="async"><?php endif; ?>
            <div class="quantum-destacada-content">
              <span><?= e($row['categoria']) ?></span>
              <h3><?= e($row['titulo']) ?></h3>
              <a href="<?= e(url_noticia($row)) ?>">Entérate aquí</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section class="quantum-access" aria-labelledby="titulo-accesos-quantum">
    <header>
      <h2 id="titulo-accesos-quantum">Centro de Operaciones Marítimas</h2>
      <span aria-hidden="true"></span>
    </header>
    <div class="quantum-access-grid">
      <a href="categoria.php?cat=<?= rawurlencode('Tecnología') ?>"><i aria-hidden="true">T</i><strong>Tecnología</strong><small>Innovación, investigación y desarrollos tecnológicos.</small></a>
      <a href="direcciones.php"><i aria-hidden="true">D</i><strong>Direcciones</strong><small>Consulta las áreas técnicas y operativas de ININDETEC.</small></a>
      <a href="videos.php"><i aria-hidden="true">V</i><strong>Videos</strong><small>Material audiovisual y demostraciones institucionales.</small></a>
      <a href="#visor-maritimo"><i aria-hidden="true">C</i><strong>Conectividad</strong><small>Visor de regiones y cobertura marítima nacional.</small></a>
    </div>
  </section>

  <section class="quantum-cifras" aria-labelledby="titulo-quantum-cifras">
    <header>
      <span>Impacto editorial</span>
      <h2 id="titulo-quantum-cifras">Quántum en cifras</h2>
      <p>Una vista rápida al contenido y alcance de la Gaceta Oficial ININDETEC.</p>
    </header>
    <div class="quantum-cifras-grid">
      <article><strong data-contador="<?= (int) $estadisticas['noticias'] ?>">0</strong><span>Publicaciones</span></article>
      <article><strong data-contador="<?= (int) $estadisticas['videos'] ?>">0</strong><span>Videos</span></article>
      <article><strong data-contador="<?= (int) $estadisticas['imagenes'] ?>">0</strong><span>Imágenes</span></article>
      <article><strong data-contador="<?= (int) $estadisticas['lecturas'] ?>">0</strong><span>Lecturas acumuladas</span></article>
    </div>
  </section>

  <section class="quantum-explora" aria-labelledby="titulo-quantum-explora">
    <header>
      <div><span>Selección editorial</span><h2 id="titulo-quantum-explora">Explora Quántum</h2></div>
      <div class="quantum-tabs" role="tablist" aria-label="Selección de publicaciones">
        <button type="button" role="tab" aria-selected="true" aria-controls="panel-recientes" id="tab-recientes" data-tab-quantum="recientes">Lo más reciente</button>
        <button type="button" role="tab" aria-selected="false" aria-controls="panel-leidas" id="tab-leidas" data-tab-quantum="leidas">Más leído</button>
        <button type="button" role="tab" aria-selected="false" aria-controls="panel-recomendadas" id="tab-recomendadas" data-tab-quantum="recomendadas">Recomendado</button>
      </div>
    </header>
    <?php foreach (['recientes' => $exploraRecientes, 'leidas' => $exploraLeidas, 'recomendadas' => $exploraRecomendadas] as $clavePanel => $elementosPanel): ?>
      <div class="quantum-tab-panel" id="panel-<?= e($clavePanel) ?>" role="tabpanel" aria-labelledby="tab-<?= e($clavePanel) ?>"<?= $clavePanel !== 'recientes' ? ' hidden' : '' ?>>
        <?php foreach ($elementosPanel as $indice => $row): ?>
          <a href="<?= e(url_noticia($row)) ?>">
            <span><?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <div><small><?= e($row['categoria']) ?></small><strong><?= e($row['titulo']) ?></strong></div>
            <?php if (isset($row['visitas'])): ?><em><?= number_format((int) $row['visitas']) ?> lecturas</em><?php else: ?><em>Ver publicación</em><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="centro-operaciones" id="visor-maritimo" aria-labelledby="titulo-centro-operaciones">
    <h2 class="seccion-titulo" id="titulo-centro-operaciones">Visor de regiones navales</h2>
    <section class="mapa-section naval-dashboard visor-operaciones-maritimas">
      <div class="visor-maritimo-layout">
        <div class="mapa-operaciones-wrap"><div id="mapaMaritimo"></div></div>
        <aside class="panel-operaciones-naval" id="panelOperacionNaval">
          <div class="panel-operaciones-header">
            <span id="estadoOperacionNaval">Sin selección</span>
            <strong id="regionNumeroNaval">--</strong>
          </div>
          <h3 id="regionTituloNaval">Centro de Operaciones Marítimas</h3>
          <p class="panel-operaciones-intro">Selecciona una región naval para consultar su sede, jurisdicción, estados, cobertura e instalaciones relacionadas.</p>
          <div class="operacion-dato"><span>Sede</span><p id="regionSedeNaval">Selecciona una región naval en el mapa.</p></div>
          <div class="operacion-dato"><span>Jurisdicción</span><p id="regionJurisdiccionNaval">El visor mostrará la jurisdicción marítima de cada región.</p></div>
          <div class="operacion-dato"><span>Estados</span><p id="regionEstadosNaval">Cobertura nacional por regiones.</p></div>
          <div class="operacion-dato"><span>Cobertura</span><p id="regionCoberturaNaval">Activa o desactiva capas desde el control del mapa.</p></div>
          <div class="operacion-dato operacion-sectores">
            <span>Sectores / instalaciones</span>
            <ul id="regionSectoresNaval">
              <li>Sectores navales por integrar</li>
              <li>Bases aeronavales por integrar</li>
              <li>Rutas marítimas por integrar</li>
            </ul>
          </div>
        </aside>
      </div>
      <div class="leyenda-regiones visor-leyenda-regiones">
        <?php foreach (['#14b8a6','#3b82f6','#fb923c','#22c55e','#2dd4bf','#8b5cf6','#ef4444','#facc15'] as $i => $color): ?>
          <span><i style="background:<?= $color ?>"></i><?= $i + 1 ?>ª Región Naval</span>
        <?php endforeach; ?>
      </div>
    </section>

  </section>

  <div class="contenido-central">
    <h2 class="seccion-titulo" id="noticias-recientes">Noticias recientes</h2>
    <section class="noticias-grid">
      <?php while ($row = $noticias->fetch_assoc()): ?>
        <article class="card">
          <?php if ($row['imagen']): ?><img src="<?= e(url_imagen($row['imagen'])) ?>" alt="<?= e($row['imagen_alt'] ?: $row['titulo']) ?>" loading="lazy" decoding="async"><?php endif; ?>
          <div class="card-body">
            <span class="categoria"><?= e($row['categoria']) ?></span>
            <h3><?= e($row['titulo']) ?></h3>
            <p><?= e($row['resumen']) ?></p>
            <a class="btn" href="<?= e(url_noticia($row)) ?>">Leer noticia</a>
          </div>
        </article>
      <?php endwhile; ?>
    </section>

    <?php if ($pasantes->num_rows > 0): ?>
      <h2 class="seccion-titulo">Los pasantes opinan</h2>
      <section class="noticias-grid">
        <?php while ($row = $pasantes->fetch_assoc()): ?>
          <article class="card">
            <?php if ($row['imagen']): ?><img src="<?= e(url_imagen($row['imagen'])) ?>" alt="<?= e($row['imagen_alt'] ?: $row['titulo']) ?>" loading="lazy" decoding="async"><?php endif; ?>
            <div class="card-body"><h3><?= e($row['titulo']) ?></h3><p><?= e($row['resumen']) ?></p><a class="btn" href="<?= e(url_noticia($row)) ?>">Leer opinión</a></div>
          </article>
        <?php endwhile; ?>
      </section>
    <?php endif; ?>
  </div>

  <aside class="sidebar-portada">
    <h2>Últimos artículos</h2>
    <?php while ($row = $recientes->fetch_assoc()): ?>
      <div class="reciente"><h3><?= e($row['titulo']) ?></h3><p><?= e($row['categoria']) ?></p><a href="<?= e(url_noticia($row)) ?>">Ver noticia</a></div>
    <?php endwhile; ?>
    <div class="categorias">
      <h2>Categorías</h2>
      <?php foreach (categorias_activas($conn) as $categoria): ?>
        <a href="categoria.php?cat=<?= rawurlencode($categoria['nombre']) ?>"><?= e($categoria['nombre']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="mas-leidas">
      <h2>Más leídas</h2>
      <?php while ($row = $mas_leidas->fetch_assoc()): ?>
        <p><a href="<?= e(url_noticia($row)) ?>"><?= e($row['titulo']) ?></a><small><?= (int) $row['visitas'] ?> lecturas</small></p>
      <?php endwhile; ?>
    </div>
  </aside>

  <?php if ($lineaTiempo): ?>
    <section class="quantum-linea-tiempo" aria-labelledby="titulo-linea-tiempo">
      <header><span>Innovación aplicada</span><h2 id="titulo-linea-tiempo">Línea del tiempo tecnológica</h2><p>Avances y publicaciones de tecnología ordenados cronológicamente.</p></header>
      <div class="linea-tiempo-filtros" role="group" aria-label="Filtrar línea del tiempo">
        <button type="button" class="activo" data-linea-filtro="todos">Todos</button>
        <?php foreach (array_values(array_unique(array_map(fn($item) => date('Y', strtotime($item['fecha'])), $lineaTiempo))) as $anio): ?>
          <button type="button" data-linea-filtro="<?= e($anio) ?>"><?= e($anio) ?></button>
        <?php endforeach; ?>
      </div>
      <ol>
        <?php foreach ($lineaTiempo as $row): $anio = date('Y', strtotime($row['fecha'])); ?>
          <li data-linea-anio="<?= e($anio) ?>">
            <time datetime="<?= e(date('Y-m-d', strtotime($row['fecha']))) ?>"><?= e(date('d M Y', strtotime($row['fecha']))) ?></time>
            <div><span><?= e($row['categoria']) ?></span><h3><?= e($row['titulo']) ?></h3><p><?= e($row['resumen']) ?></p><a href="<?= e(url_noticia($row)) ?>">Consultar avance</a></div>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  <?php endif; ?>

  <section class="newsletter-inline">
    <div><span>Boletín Quántum</span><h2>Recibe el Informe Quántum</h2><p>Actualizaciones sobre innovación tecnológica, publicaciones y proyectos de ININDETEC directamente en tu correo.</p></div>
    <form onsubmit="suscribirse(event)">
      <label class="sr-only" for="correo-inline">Correo electrónico</label>
      <input id="correo-inline" type="email" name="correo" placeholder="tu_correo@ejemplo.com" autocomplete="email" required>
      <button class="btn" type="submit">Suscribirme</button>
    </form>
  </section>
</main>

<div id="toast" class="toast" role="status" aria-live="polite"></div>
<?php include 'includes/footer.php'; ?>
