CREATE TABLE IF NOT EXISTS categorias (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nombre (nombre),
    UNIQUE KEY uq_categorias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DELETE categoria_anterior
FROM categorias AS categoria_anterior
INNER JOIN categorias AS categoria_nueva ON categoria_nueva.nombre = 'Los pasantes opinan'
WHERE categoria_anterior.nombre = 'Pasantes';

UPDATE categorias
SET nombre = 'Los pasantes opinan', slug = 'los-pasantes-opinan'
WHERE nombre = 'Pasantes';

DELETE categoria_anterior
FROM categorias AS categoria_anterior
INNER JOIN categorias AS categoria_nueva ON categoria_nueva.nombre = 'Destacado'
WHERE categoria_anterior.nombre = 'Marina';

UPDATE categorias
SET nombre = 'Destacado', slug = 'destacado'
WHERE nombre = 'Marina';

UPDATE noticias SET categoria = 'Destacado' WHERE categoria = 'Marina';

INSERT IGNORE INTO categorias (nombre, slug, orden) VALUES
('Destacado', 'destacado', 10),
('Puertos', 'puertos', 20),
('Tecnología', 'tecnologia', 30),
('Seguridad', 'seguridad', 40),
('Ciberespacio', 'ciberespacio', 50),
('Los pasantes opinan', 'los-pasantes-opinan', 70),
('Mundial 2026', 'mundial-2026', 80);

INSERT IGNORE INTO categorias (nombre, slug, orden)
SELECT DISTINCT categoria, LOWER(REPLACE(TRIM(categoria), ' ', '-')), 100
FROM noticias
WHERE categoria IS NOT NULL AND TRIM(categoria) <> '';

ALTER TABLE noticias
    ADD COLUMN IF NOT EXISTS slug VARCHAR(280) NULL AFTER titulo,
    ADD COLUMN IF NOT EXISTS imagen_alt VARCHAR(255) NULL AFTER imagen,
    ADD COLUMN IF NOT EXISTS imagen_pie VARCHAR(255) NULL AFTER imagen_alt,
    ADD COLUMN IF NOT EXISTS video VARCHAR(255) NULL AFTER imagen_pie,
    ADD COLUMN IF NOT EXISTS video_mime VARCHAR(100) NULL AFTER video,
    ADD COLUMN IF NOT EXISTS autor_publico VARCHAR(150) NULL AFTER autor,
    ADD COLUMN IF NOT EXISTS tipo_autoria ENUM('escrito_por','tomado_de') NOT NULL DEFAULT 'escrito_por' AFTER autor_publico,
    ADD COLUMN IF NOT EXISTS autor_imagen VARCHAR(255) NULL AFTER tipo_autoria,
    ADD COLUMN IF NOT EXISTS actualizado_en DATETIME NULL AFTER fecha_programada;

UPDATE noticias
SET tipo_autoria = 'tomado_de',
    autor_publico = TRIM(SUBSTRING(autor_publico, CHAR_LENGTH('Tomado de:') + 1))
WHERE LOWER(TRIM(autor_publico)) LIKE 'tomado de:%';

UPDATE noticias SET categoria = 'Los pasantes opinan' WHERE categoria = 'Pasantes';

CREATE TABLE IF NOT EXISTS revisiones_noticias (
    id BIGINT NOT NULL AUTO_INCREMENT,
    noticia_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    resumen TEXT NOT NULL,
    contenido LONGTEXT NOT NULL,
    editado_por VARCHAR(100) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_revision_noticia (noticia_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS visitas_noticias (
    id BIGINT NOT NULL AUTO_INCREMENT,
    noticia_id INT NOT NULL,
    visitante_hash CHAR(64) NOT NULL,
    dia DATE NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_visita_diaria (noticia_id, visitante_hash, dia),
    KEY idx_visitas_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS noticia_medios (
    id BIGINT NOT NULL AUTO_INCREMENT,
    noticia_id INT NOT NULL,
    tipo ENUM('imagen','video') NOT NULL,
    archivo VARCHAR(255) NOT NULL,
    mime VARCHAR(100) NULL,
    orden INT NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_noticia_medios_noticia (noticia_id, orden, id),
    CONSTRAINT fk_noticia_medios_noticia FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS intentos_login (
    id BIGINT NOT NULL AUTO_INCREMENT,
    ip_hash CHAR(64) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exitoso TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_intentos_ip_fecha (ip_hash, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE suscriptores
    ADD COLUMN IF NOT EXISTS token_baja CHAR(64) NULL AFTER correo,
    ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1 AFTER token_baja;

UPDATE suscriptores
SET token_baja = SHA2(CONCAT(correo, id, RAND()), 256)
WHERE token_baja IS NULL;

CREATE TABLE IF NOT EXISTS videos (
    id INT NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    descripcion VARCHAR(1000) NULL,
    archivo VARCHAR(255) NOT NULL,
    mime VARCHAR(100) NOT NULL,
    publicado TINYINT(1) NOT NULL DEFAULT 1,
    en_inicio TINYINT(1) NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_videos_archivo (archivo),
    KEY idx_videos_inicio (en_inicio, publicado),
    KEY idx_videos_publicados (publicado, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE videos
    ADD COLUMN IF NOT EXISTS en_inicio TINYINT(1) NOT NULL DEFAULT 0 AFTER publicado,
    ADD INDEX IF NOT EXISTS idx_videos_inicio (en_inicio, publicado);

CREATE TABLE IF NOT EXISTS bitacora_admin (
    id BIGINT NOT NULL AUTO_INCREMENT,
    admin_id INT NULL,
    admin_nombre VARCHAR(100) NOT NULL,
    accion VARCHAR(80) NOT NULL,
    entidad VARCHAR(80) NOT NULL DEFAULT '',
    entidad_id BIGINT NULL,
    detalles VARCHAR(1000) NOT NULL DEFAULT '',
    ip_hash CHAR(64) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bitacora_fecha (fecha),
    KEY idx_bitacora_admin (admin_id, fecha),
    KEY idx_bitacora_entidad (entidad, entidad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
