<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';
header('Content-Type: application/xml; charset=utf-8');
$base=url_base();
$urls=[
  ['loc'=>$base.'/','lastmod'=>date('Y-m-d')],
  ['loc'=>$base.'/direcciones.php','lastmod'=>date('Y-m-d')],
  ['loc'=>$base.'/simposium.php','lastmod'=>date('Y-m-d')],
  ['loc'=>$base.'/videos.php','lastmod'=>date('Y-m-d')],
  ['loc'=>$base.'/galeria/galeria.php','lastmod'=>date('Y-m-d')],
];
foreach(array_keys(direcciones_inindetec()) as $slug){$urls[]=['loc'=>$base.'/direccion.php?dir='.rawurlencode($slug),'lastmod'=>date('Y-m-d')];}
$categorias=$conn->query('SELECT nombre FROM categorias WHERE activa=1');
while($c=$categorias->fetch_assoc()){$urls[]=['loc'=>$base.'/categoria.php?cat='.rawurlencode($c['nombre']),'lastmod'=>date('Y-m-d')];}
$noticias=$conn->query("SELECT slug,fecha,actualizado_en FROM noticias WHERE estado='publicada' ORDER BY fecha DESC");
while($n=$noticias->fetch_assoc()){$urls[]=['loc'=>$base.'/noticia.php?slug='.rawurlencode($n['slug']),'lastmod'=>date('Y-m-d',strtotime($n['actualizado_en']?:$n['fecha']))];}
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach($urls as $url): ?>
  <url><loc><?= e($url['loc']) ?></loc><lastmod><?= e($url['lastmod']) ?></lastmod></url>
<?php endforeach; ?>
</urlset>
