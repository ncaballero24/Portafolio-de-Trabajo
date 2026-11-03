<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador', 'Editor']);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=suscriptores-' . date('Y-m-d') . '.csv');
$output=fopen('php://output','w');
fputs($output,"\xEF\xBB\xBF");
fputcsv($output,['Correo','Estado','Fecha de suscripción']);
$res=$conn->query('SELECT correo,activo,fecha FROM suscriptores ORDER BY fecha DESC');
while($row=$res->fetch_assoc()){fputcsv($output,[$row['correo'],$row['activo']?'Activo':'Baja',$row['fecha']]);}
fclose($output);
exit;
