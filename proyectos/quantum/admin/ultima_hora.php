<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador','Editor']);
$mensaje='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verificar_csrf();
    $accion=$_POST['accion']??'crear';
    if($accion==='desactivar'){
        $id=(int)($_POST['id']??0);$stmt=$conn->prepare('UPDATE ultima_hora SET activo=0 WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$mensaje='Alerta desactivada.';
    }else{
        $texto=trim($_POST['mensaje']??'');$tipo=$_POST['tipo']??'alerta';
        $inicio=!empty($_POST['fecha_inicio'])?str_replace('T',' ',$_POST['fecha_inicio']):null;
        $fin=!empty($_POST['fecha_fin'])?str_replace('T',' ',$_POST['fecha_fin']):null;
        if($texto===''||mb_strlen($texto)>255||!in_array($tipo,['alerta','aviso','informativo'],true)||($inicio&&$fin&&strtotime($fin)<=strtotime($inicio))){
            $mensaje='Revisa el mensaje, el tipo y el periodo de vigencia.';
        }else{
            $conn->begin_transaction();
            try{$conn->query('UPDATE ultima_hora SET activo=0');$stmt=$conn->prepare('INSERT INTO ultima_hora(mensaje,tipo,fecha_inicio,fecha_fin,activo) VALUES(?,?,?,?,1)');$stmt->bind_param('ssss',$texto,$tipo,$inicio,$fin);$stmt->execute();$conn->commit();$mensaje='Última hora actualizada.';}catch(Throwable $e){$conn->rollback();$mensaje='No fue posible guardar la alerta.';}
        }
    }
}
$actuales=$conn->query('SELECT * FROM ultima_hora ORDER BY fecha DESC LIMIT 20');
$admin_titulo='Última hora';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <section class="noticia-detalle"><h1>Nueva alerta</h1><?php if($mensaje):?><p class="mensaje-formulario"><?= e($mensaje) ?></p><?php endif;?>
  <form method="POST" class="admin-form-grid"><?= csrf_input() ?><input type="hidden" name="accion" value="crear">
    <label>Tipo<select class="admin-input" name="tipo"><option value="alerta">Alerta</option><option value="aviso">Aviso</option><option value="informativo">Informativo</option></select></label>
    <label>Inicio<input class="admin-input" type="datetime-local" name="fecha_inicio"></label><label>Fin<input class="admin-input" type="datetime-local" name="fecha_fin"></label>
    <label class="admin-form-full">Mensaje<textarea class="admin-input" name="mensaje" maxlength="255" rows="4" required></textarea></label><button class="btn">Publicar alerta</button>
  </form></section>
  <div class="tabla-admin"><table><thead><tr><th>Mensaje</th><th>Tipo</th><th>Estado</th><th>Vigencia</th><th>Fecha</th><th></th></tr></thead><tbody>
  <?php while($row=$actuales->fetch_assoc()):?><tr><td><?= e($row['mensaje']) ?></td><td><?= e($row['tipo']) ?></td><td><?= $row['activo']?'Activa':'Inactiva' ?></td><td><?= e($row['fecha_inicio']?:'Inmediata') ?> – <?= e($row['fecha_fin']?:'Sin fin') ?></td><td><?= e($row['fecha']) ?></td><td><?php if($row['activo']):?><form method="POST"><?= csrf_input() ?><input type="hidden" name="accion" value="desactivar"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-secundario">Desactivar</button></form><?php endif;?></td></tr><?php endwhile;?>
  </tbody></table></div>
</main>
<?php include 'includes/footer.php'; ?>
