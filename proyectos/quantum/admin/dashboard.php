<?php
require_once 'includes/inicio.php';
$stats=[
  'Noticias'=>(int)$conn->query('SELECT COUNT(*) total FROM noticias')->fetch_assoc()['total'],
  'Publicadas'=>(int)$conn->query("SELECT COUNT(*) total FROM noticias WHERE estado='publicada'")->fetch_assoc()['total'],
  'Borradores'=>(int)$conn->query("SELECT COUNT(*) total FROM noticias WHERE estado='borrador'")->fetch_assoc()['total'],
  'Programadas'=>(int)$conn->query("SELECT COUNT(*) total FROM noticias WHERE estado='programada'")->fetch_assoc()['total'],
  'Suscriptores'=>(int)$conn->query('SELECT COUNT(*) total FROM suscriptores WHERE activo=1')->fetch_assoc()['total'],
  'Comentarios pendientes'=>(int)$conn->query("SELECT COUNT(*) total FROM comentarios WHERE estado='pendiente'")->fetch_assoc()['total'],
  'Imágenes'=>(int)$conn->query('SELECT COUNT(*) total FROM galeria')->fetch_assoc()['total'],
  'Lecturas'=>(int)($conn->query('SELECT SUM(visitas) total FROM noticias')->fetch_assoc()['total']??0),
];
$top=$conn->query("SELECT id,titulo,slug,visitas,categoria FROM noticias WHERE estado='publicada' ORDER BY visitas DESC LIMIT 5");
$recientes=$conn->query('SELECT id,titulo,estado,categoria,fecha FROM noticias ORDER BY fecha DESC LIMIT 8');
$programadas=$conn->query("SELECT id,titulo,fecha_programada FROM noticias WHERE estado='programada' ORDER BY fecha_programada LIMIT 6");
$categorias=$conn->query("SELECT categoria,SUM(visitas) total FROM noticias GROUP BY categoria ORDER BY total DESC");
$labels=[];$datos=[];while($r=$categorias->fetch_assoc()){$labels[]=$r['categoria'];$datos[]=(int)$r['total'];}
$visitasDias=$conn->query("SELECT dia,COUNT(*) total FROM visitas_noticias WHERE dia>=DATE_SUB(CURDATE(),INTERVAL 29 DAY) GROUP BY dia ORDER BY dia");
$dias=[];$visitas=[];while($r=$visitasDias->fetch_assoc()){$dias[]=$r['dia'];$visitas[]=(int)$r['total'];}
$admin_titulo='Dashboard';
$admin_extra_head='<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <header class="encabezado-listado"><span>Resumen operativo</span><h1>Dashboard editorial</h1><p>Actividad y rendimiento del portal</p></header>
  <section class="stats-grid"><?php foreach($stats as $titulo=>$valor):?><a class="stat-card" href="<?= $titulo==='Comentarios pendientes'?'comentarios.php?estado=pendiente':($titulo==='Suscriptores'?'suscriptores.php':'noticias.php') ?>"><h2><?= e($titulo) ?></h2><p><?= number_format($valor) ?></p></a><?php endforeach;?></section>
  <section class="charts-grid">
    <article class="chart-card"><h2>Lecturas por categoría</h2><canvas id="graficaCategorias"></canvas></article>
    <article class="chart-card"><h2>Visitantes únicos, últimos 30 días</h2><canvas id="graficaVisitas"></canvas></article>
  </section>
  <div class="dashboard-dos-columnas">
    <section><h2 class="seccion-titulo">Más leídas</h2><div class="tabla-admin"><table><thead><tr><th>Noticia</th><th>Categoría</th><th>Lecturas</th></tr></thead><tbody><?php while($r=$top->fetch_assoc()):?><tr><td><a href="../<?= e(url_noticia($r)) ?>"><?= e($r['titulo']) ?></a></td><td><?= e($r['categoria']) ?></td><td><?= (int)$r['visitas'] ?></td></tr><?php endwhile;?></tbody></table></div></section>
    <section><h2 class="seccion-titulo">Próximas publicaciones</h2><div class="tabla-admin"><table><thead><tr><th>Noticia</th><th>Programada</th></tr></thead><tbody><?php while($r=$programadas->fetch_assoc()):?><tr><td><a href="editar_noticia.php?id=<?= (int)$r['id'] ?>"><?= e($r['titulo']) ?></a></td><td><?= e($r['fecha_programada']) ?></td></tr><?php endwhile;?></tbody></table></div></section>
  </div>
  <h2 class="seccion-titulo">Actividad reciente</h2>
  <div class="tabla-admin"><table><thead><tr><th>Título</th><th>Categoría</th><th>Estado</th><th>Fecha</th></tr></thead><tbody><?php while($r=$recientes->fetch_assoc()):?><tr><td><a href="editar_noticia.php?id=<?= (int)$r['id'] ?>"><?= e($r['titulo']) ?></a></td><td><?= e($r['categoria']) ?></td><td><span class="estado-newsletter <?= e($r['estado']) ?>"><?= e($r['estado']) ?></span></td><td><?= e($r['fecha']) ?></td></tr><?php endwhile;?></tbody></table></div>
</main>
<script>
const colorTexto="#344054", colorAzul="#245cff", colorLinea="rgba(36,92,255,.12)";
new Chart(document.getElementById("graficaCategorias"),{type:"doughnut",data:{labels:<?= json_encode($labels,JSON_UNESCAPED_UNICODE) ?>,datasets:[{data:<?= json_encode($datos) ?>,backgroundColor:["#245cff","#20b8e6","#173fc4","#6f7cff","#0b8da8","#4d9cff","#102765","#87a4ff"],borderColor:"#ffffff",borderWidth:4}]},options:{plugins:{legend:{labels:{color:colorTexto}}}}});
new Chart(document.getElementById("graficaVisitas"),{type:"line",data:{labels:<?= json_encode($dias) ?>,datasets:[{label:"Visitantes",data:<?= json_encode($visitas) ?>,borderColor:colorAzul,backgroundColor:"rgba(53,216,229,.18)",fill:true,tension:.3}]},options:{scales:{x:{grid:{color:colorLinea},ticks:{color:colorTexto}},y:{beginAtZero:true,grid:{color:colorLinea},ticks:{color:colorTexto,precision:0}}},plugins:{legend:{labels:{color:colorTexto}}}}});
</script>
<?php include 'includes/footer.php'; ?>
