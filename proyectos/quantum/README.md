# Quántum ININDETEC

Proyecto de gaceta institucional en PHP y MySQL con publicaciones, categorías,
comentarios moderados, galería, videoteca administrable, newsletter, alertas
de última hora y visor naval interactivo.

## Requisitos

- PHP 8.2 o posterior con `mysqli`, `mbstring`, `dom`, `fileinfo` y `gd`
- MySQL o MariaDB
- Apache con el proyecto dentro de `htdocs`

## Instalación

1. Crea una base de datos llamada `noticias_marina`.
2. Para una instalación nueva, importa `database/esquema.sql`.
3. Para actualizar una instalación anterior, importa `database/migracion_2026.sql`
   y ejecuta `php database/completar_migracion.php`.
4. Configura estas variables de entorno si no utilizas los valores locales:
   `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`.
5. Asegura permisos de escritura para `uploads/noticias`, `uploads/galeria`,
   `uploads/autores` y `uploads/videos`.

La configuración local predeterminada usa MySQL en el puerto `3308`, usuario
`root` y base de datos `noticias_marina`.

## Videos

Administradores y editores pueden subir, editar, ocultar y eliminar videos
desde `admin/videos_admin.php`. Se aceptan archivos MP4, WebM, MOV y M4V de
hasta 200 MB. PHP debe tener `upload_max_filesize` y `post_max_size` por encima
de ese límite.

## Publicaciones programadas

En Windows debe ejecutarse cada minuto:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\Ciberpágina\admin\publicar_programadas.php
```

Configura la tarea en tu propio servidor; no se incluye ninguna tarea del sistema.

## Seguridad

El panel utiliza contraseñas con hash, sesiones protegidas, tokens CSRF,
permisos por rol, limitación de intentos y validación de imágenes. Las acciones
de escritura administrativas se procesan exclusivamente mediante `POST`.

No subas contraseñas SMTP, archivos `.env`, respaldos ni contenido de `uploads`
al repositorio.

## Verificación

```powershell
C:\xampp\php\php.exe -l index.php
node --check js\app.js
node --check js\admin.js
```

## Aplicación web instalable (PWA)

El sitio incluye una aplicación instalable para iOS y Android:

- `manifest.webmanifest`: identidad, colores, iconos y accesos rápidos.
- `sw.js`: caché de recursos, páginas visitadas y recuperación sin conexión.
- `offline.html`: pantalla de respaldo cuando el servidor no está disponible.
- `assets/pwa`: iconos normales, adaptables y `apple-touch-icon`.

En la computadora puede probarse desde `http://localhost:8080/Ciberpágina/`.
Los navegadores consideran `localhost` un contexto apto para desarrollo. En un
teléfono conectado por IP local puede revisarse toda la interfaz, pero para
probar instalación, caché y notificaciones como en producción debe utilizarse
una dirección HTTPS temporal o el dominio definitivo.

Cada vez que cambie la estructura del contenido offline, incrementa `VERSION`
en `sw.js` para que los dispositivos reemplacen la caché anterior.

## Edición de portafolio

Se excluyeron publicaciones, usuarios, archivos subidos y respaldos. Se verificó
la sintaxis PHP; no se ejecutó una prueba integral con MySQL ni envíos de correo
en esta copia. Configura el correo mediante variables de entorno y utiliza una
cuenta de pruebas antes de habilitar newsletter.
