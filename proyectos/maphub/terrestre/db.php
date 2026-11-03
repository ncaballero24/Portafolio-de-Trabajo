<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
// libpq toma PGHOST, PGPORT, PGDATABASE, PGUSER y PGPASSWORD del entorno.
$conn = @pg_connect("connect_timeout=5");
if (!$conn) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false, 'error'=>'Base de datos no disponible. Revisa la configuración del servidor.']);
    exit;
}
