<?php
// ============================================================
//  export/excel.php  –  Genera .xlsx con PhpSpreadsheet
//  Llamado desde DashboardController::exportar()
//  La función generarExcel($datos, $filtros) no hace echo HTML,
//  solo envía el archivo al navegador y termina.
// ============================================================
declare(strict_types=1);

// PhpSpreadsheet vía Composer  (ruta relativa a este archivo)
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    die(json_encode([
        'error' => 'PhpSpreadsheet no instalado. Ejecutá: composer require phpoffice/phpspreadsheet'
    ]));
}
require $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill, Font};
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Genera y descarga el Excel de conciliación.
 *
 * @param array $datos   Filas devueltas por ConciliacionModel::obtenerParaExport()
 * @param array $filtros Filtros activos (para el subtítulo)
 */
function generarExcel(array $datos, array $filtros): void
{
    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Conciliación');

    // ── Paleta de colores ─────────────────────────────────────
    $colorHeader     = '1E3A5F';   // azul marino
    $colorConciliado = 'D1FAE5';   // verde claro
    $colorParcial    = 'FEF3C7';   // amarillo claro
    $colorPendiente  = 'FEE2E2';   // rojo claro
    $colorSubtotal   = 'EFF6FF';   // azul muy claro
    $colorTextoHead  = 'FFFFFF';

    // ── Título ────────────────────────────────────────────────
    $sheet->mergeCells('A1:O1');
    $sheet->setCellValue('A1', 'Panel de Conciliación Contable — GestoriaCristianR');
    $sheet->getStyle('A1')->applyFromArray([
        'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF' . $colorHeader]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(28);

    // ── Subtítulo con filtros activos ─────────────────────────
    $subtitulo = 'Exportado: ' . date('d/m/Y H:i');
    $partes = [];
    if ($filtros['fecha_desde']) $partes[] = 'Desde: ' . $filtros['fecha_desde'];
    if ($filtros['fecha_hasta']) $partes[] = 'Hasta: ' . $filtros['fecha_hasta'];
    if ($filtros['tipo'])        $partes[] = 'Tipo: ' . ucfirst($filtros['tipo']);
    if ($filtros['estado'])      $partes[] = 'Estado: ' . ucfirst($filtros['estado']);
    if ($filtros['busqueda'])    $partes[] = 'Búsqueda: "' . $filtros['busqueda'] . '"';
    if ($partes)                 $subtitulo .= '  |  ' . implode('  ·  ', $partes);

    $sheet->mergeCells('A2:O2');
    $sheet->setCellValue('A2', $subtitulo);
    $sheet->getStyle('A2')->applyFromArray([
        'font'      => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF64748B']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    // ── Encabezados de columna ────────────────────────────────
    $headers = [
        'A' => 'Tipo',
        'B' => 'Fecha Emisión',
        'C' => 'Comprobante',
        'D' => 'PV',
        'E' => 'Nro.',
        'F' => 'CUIT Contraparte',
        'G' => 'Nombre / Razón Social',
        'H' => 'Importe Total',
        'I' => 'Neto Gravado',
        'J' => 'IVA',
        'K' => 'Crédito Fiscal',
        'L' => 'Moneda',
        'M' => 'Fecha Banco',
        'N' => 'Comprobante OCR',
        'O' => 'Estado',
    ];

    $fila = 4;
    foreach ($headers as $col => $titulo) {
        $sheet->setCellValue("{$col}{$fila}", $titulo);
    }

    $headRange = "A{$fila}:O{$fila}";
    $sheet->getStyle($headRange)->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['argb' => 'FF' . $colorTextoHead], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . $colorHeader]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
    ]);
    $sheet->getRowDimension($fila)->setRowHeight(22);

    // ── Datos ─────────────────────────────────────────────────
    $fila++;
    $totImporte = $totNeto = $totIva = $totFiscal = 0.0;
    $contEstados = ['conciliado' => 0, 'parcial' => 0, 'pendiente' => 0];

    foreach ($datos as $r) {
        $estado  = $r['estado_conciliacion'] ?? 'pendiente';
        $bgColor = match ($estado) {
            'conciliado' => 'FF' . $colorConciliado,
            'parcial'    => 'FF' . $colorParcial,
            default      => 'FF' . $colorPendiente,
        };

        $sheet->setCellValueExplicit("A{$fila}", ucfirst($r['tipo'] ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValue("B{$fila}", $r['fecha_emision'] ?? '');
        $sheet->setCellValueExplicit("C{$fila}", $r['tipo_comprobante'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValue("D{$fila}", $r['punto_venta'] ?? '');
        $sheet->setCellValueExplicit("E{$fila}", (string)($r['numero_comprobante'] ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("F{$fila}", $r['cuit_contraparte'] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValue("G{$fila}", $r['nombre_contraparte'] ?? '');
        $sheet->setCellValue("H{$fila}", (float)($r['importe_total']  ?? 0));
        $sheet->setCellValue("I{$fila}", (float)($r['neto_gravado']   ?? 0));
        $sheet->setCellValue("J{$fila}", (float)($r['total_iva']      ?? 0));
        $sheet->setCellValue("K{$fila}", (float)($r['credito_fiscal'] ?? 0));
        $sheet->setCellValueExplicit("L{$fila}", $r['moneda'] ?? 'PES', DataType::TYPE_STRING);
        $sheet->setCellValue("M{$fila}", $r['mov_fecha'] ?? '');
        $sheet->setCellValueExplicit("N{$fila}", $r['comp_ruta'] ? basename((string)$r['comp_ruta']) : '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("O{$fila}", ucfirst($estado), DataType::TYPE_STRING);

        // Formato moneda
        foreach (['H', 'I', 'J', 'K'] as $c) {
            $sheet->getStyle("{$c}{$fila}")
                  ->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* \(#,##0.00\)');
        }

        // Color de fila por estado
        $sheet->getStyle("A{$fila}:O{$fila}")->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => $bgColor]],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);

        // Acumuladores
        $totImporte += (float)($r['importe_total']  ?? 0);
        $totNeto    += (float)($r['neto_gravado']   ?? 0);
        $totIva     += (float)($r['total_iva']      ?? 0);
        $totFiscal  += (float)($r['credito_fiscal'] ?? 0);
        $contEstados[$estado] = ($contEstados[$estado] ?? 0) + 1;

        $fila++;
    }

    // ── Fila de totales ───────────────────────────────────────
    $fila++;
    $sheet->mergeCells("A{$fila}:G{$fila}");
    $sheet->setCellValue("A{$fila}", 'TOTALES (' . count($datos) . ' registros)');
    $sheet->setCellValue("H{$fila}", $totImporte);
    $sheet->setCellValue("I{$fila}", $totNeto);
    $sheet->setCellValue("J{$fila}", $totIva);
    $sheet->setCellValue("K{$fila}", $totFiscal);

    foreach (['H', 'I', 'J', 'K'] as $c) {
        $sheet->getStyle("{$c}{$fila}")
              ->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* \(#,##0.00\)');
    }

    $sheet->getStyle("A{$fila}:O{$fila}")->applyFromArray([
        'font'    => ['bold' => true, 'size' => 10],
        'fill'    => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . $colorSubtotal]],
        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF' . $colorHeader]]],
    ]);

    // ── Fila de conteo por estado ─────────────────────────────
    $fila += 2;
    $sheet->setCellValue("A{$fila}", '🟢 Conciliados: ' . $contEstados['conciliado']);
    $sheet->setCellValue("D{$fila}", '🟡 Parciales: '  . $contEstados['parcial']);
    $sheet->setCellValue("G{$fila}", '🔴 Pendientes: ' . $contEstados['pendiente']);
    $sheet->getStyle("A{$fila}:O{$fila}")->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF475569']],
    ]);

    // ── Anchos de columna ─────────────────────────────────────
    $anchos = [
        'A' => 10, 'B' => 13, 'C' => 18, 'D' => 7, 'E' => 12,
        'F' => 18, 'G' => 38, 'H' => 16, 'I' => 16, 'J' => 14,
        'K' => 14, 'L' => 8,  'M' => 13, 'N' => 22, 'O' => 13,
    ];
    foreach ($anchos as $col => $ancho) {
        $sheet->getColumnDimension($col)->setWidth($ancho);
    }

    // Fijar encabezados al scroll
    $sheet->freezePane('A5');

    // Autofilter
    $sheet->setAutoFilter("A4:O4");

    // ── Enviar al navegador ───────────────────────────────────
    $nombre = 'conciliacion_' . date('Ymd_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
