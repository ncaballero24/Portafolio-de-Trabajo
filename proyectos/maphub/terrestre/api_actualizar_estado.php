<?php
require 'db.php';
require 'auth.php';
maphub_json_headers('POST, OPTIONS');
maphub_ensure_tables($conn);
maphub_require_role('operador');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false]);
    exit;
}

$id = intval($data['id'] ?? 0);
$estado = trim((string) ($data['estado'] ?? ''));

$allowedStates = ['Disponible', 'Patrullando', 'En camino', 'Fuera de servicio'];
if ($id <= 0 || !in_array($estado, $allowedStates, true)) {
    echo json_encode([
        "success" => false,
        "error" => "Estado no válido"
    ]);
    exit;
}

$sql = "
UPDATE patrullas
SET estado = $1
WHERE id = $2 AND NOT EXISTS (
 SELECT 1 FROM maphub_dispatches d WHERE d.patrulla_id=$2 AND LOWER(d.estado) IN ('asignada','en camino','atendiendo')
)
";

$result = pg_query_params($conn, $sql, [$estado, $id]);

if (!$result || pg_affected_rows($result)!==1) {
    echo json_encode([
        "success" => false,
        "error" => 'No se puede cambiar el estado de una unidad asignada o inexistente'
    ]);
    exit;
}

maphub_audit($conn, 'actualizar_estado_patrulla', 'patrullas', [
    'patrulla_id' => $id,
    'estado' => $estado
]);

echo json_encode(["success" => true]);
?>
