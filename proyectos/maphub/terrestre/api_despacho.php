<?php
require 'db.php'; require 'auth.php';
maphub_json_headers('POST, OPTIONS'); maphub_ensure_tables($conn); maphub_require_role('operador');
$d=json_decode(file_get_contents('php://input'),true) ?: [];
$id=filter_var($d['incidencia_id']??null,FILTER_VALIDATE_INT);
if (!$id) {http_response_code(400);echo json_encode(['success'=>false,'error'=>'Incidencia inválida']);exit;}
pg_query($conn,'BEGIN');
$i=pg_query_params($conn,'SELECT * FROM incidencias WHERE id=$1 FOR UPDATE',[$id]);$incident=$i?pg_fetch_assoc($i):null;
if (!$incident || $incident['resuelta']==='t' || $incident['patrulla_atendio']) {pg_query($conn,'ROLLBACK');http_response_code(409);echo json_encode(['success'=>false,'error'=>'La incidencia ya fue asignada, atendida o no existe.']);exit;}
$p=pg_query_params($conn,"SELECT p.id FROM patrullas p, incidencias i WHERE i.id=$1 AND LOWER(p.estado) IN ('disponible','patrullando') ORDER BY ST_Distance(p.geom,i.geom) LIMIT 1 FOR UPDATE OF p SKIP LOCKED",[$id]);$patrol=$p?pg_fetch_assoc($p):null;
if (!$patrol) {pg_query($conn,'ROLLBACK');http_response_code(409);echo json_encode(['success'=>false,'error'=>'No hay patrullas disponibles.']);exit;}
$pid=$patrol['id'];
$a=pg_query_params($conn,"UPDATE patrullas SET estado='En camino' WHERE id=$1",[$pid]);
$b=pg_query_params($conn,"UPDATE incidencias SET patrulla_atendio=$1,estado='Asignada' WHERE id=$2",[$pid,$id]);
$c=pg_query_params($conn,"INSERT INTO maphub_dispatches(incidencia_id,patrulla_id,estado) VALUES($1,$2,'En camino')",[$id,$pid]);
if (!$a||!$b||!$c) {pg_query($conn,'ROLLBACK');http_response_code(500);echo json_encode(['success'=>false,'error'=>'No se pudo registrar la asignación']);exit;}
pg_query($conn,'COMMIT');maphub_audit($conn,'asignar_patrulla','incidencias',['incidencia_id'=>$id,'patrulla_id'=>$pid]);
echo json_encode(['success'=>true,'patrulla_id'=>$pid,'incidencia_id'=>$id]);
