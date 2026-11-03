<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('GET, OPTIONS');
maphub_ensure_tables($conn);

$zonaExpr = "CASE
    WHEN ST_Y(i.geom::geometry) >= 19.145 AND ST_X(i.geom::geometry) >= -96.16 THEN 'norte-oriente'
    WHEN ST_Y(i.geom::geometry) >= 19.145 AND ST_X(i.geom::geometry) < -96.16 THEN 'norte-poniente'
    WHEN ST_Y(i.geom::geometry) < 19.145 AND ST_X(i.geom::geometry) >= -96.16 THEN 'sur-oriente'
    ELSE 'sur-poniente'
END";

$porZona = [];
$resZona = pg_query($conn, "SELECT $zonaExpr AS zona, COUNT(*) AS total FROM incidencias i GROUP BY 1 ORDER BY total DESC");
while ($resZona && ($row = pg_fetch_assoc($resZona))) $porZona[] = $row;

$porTipo = [];
$resTipo = pg_query($conn, "SELECT COALESCE(tipo, 'Sin tipo') AS tipo, COUNT(*) AS total FROM incidencias GROUP BY tipo ORDER BY total DESC LIMIT 10");
while ($resTipo && ($row = pg_fetch_assoc($resTipo))) $porTipo[] = $row;

$tiempos = [];
$resTiempos = pg_query($conn, "
    SELECT patrulla_id, ROUND(AVG(tiempo_respuesta) / 60.0, 1) AS promedio_min, COUNT(*) AS atendidas
    FROM historial_patrulla
    WHERE tiempo_respuesta IS NOT NULL
    GROUP BY patrulla_id
    ORDER BY atendidas DESC, promedio_min ASC
    LIMIT 8
");
while ($resTiempos && ($row = pg_fetch_assoc($resTiempos))) $tiempos[] = $row;

$patrullas = [];
$resPatrullas = pg_query($conn, "
    SELECT patrulla_atendio AS patrulla_id, COUNT(*) AS total
    FROM incidencias
    WHERE patrulla_atendio IS NOT NULL
    GROUP BY patrulla_atendio
    ORDER BY total DESC
    LIMIT 8
");
while ($resPatrullas && ($row = pg_fetch_assoc($resPatrullas))) $patrullas[] = $row;

echo json_encode([
    'ok' => true,
    'por_zona' => $porZona,
    'por_tipo' => $porTipo,
    'tiempos_respuesta' => $tiempos,
    'patrullas_activas' => $patrullas
], JSON_UNESCAPED_UNICODE);
?>
