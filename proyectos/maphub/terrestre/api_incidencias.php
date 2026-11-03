<?php
require 'db.php'; require 'auth.php'; require 'incidencias_consulta.php';
maphub_json_headers('GET, OPTIONS');
try { $data=maphub_consultar_incidencias($conn); echo json_encode(['ok'=>true,'count'=>count($data),'data'=>$data],JSON_UNESCAPED_UNICODE); }
catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'No se pudieron consultar las incidencias']); }
