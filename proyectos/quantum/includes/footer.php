<?php $prefijo = $prefijo ?? ''; ?>
<footer>
  <div class="footer-brand">
    <img src="<?= e(asset_url('assets/quantum-logo-vertical.png', $prefijo)) ?>" alt="">
    <p><strong>QUÁNTUM ININDETEC</strong><span>Gaceta Oficial · Innovación y conocimiento aplicado</span></p>
  </div>
  <p>&copy; <?= date('Y') ?> ININDETEC/Dirección de Ciberespacio. Todos los derechos reservados.</p>
  <nav aria-label="Enlaces legales">
    <a href="<?= e($prefijo) ?>index.php">Inicio</a>
    <a href="<?= e($prefijo) ?>direcciones.php">Direcciones</a>
    <a href="<?= e($prefijo) ?>simposium.php">SIMPOSIUM 2026</a>
    <a href="<?= e($prefijo) ?>videos.php">Videos</a>
    <a href="<?= e($prefijo) ?>galeria/galeria.php">Galería ININDETEC</a>
    <a href="<?= e($prefijo) ?>guardados.php">Mis lecturas</a>
    <a href="<?= e($prefijo) ?>sitemap.php">Mapa del sitio</a>
  </nav>
</footer>
<button class="pwa-instalar-lanzador" type="button" data-pwa-instalar hidden aria-haspopup="dialog">
  <span aria-hidden="true">Q</span>
  <span><strong>Instalar Quántum</strong><small>Acceso rápido desde tu dispositivo</small></span>
</button>
<div class="pwa-modal" data-pwa-modal role="dialog" aria-modal="true" aria-labelledby="pwa-modal-titulo" hidden>
  <section>
    <button class="pwa-modal-cerrar" type="button" data-pwa-cerrar aria-label="Cerrar">×</button>
    <img src="<?= e(asset_url('assets/pwa/icon-192.png', $prefijo)) ?>" alt="">
    <span class="pwa-modal-etiqueta">Aplicación Quántum</span>
    <h2 id="pwa-modal-titulo">Lleva la gaceta contigo</h2>
    <p data-pwa-descripcion>Instala Quántum para abrirla desde tu pantalla de inicio y conservar publicaciones disponibles sin conexión.</p>
    <ol data-pwa-pasos>
      <li>Abre esta página en Safari.</li>
      <li>Toca el botón <strong>Compartir</strong>.</li>
      <li>Selecciona <strong>Agregar a pantalla de inicio</strong> y confirma.</li>
    </ol>
    <button class="pwa-modal-accion" type="button" data-pwa-accion>Entendido</button>
    <small class="pwa-modal-nota" data-pwa-nota>La instalación completa estará disponible desde una dirección HTTPS.</small>
  </section>
</div>
<script src="<?= e(asset_url('js/app.js', $prefijo)) ?>"></script>
<?= $extra_scripts ?? '' ?>
</body>
</html>
