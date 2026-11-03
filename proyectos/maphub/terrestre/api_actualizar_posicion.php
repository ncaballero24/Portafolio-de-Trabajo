<?php
require 'db.php'; require 'auth.php';
maphub_json_headers('POST, OPTIONS'); maphub_require_role('operador');
$d=json_decode(file_get_contents('php://input'),true) ?: [];
$id=filter_var($d['id']??null,FILTER_VALIDATE_INT); $lat=filter_var($d['lat']??null,FILTER_VALIDATE_FLOAT); $lng=filter_var($d['lng']??null,FILTER_VALIDATE_FLOAT);
if (!$id || $lat===false || $lng===false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {http_response_code(400); echo json_encode(['success'=>false,'error'=>'Coordenadas inválidas']);exit;}
$r=pg_query_params($conn,'UPDATE patrullas SET geom=ST_SetSRID(ST_MakePoint($1,$2),4326)::geography WHERE id=$3',[$lng,$lat,$id]);
echo json_encode(['success'=>$r && pg_affected_rows($r)===1]);
