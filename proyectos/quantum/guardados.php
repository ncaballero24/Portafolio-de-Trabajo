<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

$page_title = 'Mis lecturas | Quántum ININDETEC';
$page_description = 'Publicaciones guardadas en este dispositivo para consultarlas más tarde.';
$canonical = url_base() . '/guardados.php';
$body_class = 'quantum-listado-page quantum-guardados-page';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-principal">
  <header class="encabezado-listado">
    <span>Biblioteca personal</span>
    <h1>Mis lecturas</h1>
    <p>Estas publicaciones se guardan únicamente en este dispositivo.</p>
  </header>
  <section class="panel-guardados">
    <div class="panel-guardados-cabecera"><p><strong data-total-guardados>0</strong> publicaciones guardadas</p><button type="button" data-limpiar-guardados>Vaciar lista</button></div>
    <div class="noticias-grid" data-lista-guardados></div>
    <div class="estado-vacio" data-guardados-vacio><h2>Aún no guardas publicaciones</h2><p>Usa el botón “Guardar” en cualquier noticia para crear tu lista de lectura.</p><a class="btn" href="index.php">Explorar Quántum</a></div>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
