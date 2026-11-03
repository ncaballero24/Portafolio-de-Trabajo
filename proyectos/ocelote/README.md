# Ocelote · Comunidad CETis 15

Foro estudiantil funcional, con interfaz en español e identidad guinda, dorada y arte de las referencias proporcionadas. Versión local con servidor Python y persistencia SQLite. No depende de servicios de pago ni de un proveedor de alojamiento.

## Iniciar

Requiere Python 3.12 o posterior. No necesita instalar paquetes.

En Windows, abre `iniciar.cmd`. También puedes ejecutar desde esta carpeta:

```powershell
python server.py
```

Abre http://127.0.0.1:8000 en el navegador. Mantén abierta la terminal del servidor. Para detenerlo, presiona Ctrl+C. La base se crea automáticamente en `data/ocelote.sqlite3` y conserva la información entre reinicios.

## Administrador

La cuenta `admin` ya está reservada en la base. Para activarla:

1. Pulsa el avatar de la esquina superior derecha.
2. Elige **Configurar administrador local**.
3. Define una contraseña de al menos 12 caracteres.

Este paso solo funciona desde localhost y una sola vez. No hay contraseña universal ni credenciales incluidas en el código. Después, inicia sesión como `admin` con la contraseña que elegiste. El panel **Administración** muestra cuentas, edades, grupos, especialidades, XP, oro, reportes y registro de acciones. Permite suspender/reactivar estudiantes, ocultar/restaurar publicaciones y comentarios, resolver reportes y crear comunidades.

## Estudiantes

Pulsa el avatar y **Crea tu cuenta**. Cada cuenta tiene usuario único, contraseña, nombre, edad, grupo y especialidad. El perfil permite editar esos datos de presentación, una breve biografía y el avatar. La edad solo se expone a su titular y al administrador; no acompaña las publicaciones.

Se puede seguir/dejar comunidades, crear publicaciones o preguntas, comentar, votar a favor/en contra, guardar publicaciones, buscarlas y reportarlas. Las recompensas e insignias se calculan en el servidor. Los datos iniciales de Mariana, Diego y Sofía son ficticios y sus cuentas no tienen una contraseña de acceso conocida. Sus publicaciones se pueden ocultar desde administración.

## Actualización 2 · guía, progresión y tienda

Si ya tienes cuentas, **conserva la carpeta `data/`**. Detén el servidor con Ctrl+C y vuelve a ejecutar `iniciar.cmd`; después recarga el navegador con Ctrl+F5. La migración agrega tablas y conserva usuarios, contraseña del administrador, publicaciones, XP, oro e insignias. No es necesario configurar de nuevo `admin`.

El tutorial animado aparece después de iniciar sesión o registrarse. Sus cinco pasos enseñan el inicio, cómo seguir comunidades, publicar, responder y ganar recompensas. Tiene controles Atrás/Siguiente, opción de saltar, accesos para probar cada función y una preferencia por cuenta para no mostrarlo en futuros accesos. Puede reabrirse desde **Guía rápida** o el menú de la cuenta. Respeta la preferencia de movimiento reducido del dispositivo.

La colección incluye **64 opciones**: tres bases gratuitas, dos conjuntos completos y 59 piezas de peinados, sombreros, ropa, accesorios y objetos extraídas de las referencias entregadas. Las ilustraciones se prepararon con transparencia. El elemento tachado en azul no forma parte del catálogo.

## Misiones y niveles

| Acción diaria | XP | Oro |
| --- | ---: | ---: |
| Publicar una idea de al menos 40 caracteres | 30 | 30 |
| Responder a otro usuario con al menos 25 caracteres | 30 | 30 |
| Completar 2 aportes distintos | 20 | 20 |
| Bono por completar las tres misiones | 10 | 20 |
| **Total diario** | **90** | **100** |

Una publicación y una respuesta ajena válida completan las tres misiones del día. Una respuesta cuenta una vez por conversación al día. Publicar el mismo texto normalizado varias veces no aumenta el progreso. Los textos más cortos siguen permitidos, pero no dan progreso económico; este mínimo no certifica por sí mismo la calidad del contenido, que sigue sujeto a moderación.

La misión semanal entrega **120 oro y 60 XP** por participar en 4 días distintos de lunes a domingo; no tienen que ser consecutivos. Unirse a una comunidad por primera vez entrega **25 oro y 20 XP**, una sola vez en la cuenta. Entrar/salir de comunidades no genera pagos diarios ni racha. Las recompensas se acreditan automáticamente y solo una vez por periodo.

Las misiones se renuevan a medianoche UTC−6, hora de Ciudad de México. Cada 100 XP sube un nivel. Publicar o responder con un aporte que cumpla las condiciones de misión mantiene la racha. La mejor racha histórica se conserva al interrumpirla, y los requisitos de tienda usan esa mejor racha para evitar perder un desbloqueo obtenido. Los saldos anteriores se respetan; una misión ya pagada por la versión anterior no se vuelve a pagar por actualizar.

### Balance inicial de la colección

| Categoría | Precio | Requisito |
| --- | ---: | --- |
| Bases de estudiante | Gratis | Nivel 1 |
| Básicos | 80–150 oro | Nivel 1 |
| Especiales | 180–280 oro | Nivel 3; algunos, racha de 3 días |
| Raros | 350–450 oro | Nivel 5 y racha de 7 días |
| Corona ocelote | 900 oro | Nivel 10 y racha de 14 días |

El nivel, la racha y el precio se cumplen simultáneamente. Los dos conjuntos completos cuestan 120 oro. La colección suma **12,860 oro**: aproximadamente 110 días de misiones completas con bonos semanales para coleccionarlo absolutamente todo; una pieza común requiere 1–2 días y un look de varias piezas se puede formar en una semana. Son estimaciones, sin contar oro previo; el balance se puede ajustar en `catalog.json` según participación real.

Las compras muestran el saldo resultante. El servidor determina precios y requisitos, impide saldo negativo y compras duplicadas, incluso simultáneas. Hay ranuras independientes para avatar, peinado, sombrero, ropa, accesorio y objeto. Al combinar piezas se utiliza el cuerpo neutral para mantener su alineación; los avatares completos se muestran al quitar las piezas individuales. La composición aparece en perfil, cabecera y aportes. Quitar una pieza no la elimina del inventario. No hay dinero real ni artículos con caducidad.

El atlas contiene ilustraciones de catálogo, no un juego original de capas de animación. La composición es estática y algunas prendas conservan la silueta de su ilustración. Los recortes y posiciones se pueden afinar por artículo en `catalog.json`.

### Insignias

| Insignia | Criterio |
| --- | --- |
| Participación activa | 20 publicaciones o comentarios visibles |
| Semana en racha | 7 días consecutivos de actividad válida |
| Racha de respuestas | Responder en 5 publicaciones distintas de otros usuarios |
| Racha de preguntas | Publicar 5 preguntas académicas |
| Aporte más rankeado | Publicación líder con al menos 10 votos netos; desempate por antigüedad |
| Un mes en racha | 30 días consecutivos |
| Dos meses en racha | 60 días consecutivos |

Las insignias se conservan después de obtenerlas. La mascota del panel lateral sigue siendo ilustrativa.

## Estructura

- `server.py`: API, sesiones, permisos y moderación.
- `schema.sql`: esquema base de datos con tablas y campos separados en líneas.
- `catalog.json`: precios, requisitos y recortes de cada diseño.
- `economy.py`: migración aditiva, misiones, compras e inventario.
- `static/features.js` y `static/features.css`: guía animada, tienda y progreso de misiones.
- `static/`: interfaz adaptable a móvil, HTML, CSS y JavaScript sin dependencias.
- `static/assets/`: imágenes entregadas como referencia, mostradas mediante recortes CSS.
- `test_server.py`: prueba integrada con servidor y base temporales.
- `test_economy.py`: pruebas de catálogo, equipamiento múltiple, progresión, pagos, compras concurrentes y migración.
- `data/`: información local de ejecución; no debe publicarse como contenido estático.

## Verificación

```powershell
python -m unittest test_server test_economy -v
node --check static/app.js
node --check static/features.js
```

La prueba integrada verifica configuración inicial, registro, privacidad de edad, bloqueo de acceso administrativo, validación de origen, pertenencia antes de publicar, recompensas únicas, insignia de preguntas, votos, guardados, reportes, moderación, suspensión, reactivación y cierre de sesión. Node solo es necesario para la comprobación opcional de sintaxis JavaScript.

## Preparación para el servidor definitivo

Esta entrega es un MVP local para validación. `http.server` sirve para desarrollo; antes de abrirlo a alumnos por Internet se debe adaptar el transporte a un servidor de producción y configurar HTTPS, dominio, copias de seguridad y admisión institucional. El registro actual es abierto y no verifica matrícula ni correo escolar. La recuperación/cambio de contraseña, MFA, adjuntos, notificaciones en tiempo real y paginación quedan fuera de esta versión.

El backend y los datos están separados del frontend para facilitar la migración. SQLite admite un despliegue inicial de una sola instancia con volumen persistente; para varias réplicas se requiere una base compartida y adaptar el acceso a datos. La limitación de peticiones actual es por proceso e IP; debe revisarse con el proxy elegido. No se debe exponer la configuración inicial a través de un proxy local: activa `admin` antes de publicar y deshabilita la ruta `/api/setup` en producción.

Variables disponibles:

- `OCELOTE_DB`: ruta de la base de datos (por defecto `data/ocelote.sqlite3`).
- `OCELOTE_SECURE_COOKIE=1`: añade `Secure` a la cookie, para un despliegue servido exclusivamente por HTTPS.

La API usa consultas parametrizadas, contraseñas scrypt, cookies HttpOnly/SameSite, sesiones con caducidad y permisos comprobados en servidor. El cliente no puede asignarse roles ni modificar su XP/oro. Para respaldar SQLite con el servicio activo, usa su API de backup; no copies solo el archivo principal mientras haya escrituras WAL.

No se ha publicado el foro ni configurado alojamiento externo, porque el servidor está pendiente de definición. El nombre CETis 15 y la estética proceden de las referencias del proyecto; los contenidos de ejemplo no representan anuncios oficiales.

## Actualización móvil

Interfaz adaptable desde 320 px, navegación inferior con tienda, búsqueda en una fila propia, controles táctiles de 44 px, campos de 16 px y márgenes para zonas seguras. El tutorial se abre siempre tras el registro; al cargar la página se recupera si la cuenta aún lo tiene pendiente y no lo desactivó. Los recursos incluyen versión en la URL para renovar la caché.
