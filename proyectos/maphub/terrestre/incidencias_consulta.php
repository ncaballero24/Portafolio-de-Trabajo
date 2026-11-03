<?php
function maphub_consultar_incidencias($conn) {
$limit = max(30, min(intval($_GET['limit'] ?? 220), 500));
$params = [];
$where = ["1=1"];
$zonaExpr = "CASE
    WHEN ST_Y(geom::geometry) >= 19.145 AND ST_X(geom::geometry) >= -96.16 THEN 'norte-oriente'
    WHEN ST_Y(geom::geometry) >= 19.145 AND ST_X(geom::geometry) < -96.16 THEN 'norte-poniente'
    WHEN ST_Y(geom::geometry) < 19.145 AND ST_X(geom::geometry) >= -96.16 THEN 'sur-oriente'
    ELSE 'sur-poniente'
END";

function maphub_inc_param(&$params, $value) {
    $params[] = $value;
    return '$' . count($params);
}

$tipo = trim((string) ($_GET['tipo'] ?? ''));
if ($tipo !== '' && strtolower($tipo) !== 'todos') {
    $where[] = "LOWER(TRIM(tipo)) = LOWER(TRIM(" . maphub_inc_param($params, $tipo) . "))";
}

$zona = trim((string) ($_GET['zona'] ?? ''));
if ($zona !== '' && strtolower($zona) !== 'todas') {
    $where[] = "$zonaExpr = " . maphub_inc_param($params, $zona);
}

if (isset($_GET['severidad_min']) && $_GET['severidad_min'] !== '') {
    $where[] = "severidad >= " . intval($_GET['severidad_min']);
}

if (isset($_GET['severidad_max']) && $_GET['severidad_max'] !== '') {
    $where[] = "severidad <= " . intval($_GET['severidad_max']);
}

if (!empty($_GET['fecha_inicio'])) {
    $where[] = "timestamp >= " . maphub_inc_param($params, $_GET['fecha_inicio'] . ' 00:00:00');
}

if (!empty($_GET['fecha_fin'])) {
    $where[] = "timestamp <= " . maphub_inc_param($params, $_GET['fecha_fin'] . ' 23:59:59');
}

$sql = "
SELECT id,
       tipo,
       severidad,
       descripcion,
       evidencia_imagen,
       estado,
       patrulla_atendio,
       timestamp,
       ST_Y(geom::geometry) AS latitud,
       ST_X(geom::geometry) AS longitud,
       $zonaExpr AS zona
FROM incidencias
WHERE " . implode(' AND ', $where) . "
ORDER BY timestamp DESC
LIMIT $limit
";

$result = pg_query_params($conn, $sql, $params);

if (!$result) {
    throw new RuntimeException('No se pudieron consultar las incidencias');
}

$data = [];
while ($row = pg_fetch_assoc($result)) {
    $data[] = $row;
}


return $data;
}
