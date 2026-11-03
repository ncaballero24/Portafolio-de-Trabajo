<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require_once "auth.php";

header("Content-Type: application/json; charset=UTF-8");

$dsn = 'pgsql:host=' . (getenv('PGHOST') ?: 'localhost') . ';port=' . (getenv('PGPORT') ?: '5432') . ';dbname=' . (getenv('PGDATABASE') ?: 'maphub');
$user = getenv('PGUSER');
$password = getenv('PGPASSWORD');

$codigo = $_GET["codigo"] ?? null;

if (!$codigo) {
    echo json_encode(["ok" => false, "error" => "Falta parametro: codigo"]);
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT id, codigo FROM barcos WHERE codigo = :codigo LIMIT 1;");
    $stmt->execute(["codigo" => $codigo]);
    $barco = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$barco) {
        echo json_encode(["ok" => false, "error" => "Barco no encontrado"]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT timestamp, latitud, longitud, velocidad, rumbo, clima, riesgo_colision
        FROM ubicaciones_barco
        WHERE barco_id = :barco_id
        ORDER BY timestamp DESC
        LIMIT 10;
    ");
    $stmt->execute(["barco_id" => $barco["id"]]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "ok" => true,
        "codigo" => $barco["codigo"],
        "count" => count($historial),
        "historial" => $historial
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => 'No se pudo consultar el historial']);
}
