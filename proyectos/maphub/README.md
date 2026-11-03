# MapHub · Centro de operaciones

Monitoreo geográfico de patrullas e incidencias con una interfaz adaptable a escritorio y celular.

## Funciones

- Consulta del mapa, unidades, filtros por fecha, tipo, zona y severidad.
- Registro de incidencias; asignación de la unidad disponible más cercana.
- Resolución y liberación de la unidad con historial y bitácora.
- Transacciones para impedir asignaciones y resoluciones duplicadas.
- Gráficas, agrupaciones, mapa de calor y tendencias descriptivas.
- Exportación Excel (tabla compatible .xls) y PDF resumido de las primeras 45 filas.
- Roles de consulta, operador y administrador; configuración y bitácora administrativas.

## Instalación local

Requiere PHP 8.2 con pgsql, pdo_pgsql y fileinfo; PostgreSQL con PostGIS.
Node.js 22.12 o posterior solo es necesario para recompilar el frontend.

1. Crea una base vacía y un usuario PostgreSQL exclusivo para esta aplicación.
2. Importa `database/esquema.sql`. PostGIS debe instalarse previamente con una cuenta autorizada.
3. Para una demostración, importa `database/demo.sql` **únicamente en una base vacía de pruebas**.
4. Define las variables de `.env.example` en el entorno del proceso PHP. El archivo no se carga automáticamente.

Ejemplo de PowerShell, sustituyendo los valores por los de tu instalación:

```powershell
$env:PGHOST = '127.0.0.1'
$env:PGPORT = '5432'
$env:PGDATABASE = 'maphub'
$env:PGUSER = 'maphub'
# Define PGPASSWORD de forma local; no lo guardes en el repositorio.
psql -f database/esquema.sql
psql -f database/demo.sql
$env:MAPHUB_DEMO = '1'
# Define MAPHUB_ADMIN_USER y MAPHUB_ADMIN_PASSWORD (mínimo 12 caracteres).
php database/crear_usuario.php
Remove-Item Env:MAPHUB_ADMIN_PASSWORD
php -S 127.0.0.1:8022 -t .
```

Abre `http://127.0.0.1:8022/frontend/`. No existen contraseñas predeterminadas.
El servidor integrado PHP es para desarrollo; el despliegue requiere Apache/Nginx,
HTTPS y configuración de acceso acorde a los datos que se mostrarán.

## Demostración y GPS

`MAPHUB_DEMO=1` habilita movimientos simulados y atención automática; el panel
lo indica con una etiqueta. `MAPHUB_DEMO=0` desactiva esa simulación y conserva
las posiciones recibidas. No hay proveedor GPS conectado en esta entrega.
La API de actualización de posiciones requiere una sesión de operador.
Antes de usar ubicaciones reales, configura la integración y restringe el acceso
al servidor: la consulta del tablero es pública en esta edición de portafolio.
Los módulos México y marino se conservan como visualizadores complementarios;
el módulo marino utiliza datos de demostración, no AIS en vivo.
Los mapas base necesitan conexión a Internet.

## Desarrollo

```powershell
cd frontend-source
npm ci
npm run build
```

Copia el contenido generado en `frontend-source/dist` a `frontend` para servirlo
con PHP. Las rutas relativas permiten instalarlo en una subcarpeta.

## Pruebas

`tests/flujo.py` crea una incidencia ficticia y comprueba asignación, resolución,
historial, permisos y rechazo de duplicados. Úsalo únicamente con una base de
pruebas y una cuenta de operador/administrador, definiendo `MAPHUB_TEST_URL`,
`MAPHUB_TEST_USER`, `MAPHUB_TEST_PASSWORD` y `MAPHUB_TEST_ALLOW_WRITES=1`.

```powershell
python tests/flujo.py
```

La evidencia se almacena fuera del control de versiones. Retirarla desvincula
el archivo de la incidencia; la limpieza física requiere una política de retención.
