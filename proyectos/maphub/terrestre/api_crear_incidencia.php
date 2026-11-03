<?php
require 'db.php'; require 'auth.php';
maphub_json_headers('POST, OPTIONS'); maphub_ensure_tables($conn); maphub_require_role('operador');
$d=json_decode(file_get_contents('php://input'),true) ?: [];
$tipo=trim($d['tipo']??''); $descripcion=trim($d['descripcion']??'');
$lat=filter_var($d['lat']??null,FILTER_VALIDATE_FLOAT);$lng=filter_var($d['lng']??null,FILTER_VALIDATE_FLOAT);$sev=filter_var($d['severidad']??null,FILTER_VALIDATE_INT);
if (!$tipo || strlen($tipo)>100 || strlen($descripcion)<10 || strlen($descripcion)>2000 || !$sev || $sev<1 || $sev>5 || $lat===false || $lng===false || abs($lat)>90 || abs($lng)>180) {http_response_code(400);echo json_encode(['ok'=>false,'error'=>'Revisa tipo, descripción, severidad y coordenadas.']);exit;}
$zona=($lat>=19.145?'norte':'sur').'-'.($lng>=-96.16?'oriente':'poniente');
$r=pg_query_params($conn,'INSERT INTO incidencias(tipo,descripcion,severidad,zona,geom) VALUES($1,$2,$3,$4,ST_SetSRID(ST_MakePoint($5,$6),4326)::geography) RETURNING id',[$tipo,$descripcion,$sev,$zona,$lng,$lat]);
if (!$r) {http_response_code(500);echo json_encode(['ok'=>false,'error'=>'No se pudo guardar']);exit;}
$id=pg_fetch_result($r,0,0); maphub_audit($conn,'crear_incidencia','incidencias',['id'=>$id]);echo json_encode(['ok'=>true,'id'=>$id]);
