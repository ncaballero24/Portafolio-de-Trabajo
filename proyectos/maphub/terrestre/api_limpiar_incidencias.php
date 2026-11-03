<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('POST, OPTIONS');
maphub_ensure_tables($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "Método no permitido"], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = maphub_require_role('administrador');
$configuredKeep = intval(maphub_get_setting($conn, 'keep_incidencias', 180));
$keep = isset($_GET['keep']) ? intval($_GET['keep']) : $configuredKeep;
$keep = max(50, min($keep, 500));

$sql = "
WITH candidatas AS (
    SELECT i.id
    FROM incidencias i
    WHERE i.id NOT IN (
        SELECT id
        FROM incidencias
        ORDER BY timestamp DESC
        LIMIT $keep
    )
    AND NOT EXISTS (
        SELECT 1
        FROM historial_patrulla h
        WHERE h.incidencia_id = i.id
    )
    ORDER BY i.timestamp ASC
)
DELETE FROM incidencias i
USING candidatas c
WHERE i.id = c.id
";

$result = pg_query($conn, $sql);

if (!$result) {
    echo json_encode(["ok" => false, "error" => pg_last_error($conn)], JSON_UNESCAPED_UNICODE);
    exit;
}

$deleted = pg_affected_rows($result);
maphub_audit($conn, 'limpiar_incidencias', 'incidencias', ['deleted' => $deleted, 'keep' => $keep, 'user' => $user['username']]);

echo json_encode([
    "ok" => true,
    "deleted" => $deleted,
    "keep" => $keep
], JSON_UNESCAPED_UNICODE);
?>
