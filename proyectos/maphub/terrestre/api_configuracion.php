<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('GET, POST, OPTIONS');
maphub_ensure_tables($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = maphub_require_role('administrador');
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $keep = max(50, min(intval($data['keep_incidencias'] ?? 180), 500));
    maphub_set_setting($conn, 'keep_incidencias', $keep);
    maphub_audit($conn, 'actualizar_configuracion', 'maphub_settings', ['keep_incidencias' => $keep]);
    echo json_encode(['ok' => true, 'keep_incidencias' => $keep, 'user' => $user], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'keep_incidencias' => intval(maphub_get_setting($conn, 'keep_incidencias', 180))
], JSON_UNESCAPED_UNICODE);
?>
