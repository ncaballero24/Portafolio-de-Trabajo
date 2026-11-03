<?php
require_once 'includes/inicio.php';
requerir_admin(['Administrador']);
require_once dirname(__DIR__).'/includes/simposium.php';
$error=''; $disponible=true;
try { $estado=simposium_cargar($conn); }
catch (Throwable $e) { error_log('SIMPOSIUM: '.$e->getMessage()); $disponible=false; $error='El editor no está disponible. Solicita aplicar la migración de SIMPOSIUM.'; }
if ($disponible && $_SERVER['REQUEST_METHOD']==='POST') {
    verificar_csrf();
    try {
        $raw=$_POST['contenido']??null;
        if (!is_string($raw) || strlen($raw)>500000) throw new InvalidArgumentException('Contenido inválido o demasiado extenso.');
        $datos=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
        if (!is_array($datos)) throw new InvalidArgumentException('Contenido inválido.');
        $datos=simposium_validar($datos);
        $revision=filter_var($_POST['revision']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if ($revision===false) throw new InvalidArgumentException('Revisión inválida. Recarga la página.');
        $estado=['datos'=>$datos,'revision'=>$revision];
        simposium_guardar($conn,$datos,$revision);
        header('Location: simposium.php?guardado=1'); exit;
    } catch (InvalidArgumentException $e) { $error=$e->getMessage(); }
    catch (JsonException $e) { $error='No se pudo interpretar el contenido. No se guardaron cambios.'; }
    catch (RuntimeException $e) { $error=$e->getMessage(); }
    catch (Throwable $e) { error_log('SIMPOSIUM guardado: '.$e->getMessage()); $error='No se guardaron los cambios. Inténtalo nuevamente.'; }
}
$admin_titulo='SIMPOSIUM';
include 'includes/header.php';
?>
<main class="detalle" id="contenido-admin">
  <h1>Administrar SIMPOSIUM</h1>
  <p>Edita los textos y el programa. Los cambios se publican al guardar. El diseño del sitio se conserva.</p>
  <a class="btn btn-secundario" href="../simposium.php" target="_blank" rel="noopener">Ver página publicada</a>
  <?php if($error): ?><p role="alert" class="mensaje-formulario"><?= e($error) ?></p><?php endif; ?>
  <?php if(isset($_GET['guardado']) && !$error): ?><p role="status" class="mensaje-formulario">Cambios guardados y publicados.</p><?php endif; ?>
  <?php if($disponible): ?>
  <noscript><p>Activa JavaScript para utilizar el editor de jornadas.</p></noscript>
  <form method="post" id="simposium-editor">
    <?= csrf_input() ?><input type="hidden" name="revision" value="<?= (int)$estado['revision'] ?>">
    <input type="hidden" name="contenido" id="simposium-json">
    <section class="noticia-detalle"><h2>Información general</h2><div class="admin-form-grid">
    <?php foreach(simposium_campos() as $clave=>$campo): ?>
      <label><?= e($campo[0]) ?>
      <?php if($campo[1]>1000): ?><textarea class="admin-input" data-general="<?= e($clave) ?>" maxlength="<?= $campo[1] ?>" rows="4"><?= e($estado['datos'][$clave]) ?></textarea>
      <?php else: ?><input class="admin-input" data-general="<?= e($clave) ?>" maxlength="<?= $campo[1] ?>" value="<?= e($estado['datos'][$clave]) ?>" required><?php endif; ?>
      </label>
    <?php endforeach; ?>
    </div></section>
    <h2>Jornadas y actividades</h2><p>El orden de las tarjetas será el orden de publicación. Las cifras se calculan automáticamente.</p>
    <div id="simposium-jornadas-editor"></div>
    <button type="button" class="btn btn-secundario" id="simposium-agregar" disabled>Agregar jornada</button>
    <p id="simposium-estado" role="status" aria-live="polite"></p>
    <button class="btn" id="simposium-guardar" disabled>Guardar y publicar</button>
  </form>
  <script type="application/json" id="simposium-datos"><?= json_encode($estado['datos'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR) ?></script>
  <script src="<?= e(asset_url('js/simposium-admin.js','../')) ?>" defer></script>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
