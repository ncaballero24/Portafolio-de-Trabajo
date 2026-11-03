<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador', 'Editor']);
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['accion'] ?? '')==='alternar') {
    verificar_csrf();
    $id=(int)($_POST['id']??0);
    $stmt=$conn->prepare('UPDATE suscriptores SET activo=1-activo WHERE id=?');
    $stmt->bind_param('i',$id);
    $stmt->execute();
}
$q=trim($_GET['q']??'');
$qLike='%'.$q.'%';
$pagina=pagina_actual();
$porPagina=30;
$stmt=$conn->prepare("SELECT COUNT(*) total FROM suscriptores WHERE (?='' OR correo LIKE ?)");
$stmt->bind_param('ss',$q,$qLike);$stmt->execute();$total=(int)$stmt->get_result()->fetch_assoc()['total'];
$offset=($pagina-1)*$porPagina;
$stmt=$conn->prepare("SELECT * FROM suscriptores WHERE (?='' OR correo LIKE ?) ORDER BY fecha DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ssii',$q,$qLike,$porPagina,$offset);$stmt->execute();$suscriptores=$stmt->get_result();
$activos=(int)$conn->query('SELECT COUNT(*) total FROM suscriptores WHERE activo=1')->fetch_assoc()['total'];
$admin_titulo='Suscriptores';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <header class="encabezado-listado"><span>Audiencia</span><h1>Suscriptores</h1><p><?= $activos ?> activos de <?= $total ?> mostrados</p><a class="btn" href="exportar_suscriptores.php">Exportar CSV</a></header>
  <form class="filtros-admin" method="GET"><input class="admin-input" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar correo"><button class="btn">Buscar</button></form>
  <div class="tabla-admin"><table><thead><tr><th>Correo</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>
  <?php while($s=$suscriptores->fetch_assoc()): ?><tr><td><?= e($s['correo']) ?></td><td><?= $s['activo']?'Activo':'Dado de baja' ?></td><td><?= e($s['fecha']) ?></td><td class="acciones-tabla">
    <form method="POST"><?= csrf_input() ?><input type="hidden" name="accion" value="alternar"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-secundario"><?= $s['activo']?'Desactivar':'Reactivar' ?></button></form>
    <form method="POST" action="eliminar_suscriptor.php" data-confirmar="¿Eliminar definitivamente este suscriptor?"><?= csrf_input() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-danger">Eliminar</button></form>
  </td></tr><?php endwhile; ?>
  </tbody></table></div>
  <?= render_paginacion($pagina,max(1,(int)ceil($total/$porPagina))) ?>
</main>
<?php include 'includes/footer.php'; ?>
