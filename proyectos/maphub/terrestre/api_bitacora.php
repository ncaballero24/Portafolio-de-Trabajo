<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('GET, OPTIONS');
maphub_ensure_tables($conn);
maphub_require_role('administrador');

$limit = max(20, min(intval($_GET['limit'] ?? 80), 200));
$result = pg_query_params($conn, "
    SELECT id, username, role, action, target, details::text AS details, created_at
    FROM maphub_audit
    ORDER BY created_at DESC
    LIMIT $1
", [$limit]);

$data = [];
while ($result && ($row = pg_fetch_assoc($result))) {
    $row['details'] = $row['details'] ? json_decode($row['details'], true) : null;
    $data[] = $row;
}

echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
?>
