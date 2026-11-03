<?php
require 'db.php';
require 'auth.php';
maphub_ensure_tables($conn);

$format = strtolower(trim((string) ($_GET['format'] ?? 'excel')));
require 'incidencias_consulta.php';
try { $rows=maphub_consultar_incidencias($conn); }
catch (Throwable $e) { http_response_code(500); exit('No se pudo generar el reporte'); }

maphub_audit($conn, 'exportar_incidencias', 'incidencias', ['format' => $format, 'rows' => count($rows)]);

if ($format === 'pdf') {
    $lines = ["MapHub - Reporte de incidencias", "Generado: " . date('Y-m-d H:i:s'), "Primeras 45 incidencias del filtro; exporta Excel para el listado completo.", ""];
    foreach (array_slice($rows, 0, 45) as $row) {
        $lines[] = sprintf(
            "#%s | %s | Zona %s | Sev. %s | %s",
            $row['id'] ?? '',
            $row['tipo'] ?? 'Incidencia',
            $row['zona'] ?? 'N/D',
            $row['severidad'] ?? 'N/D',
            $row['timestamp'] ?? ''
        );
    }

    $content = "BT /F1 12 Tf 40 790 Td 14 TL ";
    foreach ($lines as $line) {
        $line = iconv('UTF-8', 'Windows-1252//TRANSLIT', $line);
        $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], substr($line, 0, 105));
        $content .= "($safe) Tj T* ";
    }
    $content .= "ET";

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $idx => $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= ($idx + 1) . " 0 obj\n$obj\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="incidencias_maphub.pdf"');
    echo $pdf;
    exit;
}

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="incidencias_maphub.xls"');
echo "\xEF\xBB\xBF";
echo "<table border=\"1\">";
echo "<tr><th>ID</th><th>Fecha</th><th>Tipo</th><th>Zona</th><th>Severidad</th><th>Estado</th><th>Patrulla</th><th>Descripción</th></tr>";
foreach ($rows as $row) {
    echo "<tr>";
    foreach (['id', 'timestamp', 'tipo', 'zona', 'severidad', 'estado', 'patrulla_atendio', 'descripcion'] as $key) {
        echo "<td>" . htmlspecialchars((string) ($row[$key] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";
?>
