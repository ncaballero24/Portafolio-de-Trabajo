<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 0);
error_reporting(E_ALL);

require 'db.php';

header('Content-Type: application/json');

// Valores seguros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$minutos = isset($_GET['minutos']) ? intval($_GET['minutos']) : 180;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;

// Evitar valores locos
$minutos = isset($_GET['minutos']) ? intval($_GET['minutos']) : 999999;
if ($limit <= 0) $limit = 1000;

// Filtro tipo
$filtroTipo = "";
if ($tipo !== 'todos') {
    $tipo = pg_escape_string($conn, $tipo);
    $filtroTipo = "AND tipo = '$tipo'";
}

$sql = "
SELECT
    ST_Y(geom::geometry) AS lat,
    ST_X(geom::geometry) AS lng,
    severidad
FROM incidencias
WHERE timestamp >= NOW() - INTERVAL '$minutos minutes'
$filtroTipo
LIMIT $limit
";

$result = pg_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "ok" => false,
        "error" => pg_last_error($conn)
    ]);
    exit;
}

$data = [];

while ($row = pg_fetch_assoc($result)) {
    $data[] = [
        floatval($row['lat']),
        floatval($row['lng']),
        intval($row['severidad'])
    ];
}

echo json_encode([
    "ok" => true,
    "count" => count($data),
    "data" => $data
]);
?>
