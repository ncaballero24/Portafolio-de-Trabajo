<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('GET, OPTIONS');
maphub_ensure_tables($conn);

$sql = "
WITH base AS (
    SELECT
        ROUND(ST_Y(geom::geometry)::numeric, 2) AS lat_bucket,
        ROUND(ST_X(geom::geometry)::numeric, 2) AS lng_bucket,
        tipo,
        severidad,
        EXTRACT(HOUR FROM timestamp) AS hora,
        EXTRACT(DOW FROM timestamp) AS dia
    FROM incidencias
    WHERE timestamp IS NOT NULL
),
riesgo AS (
    SELECT
        AVG(lat_bucket)::float AS lat,
        AVG(lng_bucket)::float AS lng,
        COUNT(*) AS total,
        ROUND(AVG(severidad)::numeric, 2) AS severidad_promedio,
        MODE() WITHIN GROUP (ORDER BY hora) AS hora_pico,
        MODE() WITHIN GROUP (ORDER BY dia) AS dia_pico,
        MODE() WITHIN GROUP (ORDER BY tipo) AS tipo_recurrente
    FROM base
    GROUP BY lat_bucket, lng_bucket
)
SELECT *,
       ROUND((total * severidad_promedio * (1 + CASE WHEN hora_pico BETWEEN 18 AND 23 THEN 0.20 ELSE 0 END))::numeric, 2) AS score
FROM riesgo
ORDER BY score DESC
LIMIT 12
";

$result = pg_query($conn, $sql);
if (!$result) {
    echo json_encode(['ok' => false, 'error' => pg_last_error($conn)], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($result)) $data[] = $row;

echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
?>
