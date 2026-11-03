<?php
require 'db.php'; require 'auth.php';
maphub_json_headers('POST, OPTIONS');maphub_ensure_tables($conn);maphub_require_role('operador');
$d=json_decode(file_get_contents('php://input'),true)?:[];$id=filter_var($d['id']??null,FILTER_VALIDATE_INT);
if (!$id) {http_response_code(400);echo json_encode(['success'=>false,'error'=>'Incidencia inválida']);exit;}
pg_query($conn,'BEGIN');$r=pg_query_params($conn,'SELECT * FROM incidencias WHERE id=$1 FOR UPDATE',[$id]);$i=$r?pg_fetch_assoc($r):null;
if (!$i || !$i['patrulla_atendio'] || $i['resuelta']==='t') {pg_query($conn,'ROLLBACK');http_response_code(409);echo json_encode(['success'=>false,'error'=>'La incidencia debe estar asignada y pendiente de resolución.']);exit;}
$pid=$i['patrulla_atendio'];
$a=pg_query_params($conn,"UPDATE incidencias SET resuelta=TRUE,estado='Atendida' WHERE id=$1",[$id]);
$b=pg_query_params($conn,"INSERT INTO historial_patrulla(patrulla_id,incidencia_id,tiempo_respuesta,estado) SELECT $1,$2,GREATEST(0,EXTRACT(EPOCH FROM NOW()-assigned_at)::int),'Resuelta' FROM maphub_dispatches WHERE incidencia_id=$2 ON CONFLICT DO NOTHING",[$pid,$id]);
$c=pg_query_params($conn,"UPDATE patrullas SET estado='Disponible' WHERE id=$1",[$pid]);
$e=pg_query_params($conn,"UPDATE maphub_dispatches SET estado='Atendida',arrived_at=NOW(),updated_at=NOW() WHERE incidencia_id=$1",[$id]);
if (!$a||!$b||!$c||!$e) {pg_query($conn,'ROLLBACK');http_response_code(500);echo json_encode(['success'=>false,'error'=>'No se pudo completar la atención']);exit;}
pg_query($conn,'COMMIT');maphub_audit($conn,'resolver_incidencia','incidencias',['id'=>$id]);echo json_encode(['success'=>true]);
