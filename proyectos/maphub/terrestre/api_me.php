<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('GET, OPTIONS');
maphub_ensure_tables($conn);

echo json_encode([
    'ok' => true,
    'demo' => getenv('MAPHUB_DEMO') === '1',
    'user' => $_SESSION['maphub_user'] ?? null,
    'mode' => isset($_SESSION['maphub_user']) ? 'authenticated' : 'consulta'
], JSON_UNESCAPED_UNICODE);
?>
