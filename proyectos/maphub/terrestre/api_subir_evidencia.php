<?php
require 'db.php';require 'auth.php';maphub_json_headers('POST, OPTIONS');maphub_ensure_tables($conn);maphub_require_role('operador');
$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$file=$_FILES['imagen']??null;
if (!$id || !$file || $file['error']!==UPLOAD_ERR_OK || $file['size']>5*1024*1024) {http_response_code(400);echo json_encode(['success'=>false,'error'=>'Adjunta una imagen de hasta 5 MB.']);exit;}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime]??null;
$r=pg_query_params($conn,'SELECT id FROM incidencias WHERE id=$1',[$id]);
if (!$ext || !getimagesize($file['tmp_name']) || !$r || !pg_num_rows($r)) {http_response_code(400);echo json_encode(['success'=>false,'error'=>'Imagen o incidencia inválida.']);exit;}
$dir=__DIR__.'/evidencias';if (!is_dir($dir)) mkdir($dir,0755,true);
$relative='evidencias/'.bin2hex(random_bytes(16)).'.'.$ext;
if (!move_uploaded_file($file['tmp_name'],__DIR__.'/'.$relative)) {http_response_code(500);echo json_encode(['success'=>false,'error'=>'No se pudo guardar la imagen']);exit;}
$r=pg_query_params($conn,'UPDATE incidencias SET evidencia_imagen=$1 WHERE id=$2',[$relative,$id]);
maphub_audit($conn,'adjuntar_evidencia','incidencias',['id'=>$id]);echo json_encode(['success'=>(bool)$r,'ruta'=>$relative]);
