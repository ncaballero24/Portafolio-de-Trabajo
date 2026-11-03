<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';
require_once 'includes/simposium.php';
try { $simposium=simposium_cargar($conn)['datos']; }
catch (Throwable $e) {
    error_log('SIMPOSIUM lectura: '.$e->getMessage());
    http_response_code(503);
    exit('El programa no está disponible temporalmente. Inténtalo más tarde.');
}
$jornadas=$simposium['jornadas'];
$dia=null;
$noExiste=false;
if (array_key_exists('dia',$_GET)) {
    $valor=$_GET['dia'];
    if (!is_string($valor) || !preg_match('/^[1-9][0-9]?$/D',$valor) || (int)$valor>count($jornadas)) {
        $noExiste=true;http_response_code(404);
    } else { $dia=(int)$valor; }
}
$page_title=($noExiste?'Jornada no encontrada':($dia?'Día '.$dia.' · '.$jornadas[$dia-1]['titulo']:$simposium['titulo'].' '.$simposium['anio'])).' | Quántum ININDETEC';
$page_description=$dia?$jornadas[$dia-1]['descripcion']:$simposium['descripcion'];
$canonical=url_base().'/simposium.php'.($dia?'?dia='.$dia:'');
$body_class='quantum-simposium-page';
$extra_head='<link rel="stylesheet" href="'.e(asset_url('css/simposium-evento.css')).'">';
include 'includes/header.php';
include 'includes/simposium-vista.php';
include 'includes/footer.php';
