<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador']);

$pagina = pagina_actual();
$porPagina = 50;
$offset = ($pagina - 1) * $porPagina;
$total = (int) $conn->query('SELECT COUNT(*) total FROM bitacora_admin')->fetch_assoc()['total'];
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$stmt = $conn->prepare('SELECT id, admin_nombre, accion, entidad, entidad_id, detalles, fecha FROM bitacora_admin ORDER BY fecha DESC, id DESC LIMIT ? OFFSET ?');
$stmt->bind_param('ii', $porPagina, $offset);
$stmt->execute();
$registros = $stmt->get_result();
$admin_titulo = 'Bitácora';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <header class="encabezado-listado"><span>Seguridad</span><h1>Bitácora administrativa</h1><p><?= $total ?> acciones registradas. Las direcciones IP se almacenan únicamente como huellas irreversibles.</p></header>
  <div class="tabla-admin"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>Detalle</th></tr></thead><tbody>
  <?php while ($row = $registros->fetch_assoc()): ?><tr><td><?= e($row['fecha']) ?></td><td><?= e($row['admin_nombre']) ?></td><td><?= e($row['accion']) ?></td><td><?= e($row['entidad']) ?><?= $row['entidad_id'] !== null ? ' #' . (int) $row['entidad_id'] : '' ?></td><td><?= e($row['detalles']) ?></td></tr><?php endwhile; ?>
  </tbody></table></div>
  <?= render_paginacion($pagina, $totalPaginas) ?>
</main>
<?php include 'includes/footer.php'; ?>
