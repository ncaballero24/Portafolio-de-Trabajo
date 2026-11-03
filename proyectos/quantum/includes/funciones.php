<?php
declare(strict_types=1);

const LIMITE_IMAGEN_MB = 25;
const LIMITE_IMAGEN_BYTES = LIMITE_IMAGEN_MB * 1024 * 1024;
const LIMITE_VIDEO_MB = 200;
const LIMITE_VIDEO_BYTES = LIMITE_VIDEO_MB * 1024 * 1024;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function direcciones_inindetec(): array
{
    return [
        'dcd' => [
            'sigla' => 'DCD',
            'nombre' => 'Dirección de Sistemas de Comunicaciones y Detección',
            'logo' => 'assets/direcciones/dcd.png',
            'descripcion' => 'Información y publicaciones relacionadas con sistemas de comunicaciones y detección.',
        ],
        'dsa' => [
            'sigla' => 'DSA',
            'nombre' => 'Dirección de Sistemas de Armas',
            'logo' => 'assets/direcciones/dsa.png',
            'descripcion' => 'Información y publicaciones relacionadas con sistemas de armas.',
        ],
        'dsms' => [
            'sigla' => 'DSMS',
            'nombre' => 'Dirección de Sistemas de Modelado y Simulación',
            'logo' => 'assets/direcciones/dsms.png',
            'descripcion' => 'Información y publicaciones relacionadas con modelado y simulación.',
        ],
        'dsm' => [
            'sigla' => 'DSM',
            'nombre' => 'Dirección de Sistemas Mecatrónicos',
            'logo' => 'assets/direcciones/dsm.png',
            'descripcion' => 'Información y publicaciones relacionadas con sistemas mecatrónicos.',
        ],
        'ciberespacio' => [
            'sigla' => 'Ciberespacio',
            'nombre' => 'Dirección de Sistemas de Ciberespacio',
            'logo' => 'assets/direcciones/ciberespacio.png',
            'descripcion' => 'Información y publicaciones relacionadas con sistemas de ciberespacio.',
        ],
        'dcv' => [
            'sigla' => 'DCV',
            'nombre' => 'Dirección de Vinculación y Control de Proyectos',
            'logo' => 'assets/direcciones/dcv.png',
            'descripcion' => 'Información y publicaciones relacionadas con vinculación y control de proyectos.',
        ],
    ];
}

function obtener_direccion_inindetec(string $slug): ?array
{
    $direcciones = direcciones_inindetec();
    return $direcciones[$slug] ?? null;
}

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

function cabeceras_seguridad(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
}

function csrf_token(): string
{
    iniciar_sesion_segura();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    iniciar_sesion_segura();
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('La sesión del formulario expiró. Regresa a la página e inténtalo de nuevo.');
    }
}

function requerir_admin(?array $roles = null): void
{
    iniciar_sesion_segura();
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    if ($roles !== null && !in_array($_SESSION['admin_rol'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('No tienes permiso para realizar esta acción.');
    }
}

function slugify(string $texto): string
{
    $texto = trim(mb_strtolower($texto, 'UTF-8'));
    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n', 'ç' => 'c',
    ]);
    $convertido = function_exists('iconv')
        ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto)
        : false;
    $texto = $convertido !== false ? $convertido : $texto;
    $texto = preg_replace('/[^a-z0-9]+/i', '-', $texto) ?? '';
    return trim($texto, '-') ?: 'noticia';
}

function slug_unico(mysqli $conn, string $titulo, int $ignorarId = 0): string
{
    $base = slugify($titulo);
    $slug = $base;
    $numero = 2;
    do {
        $stmt = $conn->prepare('SELECT id FROM noticias WHERE slug = ? AND id != ? LIMIT 1');
        $stmt->bind_param('si', $slug, $ignorarId);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        if ($existe) {
            $slug = $base . '-' . $numero++;
        }
    } while ($existe);
    return $slug;
}

function url_base(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $esquema = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $posAdmin = strpos($script, '/admin/');
    $posGaleria = strpos($script, '/galeria/');
    if ($posAdmin !== false) {
        $ruta = substr($script, 0, $posAdmin);
    } elseif ($posGaleria !== false) {
        $ruta = substr($script, 0, $posGaleria);
    } else {
        $ruta = str_replace('\\', '/', rtrim(dirname($script), '/.\\'));
    }
    return $esquema . '://' . $host . $ruta;
}

function url_noticia(array $noticia, string $prefijo = ''): string
{
    if (!empty($noticia['slug'])) {
        return $prefijo . 'noticia.php?slug=' . rawurlencode($noticia['slug']);
    }
    return $prefijo . 'noticia.php?id=' . (int) $noticia['id'];
}

function url_imagen(?string $ruta, string $prefijo = ''): string
{
    $ruta = trim((string) $ruta);
    if ($ruta === '' || preg_match('~^(?:https?:)?//~i', $ruta)) {
        return $ruta;
    }
    return $prefijo . ltrim($ruta, '/');
}

function asset_url(string $ruta, string $prefijo = ''): string
{
    $ruta = ltrim($ruta, '/');
    $archivo = dirname(__DIR__) . '/' . $ruta;
    $version = is_file($archivo) ? (string) filemtime($archivo) : '1';
    return $prefijo . $ruta . '?v=' . rawurlencode($version);
}

function categorias_activas(mysqli $conn): array
{
    $resultado = $conn->query('SELECT nombre, slug FROM categorias WHERE activa = 1 ORDER BY orden, nombre');
    return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
}

function pagina_actual(): int
{
    return max(1, (int) ($_GET['pagina'] ?? 1));
}

function enlace_pagina(int $pagina, array $cambios = []): string
{
    $query = array_merge($_GET, $cambios, ['pagina' => $pagina]);
    return '?' . http_build_query($query);
}

function render_paginacion(int $pagina, int $totalPaginas): string
{
    if ($totalPaginas <= 1) {
        return '';
    }
    $html = '<nav class="paginacion" aria-label="Paginación">';
    if ($pagina > 1) {
        $html .= '<a href="' . e(enlace_pagina($pagina - 1)) . '">Anterior</a>';
    }
    $inicio = max(1, $pagina - 2);
    $fin = min($totalPaginas, $pagina + 2);
    for ($i = $inicio; $i <= $fin; $i++) {
        $clase = $i === $pagina ? ' class="activa" aria-current="page"' : '';
        $html .= '<a' . $clase . ' href="' . e(enlace_pagina($i)) . '">' . $i . '</a>';
    }
    if ($pagina < $totalPaginas) {
        $html .= '<a href="' . e(enlace_pagina($pagina + 1)) . '">Siguiente</a>';
    }
    return $html . '</nav>';
}

function sanitizar_html(string $html): string
{
    if (trim($html) === '') {
        return '';
    }
    $permitidos = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'a', 'img', 'figure', 'figcaption', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="contenido-raiz">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    foreach (iterator_to_array($xpath->query('//*')) as $nodo) {
        if ($nodo->nodeName === 'div' && $nodo->getAttribute('id') === 'contenido-raiz') {
            continue;
        }
        if (!in_array(strtolower($nodo->nodeName), $permitidos, true)) {
            $padre = $nodo->parentNode;
            if (in_array(strtolower($nodo->nodeName), ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                $padre?->removeChild($nodo);
                continue;
            }
            while ($padre && $nodo->firstChild) {
                $padre->insertBefore($nodo->firstChild, $nodo);
            }
            $padre?->removeChild($nodo);
            continue;
        }
        foreach (iterator_to_array($nodo->attributes ?? []) as $atributo) {
            $nombre = strtolower($atributo->nodeName);
            $valor = trim($atributo->nodeValue);
            $permitido = in_array($nombre, ['href', 'src', 'alt', 'title', 'target', 'rel'], true);
            if (!$permitido || str_starts_with($nombre, 'on') || preg_match('/^\s*javascript:/i', $valor)) {
                $nodo->removeAttribute($atributo->nodeName);
            }
        }
        if ($nodo->nodeName === 'a' && $nodo->getAttribute('target') === '_blank') {
            $nodo->setAttribute('rel', 'noopener noreferrer');
        }
    }
    $raiz = $dom->getElementById('contenido-raiz');
    $salida = '';
    if ($raiz) {
        foreach ($raiz->childNodes as $hijo) {
            $salida .= $dom->saveHTML($hijo);
        }
    }
    return $salida;
}

function guardar_imagen_segura(array $archivo, string $subcarpeta): ?array
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($archivo['size'] ?? 0) > LIMITE_IMAGEN_BYTES) {
        throw new RuntimeException('La imagen no pudo cargarse o supera el límite de ' . LIMITE_IMAGEN_MB . ' MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensiones[$mime]) || @getimagesize($archivo['tmp_name']) === false) {
        throw new RuntimeException('El archivo debe ser una imagen JPG, PNG o WebP válida.');
    }
    $directorio = dirname(__DIR__) . '/uploads/' . trim($subcarpeta, '/');
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo preparar la carpeta de imágenes.');
    }
    $baseNombre = bin2hex(random_bytes(16));
    $convertirWebp = function_exists('imagewebp') && in_array($mime, ['image/jpeg', 'image/png'], true);
    $extensionFinal = $convertirWebp ? 'webp' : $extensiones[$mime];
    $nombre = $baseNombre . '.' . $extensionFinal;
    $destino = $directorio . '/' . $nombre;
    if ($convertirWebp) {
        $imagen = $mime === 'image/jpeg' ? @imagecreatefromjpeg($archivo['tmp_name']) : @imagecreatefrompng($archivo['tmp_name']);
        if (!$imagen || !imagewebp($imagen, $destino, 82)) {
            if ($imagen) {
                imagedestroy($imagen);
            }
            throw new RuntimeException('No se pudo optimizar la imagen.');
        }
        imagedestroy($imagen);
    } elseif (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return [
        'ruta' => 'uploads/' . trim($subcarpeta, '/') . '/' . $nombre,
        'mime' => $convertirWebp ? 'image/webp' : $mime,
    ];
}

function guardar_video_seguro(array $archivo): ?array
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('El video no pudo cargarse. Verifica el tamaño del archivo e inténtalo nuevamente.');
    }
    if (($archivo['size'] ?? 0) <= 0 || ($archivo['size'] ?? 0) > LIMITE_VIDEO_BYTES) {
        throw new RuntimeException('El video supera el límite de ' . LIMITE_VIDEO_MB . ' MB.');
    }

    $extension = strtolower(pathinfo((string) ($archivo['name'] ?? ''), PATHINFO_EXTENSION));
    $formatos = [
        'mp4' => ['video/mp4', 'application/mp4', 'application/octet-stream'],
        'webm' => ['video/webm', 'application/octet-stream'],
        'mov' => ['video/quicktime', 'application/octet-stream'],
        'm4v' => ['video/x-m4v', 'video/mp4', 'application/octet-stream'],
    ];
    if (!isset($formatos[$extension])) {
        throw new RuntimeException('El archivo debe ser un video MP4, WebM, MOV o M4V.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($archivo['tmp_name']);
    if (!in_array($mime, $formatos[$extension], true)) {
        throw new RuntimeException('El contenido del archivo no coincide con un formato de video permitido.');
    }
    if ($mime === 'application/octet-stream') {
        $cabecera = (string) file_get_contents($archivo['tmp_name'], false, null, 0, 16);
        $firmaValida = $extension === 'webm'
            ? str_starts_with($cabecera, "\x1A\x45\xDF\xA3")
            : substr($cabecera, 4, 4) === 'ftyp';
        if (!$firmaValida) {
            throw new RuntimeException('No se pudo validar la estructura interna del video.');
        }
    }

    $directorio = dirname(__DIR__) . '/uploads/videos';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo preparar la carpeta de videos.');
    }
    $nombre = bin2hex(random_bytes(16)) . '.' . $extension;
    $destino = $directorio . '/' . $nombre;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar el video.');
    }

    return [
        'ruta' => 'uploads/videos/' . $nombre,
        'mime' => $mime === 'application/octet-stream' ? $formatos[$extension][0] : $mime,
    ];
}

function normalizar_archivos_multiples(array $archivos): array
{
    if (!isset($archivos['name']) || !is_array($archivos['name'])) {
        return [];
    }
    $normalizados = [];
    foreach ($archivos['name'] as $i => $nombre) {
        $normalizados[] = [
            'name' => $nombre,
            'type' => $archivos['type'][$i] ?? '',
            'tmp_name' => $archivos['tmp_name'][$i] ?? '',
            'error' => $archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $archivos['size'][$i] ?? 0,
        ];
    }
    return $normalizados;
}

function guardar_medio_noticia_seguro(array $archivo): ?array
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $extension = strtolower(pathinfo((string) ($archivo['name'] ?? ''), PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $imagen = guardar_imagen_segura($archivo, 'noticias');
        return $imagen ? ['tipo' => 'imagen', 'ruta' => $imagen['ruta'], 'mime' => $imagen['mime']] : null;
    }
    if (in_array($extension, ['mp4', 'webm', 'mov', 'm4v'], true)) {
        $video = guardar_video_seguro($archivo);
        return $video ? ['tipo' => 'video', 'ruta' => $video['ruta'], 'mime' => $video['mime']] : null;
    }
    throw new RuntimeException('Los medios deben ser imÃ¡genes JPG, PNG, WebP o videos MP4, WebM, MOV o M4V.');
}

function guardar_medios_noticia(mysqli $conn, int $noticiaId, array $archivos): void
{
    $medios = normalizar_archivos_multiples($archivos);
    if (!$medios) {
        return;
    }
    $stmtOrden = $conn->prepare('SELECT COALESCE(MAX(orden), 0) AS orden FROM noticia_medios WHERE noticia_id=?');
    if (!$stmtOrden) {
        throw new RuntimeException('Falta preparar la tabla de medios de noticias.');
    }
    $stmtOrden->bind_param('i', $noticiaId);
    $stmtOrden->execute();
    $orden = (int) ($stmtOrden->get_result()->fetch_assoc()['orden'] ?? 0);

    $stmt = $conn->prepare('INSERT INTO noticia_medios (noticia_id, tipo, archivo, mime, orden) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar el guardado de medios de la noticia.');
    }
    foreach ($medios as $archivo) {
        $medio = guardar_medio_noticia_seguro($archivo);
        if (!$medio) {
            continue;
        }
        $orden++;
        $stmt->bind_param('isssi', $noticiaId, $medio['tipo'], $medio['ruta'], $medio['mime'], $orden);
        if (!$stmt->execute()) {
            eliminar_archivo_local($medio['ruta']);
            throw new RuntimeException('No se pudo guardar uno de los medios de la noticia.');
        }
    }
}

function medios_noticia(mysqli $conn, int $noticiaId): array
{
    $stmt = $conn->prepare('SELECT * FROM noticia_medios WHERE noticia_id=? ORDER BY orden, id');
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('i', $noticiaId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function eliminar_archivo_local(?string $ruta): void
{
    if (!$ruta || !str_starts_with($ruta, 'uploads/')) {
        return;
    }
    $base = realpath(dirname(__DIR__) . '/uploads');
    $archivo = realpath(dirname(__DIR__) . '/' . $ruta);
    if ($base && $archivo && str_starts_with($archivo, $base . DIRECTORY_SEPARATOR) && is_file($archivo)) {
        unlink($archivo);
    }
}

function tiempo_lectura(string $html): int
{
    $palabras = str_word_count(strip_tags($html));
    return max(1, (int) ceil($palabras / 220));
}

function registrar_bitacora(mysqli $conn, string $accion, string $entidad = '', ?int $entidadId = null, string $detalles = ''): void
{
    if (empty($_SESSION['admin_id'])) {
        return;
    }
    $accion = mb_substr(trim($accion), 0, 80);
    $entidad = mb_substr(trim($entidad), 0, 80);
    $detalles = mb_substr(trim(strip_tags($detalles)), 0, 1000);
    $adminId = (int) $_SESSION['admin_id'];
    $adminNombre = mb_substr((string) ($_SESSION['admin_nombre'] ?? ''), 0, 100);
    $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'local') . '|quantum-audit-v1');
    try {
        $stmt = $conn->prepare('INSERT INTO bitacora_admin (admin_id, admin_nombre, accion, entidad, entidad_id, detalles, ip_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('isssiss', $adminId, $adminNombre, $accion, $entidad, $entidadId, $detalles, $ipHash);
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('No se pudo registrar la bitácora administrativa: ' . $e->getMessage());
    }
}
