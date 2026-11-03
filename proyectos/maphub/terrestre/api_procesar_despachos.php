<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('POST, OPTIONS');
maphub_ensure_tables($conn);
maphub_require_role('operador');
if (getenv('MAPHUB_DEMO') !== '1') { echo json_encode(['ok'=>true,'moved'=>0,'resolved'=>0,'simulation'=>false]); exit; }

if (ob_get_length()) ob_clean();
ini_set('display_errors', 0);

pg_query($conn, "
    INSERT INTO maphub_dispatches(
        incidencia_id, patrulla_id, estado, assigned_at, updated_at
    )
    SELECT i.id, i.patrulla_atendio, 'En camino', NOW(), NOW()
    FROM incidencias i
    WHERE i.patrulla_atendio IS NOT NULL
      AND COALESCE(i.resuelta, FALSE) = FALSE
      AND LOWER(COALESCE(i.estado, '')) <> 'atendida'
    ON CONFLICT (incidencia_id) DO NOTHING
");

$result = pg_query($conn, "
    SELECT d.id AS despacho_id,
           d.incidencia_id,
           d.patrulla_id,
           d.assigned_at,
           ST_Y(p.geom::geometry) AS patrulla_lat,
           ST_X(p.geom::geometry) AS patrulla_lng,
           ST_Y(i.geom::geometry) AS destino_lat,
           ST_X(i.geom::geometry) AS destino_lng,
           ST_Distance(p.geom, i.geom) AS distancia_m
    FROM maphub_dispatches d
    JOIN patrullas p ON p.id = d.patrulla_id
    JOIN incidencias i ON i.id = d.incidencia_id
    WHERE LOWER(d.estado) IN ('asignada', 'en camino', 'atendiendo')
      AND LOWER(TRIM(COALESCE(i.estado, ''))) <> 'atendida'
    ORDER BY d.assigned_at
");

if (!$result) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => pg_last_error($conn)]);
    exit;
}

$moved = 0;
$resolved = 0;
$details = [];
$activeDispatches = pg_num_rows($result);

while ($row = pg_fetch_assoc($result)) {
    $dispatchId = intval($row['despacho_id']);
    $incidentId = intval($row['incidencia_id']);
    $patrolId = intval($row['patrulla_id']);
    $distance = floatval($row['distancia_m']);
    $patrolLat = floatval($row['patrulla_lat']);
    $patrolLng = floatval($row['patrulla_lng']);
    $targetLat = floatval($row['destino_lat']);
    $targetLng = floatval($row['destino_lng']);

    pg_query($conn, "BEGIN");

    if ($distance <= 260) {
        $responseSeconds = max(1, time() - strtotime($row['assigned_at']));

        $incidentUpdate = pg_query_params($conn, "
            UPDATE incidencias
            SET resuelta = TRUE,
                estado = 'Atendida',
                patrulla_atendio = $1
            WHERE id = $2
        ", [$patrolId, $incidentId]);

        $patrolUpdate = pg_query_params($conn, "
            UPDATE patrullas
            SET estado = 'Disponible',
                geom = ST_SetSRID(ST_MakePoint($2, $1), 4326)::geography
            WHERE id = $3
        ", [$targetLat, $targetLng, $patrolId]);

        $historyUpdate = pg_query_params($conn, "
            INSERT INTO historial_patrulla(
                patrulla_id, incidencia_id, fecha_resolucion,
                tiempo_respuesta, estado
            )
            SELECT $1, $2, NOW(), $3, 'Resuelta'
            WHERE NOT EXISTS (
                SELECT 1
                FROM historial_patrulla
                WHERE patrulla_id = $1 AND incidencia_id = $2
            )
        ", [$patrolId, $incidentId, $responseSeconds]);

        $dispatchUpdate = pg_query_params($conn, "
            UPDATE maphub_dispatches
            SET estado = 'Atendida', updated_at = NOW(), arrived_at = NOW()
            WHERE id = $1
        ", [$dispatchId]);

        if ($incidentUpdate && $patrolUpdate && $historyUpdate && $dispatchUpdate) {
            pg_query($conn, "COMMIT");
            $resolved++;
            $details[] = [
                'patrulla_id' => $patrolId,
                'incidencia_id' => $incidentId,
                'estado' => 'Atendida',
                'distancia_m' => round($distance, 1)
            ];
            continue;
        }
    } else {
        $progress = 0.42;
        $newLat = $patrolLat + (($targetLat - $patrolLat) * $progress);
        $newLng = $patrolLng + (($targetLng - $patrolLng) * $progress);

        $patrolUpdate = pg_query_params($conn, "
            UPDATE patrullas
            SET estado = 'En camino',
                geom = ST_SetSRID(ST_MakePoint($2, $1), 4326)::geography
            WHERE id = $3
        ", [$newLat, $newLng, $patrolId]);

        $incidentUpdate = pg_query_params($conn, "
            UPDATE incidencias SET estado = 'Asignada' WHERE id = $1
        ", [$incidentId]);

        $dispatchUpdate = pg_query_params($conn, "
            UPDATE maphub_dispatches
            SET estado = 'En camino', updated_at = NOW()
            WHERE id = $1
        ", [$dispatchId]);

        if ($patrolUpdate && $incidentUpdate && $dispatchUpdate) {
            pg_query($conn, "COMMIT");
            $moved++;
            $details[] = [
                'patrulla_id' => $patrolId,
                'incidencia_id' => $incidentId,
                'estado' => 'En camino',
                'distancia_m' => round($distance, 1)
            ];
            continue;
        }
    }

    error_log("MapHub: no fue posible procesar despacho $dispatchId");
    pg_query($conn, "ROLLBACK");
}

pg_query($conn, "
    UPDATE patrullas p
    SET estado = 'Disponible'
    WHERE LOWER(TRIM(p.estado)) = 'en camino'
      AND NOT EXISTS (
          SELECT 1
          FROM maphub_dispatches d
          WHERE d.patrulla_id = p.id
            AND LOWER(d.estado) IN ('asignada', 'en camino', 'atendiendo')
      )
");

if ($moved > 0 || $resolved > 0) {
    maphub_audit($conn, 'procesar_despachos', 'maphub_dispatches', [
        'movimientos' => $moved,
        'atendidas' => $resolved
    ]);
}

echo json_encode([
    'ok' => true,
    'active_dispatches' => $activeDispatches,
    'moved' => $moved,
    'resolved' => $resolved,
    'dispatches' => $details
], JSON_UNESCAPED_UNICODE);
?>
