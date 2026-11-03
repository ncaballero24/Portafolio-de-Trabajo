<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

header('Content-Type: application/rss+xml; charset=UTF-8');
$base = rtrim(url_base(), '/');
$resultado = $conn->query("SELECT id, slug, titulo, resumen, contenido, categoria, autor, fecha FROM noticias WHERE estado='publicada' AND (fecha_programada IS NULL OR fecha_programada <= NOW()) ORDER BY fecha DESC LIMIT 30");

function rss_xml(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0">
<channel>
  <title>Quántum ININDETEC</title>
  <link><?= rss_xml($base . '/') ?></link>
  <description>Gaceta oficial de innovación, tecnología, defensa y conocimiento aplicado.</description>
  <language>es-MX</language>
  <lastBuildDate><?= gmdate(DATE_RSS) ?></lastBuildDate>
  <?php while ($noticia = $resultado->fetch_assoc()): $enlace = $base . '/' . url_noticia($noticia); ?>
  <item>
    <title><?= rss_xml($noticia['titulo']) ?></title>
    <link><?= rss_xml($enlace) ?></link>
    <guid isPermaLink="true"><?= rss_xml($enlace) ?></guid>
    <description><?= rss_xml($noticia['resumen'] ?: mb_substr(strip_tags($noticia['contenido']), 0, 300)) ?></description>
    <category><?= rss_xml($noticia['categoria']) ?></category>
    <author><?= rss_xml($noticia['autor']) ?></author>
    <pubDate><?= gmdate(DATE_RSS, strtotime($noticia['fecha'])) ?></pubDate>
  </item>
  <?php endwhile; ?>
</channel>
</rss>
