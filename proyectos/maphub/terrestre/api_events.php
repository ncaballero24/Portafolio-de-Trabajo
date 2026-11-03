<?php
require 'db.php';
require 'auth.php';
maphub_ensure_tables($conn);
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');

$lastId = null;
for ($i = 0; $i < 12; $i++) {
    $result = pg_query($conn, "SELECT COUNT(*) AS total, MAX(id) AS last_id, MAX(timestamp) AS last_timestamp FROM incidencias");
    $row = $result ? pg_fetch_assoc($result) : ['total' => 0, 'last_id' => null, 'last_timestamp' => null];
    $currentId = $row['last_id'] ?? null;
    echo "event: maphub\n";
    echo "data: " . json_encode([
        'total' => intval($row['total'] ?? 0),
        'last_id' => $currentId,
        'last_timestamp' => $row['last_timestamp'] ?? null,
        'changed' => $lastId !== null && $currentId !== $lastId
    ], JSON_UNESCAPED_UNICODE) . "\n\n";
    @ob_flush();
    flush();
    $lastId = $currentId;
    if ($i < 11) sleep(5);
}
?>
