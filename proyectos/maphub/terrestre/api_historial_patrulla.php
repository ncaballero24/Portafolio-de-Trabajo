<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require 'db.php';

$patrulla_id = intval($_GET['patrulla_id'] ?? 0);

if ($patrulla_id <= 0) {
    echo json_encode([
        "ok" => false,
        "error" => "Falta patrulla_id"
    ]);
    exit;
}

$sql = "
SELECT
    h.id,
    h.patrulla_id,
    h.incidencia_id,
    h.fecha_resolucion,
    h.tiempo_respuesta,
    h.estado,
    i.tipo,
    i.zona,
    i.severidad,
    i.descripcion,
    i.evidencia_imagen
FROM historial_patrulla h
LEFT JOIN incidencias i ON i.id = h.incidencia_id
WHERE h.patrulla_id = $1
ORDER BY h.fecha_resolucion DESC
";

$result = pg_query_params($conn, $sql, [$patrulla_id]);

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

echo json_encode([
    "ok" => true,
    "data" => $data
]);
?>
