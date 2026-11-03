<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

echo json_encode([
    [
        "camera_code" => "MX-001",
        "objeto" => "barco",
        "latitud" => 19.1738,
        "longitud" => -96.1342,
        "velocidad" => "12",
        "rumbo" => "85",
        "clima" => "Despejado",
        "riesgo_colision" => "Bajo"
    ],
    [
        "camera_code" => "MX-002",
        "objeto" => "boya",
        "latitud" => 19.25,
        "longitud" => -96.05,
        "velocidad" => "0",
        "rumbo" => "0",
        "clima" => "Nublado",
        "riesgo_colision" => "Medio"
    ],
    [
        "camera_code" => "MX-003",
        "objeto" => "barco",
        "latitud" => 19.05,
        "longitud" => -95.95,
        "velocidad" => "8",
        "rumbo" => "130",
        "clima" => "Lluvia ligera",
        "riesgo_colision" => "Medio"
    ]
]);
