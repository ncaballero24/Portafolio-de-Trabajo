<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('POST, OPTIONS');
maphub_ensure_tables($conn);
maphub_audit($conn, 'logout', 'session');
unset($_SESSION['maphub_user']);
echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
?>
