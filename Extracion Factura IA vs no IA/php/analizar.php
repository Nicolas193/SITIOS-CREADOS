<?php
// ============================================================
//  analizar.php  v2.0  — OpenAI GPT-4o / GPT-4o-mini
//  Flujo imágenes : Paso 1 OCR (gpt-4o vision) → Paso 2 JSON (gpt-4o-mini)
//  Flujo PDF      : extraer texto PHP puro      → Paso 2 JSON (gpt-4o-mini)
//  response_format: json_object garantiza JSON válido siempre
// ============================================================

@ini_set('max_execution_time', '300');
@ini_set('memory_limit',       '512M');
@ini_set('display_errors',     '0');
@ini_set('log_errors',         '1');
@set_time_limit(300);
error_reporting(E_ALL);
ob_start();

header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level()) ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error PHP fatal: ' . $e['message']]);
    }
});

// ════════════════════════════════════════════════════════════
//  CONFIGURACION
// ════════════════════════════════════════════════════════════
require_once dirname(__DIR__) . '/env.php';
define('OPENAI_KEY',      getenv('OPENAI_KEY') ?: '');
define('OPENAI_URL',      'https://api.openai.com/v1/chat/completions');
define('OPENAI_MODEL',    'gpt-4o-mini');   // texto / paso 2
define('OPENAI_MODEL_VIS','gpt-4o');        // imágenes / paso 1 OCR
define('UPLOAD_DIR',      __DIR__ . '/uploads/temp/');
define('MAX_MB',          20);
define('CURL_TIMEOUT',    240);

// Garbage collector — borrar temporales > 2h
if (is_dir(UPLOAD_DIR)) {
    foreach (glob(UPLOAD_DIR . 'ocr_*') ?: [] as $f) {
        if (is_file($f) && (time() - filemtime($f)) >= 7200) @unlink($f);
    }
}

// ════════════════════════════════════════════════════════════
//  EXTRACCIÓN DE TEXTO DE PDF (PHP puro, sin dependencias)
// ════════════════════════════════════════════════════════════
function extraerTextoPDFPuro(string $ruta): string
{
    // Intento A: pdftotext del sistema
    if (function_exists('exec')) {
        $test = []; @exec('pdftotext -v 2>&1', $test, $ret);
        if ($ret === 0 || str_contains(implode(' ', $test), 'pdftotext')) {
            $tmp = tempnam(sys_get_temp_dir(), 'ptxt_');
            @exec('pdftotext -layout -enc UTF-8 '
                . escapeshellarg($ruta) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null', $o, $r);
            if ($r === 0 && is_file($tmp)) {
                $text = file_get_contents($tmp); @unlink($tmp);
                if (strlen(trim($text)) > 80) return $text;
            }
        }
    }

    // Intento B: parsing binario PHP
    $raw  = @file_get_contents($ruta) ?: '';
    $text = '';

    if (preg_match_all('/BT\s*(.*?)\s*ET/s', $raw, $bloques)) {
        foreach ($bloques[1] as $bloque) {
            if (preg_match_all('/\(([^)\\\]*(?:\\.[^)\\\]*)*)\)\s*(?:Tj|TJ|\'|\")/s', $bloque, $ss)) {
                foreach ($ss[1] as $s) {
                    $s = stripcslashes($s);
                    $s = preg_replace('/[^\x20-\x7E\xC0-\xFF\n]/', ' ', $s);
                    if (strlen(trim($s)) > 0) $text .= $s . ' ';
                }
                $text .= "\n";
            }
            if (preg_match_all('/\[([^\]]+)\]\s*TJ/s', $bloque, $arrs)) {
                foreach ($arrs[1] as $arr) {
                    preg_match_all('/\(([^)]*)\)/', $arr, $pts);
                    foreach ($pts[1] as $p) {
                        $text .= preg_replace('/[^\x20-\x7E]/', ' ', stripcslashes($p));
                    }
                    $text .= "\n";
                }
            }
        }
    }

    // Intento C: Hex strings UTF-16BE
    if (strlen(trim($text)) < 200) {
        if (preg_match_all('/<([0-9A-Fa-f]{4,})>\s*(?:Tj|TJ)/m', $raw, $hxs)) {
            foreach ($hxs[1] as $hex) {
                if (strlen($hex) % 4 === 0) {
                    $dec = '';
                    for ($i = 0; $i < strlen($hex); $i += 4) {
                        $cp = hexdec(substr($hex, $i, 4));
                        if ($cp >= 0x0020 && $cp < 0xFFFE) $dec .= mb_chr($cp, 'UTF-8');
                    }
                    if (trim($dec)) $text .= $dec . "\n";
                }
            }
        }
    }

    return trim($text);
}

// ════════════════════════════════════════════════════════════
//  LLAMADA A OPENAI (genérica)
// ════════════════════════════════════════════════════════════
function llamarOpenAI(array $content, string $model, int $maxTokens, bool $forceJson = false): string
{
    $body = [
        'model'       => $model,
        'max_tokens'  => $maxTokens,
        'temperature' => 0.05,
        'messages'    => [['role' => 'user', 'content' => $content]],
    ];
    if ($forceJson) {
        $body['response_format'] = ['type' => 'json_object'];
    }

    $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) throw new Exception('Error serializando payload: ' . json_last_error_msg());

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => OPENAI_URL,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_KEY,
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => CURL_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $resp    = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr)     throw new Exception('Error de red al conectar con OpenAI: ' . $curlErr);
    if ($code === 401) throw new Exception('API Key de OpenAI inválida o revocada (401). Actualizá la key en analizar.php.');
    if ($code === 429) throw new Exception('Límite de requests de OpenAI alcanzado (429). Esperá unos segundos e intentá de nuevo.');
    if ($code === 413) throw new Exception('Imagen demasiado grande para OpenAI (413). Reducí el tamaño o la resolución.');

    if ($code !== 200) {
        $err = json_decode($resp, true);
        $msg = $err['error']['message'] ?? "HTTP $code";
        error_log("analizar.php OpenAI $code [$model]: " . substr($resp ?? '', 0, 500));
        throw new Exception("OpenAI respondió con error $code: $msg");
    }

    $decoded = json_decode($resp, true);
    if ($decoded === null) throw new Exception('La respuesta de OpenAI no es JSON válido.');

    $raw = $decoded['choices'][0]['message']['content'] ?? null;
    if ($raw === null) throw new Exception('OpenAI devolvió una respuesta sin contenido.');

    // finish_reason check
    $finish = $decoded['choices'][0]['finish_reason'] ?? '';
    if ($finish === 'length') {
        error_log("analizar.php: OpenAI cortó la respuesta por longitud (max_tokens=$maxTokens)");
    }

    if (is_array($raw)) {
        $txt = '';
        foreach ($raw as $b) { if (isset($b['text'])) $txt .= $b['text']; }
        $raw = $txt;
    }

    $content = trim((string) $raw);
    if ($content === '') throw new Exception('OpenAI devolvió una respuesta vacía.');
    return $content;
}

// ════════════════════════════════════════════════════════════
//  PROMPT ESTRUCTURADOR DE COMPROBANTES
// ════════════════════════════════════════════════════════════
function getPromptComprobante(): string
{
    return 'Sos un sistema experto en contabilidad argentina. '
         . 'Analizá el comprobante fiscal y devolvé ÚNICAMENTE un objeto JSON válido con la estructura indicada. '
         . 'Sin markdown, sin texto antes ni después. Montos como float. Dato inexistente = null.' . "\n\n"
         . 'ESTRUCTURA JSON REQUERIDA:' . "\n"
         . '{"tipo_comprobante":"Factura A|Factura B|Factura C|Factura M|Ticket Factura A|Ticket Factura B|Ticket Factura C|Ticket|Nota de Credito A|Nota de Credito B|Nota de Debito A|Nota de Debito B|Recibo|Extracto bancario",'
         . '"punto_venta":null,"numero_comprobante":null,'
         . '"fecha_emision":"YYYY-MM-DD","fecha_vencimiento":"YYYY-MM-DD",'
         . '"cae":"14 digitos o null",'
         . '"emisor_nombre":"Razon Social","emisor_cuit":"11 digitos sin guiones",'
         . '"emisor_iibb":"numero o null","emisor_direccion":"direccion o null",'
         . '"emisor_condicion_iva":"Responsable Inscripto|Monotributista|Exento|null",'
         . '"cliente_nombre":"nombre o null","cliente_cuit":"11 digitos sin guiones o null",'
         . '"cliente_condicion_iva":"Responsable Inscripto|Consumidor Final|Monotributista|Exento|null",'
         . '"cliente_direccion":"direccion o null",'
         . '"subtotal_bruto":null,"descuentos":null,"subtotal_neto":null,"no_gravado":null,"exento":null,'
         . '"iva_porcentaje":"21|10.5|27|0|null","iva_importe":null,"iva_21":null,"iva_10_5":null,"iva_27":null,'
         . '"iibb_importe":null,"imp_internos":null,"imp_dioxido_carbono":null,"imp_combustible_liq":null,'
         . '"itc":null,"tasas":null,"otros_impuestos":null,'
         . '"total_sin_iva":null,"total":null,'
         . '"metodo_pago":"Efectivo|Tarjeta|Transferencia|Mercado Pago|Cuenta Corriente|Cheque|null",'
         . '"moneda":"ARS|USD|EUR",'
         . '"tipo_documento":"Comprobante fiscal|Extracto bancario|Recibo|Nota de Credito|Nota de Debito",'
         . '"observaciones":"dato extra o null"}' . "\n\n"
         . 'REGLAS:' . "\n"
         . '- tipo_comprobante: detectalo por FACTURA A/B/C, TICKET FACTURA, COD. 001/006/011, etc.' . "\n"
         . '- punto_venta + numero_comprobante: devolver como enteros (sin ceros a la izquierda).' . "\n"
         . '- cae: exactamente 14 dígitos consecutivos near "CAE".' . "\n"
         . '- emisor_cuit: el primer CUIT con prefijo 20/23/24/27/30/33/34, sin guiones.' . "\n"
         . '- cliente_cuit: el segundo CUIT distinto al del emisor.' . "\n"
         . '- Combustibles YPF/Shell/Axion: extraer itc, imp_dioxido_carbono, imp_combustible_liq.' . "\n"
         . '- IVA 21% → iva_21, IVA 10.5% → iva_10_5, suma total → iva_importe.' . "\n"
         . '- moneda: ARS por defecto. Monto 0.00 = null.' . "\n"
         . '- RESPONDE SOLO EL JSON. CERO TEXTO ADICIONAL.';
}

// ════════════════════════════════════════════════════════════
//  ANALIZAR DESDE TEXTO (PDF o resultado OCR)
// ════════════════════════════════════════════════════════════
function analizarDesdeTexto(string $texto, string $modo): string
{
    $prompt  = getPromptComprobante();
    $cuerpo  = mb_substr(trim($texto), 0, 20000);

    return llamarOpenAI(
        [['type' => 'text', 'text' => $prompt . "\n\nTEXTO DEL COMPROBANTE:\n---\n" . $cuerpo . "\n---"]],
        OPENAI_MODEL,
        4096,
        true   // forceJson = true → garantiza JSON válido
    );
}

// ════════════════════════════════════════════════════════════
//  ANALIZAR DESDE IMAGEN — DOS PASOS
//  Paso 1: OCR con gpt-4o vision (texto plano)
//  Paso 2: Estructurar JSON con gpt-4o-mini (más barato)
// ════════════════════════════════════════════════════════════
function analizarDesdeImagen(string $path, string $mime): string
{
    $raw = file_get_contents($path);
    if ($raw === false) throw new Exception('No se pudo leer el archivo para enviar a OpenAI.');

    $b64     = base64_encode($raw);
    $dataUri = "data:{$mime};base64,{$b64}";

    // ── Paso 1: OCR puro ─────────────────────────────────────
    $promptOCR = 'Sos un sistema OCR especializado en comprobantes fiscales argentinos. '
               . 'Transcribí FIELMENTE todo el texto visible en la imagen, incluyendo números, '
               . 'fechas, importes, CUITs, CAE, descripciones y encabezados. '
               . 'Respetá el orden de arriba hacia abajo. '
               . 'NO interpretes ni omitas nada. Devolvé SOLO el texto transcripto.';

    $textoOCR = llamarOpenAI(
        [
            ['type' => 'image_url', 'image_url' => ['url' => $dataUri, 'detail' => 'high']],
            ['type' => 'text', 'text' => $promptOCR],
        ],
        OPENAI_MODEL_VIS,
        2048,
        false  // OCR es texto plano, no JSON
    );

    if (strlen(trim($textoOCR)) < 30) {
        throw new Exception('No se pudo extraer texto de la imagen. Verificá que la imagen sea legible y no esté rotada.');
    }

    // ── Paso 2: Estructurar JSON ─────────────────────────────
    return analizarDesdeTexto($textoOCR, 'Imagen OCR → JSON');
}

// ════════════════════════════════════════════════════════════
//  PARSEAR JSON (con múltiples fallbacks)
// ════════════════════════════════════════════════════════════
function parsearJSON(string $raw): ?array
{
    // Limpiar markdown
    $s = preg_replace('/^```(?:json)?\s*/mi', '', trim($raw));
    $s = preg_replace('/\s*```\s*$/m', '', $s);
    $s = trim($s);

    // Intento 1: JSON directo
    $data = json_decode($s, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) return $data;

    // Intento 2: Extraer bloque JSON de texto circundante
    if (preg_match('/(\{[\s\S]+\})/s', $s, $m)) {
        $data = json_decode($m[1], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) return $data;
    }

    error_log('analizar.php parsearJSON fallo. Primeros 500 chars: ' . substr($raw, 0, 500));
    return null;
}

// ════════════════════════════════════════════════════════════
//  NORMALIZAR — limpia y tipifica todos los campos
// ════════════════════════════════════════════════════════════
function normalizar(array $ia, string $nombre, string $path, string $modoAnalisis): array
{
    $num = static function ($v) {
        if ($v === null || $v === false || $v === '') return null;
        $s = preg_replace('/[^\d.,\-]/', '', str_replace([' ', "\xc2\xa0"], '', (string) $v));
        if ($s === '' || $s === '-') return null;
        if (preg_match('/\d\.\d{3},/', $s))                              $s = str_replace(['.', ','], ['', '.'], $s);
        elseif (substr_count($s, ',') === 1 && substr_count($s, '.') === 0) $s = str_replace(',', '.', $s);
        elseif (substr_count($s, '.') > 1)                               $s = str_replace('.', '', $s);
        $f = (float) $s;
        return ($f > 0.0 && $f < 1_000_000_000.0) ? round($f, 2) : null;
    };
    $str = static function ($v) {
        if ($v === null || $v === false) return null;
        $s = trim((string) $v);
        return ($s !== '' && strtolower($s) !== 'null') ? $s : null;
    };
    $ent = static function ($v) {
        if ($v === null || $v === '') return null;
        $i = (int) preg_replace('/\D/', '', (string) $v);
        return $i > 0 ? $i : null;
    };
    $cuit = static function ($v) {
        if ($v === null) return null;
        $c = preg_replace('/\D/', '', (string) $v);
        if (strlen($c) !== 11) return null;
        $p = (int) substr($c, 0, 2);
        return in_array($p, [20, 23, 24, 27, 30, 33, 34], true) ? $c : null;
    };
    $fecha = static function ($v) {
        if ($v === null || $v === '') return null;
        $s = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $p))
            return checkdate((int)$p[2], (int)$p[3], (int)$p[1]) ? $s : null;
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $s, $p))
            return checkdate((int)$p[2], (int)$p[1], (int)$p[3])
                ? sprintf('%04d-%02d-%02d', $p[3], $p[2], $p[1]) : null;
        return null;
    };
    $cae = static function ($v) {
        if ($v === null) return null;
        $c = preg_replace('/\D/', '', (string) $v);
        return strlen($c) === 14 ? $c : null;
    };

    return [
        'original_name'         => $nombre,
        'temp_path'             => $path,
        'raw_text'              => $modoAnalisis,

        'tipo_comprobante'      => $str($ia['tipo_comprobante']      ?? null),
        'punto_venta'           => $ent($ia['punto_venta']           ?? null),
        'numero_comprobante'    => $ent($ia['numero_comprobante']    ?? null),
        'fecha_emision'         => $fecha($ia['fecha_emision']       ?? null),
        'fecha_vencimiento'     => $fecha($ia['fecha_vencimiento']   ?? null),
        'cae'                   => $cae($ia['cae']                   ?? null),

        'emisor_nombre'         => $str($ia['emisor_nombre']         ?? null),
        'emisor_cuit'           => $cuit($ia['emisor_cuit']          ?? null),
        'emisor_iibb'           => $str($ia['emisor_iibb']           ?? null),
        'emisor_direccion'      => $str($ia['emisor_direccion']      ?? null),
        'emisor_condicion_iva'  => $str($ia['emisor_condicion_iva']  ?? null),

        'cliente_nombre'        => $str($ia['cliente_nombre']        ?? null),
        'cliente_cuit'          => $cuit($ia['cliente_cuit']         ?? null),
        'cliente_condicion_iva' => $str($ia['cliente_condicion_iva'] ?? null),
        'cliente_direccion'     => $str($ia['cliente_direccion']     ?? null),

        'subtotal_bruto'        => $num($ia['subtotal_bruto']        ?? null),
        'descuentos'            => $num($ia['descuentos']            ?? null),
        'subtotal_neto'         => $num($ia['subtotal_neto']         ?? null),
        'no_gravado'            => $num($ia['no_gravado']            ?? null),
        'exento'                => $num($ia['exento']                ?? null),

        'iva_porcentaje'        => $str($ia['iva_porcentaje']        ?? null),
        'iva_importe'           => $num($ia['iva_importe']           ?? null),
        'iva_10_5'              => $num($ia['iva_10_5']              ?? null),
        'iva_21'                => $num($ia['iva_21']                ?? null),
        'iva_27'                => $num($ia['iva_27']                ?? null),

        'iibb_importe'          => $num($ia['iibb_importe']          ?? null),
        'imp_internos'          => $num($ia['imp_internos']          ?? null),
        'imp_dioxido_carbono'   => $num($ia['imp_dioxido_carbono']   ?? null),
        'imp_combustible_liq'   => $num($ia['imp_combustible_liq']   ?? null),
        'itc'                   => $num($ia['itc']                   ?? null),
        'tasas'                 => $num($ia['tasas']                 ?? null),
        'otros_impuestos'       => $num($ia['otros_impuestos']       ?? null),

        'total_sin_iva'         => $num($ia['total_sin_iva']         ?? null),
        'total'                 => $num($ia['total']                 ?? null),

        'metodo_pago'           => $str($ia['metodo_pago']           ?? null),
        'moneda'                => $str($ia['moneda']                ?? null) ?? 'ARS',
        'tipo_documento'        => $str($ia['tipo_documento']        ?? null) ?? 'Comprobante fiscal',
        'observaciones'         => $str($ia['observaciones']         ?? null),
    ];
}

// ════════════════════════════════════════════════════════════
//  MAIN
// ════════════════════════════════════════════════════════════
try {
    if (empty($_FILES['file'])) throw new Exception('No se recibió ningún archivo.');
    $file = $_FILES['file'];

    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite configurado en el servidor (php.ini).',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
        UPLOAD_ERR_PARTIAL    => 'La subida fue incompleta. Intentá de nuevo.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Error de configuración del servidor: sin directorio tmp.',
        UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir el archivo.',
        UPLOAD_ERR_EXTENSION  => 'Una extensión PHP bloqueó la subida.',
    ];
    if ($file['error'] !== UPLOAD_ERR_OK)
        throw new Exception($errMap[$file['error']] ?? 'Error de subida código: ' . $file['error']);

    if ($file['size'] > MAX_MB * 1024 * 1024)
        throw new Exception('El archivo supera el límite de ' . MAX_MB . ' MB.');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $mimes = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/gif'       => 'gif',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];
    if (!array_key_exists($mime, $mimes))
        throw new Exception("Tipo de archivo no permitido: $mime. Usá JPG, PNG, WEBP o PDF.");

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true))
        throw new Exception('No se pudo crear el directorio temporal en el servidor.');

    $ext  = $mimes[$mime];
    $path = UPLOAD_DIR . 'ocr_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $path))
        throw new Exception('No se pudo guardar el archivo en el servidor.');

    // ── Elegir flujo según tipo ───────────────────────────
    if ($mime === 'application/pdf') {
        $textoPDF = extraerTextoPDFPuro($path);
        if (strlen(trim($textoPDF)) < 40) {
            throw new Exception(
                'El PDF parece ser una imagen escaneada (no tiene texto extraíble). ' .
                'Convertilo a JPG/PNG e intentá de nuevo.'
            );
        }
        $rawIA      = analizarDesdeTexto($textoPDF, 'PDF texto puro → JSON');
        $modoFinal  = 'PDF texto puro → GPT-4o-mini';
    } else {
        $rawIA      = analizarDesdeImagen($path, $mime);
        $modoFinal  = 'Imagen → GPT-4o OCR → GPT-4o-mini JSON';
    }

    $iaData = parsearJSON($rawIA);

    if ($iaData === null) {
        ob_end_clean();
        echo json_encode([
            'success'  => true,
            'ilegible' => true,
            'message'  => 'No se pudieron estructurar los datos automáticamente. Completá el formulario manualmente.',
            'data'     => [
                'temp_path'     => $path,
                'original_name' => $file['name'],
                'raw_text'      => mb_substr($rawIA, 0, 300),
                'total'         => null,
                'emisor_nombre' => null,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $datos = normalizar($iaData, $file['name'], $path, $modoFinal);

    ob_end_clean();
    echo json_encode([
        'success'  => true,
        'ilegible' => false,
        'modo'     => $modoFinal,
        'data'     => $datos,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $ex) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $ex->getMessage()], JSON_UNESCAPED_UNICODE);
}