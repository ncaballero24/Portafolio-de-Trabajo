<?php
$noticia = $noticia ?? [];
$valor = static fn(string $campo, string $default = ''): string => e($_POST[$campo] ?? $noticia[$campo] ?? $default);
?>
<form method="POST" enctype="multipart/form-data" data-editor-form data-autosave-key="noticia-<?= (int) ($noticia['id'] ?? 0) ?>">
  <?= csrf_input() ?>
  <div class="admin-form-grid">
    <label class="admin-form-full">Título
      <input class="admin-input" type="text" name="titulo" maxlength="255" value="<?= $valor('titulo') ?>" required>
    </label>
    <label class="admin-form-full">Resumen
      <textarea class="admin-input" name="resumen" maxlength="600" rows="4" required><?= $valor('resumen') ?></textarea>
    </label>
    <label class="admin-form-full">Contenido
      <textarea class="editor-html" name="contenido" required><?= $valor('contenido') ?></textarea>
    </label>
    <label>Categoría
      <select class="admin-input" name="categoria" required>
        <option value="">Selecciona una categoría</option>
        <?php foreach ($categorias as $categoria): ?>
          <option value="<?= e($categoria['nombre']) ?>" <?= ($_POST['categoria'] ?? $noticia['categoria'] ?? '') === $categoria['nombre'] ? 'selected' : '' ?>><?= e($categoria['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Estado
      <select class="admin-input" name="estado" required>
        <?php $estadosDisponibles = ($_SESSION['admin_rol'] ?? '') === 'Redactor' ? ['borrador' => 'Borrador', 'publicada' => 'Publicada'] : ['borrador' => 'Borrador', 'publicada' => 'Publicada', 'programada' => 'Programada']; ?>
        <?php foreach ($estadosDisponibles as $estado => $etiqueta): ?>
          <option value="<?= $estado ?>" <?= ($_POST['estado'] ?? $noticia['estado'] ?? 'borrador') === $estado ? 'selected' : '' ?>><?= $etiqueta ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if (($_SESSION['admin_rol'] ?? '') !== 'Redactor'): ?>
      <label>Fecha programada
        <input class="admin-input" type="datetime-local" name="fecha_programada" value="<?= $valor('fecha_programada', !empty($noticia['fecha_programada']) ? date('Y-m-d\TH:i', strtotime($noticia['fecha_programada'])) : '') ?>">
      </label>
    <?php endif; ?>
    <?php if (($_SESSION['admin_rol'] ?? '') !== 'Redactor'): ?>
      <label class="checkbox-admin">
        <input type="checkbox" name="destacada" value="1" <?= isset($_POST['destacada']) || (!$_POST && !empty($noticia['destacada'])) ? 'checked' : '' ?>>
        Noticia destacada
      </label>
    <?php endif; ?>
    <label class="admin-form-full">Imagen principal
      <input class="admin-input" type="file" name="imagen" accept="image/jpeg,image/png,image/webp">
      <small>JPG, PNG o WebP. Máximo <?= LIMITE_IMAGEN_MB ?> MB.</small>
    </label>
    <?php if (!empty($noticia['imagen'])): ?>
      <div class="admin-form-full imagen-actual"><img src="<?= e(url_imagen($noticia['imagen'], '../')) ?>" alt=""><span>Imagen actual</span></div>
    <?php endif; ?>
    <label class="admin-form-full">Video de la noticia <small>Opcional</small>
      <input class="admin-input" type="file" name="video" accept="video/mp4,video/webm,video/quicktime,video/x-m4v,.m4v">
      <small>MP4, WebM, MOV o M4V. Máximo <?= LIMITE_VIDEO_MB ?> MB.</small>
    </label>
    <?php if (!empty($noticia['video'])): ?>
      <div class="admin-form-full video-actual">
        <video controls preload="metadata">
          <source src="../<?= e($noticia['video']) ?>" type="<?= e($noticia['video_mime'] ?? 'video/mp4') ?>">
        </video>
        <label><input type="checkbox" name="eliminar_video" value="1"> Eliminar video actual</label>
      </div>
    <?php endif; ?>
    <label class="admin-form-full">Galería de imágenes y videos <small>Opcional</small>
      <input class="admin-input" type="file" name="medios[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime,video/x-m4v,.m4v" multiple>
      <small>Puedes seleccionar varios archivos. Imágenes JPG, PNG o WebP hasta <?= LIMITE_IMAGEN_MB ?> MB; videos MP4, WebM, MOV o M4V hasta <?= LIMITE_VIDEO_MB ?> MB.</small>
    </label>
    <?php if (!empty($mediosNoticia)): ?>
      <div class="admin-form-full noticia-medios-actuales">
        <strong>Medios actuales</strong>
        <div class="noticia-medios-admin-grid">
          <?php foreach ($mediosNoticia as $medio): ?>
            <label class="noticia-medio-admin">
              <?php if (($medio['tipo'] ?? '') === 'video'): ?>
                <video controls preload="metadata">
                  <source src="../<?= e($medio['archivo']) ?>" type="<?= e($medio['mime'] ?: 'video/mp4') ?>">
                </video>
              <?php else: ?>
                <img src="<?= e(url_imagen($medio['archivo'], '../')) ?>" alt="">
              <?php endif; ?>
              <span><input type="checkbox" name="eliminar_medios[]" value="<?= (int) $medio['id'] ?>"> Eliminar</span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    <label>Texto alternativo
      <input class="admin-input" type="text" name="imagen_alt" maxlength="255" value="<?= $valor('imagen_alt') ?>">
    </label>
    <label>Pie de foto
      <input class="admin-input" type="text" name="imagen_pie" maxlength="255" value="<?= $valor('imagen_pie') ?>">
    </label>
    <fieldset class="admin-form-full autor-editor">
      <legend>Autoría o fuente de la noticia</legend>
      <div class="autor-editor-grid">
        <label>Tipo de atribución
          <?php $tipoAutoriaActual = $_POST['tipo_autoria'] ?? $noticia['tipo_autoria'] ?? 'escrito_por'; ?>
          <select class="admin-input" name="tipo_autoria" required>
            <option value="escrito_por" <?= $tipoAutoriaActual === 'escrito_por' ? 'selected' : '' ?>>Escrito por:</option>
            <option value="tomado_de" <?= $tipoAutoriaActual === 'tomado_de' ? 'selected' : '' ?>>Tomado de:</option>
          </select>
        </label>
        <label>Nombre del autor o fuente <small>Obligatorio para “Tomado de:”</small>
          <input class="admin-input" type="text" name="autor_publico" maxlength="150" value="<?= $valor('autor_publico') ?>" placeholder="Ej. Cap. de Nav. María López o NIST">
        </label>
        <label>Fotografía del autor
          <input class="admin-input" type="file" name="autor_imagen" accept="image/jpeg,image/png,image/webp">
          <small>JPG, PNG o WebP. Máximo <?= LIMITE_IMAGEN_MB ?> MB.</small>
        </label>
      </div>
      <?php if (!empty($noticia['autor_imagen'])): ?>
        <div class="autor-imagen-actual">
          <img src="<?= e(url_imagen($noticia['autor_imagen'], '../')) ?>" alt="">
          <span>Fotografía actual</span>
          <label><input type="checkbox" name="eliminar_autor_imagen" value="1"> Eliminar fotografía</label>
        </div>
      <?php endif; ?>
    </fieldset>
  </div>
  <p class="autosave-status" data-autosave-status>El borrador local se guarda automáticamente.</p>
  <div class="newsletter-actions">
    <button class="btn btn-secundario" type="submit" name="accion" value="preview" formtarget="_blank">Vista previa</button>
    <button class="btn" type="submit" name="accion" value="guardar"><?= empty($noticia['id']) ? 'Crear noticia' : 'Guardar cambios' ?></button>
  </div>
</form>
