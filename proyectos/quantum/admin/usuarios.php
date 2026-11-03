<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador']);
$mensaje='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verificar_csrf();
    $accion=$_POST['accion']??'crear';
    if($accion==='crear'){
        $nombre=trim($_POST['nombre']??'');$usuario=trim($_POST['usuario']??'');$clave=$_POST['password']??'';$rol=$_POST['rol']??'Redactor';
        if($nombre===''||$usuario===''||mb_strlen($clave)<10||!in_array($rol,['Administrador','Editor','Redactor'],true)){
            $mensaje='Completa los datos. La contraseña debe tener al menos 10 caracteres.';
        }else{
            $hash=password_hash($clave,PASSWORD_DEFAULT);
            $stmt=$conn->prepare('INSERT INTO usuarios_admin(nombre,usuario,password,rol) VALUES(?,?,?,?)');
            $stmt->bind_param('ssss',$nombre,$usuario,$hash,$rol);
            if($stmt->execute()){$mensaje='Usuario creado.';registrar_bitacora($conn,'usuario_creado','usuario',(int)$conn->insert_id,$usuario.' ['.$rol.']');}
            else{$mensaje='El nombre de usuario ya existe.';}
        }
    }elseif($accion==='eliminar'){
        $id=(int)($_POST['id']??0);
        if($id===(int)$_SESSION['admin_id']){$mensaje='No puedes eliminar tu propia cuenta.';}
        else{$stmt=$conn->prepare('DELETE FROM usuarios_admin WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();registrar_bitacora($conn,'usuario_eliminado','usuario',$id);$mensaje='Usuario eliminado.';}
    }elseif($accion==='clave'){
        $id=(int)($_POST['id']??0);$clave=$_POST['nueva_clave']??'';
        if(mb_strlen($clave)<10){$mensaje='La nueva contraseña debe tener al menos 10 caracteres.';}
        else{$hash=password_hash($clave,PASSWORD_DEFAULT);$stmt=$conn->prepare('UPDATE usuarios_admin SET password=? WHERE id=?');$stmt->bind_param('si',$hash,$id);$stmt->execute();registrar_bitacora($conn,'usuario_clave_actualizada','usuario',$id);$mensaje='Contraseña actualizada.';}
    }
}
$usuarios=$conn->query('SELECT id,nombre,usuario,rol,fecha_creacion FROM usuarios_admin ORDER BY fecha_creacion DESC');
$admin_titulo='Usuarios';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <section class="noticia-detalle"><h1>Crear usuario</h1><?php if($mensaje):?><p class="mensaje-formulario"><?= e($mensaje) ?></p><?php endif;?>
  <form method="POST" class="admin-form-grid"><?= csrf_input() ?><input type="hidden" name="accion" value="crear">
    <label>Nombre<input class="admin-input" name="nombre" maxlength="100" required></label><label>Usuario<input class="admin-input" name="usuario" maxlength="100" required></label>
    <label>Contraseña<input class="admin-input" type="password" name="password" minlength="10" required></label><label>Rol<select class="admin-input" name="rol"><option>Administrador</option><option>Editor</option><option>Redactor</option></select></label>
    <button class="btn">Crear usuario</button>
  </form></section>
  <div class="tabla-admin"><table><thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>
  <?php while($u=$usuarios->fetch_assoc()):?><tr><td><?= e($u['nombre']) ?></td><td><?= e($u['usuario']) ?></td><td><?= e($u['rol']) ?></td><td><?= e($u['fecha_creacion']) ?></td><td class="acciones-tabla">
    <form method="POST" class="form-clave"><?= csrf_input() ?><input type="hidden" name="accion" value="clave"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input class="admin-input" type="password" name="nueva_clave" minlength="10" placeholder="Nueva contraseña" required><button class="btn btn-secundario">Cambiar</button></form>
    <?php if((int)$u['id']!==(int)$_SESSION['admin_id']):?><form method="POST" data-confirmar="¿Eliminar este usuario?"><?= csrf_input() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-danger">Eliminar</button></form><?php endif;?>
  </td></tr><?php endwhile;?>
  </tbody></table></div>
</main>
<?php include 'includes/footer.php'; ?>
