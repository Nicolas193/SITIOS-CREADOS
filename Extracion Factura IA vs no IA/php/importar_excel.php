<?php
// ============================================================
//  importar_excel.php  –  Gestoría Cristian R
//  Importación de archivos AFIP (CSV/XLSX) y MercadoPago (XLS)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
// BUG FIX #1: rutas rotas — faltaba el "/" después de __DIR__
require_once(__DIR__ . "/../auth.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once(__DIR__ . "/../db.php");   // conexión PDO

// ── Constantes ───────────────────────────────────────────────
define('MAX_FILE_MB',  10);
define('UPLOAD_TMP',   sys_get_temp_dir());
define('TIPOS_VALIDOS', ['afip_compras', 'afip_ventas', 'mp_movimientos']);

// ── Helpers ──────────────────────────────────────────────────
function clean(string $v): string {
    return trim(str_replace(['"', "'"], '', $v));
}

function parseDecimal(?string $v): float {
    if ($v === null || $v === '') return 0.0;
    $v = str_replace(['.', ' '], '', trim($v));   // quitar separador de miles
    $v = str_replace(',', '.', $v);               // coma decimal → punto
    return (float) $v;
}

function parseDate(?string $v): ?string {
    if (!$v || trim($v) === '') return null;
    $v = trim($v);
    // YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
    // DD/MM/YYYY
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m))
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    return null;
}

function parseDateTime(?string $v): ?string {
    if (!$v || trim($v) === '') return null;
    $v = trim($v);
    if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $v))
        return date('Y-m-d H:i:s', strtotime($v));
    return null;
}

/**
 * Lee CSV con separador `;` y encoding ISO-8859-1 → UTF-8
 * Devuelve array de arrays asociativos.
 */
function leerCSV(string $ruta): array {
    $rows = [];
    $handle = fopen($ruta, 'r');
    if (!$handle) return $rows;

    $header = null;
    while (($line = fgets($handle)) !== false) {
        $line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');
        $cols = str_getcsv($line, ';');
        $cols = array_map(fn($c) => trim($c, " \t\n\r\0\x0B\""), $cols);

        // BUG FIX #4: saltar filas que estén completamente vacías
        if (array_filter($cols, fn($c) => $c !== '') === []) continue;

        if ($header === null) {
            $header = $cols;
            continue;
        }
        // Rellena columnas faltantes con ''
        while (count($cols) < count($header)) $cols[] = '';
        $rows[] = array_combine($header, array_slice($cols, 0, count($header)));
    }
    fclose($handle);
    return $rows;
}

/**
 * Lee XLSX con openpyxl via PHP ZipArchive + SimpleXML.
 */
function leerXLSXPhp(string $ruta): array {
    $zip = new ZipArchive();
    if ($zip->open($ruta) === true) {
        $xml    = $zip->getFromName('xl/worksheets/sheet1.xml');
        $shared = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        if ($xml === false) return [];
        return parseXLSXSheet($xml, $shared ?: null);
    }
    return [];
}

function parseXLSXSheet(string $sheetXml, ?string $sharedXml): array {
    // BUG FIX #2: XLSX usa xmlns por defecto; SimpleXML no puede acceder
    // a elementos sin prefijo cuando hay namespace declarado.
    // Solución: eliminar el namespace default antes de parsear.
    $sheetXml = preg_replace('/\s+xmlns(?:=["\'][^"\']*["\'])?/', '', $sheetXml);

    // Cargar cadenas compartidas
    $strings = [];
    if ($sharedXml) {
        $sharedXml = preg_replace('/\s+xmlns(?:=["\'][^"\']*["\'])?/', '', $sharedXml);
        $sx = @simplexml_load_string($sharedXml);
        if ($sx) {
            foreach ($sx->si as $si) {
                $t = '';
                foreach ($si->r as $r) $t .= (string)$r->t;
                if (!count($si->r)) $t = (string)$si->t;
                $strings[] = $t;
            }
        }
    }

    $sx = @simplexml_load_string($sheetXml);
    if (!$sx) return [];

    $rows   = [];
    $header = null;
    foreach ($sx->sheetData->row as $row) {
        $cols = [];
        foreach ($row->c as $cell) {
            $type = (string)$cell['t'];
            $val  = (string)$cell->v;
            if ($type === 's')          $val = $strings[(int)$val] ?? '';
            elseif ($type === 'inlineStr') $val = (string)$cell->is->t;
            $cols[] = $val;
        }
        if ($header === null) { $header = $cols; continue; }
        while (count($cols) < count($header)) $cols[] = '';
        $rows[] = array_combine($header, array_slice($cols, 0, count($header)));
    }
    return $rows;
}

/**
 * Lee XLS XML SpreadsheetML (MercadoPago export).
 */
function leerXLSXML(string $ruta): array {
    $content = file_get_contents($ruta);
    if ($content === false) return [];
    // Eliminar namespace para simplificar
    $content = preg_replace('/\s+xmlns[^=]*="[^"]*"/', '', $content);
    $content = preg_replace('/\s+ss:[a-zA-Z]+="[^"]*"/', '', $content);
    $content = preg_replace('/<ss:[a-zA-Z]+[^>]*>/', '', $content);
    $content = str_replace('</ss:', '</', $content);

    $sx = @simplexml_load_string($content);
    if (!$sx) return [];

    $rows   = [];
    $header = null;
    foreach ($sx->xpath('//Row') as $row) {
        $cols = [];
        foreach ($row->xpath('Cell/Data') as $d) {
            $cols[] = (string)$d;
        }
        if (empty($cols)) continue;
        if ($header === null) { $header = $cols; continue; }
        while (count($cols) < count($header)) $cols[] = '';
        $rows[] = array_combine($header, array_slice($cols, 0, count($header)));
    }
    return $rows;
}

// ── Procesadores de cada tipo ─────────────────────────────────

function procesarAfipCompras(array $rows, int $importId, int $userId, PDO $pdo): array {
    $ok = $err = 0;
    $sql = "INSERT INTO afip_compras
        (importacion_id, usuario_id,
         fecha_emision, tipo_comprobante, desc_tipo_comprobante,
         punto_venta, numero_comprobante,
         tipo_doc_vendedor, nro_doc_vendedor, denominacion_vendedor,
         moneda, tipo_cambio,
         importe_total, importe_no_gravado, importe_exento,
         credito_fiscal_computable,
         perc_otros_imp_nac, perc_iibb, imp_municipales, perc_iva,
         imp_internos, otros_tributos,
         neto_0, neto_2_5, iva_2_5, neto_5, iva_5,
         neto_10_5, iva_10_5, neto_21, iva_21, neto_27, iva_27,
         total_neto_gravado, total_iva)
       VALUES
        (:iid, :uid,
         :fe, :tc, :dtc,
         :pv, :nc,
         :tdc, :ndc, :dv,
         :mon, :tcamb,
         :tot, :ng, :ex,
         :cfc,
         :poni, :pibb, :imun, :piva,
         :iint, :otro,
         :n0, :n25, :i25, :n5, :i5,
         :n105, :i105, :n21, :i21, :n27, :i27,
         :tng, :tiva)
       ON DUPLICATE KEY UPDATE
         importacion_id = VALUES(importacion_id),
         importe_total  = VALUES(importe_total)";

    $stmt = $pdo->prepare($sql);

    // Mapeo de encabezados AFIP
    $mapFecha = ['Fecha de Emisión','Fecha'];
    $mapTipo  = ['Tipo de Comprobante','Tipo'];
    $mapPV    = ['Punto de Venta'];
    $mapNum   = ['Número de Comprobante','Número Desde'];
    $mapTDoc  = ['Tipo Doc. Vendedor'];
    $mapNDoc  = ['Nro. Doc. Vendedor'];
    $mapDen   = ['Denominación Vendedor'];
    $mapTot   = ['Importe Total','Total'];
    $mapNoGr  = ['Importe No Gravado','No Gravado'];
    $mapEx    = ['Importe Exento','Exento'];
    $mapCFC   = ['Crédito Fiscal Computable','IVA'];
    $mapMon   = ['Moneda Original','Moneda'];
    $mapTC    = ['Tipo de Cambio','Tipo Cambio'];

    $getCol = function(array $row, array $keys): string {
        foreach ($keys as $k) {
            if (isset($row[$k]) && $row[$k] !== '') return $row[$k];
        }
        return '';
    };

    foreach ($rows as $r) {
        try {
            $tipo = clean($getCol($r, $mapTipo));
            $tipoCode = (int) preg_replace('/^(\d+).*/', '$1', $tipo);

            $stmt->execute([
                ':iid'  => $importId,
                ':uid'  => $userId,
                ':fe'   => parseDate($getCol($r, $mapFecha)),
                ':tc'   => $tipoCode ?: null,
                ':dtc'  => $tipo,
                ':pv'   => (int)$getCol($r, $mapPV) ?: null,
                ':nc'   => (int)$getCol($r, $mapNum) ?: null,
                // BUG FIX #3: usaba $r['Tipo Doc. Vendedor'] hardcodeado,
                // ignorando el mapeo $mapTDoc. Corregido con $getCol().
                ':tdc'  => (int)$getCol($r, $mapTDoc) ?: null,
                ':ndc'  => clean($getCol($r, $mapNDoc)) ?: null,
                ':dv'   => clean($getCol($r, $mapDen)) ?: null,
                ':mon'  => clean($getCol($r, $mapMon)) ?: 'PES',
                ':tcamb'=> parseDecimal($getCol($r, $mapTC)) ?: 1.0,
                ':tot'  => parseDecimal($getCol($r, $mapTot)),
                ':ng'   => parseDecimal($getCol($r, $mapNoGr)),
                ':ex'   => parseDecimal($getCol($r, $mapEx)),
                ':cfc'  => parseDecimal($getCol($r, $mapCFC)),
                ':poni' => parseDecimal($r['Importe de Per. o Pagos a Cta. de Otros Imp. Nac.'] ?? ''),
                ':pibb' => parseDecimal($r['Importe de Percepciones de Ingresos Brutos'] ?? ''),
                ':imun' => parseDecimal($r['Importe de Impuestos Municipales'] ?? ''),
                ':piva' => parseDecimal($r['Importe de Percepciones o Pagos a Cuenta de IVA'] ?? ''),
                ':iint' => parseDecimal($r['Importe de Impuestos Internos'] ?? ''),
                ':otro' => parseDecimal($r['Importe Otros Tributos'] ?? ''),
                ':n0'   => parseDecimal($r['Neto Gravado IVA 0%'] ?? ''),
                ':n25'  => parseDecimal($r['Neto Gravado IVA 2,5%'] ?? ''),
                ':i25'  => parseDecimal($r['Importe IVA 2,5%'] ?? ''),
                ':n5'   => parseDecimal($r['Neto Gravado IVA 5%'] ?? ''),
                ':i5'   => parseDecimal($r['Importe IVA 5%'] ?? ''),
                ':n105' => parseDecimal($r['Neto Gravado IVA 10,5%'] ?? ''),
                ':i105' => parseDecimal($r['Importe IVA 10,5%'] ?? ''),
                ':n21'  => parseDecimal($r['Neto Gravado IVA 21%'] ?? ''),
                ':i21'  => parseDecimal($r['Importe IVA 21%'] ?? ''),
                ':n27'  => parseDecimal($r['Neto Gravado IVA 27%'] ?? ''),
                ':i27'  => parseDecimal($r['Importe IVA 27%'] ?? ''),
                ':tng'  => parseDecimal($r['Total Neto Gravado'] ?? ''),
                ':tiva' => parseDecimal($r['Total IVA'] ?? ''),
            ]);
            $ok++;
        } catch (PDOException $e) {
            $err++;
            error_log("afip_compras error row: " . $e->getMessage());
        }
    }
    return ['ok' => $ok, 'err' => $err];
}

function procesarAfipVentas(array $rows, int $importId, int $userId, PDO $pdo): array {
    $ok = $err = 0;
    $sql = "INSERT INTO afip_ventas
        (importacion_id, usuario_id,
         fecha_emision, tipo_comprobante, desc_tipo_comprobante,
         punto_venta, numero_desde, numero_hasta,
         tipo_doc_comprador, nro_doc_comprador, denominacion_comprador,
         fecha_vencimiento_pago,
         moneda, tipo_cambio,
         importe_total, importe_no_gravado, importe_exento,
         perc_otros_imp_nac, perc_iibb, imp_municipales,
         perc_no_categorizados, imp_internos, otros_tributos,
         neto_0, neto_2_5, iva_2_5, neto_5, iva_5,
         neto_10_5, iva_10_5, neto_21, iva_21, neto_27, iva_27,
         total_neto_gravado, total_iva)
       VALUES
        (:iid, :uid,
         :fe, :tc, :dtc,
         :pv, :nd, :nh,
         :tdc, :ndc, :dc,
         :fvp,
         :mon, :tcamb,
         :tot, :ng, :ex,
         :poni, :pibb, :imun,
         :pnc, :iint, :otro,
         :n0, :n25, :i25, :n5, :i5,
         :n105, :i105, :n21, :i21, :n27, :i27,
         :tng, :tiva)
       ON DUPLICATE KEY UPDATE
         importacion_id = VALUES(importacion_id),
         importe_total  = VALUES(importe_total)";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $r) {
        try {
            $tipo = clean($r['Tipo de Comprobante'] ?? $r['Tipo'] ?? '');
            $tipoCode = (int) preg_replace('/^(\d+).*/', '$1', $tipo);

            $stmt->execute([
                ':iid'  => $importId,
                ':uid'  => $userId,
                ':fe'   => parseDate($r['Fecha de Emisión'] ?? $r['Fecha'] ?? ''),
                ':tc'   => $tipoCode ?: null,
                ':dtc'  => $tipo,
                ':pv'   => (int)($r['Punto de Venta'] ?? 0) ?: null,
                ':nd'   => (int)($r['Número de Comprobante'] ?? $r['Número Desde'] ?? 0) ?: null,
                ':nh'   => (int)($r['Número de Comprobante Hasta'] ?? $r['Número Hasta'] ?? 0) ?: null,
                ':tdc'  => (int)($r['Tipo Doc. Comprador'] ?? 0) ?: null,
                ':ndc'  => clean($r['Nro. Doc. Comprador'] ?? '') ?: null,
                ':dc'   => clean($r['Denominación Comprador'] ?? '') ?: null,
                ':fvp'  => parseDate($r['Fecha de Vencimiento del Pago'] ?? ''),
                ':mon'  => clean($r['Moneda Original'] ?? $r['Moneda'] ?? 'PES'),
                ':tcamb'=> parseDecimal($r['Tipo de Cambio'] ?? $r['Tipo Cambio'] ?? '1') ?: 1.0,
                ':tot'  => parseDecimal($r['Importe Total'] ?? $r['Total'] ?? ''),
                ':ng'   => parseDecimal($r['Importe No Gravado'] ?? $r['No Gravado'] ?? ''),
                ':ex'   => parseDecimal($r['Importe Exento'] ?? $r['Exento'] ?? ''),
                ':poni' => parseDecimal($r['Importe de Per. o Pagos a Cta. de Otros Imp. Nac.'] ?? ''),
                ':pibb' => parseDecimal($r['Importe de Percepciones de Ingresos Brutos'] ?? ''),
                ':imun' => parseDecimal($r['Importe de Impuestos Municipales'] ?? ''),
                ':pnc'  => parseDecimal($r['Percepción a No Categorizados'] ?? ''),
                ':iint' => parseDecimal($r['Importe de Impuestos Internos'] ?? ''),
                ':otro' => parseDecimal($r['Importe Otros Tributos'] ?? ''),
                ':n0'   => parseDecimal($r['Neto Gravado IVA 0%'] ?? ''),
                ':n25'  => parseDecimal($r['Neto Gravado IVA 2,5%'] ?? ''),
                ':i25'  => parseDecimal($r['Importe IVA 2,5%'] ?? ''),
                ':n5'   => parseDecimal($r['Neto Gravado IVA 5%'] ?? ''),
                ':i5'   => parseDecimal($r['Importe IVA 5%'] ?? ''),
                ':n105' => parseDecimal($r['Neto Gravado IVA 10,5%'] ?? ''),
                ':i105' => parseDecimal($r['Importe IVA 10,5%'] ?? ''),
                ':n21'  => parseDecimal($r['Neto Gravado IVA 21%'] ?? ''),
                ':i21'  => parseDecimal($r['Importe IVA 21%'] ?? ''),
                ':n27'  => parseDecimal($r['Neto Gravado IVA 27%'] ?? ''),
                ':i27'  => parseDecimal($r['Importe IVA 27%'] ?? ''),
                ':tng'  => parseDecimal($r['Total Neto Gravado'] ?? ''),
                ':tiva' => parseDecimal($r['Total IVA'] ?? ''),
            ]);
            $ok++;
        } catch (PDOException $e) {
            $err++;
            error_log("afip_ventas error row: " . $e->getMessage());
        }
    }
    return ['ok' => $ok, 'err' => $err];
}

function procesarMP(array $rows, int $importId, int $userId, PDO $pdo): array {
    $ok = $err = 0;
    $sql = "INSERT INTO mp_movimientos
        (importacion_id, usuario_id,
         fecha_compra, fecha_acreditacion, fecha_liberacion,
         contraparte_nombre, contraparte_nickname, contraparte_email,
         contraparte_telefono, contraparte_documento,
         item_id, descripcion, referencia_externa, sku, operation_id,
         estado, detalle_estado, tipo_operacion,
         transaction_amount, fee_mp, fee_marketplace,
         costo_envio, descuento_cupon, monto_neto_recibido, monto_devuelto,
         cuotas, medio_pago,
         refund_operator, claim_id, chargeback_id, marketplace)
       VALUES
        (:iid, :uid,
         :fc, :fa, :fl,
         :cn, :cnick, :ce, :ctel, :cdoc,
         :item_id, :desc, :ref, :sku, :oid,
         :est, :dest, :top,
         :ta, :fmp, :fmkt, :env, :cup, :net, :dev,
         :cuotas, :mpago,
         :rop, :cid, :cbid, :mkt)
       ON DUPLICATE KEY UPDATE
         importacion_id      = VALUES(importacion_id),
         transaction_amount  = VALUES(transaction_amount)";

    $stmt = $pdo->prepare($sql);

    $col = function(array $r, string ...$keys): string {
        foreach ($keys as $k) {
            if (isset($r[$k]) && $r[$k] !== '') return $r[$k];
        }
        return '';
    };

    foreach ($rows as $r) {
        try {
            $stmt->execute([
                ':iid'     => $importId,
                ':uid'     => $userId,
                ':fc'      => parseDateTime($col($r, 'Fecha de compra (date_created)')),
                ':fa'      => parseDateTime($col($r, 'Fecha de acreditación (date_approved)')),
                ':fl'      => parseDateTime($col($r, 'Fecha de liquidación del dinero (date_released)')),
                ':cn'      => $col($r, 'Nombre de la contraparte (counterpart_name)') ?: null,
                ':cnick'   => $col($r, 'Nickname de la contraparte (counterpart_nickname)') ?: null,
                ':ce'      => $col($r, 'E-mail de la contraparte (counterpart_email)') ?: null,
                ':ctel'    => $col($r, 'Teléfono de la contraparte (counterpart_phone_number)') ?: null,
                ':cdoc'    => $col($r, 'Documento de la contraparte (buyer_document)') ?: null,
                // BUG FIX: parámetro renombrado de :iid2 → :item_id para evitar confusión
                ':item_id' => $col($r, 'Identificador de producto (item_id)') ?: null,
                ':desc'    => $col($r, 'Descripción de la operación (reason)') ?: null,
                ':ref'     => $col($r, 'Código de referencia (external_reference)') ?: null,
                ':sku'     => $col($r, 'SKU Producto (seller_custom_field)') ?: null,
                ':oid'     => $col($r, 'Número de operación de Mercado Pago (operation_id)') ?: null,
                ':est'     => $col($r, 'Estado de la operación (status)') ?: null,
                ':dest'    => $col($r, 'Detalle del estado de la operación (status_detail)') ?: null,
                ':top'     => $col($r, 'Tipo de operación (operation_type)') ?: null,
                ':ta'      => parseDecimal($col($r, 'Valor del producto (transaction_amount)')),
                ':fmp'     => parseDecimal($col($r, 'Tarifa de Mercado Pago (mercadopago_fee)')),
                ':fmkt'    => parseDecimal($col($r, 'Comisión por uso de plataforma de terceros (marketplace_fee)')),
                ':env'     => parseDecimal($col($r, 'Costo de envío (shipping_cost)')),
                ':cup'     => parseDecimal($col($r, 'Descuento a tu contraparte (coupon_fee)')),
                ':net'     => parseDecimal($col($r, 'Monto recibido (net_received_amount)')),
                ':dev'     => parseDecimal($col($r, 'Monto devuelto (amount_refunded)')),
                ':cuotas'  => (int)$col($r, 'Cuotas (installments)') ?: null,
                ':mpago'   => $col($r, 'Medio de pago (payment_type)') ?: null,
                ':rop'     => $col($r, 'Operador que devolvió dinero (refund_operator)') ?: null,
                ':cid'     => $col($r, 'Número de reclamo (claim_id)') ?: null,
                ':cbid'    => $col($r, 'Número de contracargo (chargeback_id)') ?: null,
                ':mkt'     => $col($r, 'Plataforma (marketplace)') ?: null,
            ]);
            $ok++;
        } catch (PDOException $e) {
            $err++;
            error_log("mp_movimientos error row: " . $e->getMessage());
        }
    }
    return ['ok' => $ok, 'err' => $err];
}

// ── PROCESAMIENTO POST ────────────────────────────────────────
$resultado = null;
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo    = $_POST['tipo_importacion'] ?? '';
    $cuit    = preg_replace('/[^0-9]/', '', $_POST['cuit'] ?? '');
    $periodo = preg_replace('/[^0-9]/', '', $_POST['periodo'] ?? '');

    // Validaciones básicas
    if (!in_array($tipo, TIPOS_VALIDOS))
        $errors[] = 'Tipo de importación inválido.';
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK)
        $errors[] = 'Error al recibir el archivo. Verificá que no supere ' . MAX_FILE_MB . ' MB.';

    if (empty($errors)) {
        $file   = $_FILES['archivo'];
        $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $sizeMB = $file['size'] / 1024 / 1024;

        if ($sizeMB > MAX_FILE_MB)
            $errors[] = "El archivo supera el límite de " . MAX_FILE_MB . " MB.";
        if (!in_array($ext, ['csv','xlsx','xls']))
            $errors[] = "Extensión .$ext no permitida. Usá CSV, XLSX o XLS.";
    }

    if (empty($errors)) {
        $tmpPath  = $file['tmp_name'];
        $fileName = basename($file['name']);

        // ── LOG INICIAL ──
        $pdo->beginTransaction();
        try {
            $stLog = $pdo->prepare("INSERT INTO importaciones_log
                (usuario_id, tipo_importacion, cuit, periodo, nombre_archivo, estado, ip)
                VALUES (:uid, :tipo, :cuit, :per, :arch, 'procesando', :ip)");
            $stLog->execute([
                ':uid'  => $_SESSION['user_id'],
                ':tipo' => $tipo,
                ':cuit' => $cuit ?: null,
                ':per'  => $periodo ?: null,
                ':arch' => $fileName,
                ':ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            $importId = (int)$pdo->lastInsertId();

            // ── LEER ARCHIVO ──
            $rows = [];
            if ($ext === 'csv') {
                $rows = leerCSV($tmpPath);
            } elseif ($ext === 'xlsx') {
                $rows = leerXLSXPhp($tmpPath);
            } elseif ($ext === 'xls') {
                $head = file_get_contents($tmpPath, false, null, 0, 10);
                if (str_starts_with(trim($head), '<?xml') || str_starts_with(trim($head), '<')) {
                    $rows = leerXLSXML($tmpPath);
                } else {
                    throw new RuntimeException("El archivo .xls binario (BIFF) no está soportado. Por favor exportalo a XLSX o CSV.");
                }
            }

            if (empty($rows))
                throw new RuntimeException("No se encontraron datos en el archivo. Verificá que el formato sea correcto.");

            // ── INSERTAR ──
            // BUG FIX #5: agregado default para evitar UnhandledMatchError
            $res = match($tipo) {
                'afip_compras'   => procesarAfipCompras($rows, $importId, (int)$_SESSION['user_id'], $pdo),
                'afip_ventas'    => procesarAfipVentas($rows,  $importId, (int)$_SESSION['user_id'], $pdo),
                'mp_movimientos' => procesarMP($rows,          $importId, (int)$_SESSION['user_id'], $pdo),
                default          => throw new RuntimeException("Tipo de importación no reconocido: $tipo"),
            };

            // ── ACTUALIZAR LOG ──
            $estado = $res['err'] > 0 ? 'con_errores' : 'completado';
            $pdo->prepare("UPDATE importaciones_log SET
                total_registros=:tot, registros_ok=:ok, registros_error=:err, estado=:est
                WHERE id=:id")->execute([
                ':tot' => count($rows),
                ':ok'  => $res['ok'],
                ':err' => $res['err'],
                ':est' => $estado,
                ':id'  => $importId,
            ]);

            $pdo->commit();
            $resultado = [
                'tipo'   => $tipo,
                'archivo'=> $fileName,
                'total'  => count($rows),
                'ok'     => $res['ok'],
                'err'    => $res['err'],
                'estado' => $estado,
                'id'     => $importId,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            if (isset($importId)) {
                $pdo->prepare("UPDATE importaciones_log SET estado='fallido' WHERE id=:id")
                    ->execute([':id' => $importId]);
            }
            $errors[] = "Error al procesar: " . $e->getMessage();
            error_log("importar_excel FATAL: " . $e->getMessage());
        }
    }
}

// ── ÚLTIMAS IMPORTACIONES ─────────────────────────────────────
$historial = [];
try {
    $stHist = $pdo->prepare("
        SELECT il.*, u.username
        FROM   importaciones_log il
        JOIN   usuarios u ON u.id = il.usuario_id
        ORDER  BY il.creado_en DESC
        LIMIT  20");
    $stHist->execute();
    $historial = $stHist->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable) {}

$pagina = 'importar_excel.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Importar Datos | GestoriaCristianR</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,500;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/menu.css">
<style>
/* ── Variables ── */
:root {
  --imp-bg:        #f0f4f8;
  --imp-surface:   #ffffff;
  --imp-border:    #e2e8f0;
  --imp-primary:   #1e40af;
  --imp-primary-l: #3b82f6;
  --imp-success:   #059669;
  --imp-warning:   #d97706;
  --imp-danger:    #dc2626;
  --imp-text:      #1e293b;
  --imp-muted:     #64748b;
  --imp-radius:    12px;
  --imp-shadow:    0 4px 24px rgba(0,0,0,.07);
  --mono:          'JetBrains Mono', monospace;
}

/* ── Layout ── */
.imp-wrapper {
  max-width: 1100px;
  margin: 80px auto 40px;
  padding: 0 20px;
  font-family: 'DM Sans', sans-serif;
  color: var(--imp-text);
}

.imp-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 32px;
}
.imp-header-icon {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #1e40af, #3b82f6);
  border-radius: 14px;
  display: grid; place-items: center;
  color: #fff; font-size: 22px;
  box-shadow: 0 6px 20px rgba(59,130,246,.35);
}
.imp-header h1 { font-size: 1.65rem; font-weight: 700; margin: 0; }
.imp-header p  { margin: 4px 0 0; color: var(--imp-muted); font-size: .93rem; }

/* ── Grid ── */
.imp-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}
@media (max-width: 768px) { .imp-grid { grid-template-columns: 1fr; } }

/* ── Card ── */
.imp-card {
  background: var(--imp-surface);
  border: 1px solid var(--imp-border);
  border-radius: var(--imp-radius);
  box-shadow: var(--imp-shadow);
  overflow: hidden;
}
.imp-card-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--imp-border);
  display: flex; align-items: center; gap: 12px;
  background: #f8fafc;
}
.imp-card-header i { color: var(--imp-primary-l); font-size: 1.1rem; }
.imp-card-header h2 { font-size: 1rem; font-weight: 600; margin: 0; }
.imp-card-body { padding: 24px; }

/* ── Form elements ── */
.form-group { margin-bottom: 20px; }
.form-label {
  display: block; font-size: .85rem; font-weight: 600;
  color: var(--imp-muted); text-transform: uppercase;
  letter-spacing: .05em; margin-bottom: 6px;
}
.form-control {
  width: 100%; padding: 10px 14px;
  border: 1.5px solid var(--imp-border);
  border-radius: 8px; font-size: .95rem;
  font-family: 'DM Sans', sans-serif;
  color: var(--imp-text); background: #fff;
  transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
}
.form-control:focus {
  outline: none;
  border-color: var(--imp-primary-l);
  box-shadow: 0 0 0 3px rgba(59,130,246,.15);
}

/* ── Tipo selector tabs ── */
.tipo-tabs {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 8px; margin-bottom: 20px;
}
.tipo-tab {
  position: relative;
  cursor: pointer;
}
.tipo-tab input[type=radio] {
  position: absolute; opacity: 0; width: 0; height: 0;
}
.tipo-tab label {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  padding: 14px 8px; border: 2px solid var(--imp-border);
  border-radius: 10px; cursor: pointer; font-size: .8rem;
  font-weight: 600; text-align: center; color: var(--imp-muted);
  transition: all .18s; background: #fff;
}
.tipo-tab label i { font-size: 1.4rem; }
.tipo-tab input:checked + label {
  border-color: var(--imp-primary-l);
  background: #eff6ff; color: var(--imp-primary);
  box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}
.tipo-tab label:hover { border-color: #93c5fd; color: var(--imp-primary); }

/* ── Drop zone ── */
.drop-zone {
  border: 2px dashed var(--imp-border);
  border-radius: 10px; padding: 32px 20px;
  text-align: center; cursor: pointer;
  transition: all .2s; background: #fafbfc;
  position: relative;
}
.drop-zone.dragover {
  border-color: var(--imp-primary-l);
  background: #eff6ff;
}
.drop-zone input[type=file] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer;
}
.drop-zone-icon { font-size: 2.2rem; color: #93c5fd; margin-bottom: 10px; }
.drop-zone-text { font-size: .9rem; color: var(--imp-muted); }
.drop-zone-text strong { color: var(--imp-primary); }
.drop-zone-filename {
  display: none; margin-top: 10px; font-size: .88rem;
  font-family: var(--mono); color: var(--imp-success);
  font-weight: 600;
}

/* ── Botón principal ── */
.btn-import {
  width: 100%; padding: 13px;
  background: linear-gradient(135deg, #1e40af, #3b82f6);
  color: #fff; border: none; border-radius: 9px;
  font-size: 1rem; font-weight: 700; cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  display: flex; align-items: center; justify-content: center; gap: 10px;
  transition: opacity .18s, transform .12s;
  box-shadow: 0 4px 14px rgba(59,130,246,.4);
}
.btn-import:hover { opacity: .92; transform: translateY(-1px); }
.btn-import:active { transform: translateY(0); }
.btn-import:disabled { opacity: .55; cursor: not-allowed; transform: none; }

/* ── Alertas ── */
.alert {
  padding: 14px 18px; border-radius: 10px;
  margin-bottom: 24px; font-size: .93rem;
  display: flex; align-items: flex-start; gap: 12px;
}
.alert i { margin-top: 2px; flex-shrink: 0; }
.alert-success { background:#ecfdf5; border:1px solid #6ee7b7; color:#065f46; }
.alert-danger   { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; }
.alert-warning  { background:#fffbeb; border:1px solid #fcd34d; color:#92400e; }

/* ── Resultado detallado ── */
.result-stats {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 12px; margin-top: 16px;
}
.stat-box {
  padding: 12px; border-radius: 8px; text-align: center;
}
.stat-box .stat-val { font-size: 1.6rem; font-weight: 700; font-family: var(--mono); }
.stat-box .stat-lbl { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; margin-top: 2px; }
.stat-box.total  { background:#eff6ff; color:#1e40af; }
.stat-box.ok     { background:#ecfdf5; color:#065f46; }
.stat-box.err    { background:#fef2f2; color:#991b1b; }

/* ── Historial ── */
.historial-table-wrap { overflow-x: auto; }
table.hist {
  width: 100%; border-collapse: collapse;
  font-size: .85rem;
}
table.hist th {
  background: #f8fafc; padding: 10px 14px;
  text-align: left; font-weight: 600;
  color: var(--imp-muted); text-transform: uppercase;
  font-size: .75rem; letter-spacing: .05em;
  border-bottom: 2px solid var(--imp-border);
}
table.hist td {
  padding: 10px 14px; border-bottom: 1px solid var(--imp-border);
  vertical-align: middle;
}
table.hist tr:last-child td { border-bottom: none; }
table.hist tr:hover td { background: #f8fafc; }
.badge {
  display: inline-block; padding: 3px 9px;
  border-radius: 20px; font-size: .75rem; font-weight: 600;
}
.badge-success { background:#d1fae5; color:#065f46; }
.badge-warning { background:#fef3c7; color:#92400e; }
.badge-danger  { background:#fee2e2; color:#991b1b; }
.badge-info    { background:#dbeafe; color:#1e40af; }
.badge-secondary { background:#f1f5f9; color:#475569; }

.tag-tipo {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: .78rem; font-weight: 600; font-family: var(--mono);
  padding: 3px 8px; border-radius: 6px;
}
.tag-compras  { background:#fef9c3; color:#854d0e; }
.tag-ventas   { background:#dcfce7; color:#166534; }
.tag-mp       { background:#ede9fe; color:#4c1d95; }

/* ── Helper / nota ── */
.format-note {
  background: #f0f9ff; border: 1px solid #bae6fd;
  border-radius: 8px; padding: 12px 16px;
  font-size: .82rem; color: #0c4a6e;
  margin-top: 16px; line-height: 1.6;
}
.format-note strong { color: #0369a1; }

/* ── Spinner ── */
.spinner {
  display: none; width: 18px; height: 18px;
  border: 2px solid rgba(255,255,255,.4);
  border-top-color: #fff; border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<?php include __DIR__ . '/../menu.php'; ?>

<div class="imp-wrapper">

  <!-- Header -->
  <div class="imp-header">
    <div class="imp-header-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
    <div>
      <h1>Importar Datos</h1>
      <p>Cargá archivos de AFIP (Compras / Ventas) o MercadoPago para procesar registros en forma masiva</p>
    </div>
  </div>

  <!-- Alertas globales -->
  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <i class="fa-solid fa-circle-exclamation"></i>
    <div>
      <strong>No se pudo procesar el archivo:</strong>
      <ul style="margin:6px 0 0; padding-left:18px;">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($resultado): ?>
  <div class="alert alert-<?= $resultado['estado'] === 'completado' ? 'success' : ($resultado['estado'] === 'con_errores' ? 'warning' : 'danger') ?>">
    <i class="fa-solid fa-<?= $resultado['estado'] === 'completado' ? 'circle-check' : 'triangle-exclamation' ?>"></i>
    <div style="flex:1">
      <strong>
        <?php
          $labels = ['afip_compras'=>'Compras AFIP','afip_ventas'=>'Ventas AFIP','mp_movimientos'=>'MercadoPago'];
          echo $labels[$resultado['tipo']] . ' — ' . htmlspecialchars($resultado['archivo']);
        ?>
      </strong>
      <div class="result-stats">
        <div class="stat-box total"><div class="stat-val"><?= $resultado['total'] ?></div><div class="stat-lbl">Total filas</div></div>
        <div class="stat-box ok">  <div class="stat-val"><?= $resultado['ok'] ?></div>  <div class="stat-lbl">Insertados</div></div>
        <div class="stat-box err"> <div class="stat-val"><?= $resultado['err'] ?></div> <div class="stat-lbl">Con error</div></div>
      </div>
      <?php if ($resultado['err'] > 0): ?>
      <p style="margin:10px 0 0; font-size:.83rem;">Los registros con error pueden ser duplicados ya existentes en la base o filas con formato incorrecto. Revisá el log del servidor para detalles.</p>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="imp-grid">

    <!-- ── Formulario de carga ── -->
    <div class="imp-card">
      <div class="imp-card-header">
        <i class="fa-solid fa-upload"></i>
        <h2>Cargar archivo</h2>
      </div>
      <div class="imp-card-body">
        <form method="POST" enctype="multipart/form-data" id="import-form">

          <!-- Tipo -->
          <div class="form-group">
            <div class="form-label">Tipo de importación</div>
            <div class="tipo-tabs">
              <div class="tipo-tab">
                <input type="radio" name="tipo_importacion" id="t_compras" value="afip_compras"
                  <?= (!$resultado || $resultado['tipo']==='afip_compras') ? 'checked' : '' ?>>
                <label for="t_compras">
                  <i class="fa-solid fa-cart-shopping" style="color:#b45309;"></i>
                  Compras AFIP
                </label>
              </div>
              <div class="tipo-tab">
                <input type="radio" name="tipo_importacion" id="t_ventas" value="afip_ventas"
                  <?= ($resultado && $resultado['tipo']==='afip_ventas') ? 'checked' : '' ?>>
                <label for="t_ventas">
                  <i class="fa-solid fa-receipt" style="color:#166534;"></i>
                  Ventas AFIP
                </label>
              </div>
              <div class="tipo-tab">
                <input type="radio" name="tipo_importacion" id="t_mp" value="mp_movimientos"
                  <?= ($resultado && $resultado['tipo']==='mp_movimientos') ? 'checked' : '' ?>>
                <label for="t_mp">
                  <i class="fa-brands fa-cc-mastercard" style="color:#4c1d95;"></i>
                  MercadoPago
                </label>
              </div>
            </div>
          </div>

          <!-- CUIT + Período en fila -->
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div class="form-group">
              <label class="form-label" for="cuit">CUIT contribuyente</label>
              <input type="text" id="cuit" name="cuit" class="form-control"
                placeholder="20-12345678-9" maxlength="13"
                value="<?= htmlspecialchars($_POST['cuit'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="periodo">Período (AAAAMM)</label>
              <input type="text" id="periodo" name="periodo" class="form-control"
                placeholder="202601" maxlength="6" pattern="[0-9]{6}"
                value="<?= htmlspecialchars($_POST['periodo'] ?? '') ?>">
            </div>
          </div>

          <!-- Archivo -->
          <div class="form-group">
            <div class="form-label">Archivo</div>
            <div class="drop-zone" id="drop-zone">
              <input type="file" name="archivo" id="archivo-input"
                     accept=".csv,.xlsx,.xls" required>
              <div class="drop-zone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
              <div class="drop-zone-text">
                <strong>Hacé clic o arrastrá</strong> tu archivo aquí<br>
                <span style="font-size:.8rem;">CSV · XLSX · XLS  — máx. <?= MAX_FILE_MB ?>MB</span>
              </div>
              <div class="drop-zone-filename" id="drop-filename"></div>
            </div>
          </div>

          <button type="submit" class="btn-import" id="btn-submit">
            <div class="spinner" id="spinner"></div>
            <i class="fa-solid fa-database" id="btn-icon"></i>
            <!-- BUG FIX #6: texto en span propio para manipulación segura -->
            <span id="btn-label">Procesar e importar</span>
          </button>

          <div class="format-note">
            <strong>Formatos aceptados:</strong><br>
            • <strong>AFIP Compras/Ventas:</strong> CSV oficial descargado de AFIP (separador <code>;</code>, encoding ISO-8859-1) o XLSX exportado desde la plataforma.<br>
            • <strong>MercadoPago:</strong> XLS / colección descargada desde "Actividad" en el portal.<br>
            Los registros duplicados se actualizan automáticamente.
          </div>

        </form>
      </div>
    </div>

    <!-- ── Info lateral ── -->
    <div style="display:flex; flex-direction:column; gap:20px;">

      <!-- Qué columnas espera -->
      <div class="imp-card">
        <div class="imp-card-header">
          <i class="fa-solid fa-table-columns"></i>
          <h2>Columnas requeridas por tipo</h2>
        </div>
        <div class="imp-card-body" style="font-size:.82rem; line-height:1.7;">
          <p style="margin:0 0 8px; font-weight:600; color:#b45309;"><i class="fa-solid fa-cart-shopping"></i> Compras AFIP</p>
          <code style="font-family:var(--mono); color:#475569;">Fecha de Emisión · Tipo · Punto de Venta · Número Desde · Nro. Doc. Vendedor · Denominación · Total · IVA · …</code>
          <hr style="border:none; border-top:1px solid var(--imp-border); margin:12px 0;">
          <p style="margin:0 0 8px; font-weight:600; color:#166534;"><i class="fa-solid fa-receipt"></i> Ventas AFIP</p>
          <code style="font-family:var(--mono); color:#475569;">Fecha de Emisión · Tipo · Punto de Venta · Número Desde · Número Hasta · Nro. Doc. Comprador · Denominación · Total · IVA · …</code>
          <hr style="border:none; border-top:1px solid var(--imp-border); margin:12px 0;">
          <p style="margin:0 0 8px; font-weight:600; color:#4c1d95;"><i class="fa-brands fa-cc-mastercard"></i> MercadoPago</p>
          <code style="font-family:var(--mono); color:#475569;">date_created · date_approved · operation_id · status · transaction_amount · net_received_amount · …</code>
        </div>
      </div>

      <!-- Stats rápidas -->
      <div class="imp-card">
        <div class="imp-card-header">
          <i class="fa-solid fa-chart-bar"></i>
          <h2>Resumen de la base</h2>
        </div>
        <div class="imp-card-body">
          <?php
            $counts = [];
            foreach (['afip_compras'=>'Compras AFIP','afip_ventas'=>'Ventas AFIP','mp_movimientos'=>'Movim. MP'] as $tbl => $label) {
                try {
                    $c = $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
                    $counts[$label] = $c;
                } catch (Throwable) { $counts[$label] = '—'; }
            }
          ?>
          <div style="display:grid; grid-template-columns:1fr; gap:10px;">
            <?php foreach ($counts as $label => $cnt): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#f8fafc; border-radius:8px;">
              <span style="font-size:.88rem; color:var(--imp-muted);"><?= $label ?></span>
              <span style="font-family:var(--mono); font-weight:700; color:var(--imp-primary); font-size:1.05rem;"><?= number_format((int)$cnt) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Historial ── -->
  <?php if (!empty($historial)): ?>
  <div class="imp-card" style="margin-top:28px;">
    <div class="imp-card-header">
      <i class="fa-solid fa-clock-rotate-left"></i>
      <h2>Últimas importaciones</h2>
    </div>
    <div class="imp-card-body" style="padding:0;">
      <div class="historial-table-wrap">
        <table class="hist">
          <thead>
            <tr>
              <th>#</th>
              <th>Tipo</th>
              <th>Archivo</th>
              <th>CUIT</th>
              <th>Período</th>
              <th style="text-align:center;">Total</th>
              <th style="text-align:center;">OK</th>
              <th style="text-align:center;">Error</th>
              <th>Estado</th>
              <th>Usuario</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($historial as $h):
              $tipoMap = [
                'afip_compras'   => ['tag-compras','Compras AFIP'],
                'afip_ventas'    => ['tag-ventas','Ventas AFIP'],
                'mp_movimientos' => ['tag-mp','MercadoPago'],
              ];
              [$tagClass, $tagLabel] = $tipoMap[$h['tipo_importacion']] ?? ['badge-secondary', $h['tipo_importacion']];

              $badgeMap = [
                'completado'  => 'badge-success',
                'con_errores' => 'badge-warning',
                'fallido'     => 'badge-danger',
                'procesando'  => 'badge-info',
              ];
              $badgeClass = $badgeMap[$h['estado']] ?? 'badge-secondary';
            ?>
            <tr>
              <td style="font-family:var(--mono); color:var(--imp-muted);">#<?= $h['id'] ?></td>
              <td><span class="tag-tipo <?= $tagClass ?>"><?= $tagLabel ?></span></td>
              <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-family:var(--mono); font-size:.78rem;" title="<?= htmlspecialchars($h['nombre_archivo']) ?>">
                <?= htmlspecialchars(mb_strimwidth($h['nombre_archivo'], 0, 30, '…')) ?>
              </td>
              <td style="font-family:var(--mono); font-size:.82rem;"><?= htmlspecialchars($h['cuit'] ?? '—') ?></td>
              <td style="font-family:var(--mono); font-size:.82rem;"><?= htmlspecialchars($h['periodo'] ?? '—') ?></td>
              <td style="text-align:center; font-weight:600;"><?= number_format((int)$h['total_registros']) ?></td>
              <td style="text-align:center; color:var(--imp-success); font-weight:600;"><?= number_format((int)$h['registros_ok']) ?></td>
              <td style="text-align:center; color:<?= (int)$h['registros_error'] > 0 ? 'var(--imp-danger)' : 'var(--imp-muted)' ?>; font-weight:600;"><?= (int)$h['registros_error'] ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($h['estado']) ?></span></td>
              <td style="font-size:.82rem;"><?= htmlspecialchars($h['username']) ?></td>
              <td style="font-size:.8rem; color:var(--imp-muted); white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($h['creado_en'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /imp-wrapper -->

<script>
// ── Drop zone ──────────────────────────────────────────────
const zone    = document.getElementById('drop-zone');
const input   = document.getElementById('archivo-input');
const fname   = document.getElementById('drop-filename');
const form    = document.getElementById('import-form');
const btnSub  = document.getElementById('btn-submit');
const spinner = document.getElementById('spinner');
const btnIcon = document.getElementById('btn-icon');
// BUG FIX #6: referencia directa al span del label en vez de childNodes[2]
const btnLabel = document.getElementById('btn-label');

function showFile(file) {
    if (!file) return;
    fname.textContent = '📎 ' + file.name + '  (' + (file.size / 1024).toFixed(1) + ' KB)';
    fname.style.display = 'block';
    zone.style.borderColor = 'var(--imp-success)';
    zone.style.background  = '#f0fdf4';
}

input.addEventListener('change', () => showFile(input.files[0]));

zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', ()  => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        const dt = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        input.files = dt.files;
        showFile(input.files[0]);
    }
});

// ── Submit ─────────────────────────────────────────────────
form.addEventListener('submit', () => {
    btnSub.disabled        = true;
    spinner.style.display  = 'block';
    btnIcon.style.display  = 'none';
    btnLabel.textContent   = 'Procesando…';
});

// ── CUIT autoformat XX-XXXXXXXX-X ─────────────────────────
document.getElementById('cuit').addEventListener('input', function() {
    let v = this.value.replace(/[^0-9]/g, '');
    if (v.length > 2  && v.length <= 10) v = v.slice(0,2) + '-' + v.slice(2);
    if (v.length > 11 && v.length <= 13) v = v.slice(0,11) + '-' + v.slice(11);
    this.value = v.slice(0, 13);
});
</script>
</body>
</html>