<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
require 'db.php';
require 'auth.php';
maphub_ensure_tables($conn);

header('Content-Type: application/json');

$sql = "
SELECT p.id,
       p.estado,
       ST_Y(p.geom::geometry) AS lat,
       ST_X(p.geom::geometry) AS lng,
       d.incidencia_id,
       d.estado AS despacho_estado,
       ST_Y(i.geom::geometry) AS destino_lat,
       ST_X(i.geom::geometry) AS destino_lng,
       CASE
           WHEN i.geom IS NOT NULL THEN ROUND(ST_Distance(p.geom, i.geom)::numeric, 1)
           ELSE NULL
       END AS distancia_m
FROM patrullas p
LEFT JOIN LATERAL (
    SELECT md.incidencia_id, md.estado
    FROM maphub_dispatches md
    WHERE md.patrulla_id = p.id
      AND LOWER(md.estado) IN ('asignada', 'en camino', 'atendiendo')
    ORDER BY md.assigned_at DESC
    LIMIT 1
) d ON TRUE
LEFT JOIN incidencias i ON i.id = d.incidencia_id
ORDER BY p.id
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
    $data[] = $row;
}

echo json_encode($data);
