<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('POST, OPTIONS');
maphub_ensure_tables($conn);

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = json_decode(stripslashes($raw), true);
}
if (!is_array($data)) {
    parse_str($raw, $data);
}
if (!is_array($data)) {
    $data = [];
}
$username = trim((string) ($data['username'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "message" => "Usuario y contraseña son obligatorios"], JSON_UNESCAPED_UNICODE);
    exit;
}

$userData = null;
$result = @pg_query_params($conn, "SELECT * FROM usuarios WHERE username = $1 LIMIT 1", [$username]);
if ($result && ($row = pg_fetch_assoc($result))) {
    $stored = (string) ($row['password'] ?? '');
    $valid = password_verify($password, $stored);
    if ($valid) {
        $role = $row['role'] ?? $row['rol'] ?? ($username === 'admin' ? 'administrador' : 'operador');
        $userData = [
            'username' => $username,
            'role' => in_array($role, ['administrador', 'operador', 'consulta'], true) ? $role : 'consulta',
            'name' => $row['nombre'] ?? $row['name'] ?? $username
        ];
    }
}

if ($userData) {
    session_regenerate_id(true);
    $_SESSION['maphub_user'] = $userData;
    maphub_audit($conn, 'login', 'session', ['username' => $username, 'role' => $userData['role']]);
    echo json_encode(["ok" => true, "user" => $userData], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(401);
echo json_encode(["ok" => false, "message" => "Credenciales incorrectas"], JSON_UNESCAPED_UNICODE);
?>
