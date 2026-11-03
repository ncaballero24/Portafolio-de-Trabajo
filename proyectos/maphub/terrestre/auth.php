<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax']);
}

function maphub_json_headers($methods = 'GET, POST, OPTIONS') {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, array_map('trim', explode(',', $methods)), true)) {
        http_response_code(405); header('Content-Type: application/json');
        echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit;
    }
    if ($method === 'POST') {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($origin && parse_url($origin, PHP_URL_HOST) . (parse_url($origin, PHP_URL_PORT) ? ':' . parse_url($origin, PHP_URL_PORT) : '') !== $host) {
            http_response_code(403); header('Content-Type: application/json');
            echo json_encode(['ok'=>false,'error'=>'Origen no permitido']); exit;
        }
        if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') { http_response_code(403); exit; }
    }
    header('Cache-Control: no-store');
    header("Access-Control-Allow-Methods: $methods");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=utf-8");
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function maphub_ensure_tables($conn) {
    $readyResult = pg_query($conn, "
        SELECT to_regclass('public.maphub_settings') IS NOT NULL
           AND to_regclass('public.maphub_audit') IS NOT NULL
           AND to_regclass('public.maphub_dispatches') IS NOT NULL AS ready
    ");
    $readyRow = $readyResult ? pg_fetch_assoc($readyResult) : null;
    if (($readyRow['ready'] ?? 'f') === 't') {
        return;
    }

    pg_query($conn, "SELECT pg_advisory_lock(hashtext('maphub_schema_v2'))");

    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS maphub_settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS maphub_audit (
            id SERIAL PRIMARY KEY,
            username TEXT,
            role TEXT,
            action TEXT NOT NULL,
            target TEXT,
            details JSONB,
            created_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS maphub_dispatches (
            id BIGSERIAL PRIMARY KEY,
            incidencia_id BIGINT NOT NULL UNIQUE,
            patrulla_id BIGINT NOT NULL,
            estado TEXT NOT NULL DEFAULT 'Asignada',
            assigned_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            arrived_at TIMESTAMPTZ
        )
    ");

    pg_query($conn, "
        CREATE INDEX IF NOT EXISTS idx_maphub_dispatches_active
        ON maphub_dispatches(patrulla_id, estado)
    ");

    pg_query($conn, "
        INSERT INTO maphub_settings(key, value)
        VALUES ('keep_incidencias', '180')
        ON CONFLICT (key) DO NOTHING
    ");

    pg_query($conn, "SELECT pg_advisory_unlock(hashtext('maphub_schema_v2'))");
}

function maphub_current_user() {
    return $_SESSION['maphub_user'] ?? [
        'username' => 'consulta',
        'role' => 'consulta',
        'name' => 'Consulta'
    ];
}

function maphub_role_rank($role) {
    $ranks = ['consulta' => 1, 'operador' => 2, 'administrador' => 3];
    return $ranks[$role] ?? 0;
}

function maphub_require_role($role) {
    $user = maphub_current_user();
    if (maphub_role_rank($user['role'] ?? '') < maphub_role_rank($role)) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'error' => 'Permisos insuficientes',
            'required_role' => $role,
            'current_role' => $user['role'] ?? 'sin_sesion'
        ]);
        exit;
    }
    return $user;
}

function maphub_audit($conn, $action, $target = null, $details = []) {
    $user = maphub_current_user();
    pg_query_params($conn, "
        INSERT INTO maphub_audit(username, role, action, target, details)
        VALUES($1, $2, $3, $4, $5::jsonb)
    ", [
        $user['username'] ?? 'consulta',
        $user['role'] ?? 'consulta',
        $action,
        $target,
        json_encode($details, JSON_UNESCAPED_UNICODE)
    ]);
}

function maphub_get_setting($conn, $key, $default = null) {
    $result = pg_query_params($conn, "SELECT value FROM maphub_settings WHERE key = $1", [$key]);
    if ($result && ($row = pg_fetch_assoc($result))) {
        return $row['value'];
    }
    return $default;
}

function maphub_set_setting($conn, $key, $value) {
    return pg_query_params($conn, "
        INSERT INTO maphub_settings(key, value, updated_at)
        VALUES($1, $2, NOW())
        ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = NOW()
    ", [$key, (string) $value]);
}
?>
