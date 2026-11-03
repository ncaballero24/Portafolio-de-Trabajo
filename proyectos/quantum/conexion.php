<?php
date_default_timezone_set('America/Mexico_City');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$db = getenv('DB_NAME') ?: 'noticias_marina';
$port = (int) (getenv('DB_PORT') ?: 3308);

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $password, $db, $port);

if ($conn->connect_error) {
    error_log('Error de conexión MySQL: ' . $conn->connect_error);
    http_response_code(503);
    exit('El portal no puede conectarse temporalmente. Inténtalo más tarde.');
}

$conn->set_charset('utf8mb4');
