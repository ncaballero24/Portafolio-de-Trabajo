<?php
if (PHP_SAPI !== 'cli') {
    exit("Sólo CLI.\n");
}
require_once dirname(__DIR__) . '/conexion.php';
require_once dirname(__DIR__) . '/includes/funciones.php';

$regenerar = in_array('--regenerate', $argv, true);
if ($regenerar) {
    $conn->query('UPDATE noticias SET slug=NULL');
}
$noticias = $conn->query("SELECT id, titulo FROM noticias WHERE slug IS NULL OR slug='' ORDER BY id");
while ($noticia = $noticias->fetch_assoc()) {
    $slug = slug_unico($conn, $noticia['titulo'], (int) $noticia['id']);
    $stmt = $conn->prepare('UPDATE noticias SET slug=? WHERE id=?');
    $stmt->bind_param('si', $slug, $noticia['id']);
    $stmt->execute();
}

$indice = $conn->query("SHOW INDEX FROM noticias WHERE Key_name='uq_noticias_slug'");
if ($indice->num_rows === 0) {
    $conn->query('ALTER TABLE noticias ADD UNIQUE KEY uq_noticias_slug (slug)');
}
echo "Slugs e índice preparados.\n";
