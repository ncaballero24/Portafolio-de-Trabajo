<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../terrestre/db.php';
$username=getenv('MAPHUB_ADMIN_USER'); $password=getenv('MAPHUB_ADMIN_PASSWORD');
if (!$username || strlen($password ?: '')<12) { fwrite(STDERR,"Define usuario y contraseña de al menos 12 caracteres en el entorno.\n"); exit(1); }
$r=pg_query_params($conn,'INSERT INTO usuarios(username,password,nombre,role) VALUES($1,$2,$3,$4)',[$username,password_hash($password,PASSWORD_DEFAULT),'Administrador','administrador']);
if (!$r) exit(1);
echo "Cuenta creada.\n";
