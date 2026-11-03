<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador', 'Editor']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido.'); }
verificar_csrf();
$id = (int) ($_POST['id'] ?? 0);
$stmt = $conn->prepare('SELECT imagen FROM galeria WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$imagen = $stmt->get_result()->fetch_assoc();
$stmt = $conn->prepare('DELETE FROM galeria WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
eliminar_archivo_local($imagen['imagen'] ?? null);
header('Location: galeria_admin.php');
exit;
