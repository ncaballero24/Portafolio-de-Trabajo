<?php
declare(strict_types=1);

function simposium_campos(): array {
    return [
        'titulo'=>['Título',120], 'anio'=>['Edición / año',40],
        'etiqueta'=>['Etiqueta superior',160], 'descripcion'=>['Descripción principal',4000],
        'estado'=>['Estado del evento',80], 'programa'=>['Título de la ficha',160],
        'fecha'=>['Fecha (texto)',160], 'sede'=>['Sede',240], 'modalidad'=>['Modalidad',120],
        'intro_titulo'=>['Título de introducción',240], 'intro_texto'=>['Introducción',6000],
        'cierre_titulo'=>['Título de cierre',240], 'cierre_texto'=>['Texto de cierre',6000],
    ];
}
function simposium_texto($valor, int $max, bool $obligatorio = true): string {
    if (!is_string($valor)) throw new InvalidArgumentException('Hay campos con formato inválido.');
    $valor = trim($valor);
    if (($obligatorio && $valor === '') || mb_strlen($valor) > $max) {
        throw new InvalidArgumentException("Completa los campos obligatorios y respeta el límite de $max caracteres.");
    }
    return $valor;
}
function simposium_validar(array $datos): array {
    $limpio = [];
    foreach (simposium_campos() as $clave => $campo) {
        $limpio[$clave] = simposium_texto($datos[$clave] ?? null, $campo[1], !in_array($clave,['descripcion','intro_texto','cierre_texto'],true));
    }
    $dias = $datos['jornadas'] ?? null;
    if (!is_array($dias) || !array_is_list($dias) || count($dias) < 1 || count($dias) > 20) {
        throw new InvalidArgumentException('El programa debe tener entre 1 y 20 jornadas.');
    }
    $total = 0;
    foreach ($dias as $dia) {
        if (!is_array($dia)) throw new InvalidArgumentException('Jornada inválida.');
        $nuevo = [];
        foreach (['titulo'=>240,'enfoque'=>240,'descripcion'=>6000] as $clave=>$max) {
            $nuevo[$clave] = simposium_texto($dia[$clave] ?? null,$max,$clave!=='descripcion');
        }
        $actividades = $dia['actividades'] ?? null;
        if (!is_array($actividades) || !array_is_list($actividades) || count($actividades)>50) throw new InvalidArgumentException('Máximo 50 actividades por jornada.');
        $nuevo['actividades'] = [];
        foreach ($actividades as $actividad) {
            if (!is_array($actividad) || count($actividad)!==3 || !array_is_list($actividad)) throw new InvalidArgumentException('Actividad inválida.');
            $hora = simposium_texto($actividad[0],5);
            if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$hora)) throw new InvalidArgumentException('La hora debe tener formato HH:MM.');
            $nuevo['actividades'][] = [$hora,simposium_texto($actividad[1],240),simposium_texto($actividad[2],4000,false)];
            if (++$total > 200) throw new InvalidArgumentException('Máximo 200 actividades en total.');
        }
        $limpio['jornadas'][] = $nuevo;
    }
    if (strlen(json_encode($limpio,JSON_THROW_ON_ERROR))>500000) throw new InvalidArgumentException('El programa supera el tamaño permitido.');
    return $limpio;
}
function simposium_cargar(mysqli $conn): array {
    $resultado = $conn->query('SELECT contenido, revision FROM simposium_contenido WHERE id=1');
    if (!$resultado) throw new RuntimeException('No está disponible la configuración de SIMPOSIUM.');
    $fila = $resultado->fetch_assoc();
    if (!$fila) return ['datos'=>simposium_validar(require __DIR__.'/simposium-default.php'),'revision'=>0];
    return ['datos'=>simposium_validar(json_decode($fila['contenido'],true,64,JSON_THROW_ON_ERROR)),'revision'=>(int)$fila['revision']];
}
function simposium_guardar(mysqli $conn, array $datos, int $revision): void {
    $json = json_encode(simposium_validar($datos),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    if ($revision === 0) {
        $stmt=$conn->prepare('INSERT IGNORE INTO simposium_contenido(id,contenido,revision) VALUES(1,?,1)');
        if (!$stmt) throw new RuntimeException('No se pudo preparar el guardado.');
        $stmt->bind_param('s',$json);
    } else {
        $stmt=$conn->prepare('UPDATE simposium_contenido SET contenido=?,revision=revision+1 WHERE id=1 AND revision=?');
        if (!$stmt) throw new RuntimeException('No se pudo preparar el guardado.');
        $stmt->bind_param('si',$json,$revision);
    }
    if (!$stmt->execute()) throw new RuntimeException('No se pudo guardar. Revisa los permisos de la base de datos.');
    if ($stmt->affected_rows !== 1) throw new RuntimeException('Otro administrador guardó cambios. Conserva tu texto y recarga antes de volver a editar.');
}
