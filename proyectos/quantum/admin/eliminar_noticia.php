<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
verificar_csrf();
$id = (int) ($_POST['id'] ?? 0);
$stmt = $conn->prepare('SELECT titulo, imagen, video FROM noticias WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$noticia = $stmt->get_result()->fetch_assoc();
$medios = medios_noticia($conn, $id);
$stmt = $conn->prepare('DELETE FROM noticias WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
eliminar_archivo_local($noticia['imagen'] ?? null);
eliminar_archivo_local($noticia['video'] ?? null);
foreach ($medios as $medio) {
    eliminar_archivo_local($medio['archivo'] ?? null);
}
registrar_bitacora($conn, 'noticia_eliminada', 'noticia', $id, (string) ($noticia['titulo'] ?? ''));
header('Location: noticias.php?eliminada=1');
exit;
