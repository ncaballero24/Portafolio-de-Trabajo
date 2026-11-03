<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

$q = trim($_GET['q'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');
$autorFiltro = trim($_GET['autor'] ?? '');
$direccionFiltro = trim($_GET['direccion'] ?? '');
$fechaDesde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : '';
$fechaHasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : '';
$pagina = pagina_actual();
$porPagina = 9;
$total = 0;
$resultado = null;
$categoriasFiltro = categorias_activas($conn);
$autoresResultado = $conn->query("SELECT DISTINCT COALESCE(NULLIF(autor_publico,''), autor) AS nombre FROM noticias WHERE estado='publicada' AND COALESCE(NULLIF(autor_publico,''), autor, '') != '' ORDER BY nombre LIMIT 60");
$autoresFiltro = $autoresResultado ? $autoresResultado->fetch_all(MYSQLI_ASSOC) : [];
$hayFiltros = $categoriaFiltro !== '' || $autorFiltro !== '' || $direccionFiltro !== '' || $fechaDesde !== '' || $fechaHasta !== '';
$consultaValida = ($q === '' || (mb_strlen($q) >= 2 && mb_strlen($q) <= 100)) && ($q !== '' || $hayFiltros);

if ($consultaValida) {
    $busqueda = '%' . $q . '%';
    $autorLike = '%' . $autorFiltro . '%';
    $direccionLike = '%' . $direccionFiltro . '%';
    $sqlBase = " FROM noticias WHERE estado='publicada'
      AND (?='' OR titulo LIKE ? OR resumen LIKE ? OR contenido LIKE ?)
      AND (?='' OR categoria = ?)
      AND (?='' OR COALESCE(NULLIF(autor_publico,''), autor, '') LIKE ?)
      AND (?='' OR DATE(fecha) >= ?)
      AND (?='' OR DATE(fecha) <= ?)
      AND (?='' OR titulo LIKE ? OR resumen LIKE ? OR contenido LIKE ? OR categoria LIKE ?)";
    $tipos = str_repeat('s', 17);
    $parametros = [$q, $busqueda, $busqueda, $busqueda, $categoriaFiltro, $categoriaFiltro, $autorFiltro, $autorLike, $fechaDesde, $fechaDesde, $fechaHasta, $fechaHasta, $direccionFiltro, $direccionLike, $direccionLike, $direccionLike, $direccionLike];
    $stmtTotal = $conn->prepare('SELECT COUNT(*) AS total' . $sqlBase);
    $stmtTotal->bind_param($tipos, ...$parametros);
    $stmtTotal->execute();
    $total = (int) $stmtTotal->get_result()->fetch_assoc()['total'];
    $offset = ($pagina - 1) * $porPagina;
    $stmt = $conn->prepare('SELECT id, titulo, slug, resumen, imagen, imagen_alt, categoria, fecha, COALESCE(NULLIF(autor_publico,\'\'), autor) AS autor' . $sqlBase . ' ORDER BY destacada DESC, fecha DESC LIMIT ? OFFSET ?');
    $parametrosResultado = [...$parametros, $porPagina, $offset];
    $stmt->bind_param($tipos . 'ii', ...$parametrosResultado);
    $stmt->execute();
    $resultado = $stmt->get_result();
}

$totalPaginas = max(1, (int) ceil($total / $porPagina));
$page_title = $q ? 'Resultados para ' . $q . ' | Quántum ININDETEC' : 'Buscar publicaciones';
$page_description = 'Busca noticias marítimas, navales y portuarias.';
$parametrosCanonical = array_filter(['q' => $q, 'categoria' => $categoriaFiltro, 'autor' => $autorFiltro, 'direccion' => $direccionFiltro, 'desde' => $fechaDesde, 'hasta' => $fechaHasta], fn($valor) => $valor !== '');
$canonical = url_base() . '/buscar.php' . ($parametrosCanonical ? '?' . http_build_query($parametrosCanonical) : '');
$body_class = 'quantum-listado-page quantum-busqueda-page';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-principal">
  <header class="encabezado-listado">
    <span>Buscador</span>
    <h1><?= $q ? 'Resultados para: ' . e($q) : 'Buscar noticias' ?></h1>
    <p><?= $total ?> resultado<?= $total === 1 ? '' : 's' ?></p>
  </header>

  <form class="filtros-busqueda-inteligente" method="GET" role="search">
    <label class="filtro-busqueda-principal">Buscar
      <span class="campo-sugerencias-wrap">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Título, tema o palabra clave" autocomplete="off" data-busqueda-sugerencias data-endpoint="api/buscar_sugerencias.php">
        <span class="sugerencias-busqueda" role="listbox" hidden></span>
      </span>
    </label>
    <label>Categoría
      <select name="categoria">
        <option value="">Todas</option>
        <?php foreach ($categoriasFiltro as $categoria): ?><option value="<?= e($categoria['nombre']) ?>"<?= $categoriaFiltro === $categoria['nombre'] ? ' selected' : '' ?>><?= e($categoria['nombre']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Autor
      <input type="text" name="autor" value="<?= e($autorFiltro) ?>" placeholder="Nombre del autor" list="autores-publicados">
      <datalist id="autores-publicados"><?php foreach ($autoresFiltro as $autor): ?><option value="<?= e($autor['nombre']) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>Dirección o área
      <input type="text" name="direccion" value="<?= e($direccionFiltro) ?>" placeholder="Ej. Ciberespacio">
    </label>
    <label>Desde<input type="date" name="desde" value="<?= e($fechaDesde) ?>"></label>
    <label>Hasta<input type="date" name="hasta" value="<?= e($fechaHasta) ?>"></label>
    <div class="acciones-filtros-busqueda"><button class="btn" type="submit">Aplicar filtros</button><a href="buscar.php">Limpiar</a></div>
  </form>

  <?php if (!$consultaValida): ?>
    <div class="estado-vacio"><h2>Encuentra una publicación</h2><p>Escribe al menos dos caracteres o utiliza uno de los filtros disponibles.</p></div>
  <?php elseif ($resultado && $resultado->num_rows): ?>
    <section class="noticias-grid">
      <?php while ($row = $resultado->fetch_assoc()): ?>
        <article class="card">
          <?php if ($row['imagen']): ?><img src="<?= e(url_imagen($row['imagen'])) ?>" alt="<?= e($row['imagen_alt'] ?: $row['titulo']) ?>" loading="lazy" decoding="async"><?php endif; ?>
          <div class="card-body"><span class="categoria"><?= e($row['categoria']) ?></span><h2><?= e($row['titulo']) ?></h2><p><?= e($row['resumen']) ?></p><?php if ($row['autor']): ?><small>Por <?= e($row['autor']) ?></small><?php endif; ?><a class="btn" href="<?= e(url_noticia($row)) ?>">Leer noticia</a></div>
        </article>
      <?php endwhile; ?>
    </section>
    <?= render_paginacion($pagina, $totalPaginas) ?>
  <?php else: ?>
    <div class="estado-vacio"><h2>No encontramos coincidencias</h2><p>Prueba con términos más generales o revisa la ortografía.</p></div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
