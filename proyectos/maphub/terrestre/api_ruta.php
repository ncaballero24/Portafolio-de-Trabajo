<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "ok" => false,
        "error" => "No se recibieron datos"
    ]);
    exit;
}

$lat1 = $data['lat1'] ?? null;
$lng1 = $data['lng1'] ?? null;
$lat2 = $data['lat2'] ?? null;
$lng2 = $data['lng2'] ?? null;

if (!$lat1 || !$lng1 || !$lat2 || !$lng2) {
    echo json_encode([
        "ok" => false,
        "error" => "Coordenadas incompletas"
    ]);
    exit;
}

$url = "http://router.project-osrm.org/route/v1/driving/$lng1,$lat1;$lng2,$lat2?overview=full&geometries=geojson";

$response = @file_get_contents($url);

if ($response === false) {
    echo json_encode([
        "ok" => false,
        "error" => "No se pudo conectar con OSRM"
    ]);
    exit;
}

echo $response;
?>
