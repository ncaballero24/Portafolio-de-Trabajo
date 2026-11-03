<?php
require 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$patrulla_id = intval($data['patrulla_id']);

$sql = "
SELECT
    ST_Y(i.geom::geometry) AS lat,
    ST_X(i.geom::geometry) AS lng
FROM incidencias i
JOIN poligonos p ON ST_Contains(p.geom::geometry, i.geom::geometry)
WHERE p.id = (
    SELECT poligono_id
    FROM patrullas
    WHERE id = $1
)
AND i.resuelta = FALSE
ORDER BY i.severidad DESC, i.timestamp DESC
LIMIT 1
";

$result = pg_query_params($conn, $sql, [$patrulla_id]);

if(!$result){
    echo json_encode([
        "success"=>false,
        "error"=>pg_last_error($conn)
    ]);
    exit;
}

$row = pg_fetch_assoc($result);

if($row && $row['lat'] !== null && $row['lng'] !== null){
    echo json_encode([
        "success"=>true,
        "lat"=>$row['lat'],
        "lng"=>$row['lng']
    ]);
    exit;
}

$sql2 = "
SELECT
    ST_Y(ST_PointOnSurface(geom::geometry)) AS lat,
    ST_X(ST_PointOnSurface(geom::geometry)) AS lng
FROM poligonos
WHERE id = (
    SELECT poligono_id
    FROM patrullas
    WHERE id = $1
)
";

$result2 = pg_query_params($conn, $sql2, [$patrulla_id]);

if(!$result2){
    echo json_encode([
        "success"=>false,
        "error"=>pg_last_error($conn)
    ]);
    exit;
}

$row2 = pg_fetch_assoc($result2);

if(!$row2){
    echo json_encode([
        "success"=>false,
        "error"=>"No se encontró polígono"
    ]);
    exit;
}

echo json_encode([
    "success"=>true,
    "lat"=>$row2['lat'],
    "lng"=>$row2['lng']
]);
?>
