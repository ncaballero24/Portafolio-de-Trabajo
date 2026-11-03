CREATE EXTENSION IF NOT EXISTS postgis;
CREATE TABLE IF NOT EXISTS usuarios (
 id BIGSERIAL PRIMARY KEY, username TEXT UNIQUE NOT NULL, password TEXT NOT NULL,
 nombre TEXT NOT NULL, role TEXT NOT NULL CHECK(role IN ('administrador','operador','consulta'))
);
CREATE TABLE IF NOT EXISTS poligonos (id BIGSERIAL PRIMARY KEY, nombre TEXT, geom geometry(Polygon,4326));
CREATE TABLE IF NOT EXISTS patrullas (
 id BIGSERIAL PRIMARY KEY, estado TEXT NOT NULL DEFAULT 'Disponible',
 poligono_id BIGINT REFERENCES poligonos(id), geom geography(Point,4326) NOT NULL
);
CREATE TABLE IF NOT EXISTS incidencias (
 id BIGSERIAL PRIMARY KEY, tipo TEXT NOT NULL, severidad INTEGER NOT NULL CHECK(severidad BETWEEN 1 AND 5),
 descripcion TEXT NOT NULL, zona TEXT, evidencia_imagen TEXT, estado TEXT NOT NULL DEFAULT 'Pendiente',
 resuelta BOOLEAN NOT NULL DEFAULT FALSE, patrulla_atendio BIGINT REFERENCES patrullas(id),
 timestamp TIMESTAMPTZ NOT NULL DEFAULT NOW(), geom geography(Point,4326) NOT NULL
);
CREATE TABLE IF NOT EXISTS historial_patrulla (
 id BIGSERIAL PRIMARY KEY, patrulla_id BIGINT REFERENCES patrullas(id), incidencia_id BIGINT REFERENCES incidencias(id),
 fecha_resolucion TIMESTAMPTZ NOT NULL DEFAULT NOW(), tiempo_respuesta INTEGER, estado TEXT,
 UNIQUE(patrulla_id,incidencia_id)
);
CREATE INDEX IF NOT EXISTS idx_incidencias_geom ON incidencias USING GIST(geom);
CREATE INDEX IF NOT EXISTS idx_patrullas_geom ON patrullas USING GIST(geom);
CREATE INDEX IF NOT EXISTS idx_incidencias_fecha ON incidencias(timestamp DESC);
CREATE TABLE IF NOT EXISTS barcos(id BIGSERIAL PRIMARY KEY,codigo TEXT UNIQUE);
CREATE TABLE IF NOT EXISTS ubicaciones_barco(id BIGSERIAL PRIMARY KEY,barco_id BIGINT REFERENCES barcos(id), timestamp TIMESTAMPTZ DEFAULT NOW(),latitud FLOAT,longitud FLOAT,velocidad FLOAT,rumbo FLOAT,clima TEXT,riesgo_colision TEXT);
