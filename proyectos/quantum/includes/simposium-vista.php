<main class="sx-evento" id="contenido-principal">
<?php if($noExiste): ?>
  <section class="sx-error sx-ancho"><h1>Jornada no encontrada</h1><p>Este día no está disponible en el programa actual.</p><a class="sx-boton" href="simposium.php">Volver al programa</a></section>
<?php elseif($dia===null): ?>
  <input class="sx-pausa" type="checkbox" id="sx-pausa"><label class="sx-pausa-label" for="sx-pausa">Pausar animaciones</label>
  <section class="sx-cielo" aria-labelledby="sx-titulo">
    <div class="sx-hero sx-ancho">
      <div class="sx-presentacion">
        <p class="sx-etiqueta"><?= e($simposium['etiqueta']) ?></p>
        <h1 id="sx-titulo"><?= e($simposium['titulo']) ?><strong><?= e($simposium['anio']) ?></strong></h1>
        <p class="sx-resumen"><?= nl2br(e($simposium['descripcion'])) ?></p>
        <dl class="sx-cifras"><div><dt>Jornadas</dt><dd><?= count($jornadas) ?></dd></div><div><dt>Actividades</dt><dd><?= array_sum(array_map(fn($j)=>count($j['actividades']),$jornadas)) ?></dd></div><div><dt>Encuentro</dt><dd>1</dd></div></dl>
      </div>
      <div class="sx-atomo" aria-hidden="true"><div class="sx-orbita sx-orbita-uno"></div><div class="sx-orbita sx-orbita-dos"></div><div class="sx-orbita sx-orbita-tres"></div><div class="sx-nucleo"></div></div>
      <aside class="sx-ficha" aria-label="Información del evento"><span class="sx-estado"><?= e($simposium['estado']) ?></span><h2><?= e($simposium['programa']) ?></h2><dl><div><dt>Fecha</dt><dd><?= e($simposium['fecha']) ?></dd></div><div><dt>Sede</dt><dd><?= e($simposium['sede']) ?></dd></div><div><dt>Modalidad</dt><dd><?= e($simposium['modalidad']) ?></dd></div></dl></aside>
    </div>
  </section>
  <nav class="sx-dias sx-ancho simposium-jornadas" aria-label="Elige una jornada">
    <?php foreach($jornadas as $indice=>$jornada): ?>
      <a class="sx-dia" href="simposium.php?dia=<?= $indice+1 ?>"><span class="sx-dia-cabecera"><strong>Día <?= $indice+1 ?></strong><span aria-hidden="true">↗</span></span><span class="sx-dia-texto"><?= e($jornada['enfoque']) ?></span><span class="sx-dia-accion">Ver agenda del día</span></a>
    <?php endforeach; ?>
  </nav>
  <section class="sx-intro sx-ancho" aria-labelledby="sx-intro-titulo"><p class="sx-etiqueta">Programa general</p><h2 id="sx-intro-titulo"><?= e($simposium['intro_titulo']) ?></h2><p><?= nl2br(e($simposium['intro_texto'])) ?></p></section>
  <section class="sx-cierre sx-ancho"><h2><?= e($simposium['cierre_titulo']) ?></h2><p><?= nl2br(e($simposium['cierre_texto'])) ?></p></section>
<?php else: $jornada=$jornadas[$dia-1];
  $fotos=[['9275222','Auditorio durante una conferencia','Atlantic Ambience'],['15141543','Presentación de un equipo de trabajo','Matheus Bertelli'],['6949973','Panel de especialistas','Werner Pfennig'],['8761554','Intercambio de ideas en una conferencia','Pavel Danilyuk'],['8424560','Sesión de colaboración profesional','Pavel Danilyuk']];
  $offset=($dia-1)%count($fotos); $fotos=array_merge(array_slice($fotos,$offset),array_slice($fotos,0,$offset));
?>
  <input class="sx-pausa" type="checkbox" id="sx-pausa"><label class="sx-pausa-label" for="sx-pausa">Pausar animaciones</label>
  <section class="sx-cielo sx-memoria-cielo"><div class="sx-ancho">
    <a class="sx-volver" href="simposium.php">← Volver a <?= e($simposium['titulo']) ?></a>
    <div class="sx-memoria-titulos"><div class="sx-presentacion"><p class="sx-etiqueta"><?= e($simposium['etiqueta']) ?></p><h1><?= e($simposium['titulo']) ?><strong><?= e($simposium['anio']) ?></strong></h1></div>
    <div class="sx-jornada-titulo"><p>DÍA <?= $dia ?></p><h2><?= e($jornada['titulo']) ?></h2></div>
    <dl class="sx-cifras"><div><dt>Jornadas</dt><dd><?= count($jornadas) ?></dd></div><div><dt>Actividades del día</dt><dd><?= count($jornada['actividades']) ?></dd></div></dl>
    <div class="sx-atomo" aria-hidden="true"><div class="sx-orbita sx-orbita-uno"></div><div class="sx-orbita sx-orbita-dos"></div><div class="sx-orbita sx-orbita-tres"></div><div class="sx-nucleo"></div></div></div>
  </div></section>
  <div class="sx-memoria sx-ancho">
    <div class="sx-columna-programa"><section class="sx-resumen-dia"><h2>Acerca del día <?= $dia ?></h2><p><?= nl2br(e($jornada['descripcion'])) ?></p></section>
    <section class="sx-agenda sx-panel-claro" aria-labelledby="sx-agenda-titulo"><h2 id="sx-agenda-titulo">Agenda del día <?= $dia ?></h2>
      <?php if(!$jornada['actividades']): ?><p>Las actividades de esta jornada se anunciarán próximamente.</p><?php else: ?>
      <ol><?php foreach($jornada['actividades'] as $actividad): ?><li><time datetime="<?= e($actividad[0]) ?>"><?= e($actividad[0]) ?></time><div><h3><?= e($actividad[1]) ?></h3><p><?= nl2br(e($actividad[2])) ?></p></div></li><?php endforeach; ?></ol>
      <?php endif; ?>
    </section></div>
    <section class="sx-galeria-dia" aria-label="Galería ilustrativa del día">
      <?php foreach($fotos as $i=>$foto): ?><figure class="sx-foto<?= $i===0?' sx-foto-principal':'' ?>"><img src="<?= e(asset_url('assets/simposium-stock/'.$foto[0].'.jpg')) ?>" alt="<?= e($foto[1]) ?> (fotografía de stock)" width="1000" height="667" loading="<?= $i===0?'eager':'lazy' ?>"><figcaption><?= $i===0?'Galería ilustrativa · Día '.$dia:e($foto[1]) ?></figcaption></figure><?php endforeach; ?>
      <section class="sx-panel-claro sx-enfoque-panel"><h2>Eje de la jornada</h2><p><?= e($jornada['enfoque']) ?></p><p class="sx-aviso-stock">Fotografías de stock para ilustrar el programa; no corresponden al evento.</p><details><summary>Créditos fotográficos</summary><ul><?php foreach($fotos as $foto): ?><li><a href="https://www.pexels.com/photo/<?= e($foto[0]) ?>/" target="_blank" rel="noopener noreferrer"><?= e($foto[2]) ?> · Pexels</a></li><?php endforeach; ?></ul></details></section>
    </section>
  </div>
  <nav class="sx-dias sx-ancho sx-dias-interior" aria-label="Navegación entre jornadas"><?php foreach($jornadas as $indice=>$otra): ?><a class="sx-dia" href="simposium.php?dia=<?= $indice+1 ?>"<?= $dia===$indice+1?' aria-current="page"':'' ?>><strong>Día <?= $indice+1 ?></strong><span class="sx-dia-texto"><?= e($otra['titulo']) ?></span></a><?php endforeach; ?></nav>
<?php endif; ?>
</main>
