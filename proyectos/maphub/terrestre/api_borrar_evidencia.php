<?php
require 'auth.php';
maphub_json_headers('POST, OPTIONS');
maphub_require_role('operador');
require 'db.php';
maphub_ensure_tables($conn);
$data = json_decode(file_get_contents('php://input'), true);
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Incidencia inválida']); exit; }
// Desvincular conserva el archivo para recuperación; nunca borrar una ruta de la BD.
$result = pg_query_params($conn, 'UPDATE incidencias SET evidencia_imagen=NULL WHERE id=$1 RETURNING id', [$id]);
if (!$result || !pg_num_rows($result)) { http_response_code($result ? 404 : 500); echo json_encode(['success'=>false,'error'=>'No se pudo retirar la evidencia']); exit; }
maphub_audit($conn, 'retirar_evidencia', 'incidencias', ['id'=>$id]);
echo json_encode(['success'=>true]);
