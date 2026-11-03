<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este proceso sólo puede ejecutarse desde la línea de comandos.');
}
require_once __DIR__ . '/../conexion.php';
$stmt = $conn->prepare("UPDATE noticias SET estado='publicada', actualizado_en=NOW() WHERE estado='programada' AND fecha_programada IS NOT NULL AND fecha_programada<=NOW()");
$stmt->execute();
echo '[' . date('Y-m-d H:i:s') . '] Publicadas: ' . $stmt->affected_rows . PHP_EOL;
