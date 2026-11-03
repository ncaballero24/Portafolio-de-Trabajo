<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador', 'Editor']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido.'); }
verificar_csrf();
$id = (int) ($_POST['id'] ?? 0);
$stmt = $conn->prepare('DELETE FROM suscriptores WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
header('Location: suscriptores.php');
exit;
