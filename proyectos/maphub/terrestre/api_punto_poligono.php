<?php
require 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$patrulla_id = intval($data['patrulla_id']);

$sql = "
SELECT
    ST_Y(ST_Centroid(geom::geometry)) AS lat,
    ST_X(ST_Centroid(geom::geometry)) AS lng
FROM poligonos
WHERE id = (
    SELECT poligono_id
    FROM patrullas
    WHERE id = $1
)
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

if(!$row){
    echo json_encode([
        "success"=>false
    ]);
    exit;
}

echo json_encode([
    "success"=>true,
    "lat"=>$row['lat'],
    "lng"=>$row['lng']
]);
?>
