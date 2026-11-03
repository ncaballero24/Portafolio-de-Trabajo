<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php';

$sql = "SELECT id, ST_AsGeoJSON(geom) AS geom FROM poligonos ORDER BY id";
$result = @pg_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "ok" => false,
        "data" => [],
        "error" => pg_last_error($conn)
    ]);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
