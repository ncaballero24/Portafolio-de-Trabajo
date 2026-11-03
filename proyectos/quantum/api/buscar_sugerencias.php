<?php
require_once '../conexion.php';
require_once '../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2 || mb_strlen($q) > 80) {
    echo json_encode(['resultados' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$busqueda = '%' . $q . '%';
$stmt = $conn->prepare("SELECT id, titulo, slug, categoria, fecha FROM noticias WHERE estado='publicada' AND (titulo LIKE ? OR resumen LIKE ? OR categoria LIKE ?) ORDER BY destacada DESC, visitas DESC, fecha DESC LIMIT 6");
$stmt->bind_param('sss', $busqueda, $busqueda, $busqueda);
$stmt->execute();
$resultados = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $resultados[] = [
        'titulo' => $row['titulo'],
        'categoria' => $row['categoria'],
        'fecha' => date('d/m/Y', strtotime($row['fecha'])),
        'url' => '../' . url_noticia($row),
    ];
}

echo json_encode(['resultados' => $resultados], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
