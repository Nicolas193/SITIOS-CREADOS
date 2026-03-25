<?php
/**
 * analizar_extracto.php  v15  — Extracción robusta con chunking paralelo
 * ─────────────────────────────────────────────────────────────────────────
 *  Flujo:
 *   1. limpiarTextoExtracto()  → filtra ruido, deja solo tabla de movimientos
 *   2. _dividirEnChunks()      → divide texto largo en chunks con overlap
 *   3. _procesarChunksParalelo → envía chunks en paralelo a gpt-4o-mini
 *   4. _mergearResultados()    → une y deduplica movimientos de todos los chunks
 *   5. normalizarMovimientos() → valida/sanitiza el JSON de respuesta
 *  Sin límite de páginas. Precisión máxima en débito/crédito.
 */

ob_start();
error_reporting(E_ALL);
@ini_set('display_errors',        '0');
@ini_set('log_errors',            '1');
@ini_set('max_execution_time',    '600');
@ini_set('memory_limit',          '512M');
@ini_set('post_max_size',         '64M');
@ini_set('upload_max_filesize',   '50M');
@set_time_limit(600);
ignore_user_abort(true);

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level()) ob_end_clean();
        http_response_code(500);
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode(['success'=>false,'message'=>'Error fatal: '.$err['message']], JSON_UNESCAPED_UNICODE);
    }
});

set_error_handler(function($errno, $errstr) { error_log("PHP [$errno] $errstr"); return true; });

// ─── UTF-8 safe ───────────────────────────────────────────────────────────
function utf8safe($v) {
    if (is_array($v)) return array_map('utf8safe', $v);
    if (!is_string($v)) return $v;
    if (mb_check_encoding($v, 'UTF-8')) return $v;
    $c = @mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
    return $c !== false ? $c : mb_convert_encoding($v, 'UTF-8', 'UTF-8');
}

function jsonOut(array $d, int $c = 200): void {
    while (ob_get_level()) ob_end_clean();
    http_response_code($c);
    header('Content-Type:application/json;charset=utf-8');
    $d = utf8safe($d);
    echo json_encode($d, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

header('Content-Type:application/json;charset=utf-8');

// ─── CONFIG ───────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/env.php';
define('OPENAI_KEY', getenv('OPENAI_KEY') ?: '');
define('OPENAI_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MODEL', 'gpt-4o');
define('UPLOAD_DIR', __DIR__.'/uploads/temp/');

// Configuración de chunking (optimizado para velocidad)
define('CHUNK_SIZE',     40000);  // chars por chunk (gpt-4o soporta 128K)
define('CHUNK_OVERLAP',    500);  // overlap para no cortar movimientos
define('MAX_PARALELO',       6);  // chunks simultáneos
define('CURL_TIMEOUT_CH',  180);  // timeout por chunk
define('MAX_RETRIES',        2);  // reintentos por chunk

if (isset($_GET['test'])) {
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    jsonOut(['success'=>true,'v'=>'15','php'=>PHP_VERSION,
        'curl'=>function_exists('curl_init'),'key'=>strlen(OPENAI_KEY)>20?'OK':'NO',
        'memory'=>ini_get('memory_limit'),'max_exec'=>ini_get('max_execution_time')]);
}

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
foreach (glob(UPLOAD_DIR.'ext_*') ?: [] as $f) {
    if (is_file($f) && (time()-filemtime($f)) > 3600) @unlink($f);
}

// ═════════════════════════════════════════════════════════════════════════
//  PASO 1 — DETECTAR BANCO
// ═════════════════════════════════════════════════════════════════════════
function detectarBanco(string $texto): string {
    $t = mb_strtoupper(mb_substr($texto, 0, 5000));
    if (strpos($t, 'PATAGONIA') !== false)    return 'Banco Patagonia';
    if (strpos($t, 'GALICIA') !== false)       return 'Banco Galicia';
    if (strpos($t, 'MERCADO PAGO') !== false || strpos($t, 'MERCADOPAGO') !== false) return 'Mercado Pago';
    // MP sin marca: detectar por patrones únicos
    if (strpos($t, 'LIQUIDACIÓN DE DINERO') !== false && strpos($t, 'TRANSFERENCIA ENVIADA') !== false) return 'Mercado Pago';
    if (strpos($t, 'BANCO DE LA NACION') !== false || strpos($t, 'NACION ARGENTINA') !== false) return 'Banco Nación';
    if (strpos($t, 'BBVA') !== false || strpos($t, 'FRANCES') !== false || strpos($t, 'FRANCÉS') !== false) return 'BBVA Francés';
    if (strpos($t, 'SANTANDER') !== false)     return 'Banco Santander';
    if (strpos($t, 'HSBC') !== false)          return 'HSBC';
    if (strpos($t, 'MACRO') !== false)         return 'Banco Macro';
    if (strpos($t, 'SUPERVIELLE') !== false)   return 'Banco Supervielle';
    if (strpos($t, 'CIUDAD') !== false)        return 'Banco Ciudad';
    if (strpos($t, 'ICBC') !== false)          return 'ICBC';
    if (strpos($t, 'COMAFI') !== false)        return 'Banco Comafi';
    if (strpos($t, 'BRUBANK') !== false)       return 'Brubank';
    if (strpos($t, 'BIND') !== false)          return 'BIND';
    if (strpos($t, 'ITAU') !== false || strpos($t, 'ITAÚ') !== false) return 'Itaú';
    if (strpos($t, 'UALA') !== false || strpos($t, 'UALÁ') !== false) return 'Ualá';
    if (strpos($t, 'CREDICOOP') !== false)     return 'Banco Credicoop';
    if (strpos($t, 'HIPOTECARIO') !== false)   return 'Banco Hipotecario';
    if (strpos($t, 'PROVINCIA') !== false || strpos($t, 'BAPRO') !== false) return 'Banco Provincia';
    if (strpos($t, 'COLUMBIA') !== false)      return 'Banco Columbia';
    if (strpos($t, 'NARANJA') !== false)       return 'Naranja X';
    if (strpos($t, 'PERSONAL PAY') !== false)  return 'Personal Pay';
    return 'Banco Argentino';
}

// ═════════════════════════════════════════════════════════════════════════
//  PASO 2 — LIMPIAR TEXTO
//  Objetivo: dejar SOLO las líneas relevantes de la tabla de movimientos
//  + datos de cabecera (titular, CUIT, cuenta, período, saldos).
//  Se eliminan: encabezados de página, avisos legales, publicidades, etc.
// ═════════════════════════════════════════════════════════════════════════
function limpiarTextoExtracto(string $texto, string $banco): string {
    $lineas = explode("\n", $texto);
    $resultado = [];

    // ══ Patrones de ruido genéricos (aplican a TODOS los bancos) ══
    $ruido = [
        '/^[_\-=]{3,}$/',                          // separadores
        '/^P[aá]gina[:\s]*\d/i',                   // número de página
        '/^PAGINA[:\s]*\d/i',
        '/^HOJA[:\s]*\d/i',
        '/^www\./i',
        '/^http/i',
        '/^Tel[eé]fono/i',
        '/^\d{9,}[A-Z]\d{8}$/',                    // códigos internos
        '/^[A-Z]{2,4}\s+\d{6,}$/',                 // referencias
        '/^Ley\s+\d{4,}/i',
        '/^De conformidad/i',
        '/^Los dep[oó]sitos/i',
        '/^Se presumir/i',
        '/^Los accionistas/i',
        '/^Todos los valores/i',
        '/^A partir del/i',
        '/^Podes solicitar/i',
        '/^PODES CONSULTAR/i',
        '/^Estimado Cliente/i',
        '/^USTED PUEDE/i',
        '/^ESTIMAREMOS/i',
        '/^SIN PERJUICIO/i',
        '/^POR RAZONES/i',
        '/^LAS NORMAS/i',
        '/^ARGENTINA SOBRE/i',
        '/^LOS COSTOS/i',
        '/^\*\s+Aviso/i',
        '/^\*\s+Nota/i',
        '/^Según modalidad/i',
        '/^Relacionados/i',
        '/^Cajas de Seguridad/i',
    ];

    // ══ Patrones de ruido ESPECÍFICOS por banco ══
    $ruidoBanco = [];

    if ($banco === 'Banco Patagonia') {
        $ruidoBanco = [
            '/^Si usted reviste/i',
            '/^el monto de IVA/i',
            '/^Banco Patagonia S\.A\./i',
            '/^CUIT 30-500/i',
            '/^\*\s+Patagonia/i',
            '/^Comisiones:/i',
            '/^Tarjeta/i',
            '/^Cheques:/i',
            '/^Pagar[eé]s:/i',
            '/^Mantenimiento/i',
            '/^Dep[oó]sitos/i',
            '/^Cobranza/i',
        ];
    } elseif ($banco === 'Banco Nación') {
        $ruidoBanco = [
            '/^BANCO DE LA$/i',
            '/^NACION ARGENTINA$/i',
            '/^CUIT 30-50001091/i',
            '/^SUC:\s*\d+$/i',
            '/^CONVENIO COLECTIVO/i',
            '/^AV BELGRANO/i',
            '/^\d{4}\s+CAP FED$/i',
            '/^CAPITAL FEDERAL$/i',
            '/^OPERACIONES DEL BANCO/i',
            '/^TRANSPORTE\s+[\d.,]+$/i',
            '/^SIGUIENTE\s*-+>/i',
            '/^<-+\s*FIN/i',
            '/^TA\) DIAS/i',
            '/^TRARSE ACREDITADAS/i',
        ];
    } elseif ($banco === 'Banco Galicia') {
        $ruidoBanco = [
            '/^Banco de Galicia/i',
            '/^Galicia y Buenos Aires/i',
            '/^Casa Central/i',
            '/^Tte\. Gral\./i',
            '/^Resumen de Cuenta Corriente/i',
            '/^Resumen de Cuenta de Ahorro/i',
            '/^\d{14,}H$/i',
            '/^Cantidad de cotitulares/i',
            '/^Dispon[eé]s de \d+ d/i',
            '/^El cr[eé]dito fiscal/i',
            '/^Tasa Extraordinaria/i',
            '/^Saldos Deudores Promedio/i',
            '/^Intereses\s+\$/i',
            '/^CUIT del Responsable/i',
            '/^IVA:\s*Responsable/i',
        ];
    } elseif ($banco === 'BBVA Francés') {
        $ruidoBanco = [
            '/^BBVA Argentina/i',
            '/^Reconquista\s+\d/i',
            '/^BBVA Franc[eé]s/i',
            '/^Banco Franc[eé]s/i',
            '/^RESUMEN DE EMISION MENSUAL/i',
            '/^RESUMEN DE CUENTA/i',
            '/^Referencia BCRA/i',
            '/^Referencia de pago/i',
            '/^Banca Electr[oó]nica/i',
            '/^www\.bbva/i',
            '/^Su clave de/i',
            '/^Centro de contacto/i',
            '/^Si desea realizar/i',
            '/^Saldo promedio/i',
            '/^Tasa nominal anual/i',
            '/^Tasa efectiva anual/i',
            '/^Costo financiero total/i',
            '/^TOTAL DE OPERACIONES/i',
            '/^C\.U\.I\.T/i',
            '/^Tipo de cambio/i',
        ];
    } elseif ($banco === 'Banco Santander') {
        $ruidoBanco = [
            '/^Santander R[ií]o/i',
            '/^B\.C\.R\.A\./i',
            '/^Banco Santander/i',
            '/^Saldo total \\$/i',
            '/^Saldo total en cuentas/i',
            '/^Centro de atenci[oó]n/i',
            '/^Acuerdo de giro/i',
            '/^Garant[ií]a de dep[oó]sito/i',
            '/^Intercambio de informaci/i',
            '/^0810[-\s]/i',
            '/^Detalle impositivo/i',
            '/^Tipo de impuesto/i',
            '/^Importe susceptible/i',
        ];
    } elseif ($banco === 'Mercado Pago') {
        $ruidoBanco = [
            '/^Mercado Pago S\.?R\.?L/i',
            '/^CVU[:\s]*\d/i',
            '/^Actividad en tu cuenta/i',
            '/^mercadopago\.com/i',
            '/^Saldo disponible/i',
            '/^Tu dinero en/i',
            '/^Datos de la cuenta/i',
        ];
    }

    $ruidoCompleto = array_merge($ruido, $ruidoBanco);

    // ══ Patrones que indican filas de movimientos ══
    // Soporta: dd/mm/yyyy, dd-mm-yyyy, dd/mm/yy, dd/mm Y dd-MMM-yyyy (ej: 02-FEB-2026)
    $esFilaMovimiento = '/^\s*(?:\d{1,2}[\/\-][A-Z]{2,3}[\/\-]\d{2,4}|\d{1,2}[\/\-]\d{2}[\/\-]?\d{0,4})\s+\S/i';

    // ══ Patrones de inicio de tabla ══
    $inicioTabla = [
        '/CUENTA\s+(CORRIENTE|AHORRO)\s+EN\s+PESOS/i',
        '/ESTADO DE CUENTA/i',
        '/MOVIMIENTOS DE CUENTA/i',
        '/FECHA\s+CONCEPTO/i',
        '/FECHA\s+MOVIMIENTO/i',
        '/FECHA\s+DESCRIPCI[OÓ]N/i',
        '/SALDO\s+ANTERIOR/i',
        '/D[EÉ]BITO[S]?\s+CR[EÉ]DITO[S]?/i',
        '/RESUMEN DE CUENTA/i',
        '/EXTRACTO DE CUENTA/i',
        '/DETALLE DE MOVIMIENTOS/i',
        '/NRO\.\s*CUENTA/i',
        '/CLAVE BANCARIA UNIFORME/i',
        '/^PERIODO\s*:/i',
    ];

    // ══ Patrones de fin de tabla ══
    $finTabla = [
        '/^TRANSFERENCIAS\s+RECIBIDAS/i',
        '/^TRANSFERENCIAS\s+ENVIADAS/i',
        '/^DETALLE\s*[-–]\s*COMISION/i',
        '/^SITUACION\s+IMPOSITIVA/i',
    ];

    $enTabla = false;
    $lineaAnteriorEsMovimiento = false;

    foreach ($lineas as $linea) {
        $t = trim($linea);
        if ($t === '') continue;

        // ── Detectar inicio de tabla ──
        if (!$enTabla) {
            foreach ($inicioTabla as $pat) {
                if (preg_match($pat, $t)) { $enTabla = true; break; }
            }
            if ($enTabla) { $resultado[] = $linea; continue; }

            // Antes de la tabla, capturar datos de cabecera útiles
            if (preg_match('/TITULAR|CUIT|C\.B\.U\.|CBU|PERIODO|PERÍODO|NRO.*CUENTA|NUMERO.*CUENTA|TIPO.*CUENTA|SUCURSAL/i', $t)) {
                $resultado[] = $linea;
            }
            continue;
        }

        // ── Detectar fin de tabla ──
        $fin = false;
        foreach ($finTabla as $pat) {
            if (preg_match($pat, $t)) { $fin = true; break; }
        }
        if ($fin) {
            // Incluir la línea de fin por si tiene info impositiva
            $resultado[] = $linea;
            break;
        }

        // ── Filtrar ruido ──
        $esRuido = false;
        foreach ($ruidoCompleto as $pat) {
            if (preg_match($pat, $t)) { $esRuido = true; break; }
        }
        if ($esRuido) { $lineaAnteriorEsMovimiento = false; continue; }

        // ── Incluir solo si tiene contenido relevante ──
        $esCabecera = preg_match('/FECHA\s+CONCEPTO|D[EÉ]BITO[S]?\s+CR[EÉ]DITO[S]?|FECHA\s+MOVIMIENTO|FECHA\s+DESCRIPCI/i', $t);
        $esSaldo    = preg_match('/SALDO\s+(ANTERIOR|ACTUAL|FINAL)/i', $t);
        $esMovim    = preg_match($esFilaMovimiento, $t);
        // Montos en cualquier posición de la línea (no solo al final)
        // Cubre líneas que terminan con referencia de texto: "TRANSFERENCIA 50.000,00 1382.358,25 MACIEL"
        $tieneMontos = preg_match('/\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})/', $t) && strlen($t) > 10;
        // Líneas de impuestos al pie
        $esImpuesto = preg_match('/GRAVAMEN|I\.V\.A|IVA|IIBB|SIRCREB|LEY\s*25\.?413|RETENCION|PERCEPCION|COMIS/i', $t);
        // Totales
        $esTotal = preg_match('/^TOTAL/i', $t);

        if ($esCabecera || $esSaldo || $esMovim || $esImpuesto) {
            $resultado[] = $linea;
            $lineaAnteriorEsMovimiento = $esMovim;
        }
        // Incluir líneas que son continuación de descripción
        elseif ($lineaAnteriorEsMovimiento && strlen($t) < 120 && !preg_match('/^\d{9,}/', $t)) {
            $resultado[] = $linea;
            $lineaAnteriorEsMovimiento = false; // solo 1 línea de continuación
        }
        // Incluir filas con montos (podrían ser movimientos con formato diferente)
        elseif ($tieneMontos && strlen($t) > 10) {
            $resultado[] = $linea;
            $lineaAnteriorEsMovimiento = false;
        }
        // Incluir totales
        elseif ($esTotal) {
            $resultado[] = $linea;
            $lineaAnteriorEsMovimiento = false;
        }
        else {
            $lineaAnteriorEsMovimiento = false;
        }
    }

    // Si no se detectó tabla, devolver texto comprimido (quitar solo ruido obvio)
    if (empty($resultado)) {
        $fallback = [];
        foreach ($lineas as $linea) {
            $t = trim($linea);
            if ($t === '') continue;
            $esRuido = false;
            foreach ($ruidoCompleto as $pat) {
                if (preg_match($pat, $t)) { $esRuido = true; break; }
            }
            if (!$esRuido) $fallback[] = $linea;
        }
        return implode("\n", $fallback);
    }

    return implode("\n", $resultado);
}

// ═════════════════════════════════════════════════════════════════════════
//  PASO 3 — PROMPTS PRECISOS
// ═════════════════════════════════════════════════════════════════════════

/**
 * Prompt principal (primer chunk o texto único).
 * Incluye instrucciones completas + estructura JSON + reglas anti-invención.
 */
function getPromptPrincipal(): string {
    return <<<'PROMPT'
Actuá como un sistema experto en extracción de datos financieros desde extractos bancarios argentinos.

══ OBJETIVO ══
Extraer la tabla de movimientos SIN alterar, completar ni inventar ningún dato.

══ REGLA PRINCIPAL (CRÍTICA) ══
❌ PROHIBIDO generar, inferir, calcular o completar valores que NO estén explícitamente escritos en el texto.
✔ Si un dato no aparece en el texto → devolver null.
✔ Si no estás seguro si un monto es débito o crédito → poné ambos en null y agregá "observacion".

══ REGLAS DE DÉBITO / CRÉDITO (MÁXIMA PRIORIDAD) ══

CASO 1 — ETIQUETAS PRE-CLASIFICADAS:
  [DEBITO:1.234,56] → "debito": 1234.56, "credito": null
  [CREDITO:1.234,56] → "credito": 1234.56, "debito": null
  [SALDO:1.234,56] → "saldo": 1234.56
  Si están presentes, RESPETARLAS SIEMPRE. No reclasificar.

CASO 2 — COLUMNA ÚNICA "VALOR" CON SIGNO (ej: Mercado Pago, fintech):
  Si el extracto tiene UNA sola columna de montos (llamada "Valor", "Importe", "Monto"):
  → Monto NEGATIVO (ej: -8.046,92 o $ -8.046,92) = DÉBITO → "debito": 8046.92, "credito": null
  → Monto POSITIVO (ej: 2.407,02 o $ 2.407,02) = CRÉDITO → "credito": 2407.02, "debito": null
  NUNCA invertir esta regla. El signo es absoluto.

CASO 3 — COLUMNAS SEPARADAS (débito y crédito son columnas distintas):
  → "columna izquierda" = DÉBITO, "columna derecha" = CRÉDITO
  → Si la fila solo tiene valor en una columna, la otra es null.

CASO 4 — MONTOS CON SIGNO SIN COLUMNA EXPLÍCITA:
  → Negativo = DÉBITO, Positivo = CRÉDITO (siempre usar valor absoluto en el JSON)

══ CLASIFICACIÓN POR CONCEPTO (solo si el signo o columna no es claro) ══
  DÉBITOS (el banco/sistema cobra o sale dinero):
    Pago, Compra, Transferencia enviada, Débito automático, Impuesto,
    IIBB, IVA, Comisión, Gravamen, Suscripción, Extracción, Retiro,
    Pago de servicio, Tarjeta, Seguros, AFIP, ARBA, Retención

  CRÉDITOS (entra dinero):
    Depósito, Transferencia recibida, Acreditación, Rendición,
    Liquidación de dinero, Cobro, Haberes, Sueldo, Devolución,
    Interés acreditado, Reembolso, Cashback

══ REGLAS ══
- NUNCA pongas el mismo valor en débito Y crédito.
- Los montos en el JSON deben ser SIEMPRE float POSITIVO (valor absoluto).
- Si ves "$ -8.046,92" → "debito": 8046.92 (positivo, sin signo).
- Si no podés determinar D/C → ambos null + "observacion": "no determinable".
- Conceptos como GRAVAMEN, IVA, COMISIÓN → generalmente DÉBITOS.
- Conceptos como LIQUIDACIÓN, ACREDITACIÓN → generalmente CRÉDITOS.
- SIEMPRE respetar las etiquetas [DEBITO:]/[CREDITO:] por encima de cualquier regla.

══ REGLAS DE SALDO ══
- Si la columna SALDO existe y tiene un valor para esa fila → incluirlo como float positivo.
- Si la columna SALDO NO existe o está vacía → "saldo": null.
- NUNCA calcular ni deducir el saldo. Solo copiarlo si está escrito.

══ REGLAS GENERALES ══
1. Extraer únicamente los valores visibles en el texto. No deducir ni recalcular.
2. "SALDO ANTERIOR" → va en cabecera.saldo_inicial (NO es un movimiento).
3. "SALDO ACTUAL/FINAL" → va en cabecera.saldo_final (NO es un movimiento).
4. Incluir ABSOLUTAMENTE TODOS los movimientos visibles, incluso los de centavos.
5. Si falta un dato en una fila → null. NO completar con lógica ni contexto.

══ FORMATO DE RESPUESTA — SOLO JSON VÁLIDO ══
{
  "banco": "string o null",
  "cabecera": {
    "titular": null,
    "cuit": null,
    "tipo_cuenta": null,
    "numero_cuenta": null,
    "cbu": null,
    "moneda": "ARS",
    "sucursal": null,
    "periodo_desde": null,
    "periodo_hasta": null,
    "saldo_inicial": null,
    "saldo_final": null
  },
  "movimientos": [
    {
      "fecha": "YYYY-MM-DD",
      "descripcion": "texto del concepto",
      "comprobante": null,
      "debito": null,
      "credito": null,
      "saldo": null,
      "observacion": null
    }
  ],
  "impuestos": {}
}

══ REGLAS DE FORMATO ══
- Fechas: YYYY-MM-DD. "6/02/25" → "2025-02-06", "31/01/25" → "2025-01-31", "06/02" → inferir año del período.
- Montos: float positivo, sin $ ni puntos de miles, punto decimal. "1.234.567,89" → 1234567.89
- "$ -8.046,92" → 8046.92 (positivo, el signo determina D/C, no el valor)
- tipo: NO incluir en el JSON, se derivará después.
- comprobante: solo si aparece explícitamente un número de comprobante.
- observacion: solo si hay genuina ambigüedad; si está claro → null.

══ CONTROL FINAL ══
Antes de responder verificar:
✔ Ningún campo fue inventado
✔ Ningún saldo fue calculado
✔ Montos negativos → débito (valor absoluto positivo en JSON)
✔ Montos positivos → crédito (valor absoluto positivo en JSON)
✔ Cada monto está en la columna correcta (débito O crédito, nunca ambos)
✔ Todos los valores provienen del texto recibido
PROMPT;
}

/**
 * Prompt para chunks secundarios (solo movimientos, sin cabecera).
 */
function getPromptChunkSecundario(): string {
    return <<<'PROMPT'
Fragmento de un extracto bancario argentino. Devolvé SOLO JSON válido.

ESTRUCTURA:
{"banco":"","cabecera":{},"movimientos":[{"fecha":"YYYY-MM-DD","descripcion":"string","comprobante":null,"debito":null,"credito":null,"saldo":null,"observacion":null}],"impuestos":{}}

REGLAS CRÍTICAS:
- Extraé TODOS los movimientos visibles sin omitir ninguno.
- Si hay etiquetas [DEBITO:xxx], [CREDITO:xxx], [SALDO:xxx] → RESPETARLAS.
- Si hay una sola columna de montos ("Valor", "Importe"):
  → Monto NEGATIVO = DÉBITO, Monto POSITIVO = CRÉDITO.
- Si hay columnas separadas: izquierda = DÉBITOS, derecha = CRÉDITOS.
- Montos en JSON: SIEMPRE float POSITIVO (valor absoluto). "$ -8.046,92" → "debito": 8046.92
- NUNCA pongas el mismo monto en débito Y crédito.
- Si no hay columna de saldo o está vacía → "saldo": null. NO calcular.
- Si no podés determinar si es débito o crédito → ambos null + "observacion".
- Clasificación por concepto (si el signo no es claro):
  DÉBITOS: Pago, Compra, Transferencia enviada, Impuesto, Comisión, Gravamen, Suscripción
  CRÉDITOS: Depósito, Transferencia recibida, Acreditación, Liquidación, Cobro, Devolución
- Montos: float positivo con punto decimal. "1.234,56" → 1234.56
- Fechas: YYYY-MM-DD.
- NO inventar datos. Si no está en el texto → null.
PROMPT;
}

// ═════════════════════════════════════════════════════════════════════════
//  PASO 4 — DIVIDIR EN CHUNKS
// ═════════════════════════════════════════════════════════════════════════
function _dividirEnChunks(string $texto): array {
    $chunks = [];
    $len    = mb_strlen($texto);
    $pos    = 0;

    while ($pos < $len) {
        $end = min($pos + CHUNK_SIZE, $len);
        // Cortar en salto de línea para no partir un movimiento
        if ($end < $len) {
            $seg   = mb_substr($texto, $pos, $end - $pos);
            $corte = mb_strrpos($seg, "\n");
            if ($corte !== false && $corte > CHUNK_SIZE * 0.6) {
                $end = $pos + $corte + 1;
            }
        }
        $chunk = mb_substr($texto, $pos, $end - $pos);
        if (mb_strlen(trim($chunk)) > 50) {
            $chunks[] = $chunk;
        }
        // Avanzar: siguiente chunk empieza con overlap desde el final actual
        $nextPos = $end - CHUNK_OVERLAP;
        if ($nextPos <= $pos) $nextPos = $end; // Evitar loop infinito
        $pos = $nextPos;
    }

    return $chunks;
}

/**
 * Calcula max_tokens dinámico según tamaño del chunk.
 */
function calcularMaxTokens(string $chunk): int {
    $chars = mb_strlen($chunk);
    if ($chars < 3000)  return 3000;
    if ($chars < 6000)  return 5000;
    if ($chars < 12000) return 8000;
    if ($chars < 18000) return 12000;
    return 16000;
}

// ═════════════════════════════════════════════════════════════════════════
//  PASO 5 — PROCESAR CHUNKS EN PARALELO (curl_multi)
// ═════════════════════════════════════════════════════════════════════════
function _procesarChunksParalelo(array $chunks, string $archivo, string $banco): array {
    $total      = count($chunks);
    $resultados = array_fill(0, $total, null);
    $lotes      = array_chunk(array_keys($chunks), MAX_PARALELO);

    foreach ($lotes as $lote) {
        $mh      = curl_multi_init();
        $handles = [];

        foreach ($lote as $i) {
            $prompt  = ($i === 0) ? getPromptPrincipal() : getPromptChunkSecundario();
            $maxTok  = calcularMaxTokens($chunks[$i]);

            $userMsg = "BANCO: {$banco}\nARCHIVO: {$archivo}\nCHUNK " . ($i+1) . "/{$total}\n\nTABLA DE MOVIMIENTOS:\n---\n{$chunks[$i]}\n---";

            $body = [
                'model'           => OPENAI_MODEL,
                'max_tokens'      => $maxTok,
                'temperature'     => 0,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user',   'content' => $userMsg],
                ],
            ];

            $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_LOW_SPEED_LIMIT => 1,
                CURLOPT_LOW_SPEED_TIME  => 120,
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }

        // Ejecutar en paralelo
        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running > 0 && $status === CURLM_OK);

        // Recolectar resultados
        foreach ($lote as $i) {
            $ch   = $handles[$i];
            $resp = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if ($err || $code !== 200) {
                // For curl_multi, also check the multi info for errors
                $curlErrNo = curl_errno($ch);
                $errMsg    = $err ?: "curl_errno=$curlErrNo";
                if ($code === 0 && !$err) $errMsg = "Timeout/connection failed (errno=$curlErrNo)";
                error_log("Chunk $i falló: HTTP $code | $errMsg | " . substr($resp ?? '', 0, 200));

                // Retry on timeout (HTTP 0), rate limit (429), or server error (500+)
                if ($code === 0 || $code === 429 || $code >= 500) {
                    for ($retry = 1; $retry <= MAX_RETRIES; $retry++) {
                        $wait = ($code === 0) ? 5 : $retry * 2; // Shorter wait for timeouts
                        sleep($wait);
                        error_log("Chunk $i retry $retry (after HTTP $code)...");
                        $retryResult = _reintentarChunk($chunks[$i], $archivo, $banco, $i, $total);
                        if ($retryResult !== null) {
                            $resultados[$i] = $retryResult;
                            break;
                        }
                    }
                }
                continue;
            }

            $decoded    = json_decode($resp, true);
            $rawContent = $decoded['choices'][0]['message']['content'] ?? null;
            if (!$rawContent) continue;

            try {
                $resultados[$i] = parsearRespuestaJSON(trim((string)$rawContent));
            } catch (RuntimeException $e) {
                error_log("Chunk $i parse error: " . $e->getMessage());
            }
        }

        curl_multi_close($mh);
    }

    return array_values(array_filter($resultados));
}

/**
 * Reintento individual de un chunk fallido.
 */
function _reintentarChunk(string $chunkTexto, string $archivo, string $banco, int $idx, int $total): ?array {
    $prompt  = ($idx === 0) ? getPromptPrincipal() : getPromptChunkSecundario();
    $maxTok  = calcularMaxTokens($chunkTexto);
    $userMsg = "BANCO: {$banco}\nARCHIVO: {$archivo}\nCHUNK " . ($idx+1) . "/{$total}\n\nTABLA DE MOVIMIENTOS:\n---\n{$chunkTexto}\n---";

    $body = [
        'model'           => OPENAI_MODEL,
        'max_tokens'      => $maxTok,
        'temperature'     => 0,
        'response_format' => ['type' => 'json_object'],
        'messages'        => [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user',   'content' => $userMsg],
        ],
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => OPENAI_URL,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_KEY],
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT        => 300,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return null;

    $decoded = json_decode($resp, true);
    $raw = $decoded['choices'][0]['message']['content'] ?? null;
    if (!$raw) return null;

    try {
        return parsearRespuestaJSON(trim($raw));
    } catch (RuntimeException $e) {
        return null;
    }
}

// ═════════════════════════════════════════════════════════════════════════
//  PASO 6 — PARSEAR RESPUESTA JSON (resiliente a 4 niveles)
// ═════════════════════════════════════════════════════════════════════════
function parsearRespuestaJSON(string $raw): array {
    // Quitar bloques markdown
    $clean = preg_replace('/^```(?:json)?\s*/mi', '', $raw);
    $clean = preg_replace('/\s*```\s*$/m', '', $clean);
    $clean = trim($clean);

    if ($clean === '') throw new RuntimeException('OpenAI devolvió respuesta vacía.');

    // Intento 1: JSON directo
    $data = json_decode($clean, true);
    if ($data !== null && is_array($data)) return $data;

    // Intento 2: Extraer bloque JSON del texto
    if (preg_match('/(\{[\s\S]+\})/s', $clean, $m)) {
        $data = json_decode($m[1], true);
        if ($data !== null && is_array($data)) return $data;
    }

    // Intento 3: JSON truncado — cerrar arrays/objetos abiertos
    if (preg_match('/(.*"movimientos"\s*:\s*\[)(.*)/s', $clean, $parts)) {
        $arrBody   = $parts[2];
        $lastClose = strrpos($arrBody, '}');
        if ($lastClose !== false) {
            $fixed = $parts[1] . substr($arrBody, 0, $lastClose + 1) . '],';
            if (strpos($fixed, '"estadisticas"') === false) {
                $fixed .= '"estadisticas":{"total_movimientos":0,"total_creditos":0,"total_debitos":0,"neto":0},';
            }
            if (strpos($fixed, '"impuestos"') === false) $fixed .= '"impuestos":{}';
            $fixed = rtrim($fixed, ',') . '}';
            $data  = json_decode($fixed, true);
            if ($data !== null) return $data;
        }
    }

    // Intento 4: Extraer movimientos individuales con regex
    if (preg_match_all('/\{\s*"fecha"\s*:.*?"(?:observacion|saldo|credito|debito)"\s*:\s*(?:null|[\d.]+|"[^"]*")\s*\}/s', $clean, $movMatches)) {
        $movimientos = array_filter(array_map(fn($m) => json_decode($m, true), $movMatches[0]));
        if (!empty($movimientos)) {
            preg_match('/"banco"\s*:\s*"([^"]+)"/', $clean, $bm);
            return [
                'banco'       => $bm[1] ?? null,
                'cabecera'    => [],
                'movimientos' => array_values($movimientos),
                'impuestos'   => [],
            ];
        }
    }

    error_log('analizar_extracto v15 JSON fail: ' . substr($raw, 0, 600));
    throw new RuntimeException('OpenAI no devolvió JSON válido.');
}

// ═════════════════════════════════════════════════════════════════════════
//  PASO 7 — MERGE Y DEDUPLICACIÓN
// ═════════════════════════════════════════════════════════════════════════
function _mergearResultados(array $resultados): array {
    if (empty($resultados)) throw new RuntimeException('Ningún chunk pudo procesarse.');

    $base             = $resultados[0];
    $todosMovimientos = $base['movimientos'] ?? [];

    foreach (array_slice($resultados, 1) as $parcial) {
        foreach ($parcial['movimientos'] ?? [] as $m) {
            $todosMovimientos[] = $m;
        }
        // Merge impuestos
        if (!empty($parcial['impuestos'])) {
            $base['impuestos'] = _mergeImpuestos($base['impuestos'] ?? [], $parcial['impuestos']);
        }
        // Tomar saldo_final del último chunk que lo tenga
        if (!empty($parcial['cabecera']['saldo_final'])) {
            if (!isset($base['cabecera'])) $base['cabecera'] = [];
            $base['cabecera']['saldo_final'] = $parcial['cabecera']['saldo_final'];
        }
    }

    $base['movimientos'] = _deduplicarMovimientos($todosMovimientos);
    return $base;
}

function _deduplicarMovimientos(array $movs): array {
    $vistos = [];
    $result = [];
    foreach ($movs as $m) {
        $key = ($m['fecha'] ?? '') . '|'
             . mb_strtolower(trim($m['descripcion'] ?? '')) . '|'
             . ($m['debito'] ?? 'N') . '|'
             . ($m['credito'] ?? 'N') . '|'
             . ($m['saldo'] ?? 'N');
        if (!isset($vistos[$key])) {
            $vistos[$key] = true;
            $result[] = $m;
        }
    }
    return $result;
}

function _mergeImpuestos(array $base, array $nuevo): array {
    foreach ($nuevo as $k => $v) {
        if ($v !== null && $v > 0 && (!isset($base[$k]) || $base[$k] === null || $v > $base[$k])) {
            $base[$k] = $v;
        }
    }
    return $base;
}

// ═════════════════════════════════════════════════════════════════════════
//  PASO 8 — NORMALIZAR Y VALIDAR
// ═════════════════════════════════════════════════════════════════════════
function normalizarMovimientos(array $data, string $banco): array {
    $movs = [];
    foreach (($data['movimientos'] ?? []) as $m) {
        if (!is_array($m)) continue;

        $db  = isset($m['debito'])  && $m['debito']  !== null ? abs((float)$m['debito'])  : null;
        $cr  = isset($m['credito']) && $m['credito'] !== null ? abs((float)$m['credito']) : null;
        $sal = isset($m['saldo'])   && $m['saldo']   !== null ? (float)$m['saldo']        : null;
        $obs = isset($m['observacion']) && $m['observacion'] !== null ? trim((string)$m['observacion']) : null;

        // Nunca $db=0 ni $cr=0 como valor real (convertir a null)
        if ($db !== null && $db == 0) $db = null;
        if ($cr !== null && $cr == 0) $cr = null;

        // Validar: nunca ambos débito Y crédito con valor
        if ($db !== null && $db > 0 && $cr !== null && $cr > 0) {
            $obs = ($obs ? $obs . ' | ' : '') . 'débito y crédito ambos presentes — revisar manualmente';
        }

        // Derivar tipo
        $tipo = 'I';
        if ($db !== null && $db > 0 && !($cr !== null && $cr > 0)) $tipo = 'D';
        if ($cr !== null && $cr > 0 && !($db !== null && $db > 0)) $tipo = 'C';

        // Validar fecha
        $fecha = $m['fecha'] ?? null;
        if ($fecha !== null) {
            $fecha = trim((string)$fecha);
            // Aceptar formatos YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $fecha = null; // fecha inválida
            }
        }

        $movs[] = [
            'fecha'       => $fecha,
            'fecha_valor' => null,
            'descripcion' => mb_substr(trim((string)($m['descripcion'] ?? '')), 0, 500),
            'comprobante' => isset($m['comprobante']) && $m['comprobante'] !== null
                              ? mb_substr(trim((string)$m['comprobante']), 0, 50) : null,
            'debito'      => ($db !== null && $db > 0) ? round($db, 2) : null,
            'credito'     => ($cr !== null && $cr > 0) ? round($cr, 2) : null,
            'saldo'       => $sal !== null ? round($sal, 2) : null,
            'tipo'        => $tipo,
            'observacion' => $obs ?: null,
        ];
    }

    // Filtrar filas completamente vacías
    $movs = array_values(array_filter($movs, function($m) {
        return $m['fecha'] !== null || $m['debito'] !== null || $m['credito'] !== null
            || (isset($m['descripcion']) && $m['descripcion'] !== '');
    }));

    // ══ POST-CORRECCIÓN D/C MATEMÁTICA: usar delta de saldo ══
    // Si tenemos saldos consecutivos, la diferencia determina D/C con certeza matemática
    for ($i = 0; $i < count($movs); $i++) {
        $monto = ($movs[$i]['debito'] ?? 0) + ($movs[$i]['credito'] ?? 0);
        if ($monto <= 0) continue; // sin monto, no podemos corregir

        $saldoActual = $movs[$i]['saldo'] ?? null;
        if ($saldoActual === null) continue;

        // Buscar saldo anterior (del movimiento anterior o saldo_inicial)
        $saldoPrev = null;
        if ($i > 0 && $movs[$i-1]['saldo'] !== null) {
            $saldoPrev = $movs[$i-1]['saldo'];
        }

        if ($saldoPrev === null) continue;

        $delta = round($saldoActual - $saldoPrev, 2);

        // Si el saldo bajó → es un DÉBITO (salió dinero)
        // Si el saldo subió → es un CRÉDITO (entró dinero)
        if (abs($delta) < 0.01) continue; // sin cambio

        $deberiaSerD = ($delta < 0);
        $deberiaSerC = ($delta > 0);

        $esD = ($movs[$i]['debito'] !== null && $movs[$i]['debito'] > 0);
        $esC = ($movs[$i]['credito'] !== null && $movs[$i]['credito'] > 0);

        // Corregir si está invertido
        if ($deberiaSerD && $esC && !$esD) {
            // Está como crédito pero el saldo bajó → es débito
            $movs[$i]['debito'] = $movs[$i]['credito'];
            $movs[$i]['credito'] = null;
            $movs[$i]['tipo'] = 'D';
        } elseif ($deberiaSerC && $esD && !$esC) {
            // Está como débito pero el saldo subió → es crédito
            $movs[$i]['credito'] = $movs[$i]['debito'];
            $movs[$i]['debito'] = null;
            $movs[$i]['tipo'] = 'C';
        }

        // Si ambos son null pero hay monto, asignar según delta
        if ($movs[$i]['debito'] === null && $movs[$i]['credito'] === null) {
            if ($deberiaSerD) {
                $movs[$i]['debito'] = $monto;
                $movs[$i]['tipo'] = 'D';
            } else {
                $movs[$i]['credito'] = $monto;
                $movs[$i]['tipo'] = 'C';
            }
        }
    }

    // Fallback: para movimientos sin saldo, inferir por keyword
    foreach ($movs as &$mov) {
        if ($mov['tipo'] === 'I' && ($mov['debito'] !== null || $mov['credito'] !== null)) {
            $tipoKw = _inferirTipoPorDesc($mov['descripcion'] ?? '');
            if ($tipoKw === 'D' && $mov['credito'] !== null && $mov['debito'] === null) {
                $mov['debito'] = $mov['credito']; $mov['credito'] = null; $mov['tipo'] = 'D';
            } elseif ($tipoKw === 'C' && $mov['debito'] !== null && $mov['credito'] === null) {
                $mov['credito'] = $mov['debito']; $mov['debito'] = null; $mov['tipo'] = 'C';
            }
        }
    }
    unset($mov);

    // Normalizar cabecera
    $cab = $data['cabecera'] ?? [];
    $cabecera = [
        'titular'       => isset($cab['titular'])       && $cab['titular']       !== null ? trim((string)$cab['titular'])       : null,
        'cuit'          => isset($cab['cuit'])           && $cab['cuit']           !== null ? preg_replace('/\D/','',(string)$cab['cuit']) : null,
        'condicion_iva' => $cab['condicion_iva'] ?? null,
        'tipo_cuenta'   => isset($cab['tipo_cuenta'])   && $cab['tipo_cuenta']   !== null ? trim((string)$cab['tipo_cuenta'])   : null,
        'numero_cuenta' => isset($cab['numero_cuenta']) && $cab['numero_cuenta'] !== null ? trim((string)$cab['numero_cuenta']) : null,
        'cbu'           => isset($cab['cbu'])            && $cab['cbu']           !== null ? preg_replace('/\D/','',(string)$cab['cbu'])   : null,
        'moneda'        => $cab['moneda'] ?? 'ARS',
        'sucursal'      => $cab['sucursal'] ?? null,
        'periodo_desde' => $cab['periodo_desde'] ?? null,
        'periodo_hasta' => $cab['periodo_hasta'] ?? null,
        'saldo_inicial' => isset($cab['saldo_inicial']) && $cab['saldo_inicial'] !== null ? round((float)$cab['saldo_inicial'], 2) : null,
        'saldo_final'   => isset($cab['saldo_final'])   && $cab['saldo_final']   !== null ? round((float)$cab['saldo_final'],   2) : null,
    ];

    // Si saldo_final no vino en cabecera, tomar el último saldo explícito de la tabla
    if ($cabecera['saldo_final'] === null) {
        for ($i = count($movs) - 1; $i >= 0; $i--) {
            if ($movs[$i]['saldo'] !== null) {
                $cabecera['saldo_final'] = $movs[$i]['saldo'];
                break;
            }
        }
    }

    // Normalizar impuestos
    $imp = $data['impuestos'] ?? [];
    $impuestos = [
        'iva_debitos'           => isset($imp['iva_debitos'])           && $imp['iva_debitos']           ? round((float)$imp['iva_debitos'], 2)           : null,
        'iva_percepcion'        => isset($imp['iva_percepcion'])        && $imp['iva_percepcion']        ? round((float)$imp['iva_percepcion'], 2)        : null,
        'imp_deb_cred_banc'     => isset($imp['imp_deb_cred_banc'])     && $imp['imp_deb_cred_banc']     ? round((float)$imp['imp_deb_cred_banc'], 2)     : null,
        'ret_ley25413_creditos' => isset($imp['ret_ley25413_creditos']) && $imp['ret_ley25413_creditos'] ? round((float)$imp['ret_ley25413_creditos'], 2) : null,
        'ret_ley25413_debitos'  => isset($imp['ret_ley25413_debitos'])  && $imp['ret_ley25413_debitos']  ? round((float)$imp['ret_ley25413_debitos'], 2)  : null,
        'credito_computable'    => isset($imp['credito_computable'])    && $imp['credito_computable']    ? round((float)$imp['credito_computable'], 2)    : null,
        'iibb_tucuman'          => isset($imp['iibb_tucuman'])          && $imp['iibb_tucuman']          ? round((float)$imp['iibb_tucuman'], 2)          : null,
        'iibb_sircreb'          => isset($imp['iibb_sircreb'])          && $imp['iibb_sircreb']          ? round((float)$imp['iibb_sircreb'], 2)          : null,
        'retencion_sircreb'     => isset($imp['retencion_sircreb'])     && $imp['retencion_sircreb']     ? round((float)$imp['retencion_sircreb'], 2)     : null,
        'comision_cuenta'       => isset($imp['comision_cuenta'])       && $imp['comision_cuenta']       ? round((float)$imp['comision_cuenta'], 2)       : null,
    ];

    return ['movimientos' => $movs, 'cabecera' => $cabecera, 'impuestos' => $impuestos];
}

// ═════════════════════════════════════════════════════════════════════════
//  PARSER RÁPIDO v2: extrae movimientos de texto etiquetado sin IA
//  Integra parseMonto, inferirTipoPorDesc e inferirDCPorSaldo del código
//  SIN IA para máxima precisión sin llamar a OpenAI.
// ═════════════════════════════════════════════════════════════════════════

/**
 * Parsea montos argentinos (robusto): 1.234,56 | 1,234.56 | 800,00 | -142.657,70
 * Excluye CUITs/CBUs (>999 mil millones). Maneja guión al final.
 */
function _parsearMontoAR(string $s): ?float {
    $s = str_replace([' ', '$', "\xc2\xa0", '%'], '', trim($s));
    if ($s === '' || $s === 'null') return null;
    // Guión al final (Nación: "258.637,70-") → llevar al inicio
    if (substr($s, -1) === '-' && strlen($s) > 1) $s = '-' . substr($s, 0, -1);
    $s = preg_replace('/[^\d.,\-]/', '', $s);
    if ($s === '' || $s === '-') return null;

    $neg = ($s[0] === '-');
    $s   = ltrim($s, '-');

    $posC = strrpos($s, ',');
    $posP = strrpos($s, '.');

    if ($posC !== false && $posP !== false) {
        if ($posC > $posP) {
            $s = str_replace('.', '', $s); $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }
    } elseif ($posC !== false) {
        $dec = strlen($s) - $posC - 1;
        $s = ($dec <= 2) ? str_replace(',', '.', $s) : str_replace(',', '', $s);
    } elseif ($posP !== false) {
        if (substr_count($s, '.') > 1) {
            $s = str_replace('.', '', $s);
        } else {
            $dec = strlen($s) - $posP - 1;
            if ($dec !== 2) $s = str_replace('.', '', $s);
        }
    }

    $f = (float)$s;
    if (abs($f) > 999999999999.0) return null;
    return round($neg ? -$f : $f, 2);
}

/**
 * Inferir tipo D/C por palabras clave en descripción.
 * Retorna 'D' (débito), 'C' (crédito), o 'I' (indeterminado).
 * 70+ patrones de todos los bancos argentinos.
 */
function _inferirTipoPorDesc(string $desc): string {
    $d = mb_strtolower($desc);

    // ── DÉBITOS claros ─────────────────────────────────────────────
    // Impuestos y gravámenes
    if (preg_match('/\bgravamen\b/i', $d))                     return 'D';
    if (preg_match('/imp[\.\s]*db[\/\s]*cr[\.\s]*banc/i', $d)) return 'D';
    if (preg_match('/imp[\.\s]*cre[\.\s]*ley/i', $d))          return 'D';
    if (preg_match('/imp[\.\s]*deb[\.\s]*ley/i', $d))          return 'D';
    if (preg_match('/impuesto\s+ley\s+25[.,]413/i', $d))       return 'D';
    if (preg_match('/\b(ret\.?iibb|iibb\s+(sircreb|tucuman)|recaud\s+iibb|sircreb)\b/i', $d)) return 'D';
    if (preg_match('/\bpercepci[oó]n\b/i', $d))                return 'D';
    if (preg_match('/\bpercep[\.\s]*iva\b/i', $d))             return 'D';
    if (preg_match('/\bing[\.\s]*brutos\b/i', $d))             return 'D';
    if (preg_match('/\bsellos\b/i', $d))                       return 'D';
    // IVA / I.V.A.
    if (preg_match('/\biva\b/i', $d))                          return 'D';
    if (preg_match('/\bi\.v\.a\.\b/i', $d))                    return 'D';
    if (preg_match('/\bv[\.\s]*a[\.\s]*\s*base\b/i', $d))      return 'D';
    // Comisiones
    if (preg_match('/\bcomis/i', $d))                          return 'D';
    // Intereses
    if (preg_match('/\bintereses\b/i', $d))                    return 'D';
    // Débitos automáticos
    if (preg_match('/\bdeb[\.\s]*aut(om)?\b/i', $d))           return 'D';
    if (preg_match('/\bdebito\s+autom/i', $d))                 return 'D';
    // Pagos y cargos
    if (preg_match('/\b(pago\s+(de\s+)?serv|pago\s+afip|pago\s+de\s+servicios)\b/i', $d)) return 'D';
    if (preg_match('/\b(tarjeta\s+cred|pago\s+visa|pago\s+cci|pago\s+mastercard)\b/i', $d)) return 'D';
    if (preg_match('/\b(servicio\s+terminal|cargo\s+(por|de))\b/i', $d)) return 'D';
    if (preg_match('/\bcontracargo\b/i', $d))                  return 'D';
    if (preg_match('/\b(extracci[oó]n|retiro|descuento)\b/i', $d)) return 'D';
    // Transferencias SALIENTES
    if (preg_match('/\btransf[\.\s]*(a\s+)?terceros\b/i', $d)) return 'D';
    if (preg_match('/\btransfer(encia)?\s+realizad/i', $d))    return 'D';
    if (preg_match('/\btransferencia\s+inmediata\s+a\b/i', $d))return 'D';
    if (preg_match('/\btransferencia\s+enviad/i', $d))         return 'D';
    if (preg_match('/\bdebito\s+transf\b/i', $d))              return 'D';
    if (preg_match('/\btransf[\.\s]+inmed\s+proveed\b/i', $d)) return 'D';
    if (preg_match('/\bsnp\s+pago\s+a\s+proveedores\b/i', $d))return 'D';
    if (preg_match('/\bcobrado\s+por\s+caja\b/i', $d))         return 'D';
    // Seguros
    if (preg_match('/\bseguros?\s*\d/i', $d))                  return 'D';
    if (preg_match('/\bgalseguros\b/i', $d))                   return 'D';

    // ── CRÉDITOS claros ────────────────────────────────────────────
    // DEBIN / CR DEBIN
    if (preg_match('/\bdebin\b/i', $d))                        return 'C';
    if (preg_match('/\bebin\b/i', $d))                         return 'C';
    if (preg_match('/\bcr[\.\s]*debin\b/i', $d))               return 'C';
    // Depósitos
    if (preg_match('/\bdep[\.\s]*ch\b/i', $d))                 return 'C';
    if (preg_match('/\bdeposito\b/i', $d))                     return 'C';
    // Transferencias ENTRANTES
    if (preg_match('/\btransferencia\s+de\s+terceros\b/i', $d))return 'C';
    if (preg_match('/\bcredito\s+transferencia\b/i', $d))      return 'C';
    if (preg_match('/\btransferencia\s+recib/i', $d))          return 'C';
    if (preg_match('/\btransf\s+recibida/i', $d))              return 'C';
    if (preg_match('/\bcredito\s+por\s+(transf|dep)/i', $d))   return 'C';
    if (preg_match('/\bacredit/i', $d))                        return 'C';
    if (preg_match('/\btransf[\.\s]+recibida\s+cvu\b/i', $d))  return 'C';
    if (preg_match('/\btransferencia\s+haberes\b/i', $d))      return 'C';
    // Rendiciones / Recaudación
    if (preg_match('/\b(rend[\.\s]*p[\/\s]*serv|rend\.p|rendici[oó]n)\b/i', $d)) return 'C';
    // Transferencias interbancarias (crédito en Nación)
    if (preg_match('/transf[\.\s]*int[\.\s]*(dist|nac|bco|banel|red|link)/i', $d)) return 'C';
    if (preg_match('/transf\.int/i', $d))                      return 'C';
    // CR.TR. / BCA.E.TR.
    if (preg_match('/\bcr[\.\s]*[ti]r[\.\s]/i', $d))           return 'C';
    if (preg_match('/\bbca[\.\s]*e[\.\s]*tr\b/i', $d))         return 'C';
    // Ventas / Cobros
    if (preg_match('/\b(nave[\s\-]+venta|venta\s+con\s+tarjeta)\b/i', $d)) return 'C';
    if (preg_match('/\b(getnet|coelsa)\b/i', $d))              return 'C';
    if (preg_match('/\b(rescate\s+fima|rescate\s+fondos)\b/i', $d)) return 'C';
    if (preg_match('/\bpago\s+a\s+proveedores\s+recibid/i', $d)) return 'C';
    // Mercado Pago
    if (preg_match('/\bcobro\s+(con|qr|link|point)/i', $d))    return 'C';
    if (preg_match('/\bdinero\s+recibido\b/i', $d))            return 'C';
    if (preg_match('/\bventa\s+(mercado\s+libre|ml)\b/i', $d)) return 'C';

    return 'I'; // indeterminado
}

/**
 * Segundo pase: corrige movimientos de tipo indeterminado usando diferencia de saldo.
 * Algoritmo acumulativo del código SIN IA.
 */
function _inferirDCPorSaldo(array &$movs, ?float $saldoInicial = null): void {
    $prevSaldo = $saldoInicial;
    $accumD = 0.0;
    $accumC = 0.0;

    foreach ($movs as &$m) {
        if ($m['tipo'] === 'I') {
            $montoOp = $m['debito'] ?? $m['credito'] ?? null;

            if ($m['saldo'] !== null && $prevSaldo !== null) {
                $saldoBase = $prevSaldo - $accumD + $accumC;
                $diff = $m['saldo'] - $saldoBase;

                if ($montoOp !== null) {
                    if ($diff > 0.5) {
                        $m['credito'] = abs($montoOp); $m['debito'] = null; $m['tipo'] = 'C';
                    } elseif ($diff < -0.5) {
                        $m['debito'] = abs($montoOp); $m['credito'] = null; $m['tipo'] = 'D';
                    }
                } else {
                    if ($diff > 0.5)      { $m['credito'] = round(abs($diff), 2); $m['tipo'] = 'C'; }
                    elseif ($diff < -0.5) { $m['debito']  = round(abs($diff), 2); $m['tipo'] = 'D'; }
                }
            } else {
                if ($montoOp !== null) {
                    $m['debito'] = abs($montoOp); $m['credito'] = null; $m['tipo'] = 'D';
                }
            }
        }

        // Acumular
        if ($m['tipo'] === 'D' && ($m['debito'] ?? 0) > 0)  $accumD += $m['debito'];
        if ($m['tipo'] === 'C' && ($m['credito'] ?? 0) > 0) $accumC += $m['credito'];

        // Reset en cada checkpoint de saldo
        if ($m['saldo'] !== null) {
            $prevSaldo = $m['saldo'];
            $accumD = 0.0;
            $accumC = 0.0;
        }
    }
    unset($m);
}

/**
 * Parser rápido por regex para texto con etiquetas [DEBITO:], [CREDITO:], [SALDO:].
 * Incluye verificación por keywords y saldo para máxima precisión.
 * Retorna array de movimientos o null si no hay suficientes etiquetas.
 */
function _parsearTextoEtiquetado(string $texto): ?array {
    $countTags = preg_match_all('/\[(DEBITO|CREDITO|SALDO):/', $texto);
    if ($countTags < 3) return null;

    $movimientos = [];
    $lineas = explode("\n", $texto);

    $rxFecha   = '/^(\d{1,2}\/\d{2}\/?\d{0,4})\s+/';
    $rxDebito  = '/\[DEBITO:([^\]]+)\]/';
    $rxCredito = '/\[CREDITO:([^\]]+)\]/';
    $rxSaldo   = '/\[SALDO:([^\]]+)\]/';

    foreach ($lineas as $linea) {
        $t = trim($linea);
        if (!$t) continue;
        if (!preg_match($rxFecha, $t, $mFecha)) continue;

        // Fecha
        $fechaRaw = $mFecha[1];
        $partes   = explode('/', $fechaRaw);
        $dia = intval($partes[0]);
        $mes = intval($partes[1]);
        $anio = isset($partes[2]) && $partes[2] !== '' ? intval($partes[2]) : null;
        if ($anio !== null && $anio < 100) $anio += 2000;
        if ($anio === null) $anio = (int) date('Y');
        if (!checkdate($mes, $dia, $anio)) continue;
        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);

        // Descripción
        $sinFecha = preg_replace($rxFecha, '', $t);
        $desc = preg_replace('/\t?\[(DEBITO|CREDITO|SALDO):[^\]]*\]/', '', $sinFecha);
        // Limpiar CUITs y números largos de la descripción
        $desc = preg_replace('/\b\d{11,}\b/', '', $desc);
        $desc = trim(preg_replace('/\s{2,}/', ' ', $desc));

        // Descartar SALDO ANTERIOR/ACTUAL/INICIAL/FINAL
        if (preg_match('/SALDO\s*(ANTERIOR|ACTUAL|INICIAL|FINAL)/i', $desc)) continue;
        // Descartar headers repetidos
        if (preg_match('/^(FECHA|Movimientos|Resumen|Descripci[oó]n|Origen|P[aá]gina)/i', $desc)) continue;

        // Extraer montos
        $debito  = null;
        $credito = null;
        $saldo   = null;
        if (preg_match($rxDebito, $t, $md))  $debito  = _parsearMontoAR($md[1]);
        if (preg_match($rxCredito, $t, $mc)) $credito = _parsearMontoAR($mc[1]);
        if (preg_match($rxSaldo, $t, $ms))   $saldo   = _parsearMontoAR($ms[1]);

        if ($debito === null && $credito === null && $saldo === null) continue;

        // Verificar con keywords — si la etiqueta dice débito pero el keyword dice crédito, investigar
        $tipoKw = _inferirTipoPorDesc($desc);

        $tipo = 'I';
        if ($debito !== null && $debito > 0) {
            $tipo = 'D';
            if ($tipoKw === 'C' && $credito === null) {
                $credito = $debito; $debito = null; $tipo = 'C';
            }
        }
        if ($credito !== null && $credito > 0) {
            $tipo = 'C';
            if ($tipoKw === 'D' && $debito === null) {
                $debito = $credito; $credito = null; $tipo = 'D';
            }
        }

        // Si solo hay saldo sin débito ni crédito → inferir por keyword
        if ($debito === null && $credito === null && $saldo !== null) {
            if ($tipoKw === 'D')      { $debito = $saldo; $tipo = 'D'; $saldo = null; }
            elseif ($tipoKw === 'C')  { $credito = $saldo; $tipo = 'C'; $saldo = null; }
            else continue;
        }

        // Comprobante (5-9 dígitos que no son CUIT ni monto)
        $comprobante = null;
        if (preg_match('/(?<!\d)(\d{5,9})(?!\d)/', $desc, $cm)) {
            $posible = $cm[1];
            if (strlen($posible) < 11 && !preg_match('/[\d.,]' . preg_quote($posible, '/') . '/', $t)) {
                $comprobante = $posible;
                $desc = trim(str_replace($posible, '', $desc));
            }
        }

        if (mb_strlen($desc) < 2) $desc = 'Movimiento';

        $movimientos[] = [
            'fecha'       => $fecha,
            'descripcion' => $desc,
            'comprobante' => $comprobante,
            'debito'      => ($debito !== null && $debito > 0) ? round($debito, 2) : null,
            'credito'     => ($credito !== null && $credito > 0) ? round($credito, 2) : null,
            'saldo'       => $saldo !== null ? round(abs($saldo), 2) : null,
            'tipo'        => $tipo,
            'observacion' => null,
        ];
    }

    if (empty($movimientos)) return null;

    // Segundo pase: verificar y corregir ambiguos por diferencia de saldo
    _inferirDCPorSaldo($movimientos);

    return $movimientos;
}

/**
 * Extrae cabecera (CUIT, CBU, cuenta, período, saldos, titular) del texto con regex.
 */
function _extraerCabeceraRegex(string $texto): array {
    $cab = [
        'titular'       => null,
        'cuit'          => null,
        'condicion_iva' => null,
        'tipo_cuenta'   => null,
        'numero_cuenta' => null,
        'cbu'           => null,
        'moneda'        => 'ARS',
        'sucursal'      => null,
        'periodo_desde' => null,
        'periodo_hasta' => null,
        'saldo_inicial' => null,
        'saldo_final'   => null,
    ];

    // CUIT: 30-12345678-9 / 20-12345678-9 / 27-12345678-9
    if (preg_match('/CUIT[:\s]*(\d{2}[-\s]?\d{7,8}[-\s]?\d)/i', $texto, $m))
        $cab['cuit'] = preg_replace('/\D/', '', $m[1]);

    // CBU: 22 dígitos
    if (preg_match('/CBU[:\s]*(\d{22})/i', $texto, $m))
        $cab['cbu'] = $m[1];

    // Número de cuenta
    if (preg_match('/(?:N[°º]?\s*(?:DE\s+)?CUENTA|NRO\.?\s*CUENTA|CUENTA\s*N[°º]?|N[úu]mero\s+de\s+cuenta)[:\s]*([0-9\-\/\s]{4,30})/i', $texto, $m))
        $cab['numero_cuenta'] = trim(preg_replace('/\s+/', '', $m[1]));

    // Tipo de cuenta
    if (preg_match('/(CUENTA\s+CORRIENTE|CAJA\s+DE\s+AHORRO|CUENTA\s+(?:DE\s+)?AHORRO)/i', $texto, $m))
        $cab['tipo_cuenta'] = $m[1];

    // Moneda
    if (preg_match('/\b(D[OÓ]LARES?|USD|U\$S|US\$)\b/i', $texto))
        $cab['moneda'] = 'USD';

    // Período: "PERIODO: DD/MM/YYYY AL DD/MM/YYYY" o "Período de movimientos"
    if (preg_match('/(?:PERIODO|PERÍODO|Per[ií]odo)[:\s]*(?:DE\s+)?(?:movimientos\s+)?(?:\$[\d.,]+\s+\$[\d.,]+\s+)?(\d{1,2}\/\d{2}\/\d{2,4})\s*(?:AL?|A|-|HASTA|a)\s*(\d{1,2}\/\d{2}\/\d{2,4})/i', $texto, $m)) {
        $cab['periodo_desde'] = _formatearFecha($m[1]);
        $cab['periodo_hasta'] = _formatearFecha($m[2]);
    }
    // Galicia: "Período de movimientos" seguido de fechas en formato especial
    if ($cab['periodo_desde'] === null && preg_match('/(\d{1,2}\/\d{2}\/\d{4})\s+(\d{1,2}\/\d{2}\/\d{4})\s+(?:Saldos|Per[ií]odo)/i', $texto, $m2)) {
        $cab['periodo_hasta'] = _formatearFecha($m2[1]);
        $cab['periodo_desde'] = _formatearFecha($m2[2]);
    }

    // Sucursal
    if (preg_match('/(?:SUCURSAL|SUC[:\.])[:\s]*(\d+(?:\s*[-]\s*\d+)?|[A-ZÁÉÍÓÚ][A-ZÁÉÍÓÚ\s]{2,30})/i', $texto, $m))
        $cab['sucursal'] = trim($m[1]);

    // Titular
    if (preg_match('/(?:TITULAR|DENOMINACI[OÓ]N|RAZ[OÓ]N\s+SOCIAL|CLIENTE)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s,\.\-]{3,60})/i', $texto, $m))
        $cab['titular'] = trim($m[1]);
    // Galicia: busca nombre del titular en header antes del CUIT
    if ($cab['titular'] === null && preg_match('/(?:FARMACIA|UNION|EMPRESA|SOCIEDAD|[A-ZÁÉÍÓÚ]{3,})\s+(?:REX|DE|DEL|S\.?A\.?|S\.?R\.?L\.?)\s+[A-ZÁÉÍÓÚÑ\s]{2,}/i', $texto, $m))
        $cab['titular'] = trim($m[0]);

    // Saldo anterior/inicial
    if (preg_match('/SALDO\s*(?:ANTERIOR|INICIAL)[:\s]*\$?\s*([\d.,]+)/i', $texto, $m))
        $cab['saldo_inicial'] = _parsearMontoAR($m[1]);
    // Galicia saldos: "$371.884,97 $157.503,45 Saldos"
    if ($cab['saldo_inicial'] === null && preg_match('/\$([\d.,]+)\s+\$([\d.,]+)\s+Saldos/i', $texto, $m2))
        $cab['saldo_inicial'] = _parsearMontoAR($m2[2]);

    // Condición IVA
    if (preg_match('/IVA[:\s]*(Responsable\s+[Ii]nscripto|Monotribut\w*|Exento)/i', $texto, $m))
        $cab['condicion_iva'] = trim($m[1]);

    return $cab;
}

function _formatearFecha(string $f): ?string {
    $p = explode('/', $f);
    if (count($p) < 3) return null;
    $a = intval($p[2]); if ($a < 100) $a += 2000;
    $m = intval($p[1]); $d = intval($p[0]);
    if (!checkdate($m, $d, $a)) return null;
    return sprintf('%04d-%02d-%02d', $a, $m, $d);
}

// ═════════════════════════════════════════════════════════════════════════
//  PARSER SIN ETIQUETAS: extrae movimientos de texto plano (sin tags)
//  Fallback para bancos donde el frontend no detecta columnas (BBVA, MP)
// ═════════════════════════════════════════════════════════════════════════

/**
 * Extrae todos los montos de una línea de texto con su posición.
 * Retorna array de ['v' => float, 'pos' => int, 'str' => string]
 */
function _extraerMontos(string $linea): array {
    $montos = [];
    // Regex: captura montos argentinos con $ opcional, negativos, guión final
    $re = '/(?<![0-9])(-?\$?\s*\d{1,3}(?:[.,]\d{3})*[.,]\d{2})(?:-)?(?![0-9])/';
    if (preg_match_all($re, $linea, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $m) {
            $raw = $m[0];
            $pos = $m[1];
            $v = _parsearMontoAR($raw);
            if ($v !== null && abs($v) > 0.01 && abs($v) < 999999999.0) {
                $montos[] = ['v' => $v, 'pos' => $pos, 'str' => $raw];
            }
        }
    }
    return $montos;
}

/**
 * Parser sin etiquetas: extrae movimientos de texto plano.
 * Soporta: BBVA (dd/mm, signo), MP, y cualquier formato tabulado.
 * Usa: fecha + montos + signo + keywords + saldo para clasificar D/C.
 */
function _parsearTextoSinEtiquetas(string $texto): ?array {
    $movs = [];
    $texto = str_replace(["\r\n", "\r"], "\n", $texto);
    $lineas = explode("\n", $texto);

    // Inferir año del PERIODO header
    $anioInferido = date('Y');
    if (preg_match('/(?:PERIODO|PERÍODO)\s*:?\s*\d{1,2}[\/\-]\d{2}[\/\-](\d{4})/i', $texto, $pm))
        $anioInferido = $pm[1];
    elseif (preg_match('/(?:PERIODO|PERÍODO)\s*:?\s*\d{1,2}[\/\-]\d{2}[\/\-](\d{2})\b/i', $texto, $pm))
        $anioInferido = '20' . $pm[1];
    elseif (preg_match('/(?:ENERO|FEBRERO|MARZO|ABRIL|MAYO|JUNIO|JULIO|AGOSTO|SEPTIEMBRE|OCTUBRE|NOVIEMBRE|DICIEMBRE)\s+(\d{4})/i', $texto, $pm))
        $anioInferido = $pm[1];

    // Regex para fechas: dd/mm, dd/mm/yy, dd/mm/yyyy, dd-mm-yyyy, con posible " D" después
    $reFecha = '/^\s*(\d{1,2}[\/-]\d{2}(?:[\/-]\d{2,4})?)\s*(?:D\b)?\s/';
    $reFechaMP = '/^\s*(\d{2}-\d{2}-\d{4})\s/';
    $reSkip  = '/^(FECHA|SALDO\s*(ANTERIOR|ACTUAL|INICIAL|FINAL)|TRANSPORTE|P[aá]gina\s+\d|Resumen\s+de|Movimientos|Descripci[oó]n|Banco\s+BBVA|Sobre\s*\(|CBU\s+\d|Entradas:|Salidas:|Saldo\s+(inicial|final))/i';
    $reHeader = '/D[EÉ]BITO|CR[EÉ]DITO|CONCEPTO|COMPROBANTE|MOVIMIENTO|Valor\s/i';
    // Stop markers: si encontramos estas líneas, dejar de parsear (BBVA secondary tables)
    $reStop = '/^(SALDO\s+AL\s+\d|TOTAL\s+(MOVIMIENTOS|COBRADO|DEV)|RETENCIONES\s+ARBA|D[eé]bitos\s+autom[aá]|Legales\s+y\s+avisos|El\s+credito\s+de\s+impuesto)/i';
    // Skip detail lines from BBVA (second section with CUIT/company numbers)
    $reDetail = '/^\d{1,2}\/\d{2}\s+\d{3,4}\s+\d{10,}/';
    // Page markers (MP: 1/63, 2/63...)
    $rePageMarker = '/^\d{1,3}\/\d{1,3}$/';
    // MP multi-line: description on prev line, amounts on this line
    $pendienteDesc = null;

    $enTabla = false;
    $finTabla = false;

    foreach ($lineas as $linea) {
        $t = trim($linea);
        if ($t === '') continue;
        if (preg_match($rePageMarker, $t)) continue; // skip page markers

        // Stop marker: dejar de parsear
        if (preg_match($reStop, $t)) { $finTabla = true; continue; }
        if ($finTabla) continue;

        // MP: line is just a name continuation (no date, no amounts) → save as pending desc
        if (!preg_match($reFecha, $t) && !preg_match($reFechaMP, $t)
            && !preg_match('/\$/', $t) && mb_strlen($t) < 60 && mb_strlen($t) > 1
            && preg_match('/^[A-ZÁÉÍÓÚÑa-záéíóúñ\s,\.\-]+$/', $t)) {
            // Could be continuation of prev description or standalone name
            if ($pendienteDesc !== null) $pendienteDesc .= ' ' . $t;
            continue;
        }

        // Skip noise/header lines
        if (preg_match($reSkip, $t)) { $enTabla = true; continue; }
        if (preg_match($reHeader, $t) && !preg_match($reFecha, $t)) { $enTabla = true; continue; }
        // Skip detail section lines (BBVA second section with CUIT numbers)
        if (preg_match($reDetail, $t)) continue;
        // Skip garbage/OCR artifacts
        if (preg_match('/^[\x{00C0}-\x{00FF}\x{0100}-\x{017F}]{2,}/u', $t)) continue;

        if (!$enTabla && (preg_match($reFecha, $t) || preg_match($reFechaMP, $t))) $enTabla = true;
        if (!$enTabla) continue;

        // Detectar fecha (BBVA: dd/mm D, MP: dd-mm-yyyy)
        $fechaRaw = null;
        if (preg_match($reFechaMP, $t, $fm)) {
            $fechaRaw = $fm[1];
        } elseif (preg_match($reFecha, $t, $fm)) {
            $fechaRaw = $fm[1];
        } else {
            // MP: línea con descripción sin fecha que será continuación
            if (preg_match('/^(Transferencia enviada|Pago con QR|Pago de suscripci)/i', $t)) {
                $pendienteDesc = preg_replace('/\s+\d{11}\s*$/', '', $t);
            }
            continue;
        }

        // Normalizar fecha: dd-mm-yyyy → dd/mm/yyyy
        $fechaRaw = str_replace('-', '/', $fechaRaw);

        // Completar fecha sin año
        $partes = explode('/', $fechaRaw);
        if (count($partes) === 2) {
            $fechaRaw .= '/' . $anioInferido;
        } elseif (count($partes) === 3 && strlen($partes[2]) === 2) {
            $fechaRaw = $partes[0] . '/' . $partes[1] . '/20' . $partes[2];
        }

        $fecha = _formatearFecha($fechaRaw);
        if (!$fecha) { $pendienteDesc = null; continue; }

        // Extraer montos del texto (strip $ para regex)
        $lineaMontos = preg_replace('/\\$\s*/', '', $t);
        $montos = _extraerMontos($lineaMontos);
        if (empty($montos)) { $pendienteDesc = null; continue; }

        // Descripción: quitar fecha y montos
        $desc = preg_replace('/^\s*\d{1,2}[\/-]\d{2}(?:[\/-]\d{2,4})?\s*(?:D\b)?\s+/', '', $t);
        // Si hay descripción pendiente (MP multi-línea), anteponer
        if ($pendienteDesc !== null) {
            $desc = $pendienteDesc . ' ' . $desc;
            $pendienteDesc = null;
        }
        // Quitar comprobante numérico (BBVA: "587 DNET...", MP: "97743970919")
        $comprobante = null;
        if (preg_match('/^(\d{3})\s+/', $desc, $cm)) {
            $comprobante = $cm[1];
            $desc = substr($desc, strlen($cm[0]));
        }
        foreach ($montos as $mc) {
            $desc = str_replace($mc['str'], '', $desc);
        }
        // Quitar $ y montos con $ del desc
        $desc = preg_replace('/\$\s*-?\d[\d.,]*/', '', $desc);
        $desc = str_replace('$', '', $desc);  // residual $ signs
        $desc = preg_replace('/\b\d{11,}\b/', '', $desc);  // CUITs
        if (!$comprobante && preg_match('/(?<!\d)(\d{5,9})(?!\d)/', $desc, $cm)) {
            $comprobante = $cm[1];
            $desc = str_replace($cm[0], '', $desc);
        }
        $desc = trim(preg_replace('/[\t\s]{2,}/', ' ', $desc));
        $desc = trim(preg_replace('/^[\-\s]+|[\-\s]+$/', '', $desc));
        if (mb_strlen($desc) < 2) $desc = 'Movimiento';

        $n = count($montos);
        $debito = null;
        $credito = null;
        $saldo = null;

        if ($n >= 2) {
            // Último = saldo, penúltimo (o anterior) = operación
            $saldoVal = $montos[$n - 1]['v'];
            $montoOp  = $montos[$n - 2]['v'];
            $saldo = $saldoVal; // BBVA saldos pueden ser negativos, mantener signo

            // Clasificar por SIGNO del monto de operación
            if ($montoOp < -0.01) {
                $debito = abs($montoOp);
            } elseif ($montoOp > 0.01) {
                $credito = abs($montoOp);
            } else {
                continue; // monto cero
            }
        } elseif ($n === 1) {
            $montoOp = $montos[0]['v'];
            $tipoKw = _inferirTipoPorDesc($desc);
            if ($montoOp < -0.01) {
                $debito = abs($montoOp);
            } elseif ($montoOp > 0.01) {
                if ($tipoKw === 'D') $debito = abs($montoOp);
                else $credito = abs($montoOp);
            } else continue;
        }

        if ($debito === null && $credito === null) continue;

        // Verificación cruzada con keywords
        $tipoKw = _inferirTipoPorDesc($desc);
        $tipo = 'I';
        if ($debito !== null && $debito > 0) $tipo = 'D';
        if ($credito !== null && $credito > 0) $tipo = 'C';

        // Si el keyword contradice fuertemente y no hay saldo, reclasificar
        if ($tipo === 'D' && $tipoKw === 'C' && $saldo === null) {
            $credito = $debito; $debito = null; $tipo = 'C';
        } elseif ($tipo === 'C' && $tipoKw === 'D' && $saldo === null) {
            $debito = $credito; $credito = null; $tipo = 'D';
        }

        $movs[] = [
            'fecha'       => $fecha,
            'descripcion' => $desc,
            'comprobante' => $comprobante,
            'debito'      => ($debito !== null && $debito > 0) ? round($debito, 2) : null,
            'credito'     => ($credito !== null && $credito > 0) ? round($credito, 2) : null,
            'saldo'       => $saldo !== null ? round($saldo, 2) : null,
            'tipo'        => $tipo,
            'observacion' => null,
        ];
    }

    if (count($movs) < 2) return null;

    // Segundo pase: corregir ambiguos por saldo
    _inferirDCPorSaldo($movs);

    return $movs;
}

// ═════════════════════════════════════════════════════════════════════════
//  SISTEMA DE AUTO-APRENDIZAJE
//  Cuando la IA procesa un extracto, analiza el formato del texto y guarda
//  un "perfil" del banco. La próxima vez, usa ese perfil para parsear
//  por regex sin necesidad de IA → instantáneo.
// ═════════════════════════════════════════════════════════════════════════

define('PATRONES_FILE', __DIR__ . '/patrones_aprendidos.json');

/**
 * Carga los patrones aprendidos del archivo JSON.
 */
function _cargarPatrones(): array {
    if (!file_exists(PATRONES_FILE)) return [];
    $data = @json_decode(file_get_contents(PATRONES_FILE), true);
    return is_array($data) ? $data : [];
}

/**
 * Guarda los patrones aprendidos al archivo JSON.
 */
function _guardarPatrones(array $patrones): void {
    @file_put_contents(PATRONES_FILE, json_encode($patrones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ─────────────────────────────────────────────────────────────────────────
//  AUTO-APRENDIZAJE IA: Generación de patrones regex por IA
//  Después de que la IA extrae los movimientos, hace una segunda llamada
//  (rápida, gpt-4o-mini) para que genere los regex PHP exactos del banco.
//  Esos regex se guardan y la próxima vez se usan SIN necesidad de IA.
// ─────────────────────────────────────────────────────────────────────────

/**
 * Valida que un string sea un regex PHP compilable.
 * Retorna true si es válido, false si produce error.
 */
function _validarRegex(string $patron): bool {
    if (empty(trim($patron)) || $patron === 'null') return false;
    // Agregar delimitadores si el usuario los omitió
    $full = '/' . $patron . '/u';
    return @preg_match($full, '') !== false;
}

/**
 * Envuelve un regex sin delimitadores en delimitadores / / con flag u.
 * Si el patron ya tiene delimitadores los respeta.
 */
function _wrapRegex(string $patron): string {
    $patron = trim($patron);
    // Si ya tiene delimitador al inicio (/, #, ~, etc.) respetar
    if (strlen($patron) > 1 && in_array($patron[0], ['/', '#', '~', '!', '@'])) {
        return $patron;
    }
    return '/' . str_replace('/', '\\/', $patron) . '/u';
}

/**
 * Hace una llamada rápida a gpt-4o-mini para que analice el texto del extracto
 * y genere patrones regex PHP específicos para parsear ese banco sin IA.
 *
 * @param string $textoLimpio  Texto ya limpio del extracto (tabla de movimientos)
 * @param array  $movimientos  Movimientos ya extraídos por la IA (referencia de verdad)
 * @param string $banco        Nombre del banco detectado
 * @return array               Patrones generados, o [] si falló
 */
function _generarPatronesConIA(string $textoLimpio, array $movimientos, string $banco): array {
    // Solo las primeras 3500 chars de texto como muestra
    $muestra = mb_substr($textoLimpio, 0, 3500);

    // Solo los primeros 8 movimientos como referencia
    $movsMuestra = array_slice($movimientos, 0, 8);
    $movsJson    = json_encode($movsMuestra, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $systemPrompt = <<<'PROMPT'
Sos un experto en regex PHP para parsear extractos bancarios argentinos en texto plano.

TAREA: Analizá el texto de muestra de un extracto bancario y los movimientos que ya fueron extraídos
correctamente. Generá patrones regex PHP para que el sistema pueda parsear este formato SIN IA la próxima vez.

REGLAS OBLIGATORIAS:
- Todos los regex deben ser compatibles con preg_match() de PHP.
- NO incluir delimitadores externos (sin / al principio y al final). El sistema los agrega.
- Los grupos de captura deben usar paréntesis simples: (\d{2}\/\d{2}\/\d{4})
- Deben ser lo suficientemente generales (no hardcodear fechas o montos específicos).
- Si un campo no aplica para este formato, usar null.
- Devolver SOLO JSON válido, sin texto adicional ni bloques markdown.

CAMPOS DEL JSON:
{
  "regex_linea_movimiento": "regex para detectar si una línea ES inicio de movimiento (empieza con fecha)",
  "regex_captura_fecha": "regex con UN grupo de captura () para extraer la fecha cruda de la línea",
  "formato_fecha": "uno de: dd/mm/yyyy | dd/mm/yy | dd/mm | dd-mm-yyyy | yyyy-mm-dd",
  "estructura_montos": "uno de: monto_saldo | debito_credito_saldo | signo_unico | monto_solo",
  "montos_con_signo": true_o_false,
  "regex_captura_debito": "regex con UN grupo () para el monto de débito, o null si no aplica",
  "regex_captura_credito": "regex con UN grupo () para el monto de crédito, o null si no aplica",
  "regex_captura_monto_op": "regex con UN grupo () para el monto de operación cuando es columna única, o null",
  "regex_captura_saldo": "regex con UN grupo () para el saldo al final de línea, o null si no hay",
  "tiene_sufijo_D": true_o_false,
  "patrones_skip_linea": ["strings fijos (no regex) que identifican líneas de encabezado/ruido a saltear"],
  "patrones_inicio_tabla": ["regex (sin delimitadores) que detectan la primera línea de la tabla"],
  "patrones_fin_tabla": ["regex (sin delimitadores) que detectan el fin de la tabla"],
  "descripcion": "1 oración describiendo el formato de este extracto"
}
PROMPT;

    $userMsg = "BANCO: {$banco}\n\n=== MUESTRA DEL TEXTO ===\n{$muestra}\n\n=== MOVIMIENTOS EXTRAÍDOS (referencia) ===\n{$movsJson}";

    $body = [
        'model'           => 'gpt-4o-mini',
        'max_tokens'      => 2000,
        'temperature'     => 0,
        'response_format' => ['type' => 'json_object'],
        'messages'        => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userMsg],
        ],
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => OPENAI_URL,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_KEY,
        ],
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code !== 200 || !$resp) {
        error_log("_generarPatronesConIA: HTTP {$code} {$err} — sin patrones IA");
        return [];
    }

    $decoded = json_decode($resp, true);
    $raw     = $decoded['choices'][0]['message']['content'] ?? '';
    if (!$raw) return [];

    try {
        $resultado = parsearRespuestaJSON(trim($raw));
    } catch (\Throwable $e) {
        error_log("_generarPatronesConIA parse error: " . $e->getMessage());
        return [];
    }

    // Validar campos obligatorios y compilabilidad de cada regex
    $camposRegex = [
        'regex_linea_movimiento',
        'regex_captura_fecha',
        'regex_captura_debito',
        'regex_captura_credito',
        'regex_captura_monto_op',
        'regex_captura_saldo',
    ];
    foreach ($camposRegex as $campo) {
        if (!empty($resultado[$campo]) && $resultado[$campo] !== 'null') {
            if (!_validarRegex($resultado[$campo])) {
                error_log("_generarPatronesConIA: regex inválido en '{$campo}': " . $resultado[$campo]);
                $resultado[$campo] = null; // descartar el regex inválido
            }
        }
    }

    // Validar regex de patrones de inicio/fin
    foreach (['patrones_inicio_tabla', 'patrones_fin_tabla'] as $listaCampo) {
        if (!empty($resultado[$listaCampo]) && is_array($resultado[$listaCampo])) {
            $resultado[$listaCampo] = array_values(array_filter(
                $resultado[$listaCampo],
                fn($r) => !empty($r) && _validarRegex($r)
            ));
        }
    }

    if (empty($resultado['regex_linea_movimiento']) || empty($resultado['formato_fecha'])) {
        error_log("_generarPatronesConIA: patrones insuficientes para {$banco}");
        return [];
    }

    // ── FIX BUG 3: SELF-TEST ─────────────────────────────────────────────────
    // Verificar que el regex principal realmente detecta líneas en el texto de muestra.
    // Un regex que compila pero matchea 0 líneas es inútil y genera falsos positivos.
    $reTest = _wrapRegex($resultado['regex_linea_movimiento']);
    $hitsTest = 0;
    foreach (explode("\n", $muestra) as $lineaTest) {
        if (@preg_match($reTest, trim($lineaTest))) $hitsTest++;
    }
    $resultado['self_test_hits'] = $hitsTest;

    if ($hitsTest === 0) {
        error_log("_generarPatronesConIA: SELF-TEST FALLÓ para '{$banco}' — 0 hits. Construyendo regex desde movimientos...");
        // Construir regex de fecha determinista buscando las fechas reales en el texto
        $fallback = _construirRegexDesdeFechasReales($movsMuestra, $muestra);
        if ($fallback !== null) {
            $resultado['regex_linea_movimiento'] = $fallback['regex_linea'];
            $resultado['regex_captura_fecha']    = $fallback['regex_fecha'];
            $resultado['formato_fecha']          = $fallback['formato'];
            $resultado['self_test_hits']         = $fallback['hits'];
            $resultado['self_test_fallback']     = true;
            error_log("_generarPatronesConIA: fallback regex construido — {$fallback['hits']} hits, formato={$fallback['formato']}");
        } else {
            error_log("_generarPatronesConIA: imposible construir regex para '{$banco}' — descartando patrones");
            return [];
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    error_log("_generarPatronesConIA OK para '{$banco}': " . ($resultado['descripcion'] ?? '') . " [{$resultado['self_test_hits']} hits]");
    return $resultado;
}

/**
 * Construye regex de detección de línea de movimiento de forma determinista,
 * buscando las fechas reales de los movimientos dentro del texto de muestra.
 * Es el fallback cuando el regex generado por IA no detecta ninguna línea.
 *
 * @param array  $movs   Primeros movimientos extraídos por IA (con campo 'fecha' en YYYY-MM-DD)
 * @param string $texto  Texto de muestra del extracto
 * @return array|null    ['regex_linea', 'regex_fecha', 'formato', 'hits'] o null si falla
 */
function _construirRegexDesdeFechasReales(array $movs, string $texto): ?array {
    // Candidatos de formato de fecha a probar, ordenados por frecuencia en Argentina
    $formatos = [
        'dd/mm/yyyy' => ['/^\d{2}\/\d{2}\/\d{4}\b/', '/^(\d{2}\/\d{2}\/\d{4})\s/'],
        'dd/mm/yy'   => ['/^\d{2}\/\d{2}\/\d{2}\b/',  '/^(\d{2}\/\d{2}\/\d{2})\s/'],
        'dd-mm-yyyy' => ['/^\d{2}-\d{2}-\d{4}\b/',    '/^(\d{2}-\d{2}-\d{4})\s/'],
        'dd/mm'      => ['/^\d{1,2}\/\d{2}\s/',         '/^(\d{1,2}\/\d{2})\s/'],
        'dd-mm'      => ['/^\d{1,2}-\d{2}\s/',          '/^(\d{1,2}-\d{2})\s/'],
    ];

    // Recolectar variantes de fecha de los movimientos
    $variantesBuscadas = [];
    foreach ($movs as $m) {
        $f = $m['fecha'] ?? '';
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $f, $pm)) continue;
        [$_, $a, $mes, $d] = $pm;
        $a2 = substr($a, 2);
        $variantesBuscadas[] = "{$d}/{$mes}/{$a}";   // dd/mm/yyyy
        $variantesBuscadas[] = "{$d}/{$mes}/{$a2}";  // dd/mm/yy
        $variantesBuscadas[] = "{$d}-{$mes}-{$a}";   // dd-mm-yyyy
        $variantesBuscadas[] = "{$d}/{$mes}";         // dd/mm
        $variantesBuscadas[] = "{$d}-{$mes}";         // dd-mm
    }
    $variantesBuscadas = array_unique($variantesBuscadas);

    // Verificar qué variante aparece en el texto
    $formatoDetectado = null;
    foreach ($variantesBuscadas as $var) {
        if (mb_strpos($texto, $var) !== false) {
            // Determinar qué formato es
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $var)) { $formatoDetectado = 'dd/mm/yyyy'; break; }
            if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $var)) { $formatoDetectado = 'dd/mm/yy'; break; }
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $var))   { $formatoDetectado = 'dd-mm-yyyy'; break; }
            if (preg_match('/^\d{1,2}\/\d{2}$/', $var))       { $formatoDetectado = 'dd/mm'; break; }
            if (preg_match('/^\d{1,2}-\d{2}$/', $var))        { $formatoDetectado = 'dd-mm'; break; }
        }
    }

    if ($formatoDetectado === null) {
        // Último intento: contar qué formato aparece más en el texto completo
        $counts = [];
        foreach ($formatos as $fmt => $resPair) {
            $count = preg_match_all($resPair[0], $texto);
            if ($count !== false) $counts[$fmt] = $count;
        }
        if (!empty($counts)) {
            arsort($counts);
            $formatoDetectado = array_key_first($counts);
        }
    }

    if ($formatoDetectado === null || !isset($formatos[$formatoDetectado])) return null;

    [$reLinea, $reFecha] = $formatos[$formatoDetectado];

    // Contar hits reales en el texto
    $hits = 0;
    foreach (explode("\n", $texto) as $l) {
        if (@preg_match($reLinea, trim($l))) $hits++;
    }

    if ($hits === 0) return null;

    // Devolver sin delimitadores (el caller los agrega con _wrapRegex)
    $reLineaSinDelim = trim($reLinea, '/');
    $reFechaSinDelim = trim($reFecha, '/');

    return [
        'regex_linea'  => $reLineaSinDelim,
        'regex_fecha'  => $reFechaSinDelim,
        'formato'      => $formatoDetectado,
        'hits'         => $hits,
    ];
}

/**
 * Elimina el campo de fecha del inicio de la línea para que _extraerMontos
 * no capture el día (02, 03...) como si fuera un monto.
 * Soporta: dd-MMM-yyyy, dd/mm/yyyy, dd-mm-yyyy, dd/mm/yy, dd/mm
 */
function _stripFechaDeLinea(string $linea): string {
    // Quitar dd-MMM-yyyy (02-FEB-2026) o dd-mm-yyyy o dd/mm/yyyy o dd/mm/yy o dd/mm
    return trim(preg_replace(
        '/^\d{1,2}[-\/][A-Z]{2,3}[-\/]\d{2,4}\s*|^\d{1,2}[-\/]\d{2}[-\/]\d{2,4}\s*|^\d{1,2}[-\/]\d{2}\s*/i',
        '',
        $linea
    ));
}

/**
 * Igual que _extraerMontos pero SOLO acepta montos con decimal explícito (coma o punto).
 * Evita que números enteros como "02" del día o referencias "20145315558" se parseen como monto.
 * Retorna array de ['v'=>float, 'str'=>string] igual que _extraerMontos.
 */
function _extraerMontosConDecimal(string $linea): array {
    $result = [];
    // Monto con decimal OBLIGATORIO en formato argentino.
    // \d{1,4} cubre grupos de 1-4 dígitos al inicio (ej: 1432.358,25 donde 1432 es el primer grupo).
    // Admite separadores de miles punto o coma, y decimal coma o punto.
    // NO matchea números enteros sin decimal → evita capturar el día "02" de la fecha.
    if (!preg_match_all(
        '/(?<![,.\d])(\d{1,4}(?:[.,]\d{3})*[.,]\d{2})(?![,.\d])/u',
        $linea, $matches, PREG_SET_ORDER
    )) {
        return [];
    }
    foreach ($matches as $m) {
        $v = _parsearMontoAR($m[1]);
        if ($v === null || abs($v) < 0.005) continue;
        if (abs($v) > 9999999999.0) continue; // excluir números absurdos
        $result[] = ['v' => $v, 'str' => $m[1]];
    }
    return $result;
}

/**
 * Parser que usa los regex generados por IA para extraer movimientos
 * sin necesidad de llamar a la IA otra vez.
 *
 * @param string $texto  Texto limpio del extracto
 * @param string $banco  Nombre del banco
 * @return array|null    Movimientos o null si no hay perfil IA o falla
 */
function _parsearConPatronesIA(string $texto, string $banco): ?array {
    $patrones = _cargarPatrones();

    $perfil     = null;
    $bancoUsado = $banco;

    // ── Paso A: coincidencia exacta por nombre ──────────────────────────────
    if (isset($patrones[$banco]) && !empty($patrones[$banco]['ia_patrones'])) {
        $perfil = $patrones[$banco];
        error_log("_parsearConPatronesIA: match exacto → '{$banco}'");
    }

    // ── Paso B: buscar por campo banco_detector ─────────────────────────────
    // FIX: cubre el caso donde el alias se guardó bajo el mismo nombre detector
    // pero ia_patrones vacío en el alias; buscamos el perfil con banco_detector==$banco
    if ($perfil === null) {
        foreach ($patrones as $nombreBanco => $p) {
            if (!empty($p['ia_patrones']) && ($p['banco_detector'] ?? '') === $banco) {
                $perfil     = $p;
                $bancoUsado = $nombreBanco;
                error_log("_parsearConPatronesIA: match banco_detector → '{$nombreBanco}' (detector='{$banco}')");
                break;
            }
        }
    }

    // ── Paso C: fingerprint mejorado (formato de fecha + regex hits) ────────
    if ($perfil === null) {
        $mejorScore = 0;
        $mejorBanco = null;

        foreach ($patrones as $nombreBanco => $p) {
            if (empty($p['ia_patrones'])) continue;
            $iap   = $p['ia_patrones'];
            $score = 0;

            // 1. Probar regex_linea_movimiento directamente sobre el texto real
            //    (máxima señal — si matchea muchas líneas es casi seguro el banco correcto)
            if (!empty($iap['regex_linea_movimiento'])) {
                $re   = _wrapRegex($iap['regex_linea_movimiento']);
                $hits = 0;
                foreach (explode("\n", mb_substr($texto, 0, 6000)) as $l) {
                    if (@preg_match($re, trim($l))) $hits++;
                }
                if ($hits >= 5)      $score += 10;
                elseif ($hits >= 2)  $score += 5;
                elseif ($hits >= 1)  $score += 2;
                error_log("_parsearConPatronesIA fingerprint '{$nombreBanco}': regex_hits={$hits}");
            }

            // 2. Formato de fecha — usar patrón genérico, NO fechas específicas
            //    (FIX BUG 2: las fechas cambian entre meses, el formato no)
            $fmt = $iap['formato_fecha'] ?? ($p['formato_fecha'] ?? '');
            $fmtPatrones = [
                'dd/mm/yyyy' => '/\d{2}\/\d{2}\/\d{4}/',
                'dd/mm/yy'   => '/\d{2}\/\d{2}\/\d{2}\b/',
                'dd-mm-yyyy' => '/\d{2}-\d{2}-\d{4}/',
                'dd/mm'      => '/^\d{1,2}\/\d{2}\s/m',
            ];
            if ($fmt && isset($fmtPatrones[$fmt]) && @preg_match($fmtPatrones[$fmt], $texto)) {
                $score += 3;
            }

            // 3. Keywords de encabezado de tabla (ej: "Fecha", "Concepto", "Débito")
            foreach (($p['header_keywords'] ?? []) as $kw) {
                $kw = trim($kw);
                if ($kw !== '' && mb_stripos($texto, $kw) !== false) $score += 2;
            }

            // 4. Ruido aprendido (textos repetitivos del banco)
            $ruidoHits = 0;
            foreach (($p['ruido'] ?? []) as $r) {
                if ($r !== '' && mb_stripos($texto, $r) !== false) $ruidoHits++;
            }
            if ($ruidoHits >= 3)     $score += 4;
            elseif ($ruidoHits >= 1) $score += 1;

            if ($score > $mejorScore) {
                $mejorScore = $score;
                $mejorBanco = $nombreBanco;
            }
        }

        if ($mejorScore >= 5 && $mejorBanco !== null) {
            $perfil     = $patrones[$mejorBanco];
            $bancoUsado = $mejorBanco;
            error_log("_parsearConPatronesIA: match fingerprint → '{$mejorBanco}' (score={$mejorScore})");
        } else {
            error_log("_parsearConPatronesIA: sin match — banco='{$banco}', mejor_score={$mejorScore}, perfiles_con_ia=" . count(array_filter($patrones, fn($p) => !empty($p['ia_patrones']))));
            return null;
        }
    }

    $iap = $perfil['ia_patrones'] ?? [];
    if (empty($iap['regex_linea_movimiento'])) {
        error_log("_parsearConPatronesIA: perfil '{$bancoUsado}' no tiene regex_linea_movimiento — abortando");
        return null;
    }

    // ── Compilar regex del perfil ──────────────────────────────────────────
    $reLinea   = _wrapRegex($iap['regex_linea_movimiento']);
    $reFecha   = !empty($iap['regex_captura_fecha'])    ? _wrapRegex($iap['regex_captura_fecha'])    : null;
    $reDebito  = !empty($iap['regex_captura_debito'])   ? _wrapRegex($iap['regex_captura_debito'])   : null;
    $reCredito = !empty($iap['regex_captura_credito'])  ? _wrapRegex($iap['regex_captura_credito'])  : null;
    $reMontoOp = !empty($iap['regex_captura_monto_op']) ? _wrapRegex($iap['regex_captura_monto_op']) : null;
    $reSaldo   = !empty($iap['regex_captura_saldo'])    ? _wrapRegex($iap['regex_captura_saldo'])    : null;

    $fmtFecha    = $iap['formato_fecha']       ?? 'dd/mm/yyyy';
    $conSigno    = (bool)($iap['montos_con_signo'] ?? false);
    $estructura  = $iap['estructura_montos']   ?? 'monto_saldo';
    $skipStrings = $iap['patrones_skip_linea'] ?? [];

    $reInicioTabla = [];
    foreach (($iap['patrones_inicio_tabla'] ?? []) as $r) {
        if ($rv = _wrapRegex($r)) $reInicioTabla[] = $rv;
    }
    $reFinTabla = [];
    foreach (($iap['patrones_fin_tabla'] ?? []) as $r) {
        if ($rv = _wrapRegex($r)) $reFinTabla[] = $rv;
    }

    // ── Inferir año desde el texto ──────────────────────────────────────────
    $anioInferido = (int)date('Y');
    if (preg_match('/(?:ENERO|FEBRERO|MARZO|ABRIL|MAYO|JUNIO|JULIO|AGOSTO|SEPTIEMBRE|OCTUBRE|NOVIEMBRE|DICIEMBRE)\s+(\d{4})/i', $texto, $pm))
        $anioInferido = (int)$pm[1];
    elseif (preg_match('/(\d{4})/', $texto, $pm) && (int)$pm[1] >= 2020)
        $anioInferido = (int)$pm[1];

    // Leer config de columnas tab (Fix Bug 2: índices de columna en lugar de regex idénticos)
    $colTab       = $iap['col_tab']      ?? [];
    $esColumnaTab = ($estructura === 'columnas_tab') || (!empty($colTab));

    $lineas = explode("\n", str_replace(["\r\n", "\r"], "\n", $texto));

    // ── DIAGNÓSTICO pre-extracción ───────────────────────────────────────────
    $hitsRegex = 0;
    foreach ($lineas as $l) {
        if (@preg_match($reLinea, trim($l))) $hitsRegex++;
    }
    error_log("_parsearConPatronesIA '{$bancoUsado}': regex_hits={$hitsRegex} esColumnaTab=" . ($esColumnaTab?'sí':'no') . " reInicioTabla=" . count($reInicioTabla));

    // ── FALLBACK A: regex detecta 0 líneas → reconstruir ────────────────────
    if ($hitsRegex === 0) {
        error_log("_parsearConPatronesIA '{$bancoUsado}': 0 hits → reconstruyendo regex desde fechas");
        $fallback = _construirRegexDesdeFechasReales([], $texto);
        if ($fallback !== null && $fallback['hits'] >= 2) {
            $reLinea   = _wrapRegex($fallback['regex_linea']);
            $reFecha   = _wrapRegex($fallback['regex_fecha']);
            $fmtFecha  = $fallback['formato'];
            $hitsRegex = $fallback['hits'];
            $iap['regex_linea_movimiento'] = $fallback['regex_linea'];
            $iap['regex_captura_fecha']    = $fallback['regex_fecha'];
            $iap['formato_fecha']          = $fallback['formato'];
            $iap['self_test_hits']         = $fallback['hits'];
            $iap['self_test_fallback']     = true;
            $patrones[$bancoUsado]['ia_patrones'] = $iap;
            _guardarPatrones($patrones);
            error_log("_parsearConPatronesIA '{$bancoUsado}': regex reconstruido → {$fallback['hits']} hits, fmt={$fallback['formato']}");
        } else {
            error_log("_parsearConPatronesIA '{$bancoUsado}': reconstrucción imposible — abortando");
            return null;
        }
    }

    // ── FALLBACK B: patrones_inicio_tabla no matchean → ignorarlos ──────────
    // Fix Bug 3: tolerar \t en lugar de \s en la línea de encabezado
    $enTabla = empty($reInicioTabla);
    if (!$enTabla) {
        $inicioDetectado = false;
        foreach ($lineas as $l) {
            $tv = trim($l);
            foreach ($reInicioTabla as $re) {
                $reFlex = '/' . str_replace(['\\s+', '/'], ['[\\s\\t]+', '\\/'], trim($re, '/')) . '/ui';
                if (@preg_match($re, $tv) || @preg_match($reFlex, $tv)) {
                    $inicioDetectado = true; break 2;
                }
            }
        }
        if (!$inicioDetectado) {
            $enTabla = true;
            error_log("_parsearConPatronesIA '{$bancoUsado}': inicio_tabla no matcheó → modo sin inicio");
        }
    }

    $finTabla       = false;
    $movs           = [];
    $lineasMovRegex = 0;
    $lineasSinMonto = 0;
    $lineasSinFecha = 0;

    foreach ($lineas as $linea) {
        // Preservar línea original para extracción por columna (tabs intactos)
        $lineaOriginal = rtrim(str_replace(["\r\n","\r"], '', $linea));
        $t = trim($lineaOriginal);
        if ($t === '') continue;

        // ── Fin de tabla ──
        if (!$finTabla && !empty($reFinTabla)) {
            foreach ($reFinTabla as $re) {
                if (@preg_match($re, $t)) { $finTabla = true; break; }
            }
        }
        if ($finTabla) continue;

        // ── Inicio de tabla (con tolerancia de tabs) ──
        if (!$enTabla) {
            foreach ($reInicioTabla as $re) {
                $reFlex = '/' . str_replace(['\\s+', '/'], ['[\\s\\t]+', '\\/'], trim($re, '/')) . '/ui';
                if (@preg_match($re, $t) || @preg_match($reFlex, $t)) { $enTabla = true; break; }
            }
            if (!$enTabla) continue;
        }

        // ── Skip ruido ──
        $esSkip = false;
        foreach ($skipStrings as $sk) {
            if ($sk !== '' && mb_stripos($t, $sk) !== false) { $esSkip = true; break; }
        }
        if ($esSkip) continue;

        // ── Detectar línea de movimiento ──
        if (!@preg_match($reLinea, $t)) continue;
        $lineasMovRegex++;

        // ── Extraer fecha ─────────────────────────────────────────────────
        $fechaRaw = null;
        $cols     = $esColumnaTab ? explode("\t", $lineaOriginal) : [];

        if ($esColumnaTab) {
            // La columna de fecha puede contener "fecha + descripcion" juntos (ej: "02-FEB-2026 TRANSFERENCIA")
            // → extraer la fecha usando reFecha sobre el contenido de esa columna
            $colFechaIdx = $colTab['fecha'] ?? 0;
            $colContenido = trim($cols[$colFechaIdx] ?? '');

            if ($reFecha && @preg_match($reFecha, $colContenido, $mf)) {
                $fechaRaw = $mf[1];
            } elseif (@preg_match('/^(\d{1,2}[\/\-][A-Z]{2,12}[\/\-]\d{2,4}|\d{1,2}[\/\-]\d{2}(?:[\/\-]\d{2,4})?)/i', $colContenido, $mf)) {
                $fechaRaw = $mf[1];
            }
        } elseif ($reFecha && @preg_match($reFecha, $t, $mf)) {
            $fechaRaw = $mf[1] ?? null;
        }
        // Fallback: extraer fecha del inicio de la línea completa
        if (!$fechaRaw) {
            if (@preg_match('/^(\d{1,2}[\/\-][A-ZÁÉÍÓÚ]{2,12}[\/\-]\d{2,4}|\d{1,2}[\/\-]\d{2}(?:[\/\-]\d{2,4})?)/i', $t, $mf3)) {
                $fechaRaw = $mf3[1];
            }
        }
        if (!$fechaRaw) { $lineasSinFecha++; continue; }

        $fecha = _normalizarFechaFlexible($fechaRaw, $fmtFecha, $anioInferido);
        if (!$fecha) { $lineasSinFecha++; continue; }

        // ══ EXTRACCIÓN UNIVERSAL ═════════════════════════════════════════
        // 1. Quitar fecha del inicio de la línea
        $lineaSinFecha = trim(preg_replace(
            '/^\d{1,2}[\/\-][A-Z]{2,12}[\/\-]\d{2,4}\s*/i', '', $t
        ));
        if ($lineaSinFecha === $t) {
            $lineaSinFecha = trim(preg_replace(
                '/^\d{1,2}[\/\-]\d{2}(?:[\/\-]\d{2,4})?\s*/', '', $t
            ));
        }

        // 2. Extraer todos los montos con decimal obligatorio
        $allMontos = _extraerMontosConDecimal($lineaSinFecha);
        $debito  = null;
        $credito = null;
        $saldo   = null;

        if (count($allMontos) >= 2) {
            // Último = saldo, penúltimo = monto de operación
            $saldo      = abs($allMontos[count($allMontos) - 1]['v']);
            $montoOpVal = $allMontos[count($allMontos) - 2]['v']; // puede ser negativo

            if ($conSigno) {
                // Signo determina D/C directamente
                if ($montoOpVal < -0.001) $debito  = abs($montoOpVal);
                else                       $credito = abs($montoOpVal);
            } else {
                // Sin signo: poner todo como débito tentativo
                // El pase de delta-saldo lo corrige a crédito donde corresponde
                $debito = abs($montoOpVal);
            }

        } elseif (count($allMontos) === 1) {
            // Un solo monto: o es solo saldo o es monto sin saldo
            // Si la línea tiene menos de 2 montos, tratar como movimiento sin importe
            $saldo = abs($allMontos[0]['v']);
            // Sin monto de operación → no agregar como movimiento con D/C
        }

        // 3. Descripción = texto antes del primer monto
        if (!empty($allMontos)) {
            $primerMontoStr = $allMontos[0]['str'];
            $pos = mb_strpos($lineaSinFecha, $primerMontoStr);
            $desc = ($pos !== false) ? trim(mb_substr($lineaSinFecha, 0, $pos)) : '';
        }
        // Limpiar la descripción de tabs y espacios múltiples
        $desc = trim(preg_replace('/[\t ]{2,}/', ' ', $desc ?? ''));
        // Quitar referencias numéricas del inicio (comprobantes, nros de cuenta)
        $desc = trim(preg_replace('/^\d{5,}\s*/', '', $desc));
        if (mb_strlen($desc) < 2) {
            // Fallback: quitar todos los números con decimal y lo que queda es la descripción
            $desc = trim(preg_replace('/\d{1,4}(?:[.,]\d{3})*[.,]\d{2}.*/', '', $lineaSinFecha));
            $desc = trim(preg_replace('/[\t ]{2,}/', ' ', $desc));
        }
        if (mb_strlen(trim($desc)) < 2) $desc = 'Movimiento';

        if ($debito === null && $credito === null) { $lineasSinMonto++; continue; }

        // ── Descripción ───────────────────────────────────────────────────
        if ($desc === null) {
            $desc = preg_replace('/^\s*[\d\-\/A-Z]+\s*(?:D\b)?\s*/i', '', $t);
            $desc = preg_replace('/\$?\s*-?[\d.,]+(?:\s*-)?(?=\s|$)/', ' ', $desc);
            $desc = trim(preg_replace('/\s{2,}/', ' ', $desc));
        }
        if (mb_strlen(trim($desc)) < 2) $desc = 'Movimiento';

        $tipo = 'I';
        if ($debito  !== null && $debito  > 0) $tipo = 'D';
        if ($credito !== null && $credito > 0) $tipo = 'C';

        $movs[] = [
            'fecha'       => $fecha,
            'descripcion' => mb_substr(trim($desc), 0, 300),
            'comprobante' => null,
            'debito'      => $debito  !== null ? round(abs($debito),  2) : null,
            'credito'     => $credito !== null ? round(abs($credito), 2) : null,
            'saldo'       => $saldo   !== null ? round(abs($saldo),   2) : null,
            'tipo'        => $tipo,
            'observacion' => null,
        ];
    }

    // ── Diagnóstico post-extracción ─────────────────────────────────────────
    error_log(
        "_parsearConPatronesIA '{$bancoUsado}': "
        . "regexHits={$hitsRegex} lineasMovRegex={$lineasMovRegex} "
        . "sinFecha={$lineasSinFecha} sinMonto={$lineasSinMonto} "
        . "movimientosExtraidos=" . count($movs)
    );

    if (count($movs) < 2) {
        error_log("_parsearConPatronesIA '{$bancoUsado}': extracción insuficiente (" . count($movs) . " movs — regexHits={$hitsRegex} lineasMov={$lineasMovRegex} sinFecha={$lineasSinFecha} sinMonto={$lineasSinMonto}) — retornando null");
        return null;
    }

    _inferirDCPorSaldo($movs);

    // ── Corrección por delta de saldo — fuente de verdad matemática ──────────
    // Aplica a TODOS los movimientos con saldo explícito, no solo tipo='I'.
    // Tolerancia del 2% para redondeos del extracto.
    for ($i = 1; $i < count($movs); $i++) {
        $saldoAnt = $movs[$i-1]['saldo'] ?? null;
        $saldoAct = $movs[$i]['saldo']   ?? null;
        if ($saldoAnt === null || $saldoAct === null) continue;

        $monto = ($movs[$i]['debito'] ?? 0) + ($movs[$i]['credito'] ?? 0);
        if ($monto <= 0.005) continue;

        $delta = round($saldoAct - $saldoAnt, 2);
        if (abs($delta) < 0.005) continue;

        // Verificar que el delta coincide con el monto (tolerancia 2%)
        $tolerancia = max(0.02, $monto * 0.02);
        if (abs(abs($delta) - $monto) > $tolerancia) continue;

        $esD = ($movs[$i]['debito']  !== null && $movs[$i]['debito']  > 0);
        $esC = ($movs[$i]['credito'] !== null && $movs[$i]['credito'] > 0);

        if ($delta > 0 && $esD && !$esC) {
            // Saldo subió → debe ser crédito
            $movs[$i]['credito'] = $movs[$i]['debito'];
            $movs[$i]['debito']  = null;
            $movs[$i]['tipo']    = 'C';
        } elseif ($delta < 0 && $esC && !$esD) {
            // Saldo bajó → debe ser débito
            $movs[$i]['debito']  = $movs[$i]['credito'];
            $movs[$i]['credito'] = null;
            $movs[$i]['tipo']    = 'D';
        }
    }

    // ── Segundo pase: inferir D/C para movimientos sin saldo, por keyword ────
    foreach ($movs as &$mov) {
        if ($mov['tipo'] === 'I' && ($mov['debito'] !== null || $mov['credito'] !== null)) {
            $kw = _inferirTipoPorDesc($mov['descripcion'] ?? '');
            if ($kw === 'D' && $mov['credito'] !== null && $mov['debito'] === null) {
                $mov['debito']  = $mov['credito'];
                $mov['credito'] = null;
                $mov['tipo']    = 'D';
            } elseif ($kw === 'C' && $mov['debito'] !== null && $mov['credito'] === null) {
                $mov['credito'] = $mov['debito'];
                $mov['debito']  = null;
                $mov['tipo']    = 'C';
            } elseif ($mov['debito'] !== null) {
                $mov['tipo'] = 'D';
            } elseif ($mov['credito'] !== null) {
                $mov['tipo'] = 'C';
            }
        }
    }
    unset($mov);
    $patrones[$bancoUsado]['ia_patrones_usos']       = ($patrones[$bancoUsado]['ia_patrones_usos'] ?? 0) + 1;
    $patrones[$bancoUsado]['ia_patrones_ultimo_uso'] = date('Y-m-d H:i:s');
    unset($patrones[$bancoUsado]['ia_patrones_invalido']); // limpiar flag si existía
    _guardarPatrones($patrones);

    return $movs;
}

/**
 * Después de que la IA procesa exitosamente, analiza el texto crudo
 * y los movimientos resultantes para "aprender" el formato del banco.
 * Guarda un perfil con: formato de fecha, separadores, signo, noise, etc.
 *
 * @param string $textoLimpio   Texto ya limpio del extracto
 * @param array  $movimientos   Movimientos extraídos por la IA
 * @param string $banco         Nombre final del banco (puede ser el detectado por IA)
 * @param string $bancoDetector Nombre que devolvió detectarBanco(). Se guarda también bajo
 *                              esa clave para que el segundo upload lo encuentre sin fingerprint.
 */
function _aprenderFormatoBanco(string $textoLimpio, array $movimientos, string $banco, string $bancoDetector = ''): void {
    if (count($movimientos) < 3) return;

    $lineas = explode("\n", str_replace(["\r\n", "\r"], "\n", $textoLimpio));

    // ─── Detectar formato de fecha ───
    $formatos = ['dd-mm-yyyy' => 0, 'dd/mm/yyyy' => 0, 'dd/mm/yy' => 0, 'dd/mm' => 0, 'yyyy-mm-dd' => 0, 'dd-MMM-yyyy' => 0];
    foreach ($lineas as $l) {
        $t = trim($l);
        if (preg_match('/^\d{2}-[A-Z]{3}-\d{4}/', $t))  $formatos['dd-MMM-yyyy']++;  // 02-FEB-2026 ANTES que dd-mm-yyyy
        if (preg_match('/^\d{2}-\d{2}-\d{4}/', $t))     $formatos['dd-mm-yyyy']++;
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $t))   $formatos['dd/mm/yyyy']++;
        if (preg_match('/^\d{2}\/\d{2}\/\d{2}\b/', $t)) $formatos['dd/mm/yy']++;
        if (preg_match('/^\d{2}\/\d{2}\s/', $t))         $formatos['dd/mm']++;
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $t))     $formatos['yyyy-mm-dd']++;
    }
    arsort($formatos);
    $formatoFecha = array_key_first($formatos);

    // ─── Detectar separador de columnas ───
    $tabCount = 0; $pipeCount = 0; $multiSpace = 0;
    foreach (array_slice($lineas, 0, 100) as $l) {
        if (strpos($l, "\t") !== false) $tabCount++;
        if (strpos($l, '|') !== false) $pipeCount++;
        if (preg_match('/  {2,}/', $l)) $multiSpace++;
    }
    $separador = 'tab';
    if ($pipeCount > $tabCount && $pipeCount > $multiSpace) $separador = 'pipe';
    elseif ($multiSpace > $tabCount) $separador = 'spaces';

    // ─── Detectar si los montos tienen signo (neg=débito) o columnas separadas ───
    $negativos = 0; $positivos = 0; $conSigno = false;
    foreach ($lineas as $l) {
        if (preg_match('/[\$\s]-\d/', $l)) $negativos++;
        if (preg_match('/[\$\s]\d/', $l)) $positivos++;
    }
    if ($negativos > count($movimientos) * 0.2) $conSigno = true;

    // ─── Detectar prefijo de montos ───
    $conDolar = 0;
    foreach (array_slice($lineas, 0, 100) as $l) {
        if (preg_match('/\$\s*-?\d/', $l)) $conDolar++;
    }
    $prefijoMonto = ($conDolar > 10) ? '$' : '';

    // ─── Capturar líneas de ruido (que no tienen fecha ni montos) ───
    $patronesRuido = [];
    $conteoRuido = [];
    foreach ($lineas as $l) {
        $t = trim($l);
        if (mb_strlen($t) < 3 || mb_strlen($t) > 120) continue;
        // Si no tiene fecha ni montos, probablemente es ruido
        if (!preg_match('/\d{1,2}[\/-]\d{2}/', $t) && !preg_match('/\d{1,3}[.,]\d{3}/', $t)) {
            // Generalizar: reemplazar números por \d+
            $patron = preg_replace('/\d+/', '\\d+', preg_quote($t, '/'));
            $key = mb_substr($t, 0, 30);
            if (!isset($conteoRuido[$key])) $conteoRuido[$key] = 0;
            $conteoRuido[$key]++;
        }
    }
    // Solo guardar ruido que aparece > 2 veces (repetitivo)
    foreach ($conteoRuido as $key => $count) {
        if ($count >= 2) $patronesRuido[] = $key;
    }

    // ─── Guardar las primeras 5 líneas de movimiento como "muestra" ───
    $muestras = [];
    $mCount = 0;
    foreach ($lineas as $l) {
        $t = trim($l);
        if ($mCount >= 5) break;
        if (preg_match('/^\d{1,2}[\/-]\d{2}/', $t) && preg_match('/\d{1,3}[.,]\d{3}/', $t)) {
            $muestras[] = $t;
            $mCount++;
        }
    }

    // ─── Detectar keywords de la tabla ───
    $headerKeywords = [];
    foreach ($lineas as $l) {
        $t = trim($l);
        if (preg_match('/(?:Fecha|Date)/i', $t) && preg_match('/(?:Descripci|Concepto|Detalle)/i', $t)) {
            $headerKeywords = array_filter(preg_split('/[\t|]+/', $t));
            break;
        }
    }

    // ─── Detectar si tiene sufijo D en fecha (BBVA) ───
    $sufijoDEnFecha = false;
    foreach (array_slice($lineas, 0, 50) as $l) {
        if (preg_match('/^\d{2}\/\d{2}\s+D\s/', trim($l))) { $sufijoDEnFecha = true; break; }
    }

    // ─── Generar patrones regex por IA (segunda llamada rápida a gpt-4o-mini) ───
    $iaPatrones = [];
    try {
        $iaPatrones = _generarPatronesConIA($textoLimpio, $movimientos, $banco);
    } catch (\Throwable $e) {
        error_log("AUTO-APRENDIZAJE: error generando patrones IA para '{$banco}': " . $e->getMessage());
    }

    // ─── Construir y guardar perfil ───
    $perfil = [
        'banco'            => $banco,
        'banco_detector'   => $bancoDetector ?: $banco,  // clave que usa detectarBanco()
        'formato_fecha'    => $formatoFecha,
        'separador'        => $separador,
        'montos_con_signo' => $conSigno,
        'prefijo_monto'    => $prefijoMonto,
        'sufijo_d_fecha'   => $sufijoDEnFecha,
        'ruido'            => array_slice($patronesRuido, 0, 20),
        'header_keywords'  => $headerKeywords,
        'muestras'         => $muestras,
        'movimientos_ia'   => count($movimientos),
        'aprendido_en'     => date('Y-m-d H:i:s'),
        'veces_usado'      => 0,
        // Patrones regex generados por IA — se usan en _parsearConPatronesIA()
        'ia_patrones'            => $iaPatrones,
        'ia_patrones_generados'  => !empty($iaPatrones),
        'ia_patrones_usos'       => 0,
        'ia_patrones_ultimo_uso' => null,
    ];

    $patrones = _cargarPatrones();
    // Preservar estadísticas de uso si el banco ya tenía perfil previo
    if (isset($patrones[$banco])) {
        $perfil['veces_usado']       = $patrones[$banco]['veces_usado']       ?? 0;
        $perfil['ia_patrones_usos']  = $patrones[$banco]['ia_patrones_usos']  ?? 0;
    }

    // Guardar bajo el nombre IA (ej: "Banco Columbia")
    $patrones[$banco] = $perfil;

    // ── FIX BUG 1: guardar también bajo la clave de detectarBanco() ──────────
    // Esto garantiza que en el segundo upload (donde detectarBanco() devuelve
    // 'Banco Argentino'), el lookup exacto funcione sin necesidad de fingerprint.
    if ($bancoDetector && $bancoDetector !== $banco) {
        $perfilAlias = $perfil;
        $perfilAlias['banco'] = $bancoDetector;
        $perfilAlias['banco_ia'] = $banco; // guardar el nombre IA como referencia
        if (isset($patrones[$bancoDetector])) {
            $perfilAlias['veces_usado']      = $patrones[$bancoDetector]['veces_usado']      ?? 0;
            $perfilAlias['ia_patrones_usos'] = $patrones[$bancoDetector]['ia_patrones_usos'] ?? 0;
        }
        $patrones[$bancoDetector] = $perfilAlias;
        error_log("AUTO-APRENDIZAJE: alias guardado bajo '{$bancoDetector}' → '{$banco}'");
    }
    // ─────────────────────────────────────────────────────────────────────────

    _guardarPatrones($patrones);

    $iaOk = !empty($iaPatrones)
        ? ' + regex IA ✓ (' . ($iaPatrones['self_test_hits'] ?? '?') . ' hits en muestra)'
        : ' (sin regex IA)';
    error_log("AUTO-APRENDIZAJE: perfil guardado para '{$banco}'" . ($bancoDetector && $bancoDetector !== $banco ? " + alias '{$bancoDetector}'" : '') . " — {$formatoFecha}, sep={$separador}, signo=" . ($conSigno ? 'sí' : 'no') . $iaOk);
}

/**
 * Intenta parsear usando patrones aprendidos de procesamientos anteriores con IA.
 * Carga el perfil del banco y configura el parser genérico según ese perfil.
 * Retorna array de movimientos o null si no tiene perfil o falla.
 */
function _parsearConPatronesAprendidos(string $texto, string $banco): ?array {
    $patrones = _cargarPatrones();
    if (empty($patrones)) return null;

    $perfil = null;
    $bancoUsado = $banco;

    if (isset($patrones[$banco])) {
        // Coincidencia exacta por nombre de banco
        $perfil = $patrones[$banco];
        $bancoUsado = $banco;
    } else {
        // No hay coincidencia exacta → intentar TODOS los perfiles aprendidos
        // Buscar el perfil que mejor coincida con el texto actual usando fingerprints
        $mejorScore = 0;
        $mejorBanco = null;

        foreach ($patrones as $nombreBanco => $p) {
            $score = 0;

            // Match por formato de fecha detectado en el texto
            $fmt = $p['formato_fecha'] ?? '';
            if ($fmt === 'dd-mm-yyyy' && preg_match('/\d{2}-\d{2}-\d{4}/', $texto)) $score += 3;
            if ($fmt === 'dd/mm/yyyy' && preg_match('/\d{2}\/\d{2}\/\d{4}/', $texto)) $score += 3;
            if ($fmt === 'dd/mm/yy'   && preg_match('/\d{2}\/\d{2}\/\d{2}\b/', $texto)) $score += 3;
            if ($fmt === 'dd/mm'      && preg_match('/\d{2}\/\d{2}\s/', $texto)) $score += 2;

            // Match por prefijo de monto
            if (!empty($p['prefijo_monto']) && strpos($texto, '$') !== false) $score += 2;
            if (empty($p['prefijo_monto']) && strpos($texto, '$') === false) $score += 1;

            // Match por muestras de texto (las líneas ejemplo guardadas)
            foreach (($p['muestras'] ?? []) as $muestra) {
                // Extraer patrón del primer token (fecha) para comparar estructura
                $muestraFecha = substr($muestra, 0, 10);
                if (preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{2,4}/', $muestraFecha)) {
                    // Buscar misma estructura de fecha en el texto actual
                    $reTest = '/^' . preg_replace('/\d/', '\\d', preg_quote(substr($muestraFecha, 0, 5), '/')) . '/m';
                    if (preg_match($reTest, $texto)) $score += 2;
                }
            }

            // Match por keywords de header (columnas de la tabla)
            foreach (($p['header_keywords'] ?? []) as $kw) {
                $kw = trim($kw);
                if ($kw !== '' && mb_stripos($texto, $kw) !== false) $score += 2;
            }

            // Match por ruido detectado (patrones específicos del banco)
            $ruidoHits = 0;
            foreach (($p['ruido'] ?? []) as $r) {
                if (mb_stripos($texto, $r) !== false) $ruidoHits++;
            }
            if ($ruidoHits >= 2) $score += 3;

            if ($score > $mejorScore) {
                $mejorScore = $score;
                $mejorBanco = $nombreBanco;
            }
        }

        // Necesitamos un score mínimo para confiar en el match
        if ($mejorScore >= 5 && $mejorBanco !== null) {
            $perfil = $patrones[$mejorBanco];
            $bancoUsado = $mejorBanco;
            error_log("AUTO-APRENDIZAJE: match por fingerprint → '{$mejorBanco}' (score={$mejorScore})");
        } else {
            return null; // No hay perfil confiable
        }
    }

    // Configurar regex de fecha según perfil
    $fmt = $perfil['formato_fecha'] ?? 'dd/mm/yyyy';
    $sufD = $perfil['sufijo_d_fecha'] ?? false;
    $stripDolar = !empty($perfil['prefijo_monto']);
    $conSigno = $perfil['montos_con_signo'] ?? false;

    $texto = str_replace(["\r\n", "\r"], "\n", $texto);
    $lineas = explode("\n", $texto);

    // Inferir año
    $anioInferido = date('Y');
    if (preg_match('/(?:ENERO|FEBRERO|MARZO|ABRIL|MAYO|JUNIO|JULIO|AGOSTO|SEPTIEMBRE|OCTUBRE|NOVIEMBRE|DICIEMBRE)\s+(\d{4})/i', $texto, $pm))
        $anioInferido = $pm[1];
    elseif (preg_match('/(\d{4})/i', $texto, $pm))
        $anioInferido = $pm[1];

    // Regex de fecha según formato aprendido
    switch ($fmt) {
        case 'dd-mm-yyyy': $reFecha = '/^\s*(\d{2}-\d{2}-\d{4})\s/'; break;
        case 'dd/mm/yyyy': $reFecha = '/^\s*(\d{2}\/\d{2}\/\d{4})\s/'; break;
        case 'dd/mm/yy':   $reFecha = '/^\s*(\d{2}\/\d{2}\/\d{2})\s/'; break;
        case 'dd/mm':      $reFecha = $sufD
                            ? '/^\s*(\d{1,2}\/\d{2})\s*(?:D\b)?\s/'
                            : '/^\s*(\d{1,2}\/\d{2})\s/'; break;
        default:            $reFecha = '/^\s*(\d{1,2}[\/\-]\d{2}(?:[\/\-]\d{2,4})?)\s/'; break;
    }

    // Patrones de skip/ruido aprendidos
    $ruidoAprendido = $perfil['ruido'] ?? [];
    $reSkip = '/^(FECHA|SALDO\s*(ANTERIOR|ACTUAL|INICIAL|FINAL)|TOTAL\s|Legales|Banco\s+BBVA|CBU\s+\d|Entradas:|Salidas:|Saldo\s+(inicial|final)|RETENCIONES|D[eé]bitos\s+autom)/i';
    $reStop = '/^(SALDO\s+AL\s+\d|TOTAL\s+(MOVIMIENTOS|COBRADO|DEV)|RETENCIONES\s+ARBA|Legales\s+y\s+avisos)/i';
    $rePageMarker = '/^\d{1,3}\/\d{1,3}$/';

    $movs = [];
    $enTabla = false;
    $finTabla = false;
    $pendienteDesc = null;

    foreach ($lineas as $linea) {
        $t = trim($linea);
        if ($t === '' || preg_match($rePageMarker, $t)) continue;
        if (preg_match($reStop, $t)) { $finTabla = true; continue; }
        if ($finTabla) continue;
        if (preg_match($reSkip, $t)) { $enTabla = true; continue; }

        // Ruido aprendido
        $esRuido = false;
        foreach ($ruidoAprendido as $r) {
            if (mb_stripos($t, $r) === 0) { $esRuido = true; break; }
        }
        if ($esRuido) continue;

        // Continuación de descripción multi-línea
        if (!preg_match($reFecha, $t) && !preg_match('/\d{1,3}[.,]\d{3}/', $t)
            && mb_strlen($t) < 60 && mb_strlen($t) > 1) {
            if ($pendienteDesc !== null) $pendienteDesc .= ' ' . $t;
            continue;
        }

        // Header de tabla
        if (preg_match('/D[EÉ]BITO|CR[EÉ]DITO|CONCEPTO|COMPROBANTE|Valor\s/i', $t) && !preg_match($reFecha, $t)) {
            $enTabla = true; continue;
        }

        if (!$enTabla && preg_match($reFecha, $t)) $enTabla = true;
        if (!$enTabla) continue;

        // Línea con descripción sin fecha (MP multi-línea)
        if (!preg_match($reFecha, $t)) {
            if (preg_match('/^(Transferencia|Pago con|Pago de)/i', $t)) {
                $pendienteDesc = preg_replace('/\s+\d{11}\s*$/', '', $t);
            }
            continue;
        }

        preg_match($reFecha, $t, $fm);
        $fechaRaw = str_replace('-', '/', $fm[1]);

        $partes = explode('/', $fechaRaw);
        if (count($partes) === 2) $fechaRaw .= '/' . $anioInferido;
        elseif (count($partes) === 3 && strlen($partes[2]) === 2) $fechaRaw = $partes[0] . '/' . $partes[1] . '/20' . $partes[2];

        $fecha = _formatearFecha($fechaRaw);
        if (!$fecha) { $pendienteDesc = null; continue; }

        $lineaMontos = $stripDolar ? preg_replace('/\$\s*/', '', $t) : $t;
        $montos = _extraerMontos($lineaMontos);
        if (empty($montos)) { $pendienteDesc = null; continue; }

        // Descripción
        $desc = preg_replace('/^\s*\d{1,2}[\/-]\d{2}(?:[\/-]\d{2,4})?\s*(?:D\b)?\s+/', '', $t);
        if ($pendienteDesc !== null) { $desc = $pendienteDesc . ' ' . $desc; $pendienteDesc = null; }

        $comprobante = null;
        if (preg_match('/^(\d{3})\s+/', $desc, $cm)) { $comprobante = $cm[1]; $desc = substr($desc, strlen($cm[0])); }
        foreach ($montos as $mc) $desc = str_replace($mc['str'], '', $desc);
        $desc = preg_replace('/\$\s*-?\d[\d.,]*/', '', $desc);
        $desc = str_replace('$', '', $desc);
        $desc = preg_replace('/\b\d{11,}\b/', '', $desc);
        if (!$comprobante && preg_match('/(?<!\d)(\d{5,9})(?!\d)/', $desc, $cm)) {
            $comprobante = $cm[1]; $desc = str_replace($cm[0], '', $desc);
        }
        $desc = trim(preg_replace('/[\t\s]{2,}/', ' ', $desc));
        $desc = trim(preg_replace('/^[\-\s]+|[\-\s]+$/', '', $desc));
        if (mb_strlen($desc) < 2) $desc = 'Movimiento';

        $n = count($montos);
        $debito = $credito = $saldo = null;

        if ($n >= 2) {
            $saldo = $montos[$n - 1]['v'];
            $montoOp = $montos[$n - 2]['v'];
            if ($conSigno) {
                if ($montoOp < -0.01) $debito = abs($montoOp);
                elseif ($montoOp > 0.01) $credito = abs($montoOp);
                else continue;
            } else {
                $tipoKw = _inferirTipoPorDesc($desc);
                $val = abs($montoOp);
                if ($tipoKw === 'D') $debito = $val;
                elseif ($tipoKw === 'C') $credito = $val;
                elseif ($montoOp < 0) $debito = $val;
                else $debito = $val;
            }
        } elseif ($n === 1) {
            $montoOp = $montos[0]['v'];
            if ($montoOp < -0.01) $debito = abs($montoOp);
            elseif ($montoOp > 0.01) {
                $tipoKw = _inferirTipoPorDesc($desc);
                if ($tipoKw === 'D') $debito = abs($montoOp);
                else $credito = abs($montoOp);
            } else continue;
        }

        if ($debito === null && $credito === null) continue;

        $tipo = 'I';
        if ($debito !== null && $debito > 0) $tipo = 'D';
        if ($credito !== null && $credito > 0) $tipo = 'C';

        $movs[] = [
            'fecha'       => $fecha,
            'descripcion' => $desc,
            'comprobante' => $comprobante,
            'debito'      => ($debito !== null && $debito > 0) ? round($debito, 2) : null,
            'credito'     => ($credito !== null && $credito > 0) ? round($credito, 2) : null,
            'saldo'       => $saldo !== null ? round($saldo, 2) : null,
            'tipo'        => $tipo,
            'observacion' => null,
        ];
    }

    if (count($movs) < 2) return null;

    _inferirDCPorSaldo($movs);

    // Actualizar contador de uso
    $patrones[$bancoUsado]['veces_usado'] = ($patrones[$bancoUsado]['veces_usado'] ?? 0) + 1;
    $patrones[$bancoUsado]['ultimo_uso'] = date('Y-m-d H:i:s');
    _guardarPatrones($patrones);

    return $movs;
}

// ─────────────────────────────────────────────────────────────────────────
//  NORMALIZACIÓN DE FECHA FLEXIBLE
//  Soporta: dd/mm/yyyy | dd/mm/yy | dd/mm | dd-mm-yyyy | yyyy-mm-dd
//           dd-MMM-yyyy (mes textual español, ej: 22-MAR-2026)
// ─────────────────────────────────────────────────────────────────────────

/** Mapa de abreviaciones de mes en español e inglés → número */
function _mesesAbrev(): array {
    return [
        // Español estándar
        'ENE'=>1,'FEB'=>2,'MAR'=>3,'ABR'=>4,'MAY'=>5,'JUN'=>6,
        'JUL'=>7,'AGO'=>8,'SEP'=>9,'OCT'=>10,'NOV'=>11,'DIC'=>12,
        // Argentina usa SET para septiembre (no SEP)
        'SET'=>9,
        // Inglés
        'JAN'=>1,'APR'=>4,'AUG'=>8,'DEC'=>12,
        // Formas completas español
        'ENERO'=>1,'FEBRERO'=>2,'MARZO'=>3,'ABRIL'=>4,'MAYO'=>5,'JUNIO'=>6,
        'JULIO'=>7,'AGOSTO'=>8,'SEPTIEMBRE'=>9,'OCTUBRE'=>10,'NOVIEMBRE'=>11,'DICIEMBRE'=>12,
        // Formas completas inglés por si acaso
        'JANUARY'=>1,'FEBRUARY'=>2,'MARCH'=>3,'APRIL'=>4,'JUNE'=>6,
        'JULY'=>7,'AUGUST'=>8,'SEPTEMBER'=>9,'OCTOBER'=>10,'NOVEMBER'=>11,'DECEMBER'=>12,
    ];
}

/**
 * Normaliza cualquier representación de fecha a YYYY-MM-DD.
 * Retorna null si no puede parsear.
 *
 * @param string $fechaRaw  Fecha cruda capturada por el regex
 * @param string $fmtHint   Formato conocido (del perfil), opcional
 * @param int    $anioInf   Año inferido del documento, para fechas sin año
 */
function _normalizarFechaFlexible(string $fechaRaw, string $fmtHint = '', int $anioInf = 0): ?string {
    $f = trim($fechaRaw);
    if ($f === '') return null;
    if ($anioInf === 0) $anioInf = (int)date('Y');

    // ── Caso 1: dd-MMM-yyyy o dd-MMM-yy  (ej: 22-MAR-2026, 05-ENE-26) ──
    if (preg_match('/^(\d{1,2})[\/\-]([A-ZÁÉÍÓÚ]{2,12})[\/\-](\d{2,4})$/i', $f, $pm)) {
        $meses = _mesesAbrev();
        $mesNum = $meses[mb_strtoupper($pm[2])] ?? null;
        if ($mesNum === null) return null;
        $a = (int)$pm[3]; if ($a < 100) $a += 2000;
        $d = (int)$pm[1];
        if (!@checkdate($mesNum, $d, $a)) return null;
        return sprintf('%04d-%02d-%02d', $a, $mesNum, $d);
    }

    // ── Caso 2: yyyy-mm-dd ──
    if (preg_match('/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})$/', $f, $pm)) {
        [$_, $a, $m, $d] = array_map('intval', $pm);
        if (!@checkdate($m, $d, $a)) return null;
        return sprintf('%04d-%02d-%02d', $a, $m, $d);
    }

    // ── Caso 3: dd/mm/yyyy o dd-mm-yyyy ──
    if (preg_match('/^(\d{1,2})[\/\-](\d{2})[\/\-](\d{4})$/', $f, $pm)) {
        [$_, $d, $m, $a] = array_map('intval', $pm);
        if (!@checkdate($m, $d, $a)) return null;
        return sprintf('%04d-%02d-%02d', $a, $m, $d);
    }

    // ── Caso 4: dd/mm/yy ──
    if (preg_match('/^(\d{1,2})[\/\-](\d{2})[\/\-](\d{2})$/', $f, $pm)) {
        [$_, $d, $m, $a] = array_map('intval', $pm);
        $a += 2000;
        if (!@checkdate($m, $d, $a)) return null;
        return sprintf('%04d-%02d-%02d', $a, $m, $d);
    }

    // ── Caso 5: dd/mm (sin año) ──
    if (preg_match('/^(\d{1,2})[\/\-](\d{2})$/', $f, $pm)) {
        $d = (int)$pm[1]; $m = (int)$pm[2];
        if (!@checkdate($m, $d, $anioInf)) return null;
        return sprintf('%04d-%02d-%02d', $anioInf, $m, $d);
    }

    return null;
}

//
//  La IA analiza la ESTRUCTURA del texto (no extrae datos).
//  Emite únicamente patrones REGEX listos para el motor PHP.
//  No genera esquemas de BD, ni estructuras de almacenamiento.
//  Precisión sobre flexibilidad: regex específicos para el banco.
// ═════════════════════════════════════════════════════════════════════════

/**
 * Prompt del sistema para el generador puro de REGEX.
 * Separado para facilitar mantenimiento y ajuste fino.
 */
function _getPromptGeneradorRegex(): string {
    return <<<'SYSTEM'
Sos un experto en parsing de extractos bancarios argentinos en texto plano.

El sistema PHP ya pre-analizó el texto y te entrega datos precisos:
- LINEAS_MOVIMIENTO: líneas reales con fecha + monto (movimientos confirmados).
- LINEAS_RUIDO: líneas sin fecha/monto (encabezados, legales, pie de página).
- LINEAS_SIN_MONTO: líneas con fecha pero sin monto (ej: "TRANSPORTE" sin importe).
- SEPARADOR, FORMATO_FECHA_PHP, ESTRUCTURA_INFERIDA, MONTOS_CON_SIGNO.

Tu tarea: generar el JSON de patrones REGEX. NO extraigas datos, solo patrones.

════════════════════════════════════════════════
PASO 1 — regex_linea_movimiento y regex_captura_fecha
════════════════════════════════════════════════
Usá el FORMATO_FECHA_PHP para construir los regex. Tabla de conversión exacta:

  FORMATO_FECHA_PHP = "dd-MMM-yyyy"  →  linea: ^\d{2}-[A-Z]{3}-\d{4}\s   fecha: ^(\d{2}-[A-Z]{3}-\d{4})\s
  FORMATO_FECHA_PHP = "dd/mm/yyyy"   →  linea: ^\d{2}\/\d{2}\/\d{4}\s    fecha: ^(\d{2}\/\d{2}\/\d{4})\s
  FORMATO_FECHA_PHP = "dd-mm-yyyy"   →  linea: ^\d{2}-\d{2}-\d{4}\s      fecha: ^(\d{2}-\d{2}-\d{4})\s
  FORMATO_FECHA_PHP = "dd/mm/yy"     →  linea: ^\d{2}\/\d{2}\/\d{2}\s    fecha: ^(\d{2}\/\d{2}\/\d{2})\s
  FORMATO_FECHA_PHP = "dd/mm"        →  linea: ^\d{1,2}\/\d{2}\s          fecha: ^(\d{1,2}\/\d{2})\s
  FORMATO_FECHA_PHP = "yyyy-mm-dd"   →  linea: ^\d{4}-\d{2}-\d{2}\s      fecha: ^(\d{4}-\d{2}-\d{2})\s

Copiá el regex que corresponde al FORMATO_FECHA_PHP recibido. No inventés otro.

════════════════════════════════════════════════
PASO 2 — estructura_montos y montos_con_signo
════════════════════════════════════════════════
Usá directamente ESTRUCTURA_INFERIDA y MONTOS_CON_SIGNO del pre-análisis PHP.
No los deducas vos — el PHP ya los calculó correctamente.

Posibles valores de ESTRUCTURA_INFERIDA:
  "monto_saldo"   → separador=spaces, cada línea tiene MONTO y SALDO.
                    El extractor PHP toma penúltimo número = monto, último = saldo.
  "signo_unico"   → separador=spaces, montos negativos=débito, positivos=crédito.
  "columnas_tab"  → separador=tab, columnas separadas por \t.
                    Completar col_tab con índices base-0 de cada columna.

IMPORTANTE para "columnas_tab":
  Cada tab separa una columna. Contá los tabs en las LINEAS_MOVIMIENTO.
  Si la línea es: "02-FEB-2026 TRANSFERENCIA\t50.000,00\t\t1382.358,25"
  → col[0]="02-FEB-2026 TRANSFERENCIA", col[1]="50.000,00", col[2]="", col[3]="1382.358,25"
  → col_tab = {"fecha":0,"descripcion":0,"debito":1,"credito":2,"saldo":3}
  Las columnas vacías (crédito o débito en 0) cuentan como índice igualmente.

════════════════════════════════════════════════
PASO 3 — patrones_skip_linea
════════════════════════════════════════════════
skip_linea son strings ESTRUCTURALES DEL BANCO que sirven para CUALQUIER PDF del
mismo banco, sin importar el titular, la fecha del período ni el número de cuenta.

⚠ PROHIBIDO incluir en skip_linea:
  - Nombre del titular (ej: "MACIEL NICOLAS", "GARCIA JUAN")
  - Dirección del titular (ej: "CIRILO CORREA 8060", "BOLIVAR 191")
  - CUIL/CUIT del titular (ej: "20-41952385-3")
  - Número de cuenta específico
  - Cualquier dato que cambie entre extractos del mismo banco

✓ SÍ incluir en skip_linea (son del banco, no del titular):
  - Encabezados de tabla repetidos: "FECHA CONCEPTO", "RESUMEN DE CUENTA", "HOJA NRO"
  - Textos legales fijos: "IVA RESPOSABLE INSCRIPTO", "Estimado Cliente", "Beneficios"
  - Datos del banco (no del titular): "CUIT: 30-" (CUIT del banco), "Domicilio: Florida"
  - Palabras clave de cabecera: "DOCUMENTACION COMERCIAL", "SALDO ANTERIOR"
  - Pie de página legal: "I. BRUTOS", "Podés consultar", "Podés operar"

════════════════════════════════════════════════
PASO 4 — patrones_fin_tabla (REGLA CRÍTICA)
════════════════════════════════════════════════
fin_tabla SOLO para el patrón que marca el CIERRE del extracto, sin fecha específica.

⚠ PROHIBIDO en fin_tabla:
  - "SALDO AL 27/02/2026" → tiene fecha específica, no funciona para otros períodos
  - "IVA RESPOSABLE INSCRIPTO" → aparece en el pie de CADA página
  - Cualquier string con fecha concreta o datos del período

✓ SÍ en fin_tabla (genérico, sin fecha):
  - "SALDO AL" → funciona para cualquier período (ej: "SALDO AL 27/02/2026", "SALDO AL 30/09/2024")
  - ".MACIEL" → NO, es nombre del titular
  - "TOTAL MOVIMIENTOS" → si aparece solo al final siempre

Si no encontrás un patrón genérico de cierre → dejá fin_tabla = [].

════════════════════════════════════════════════
FORMATO JSON DE SALIDA
════════════════════════════════════════════════
{
  "nombre_banco_detectado": "nombre del banco del encabezado",
  "regex_linea_movimiento": "copiado de la tabla del PASO 1",
  "regex_captura_fecha": "copiado de la tabla del PASO 1",
  "formato_fecha": "exactamente el valor de FORMATO_FECHA_PHP",
  "estructura_montos": "exactamente el valor de ESTRUCTURA_INFERIDA",
  "montos_con_signo": false,
  "tiene_sufijo_D": false,
  "col_tab": {},
  "regex_captura_debito": null,
  "regex_captura_credito": null,
  "regex_captura_monto_op": null,
  "regex_captura_saldo": null,
  "patrones_skip_linea": ["primeros 20 chars de cada linea de ruido"],
  "patrones_inicio_tabla": [],
  "patrones_fin_tabla": [],
  "descripcion": "descripcion breve del formato"
}
SYSTEM;
}

/**
 * PRE-ANÁLISIS PHP: identifica líneas reales de movimiento sin IA.
 * Retorna arrays de líneas clasificadas para que la IA trabaje con datos reales.
 */
function _preanalizarTexto(string $texto): array {
    $lineas = explode("\n", str_replace(["\r\n", "\r"], "\n", $texto));

    // ── 1. Detectar separador ────────────────────────────────────────────────
    $tabC = $spaceC = 0;
    foreach (array_slice($lineas, 0, 200) as $l) {
        if (substr_count($l, "\t") >= 2) $tabC++;       // al menos 2 tabs = columnar
        elseif (preg_match('/  {4,}/', $l))  $spaceC++; // 4+ espacios = alineación columnar
    }
    $separador = ($tabC >= 5 && $tabC > $spaceC) ? 'tab' : 'spaces';

    // ── 2. Detectar formato de fecha ──────────────────────────────────────────
    // Orden importa: dd-MMM-yyyy debe evaluarse ANTES que dd-mm-yyyy
    $resFecha = [
        'dd-MMM-yyyy' => '/^\s*\d{2}-[A-Z]{3}-\d{4}\s/i',
        'dd/mm/yyyy'  => '/^\s*\d{2}\/\d{2}\/\d{4}\s/',
        'dd-mm-yyyy'  => '/^\s*\d{2}-\d{2}-\d{4}\s/',
        'dd/mm/yy'    => '/^\s*\d{2}\/\d{2}\/\d{2}\s/',
        'dd/mm'       => '/^\s*\d{1,2}\/\d{2}\s/',
        'yyyy-mm-dd'  => '/^\s*\d{4}-\d{2}-\d{2}\s/',
    ];

    $conteos = array_fill_keys(array_keys($resFecha), 0);
    $tieneMonto = '/\d{1,4}(?:[.,]\d{3})*[.,]\d{2}/';

    // Solo contar en líneas que también tienen monto (para evitar falsos positivos de fechas en texto legal)
    foreach ($lineas as $l) {
        $t = trim($l);
        if (!@preg_match($tieneMonto, $t)) continue;
        foreach ($resFecha as $fmt => $re) {
            if (@preg_match($re, $t)) { $conteos[$fmt]++; break; }
        }
    }
    arsort($conteos);
    $formatoFecha   = array_key_first($conteos);
    $reFechaGanador = $resFecha[$formatoFecha] ?? null;
    $totalConteo    = $conteos[$formatoFecha] ?? 0;

    // ── 3. Clasificar líneas ─────────────────────────────────────────────────
    $lineasMovimiento = [];
    $lineasSoloFecha  = []; // líneas con fecha pero sin monto (TRANSPORTE, etc.)
    $lineasRuido      = [];
    $conSignoNeg      = 0;  // contador de montos negativos (signo_unico)

    foreach ($lineas as $l) {
        $t = trim($l);
        if (strlen($t) < 3) continue;

        $tieneF = $reFechaGanador && @preg_match($reFechaGanador, $t);
        $tieneM = @preg_match($tieneMonto, $t);

        if ($tieneF && $tieneM) {
            $lineasMovimiento[] = $t;
            // Detectar montos con signo negativo
            if (preg_match('/-\s*\d{1,4}[.,]/', $t) || preg_match('/\d[.,]\d{2}\s*-(?:\s|$)/', $t)) {
                $conSignoNeg++;
            }
        } elseif ($tieneF && !$tieneM) {
            $lineasSoloFecha[] = $t; // movimientos sin monto (ej: TRANSPORTE)
        } else {
            $lineasRuido[] = $t;
        }
    }

    // ── 4. Muestra distribuida (tomar de principio, medio y fin del extracto) ─
    $totalMov = count($lineasMovimiento);
    $muestra  = [];
    if ($totalMov <= 15) {
        $muestra = $lineasMovimiento;
    } else {
        // Primeras 5, medias 5, últimas 5
        $muestra = array_merge(
            array_slice($lineasMovimiento, 0, 5),
            array_slice($lineasMovimiento, (int)($totalMov / 2) - 2, 5),
            array_slice($lineasMovimiento, -5)
        );
        $muestra = array_unique($muestra);
    }

    // ── 5. Detectar si montos tienen signo (signo_unico) ─────────────────────
    $montosSonSigno = ($conSignoNeg > max(1, $totalMov * 0.1));

    // ── 6. Analizar estructura de montos en las líneas de muestra ────────────
    $conteoMontosPorLinea = [];
    foreach (array_slice($muestra, 0, 10) as $lm) {
        $sinFecha = preg_replace($reFechaGanador ?? '/$^/', '', $lm, 1);
        $ms = [];
        preg_match_all('/\d{1,4}(?:[.,]\d{3})*[.,]\d{2}/', $sinFecha, $ms);
        $conteoMontosPorLinea[] = count($ms[0]);
    }
    $promedioMontos = !empty($conteoMontosPorLinea)
        ? round(array_sum($conteoMontosPorLinea) / count($conteoMontosPorLinea), 1)
        : 2;

    // Estructura inferida
    if ($separador === 'tab') {
        $estructuraInferida = 'columnas_tab';
    } elseif ($montosSonSigno) {
        $estructuraInferida = 'signo_unico';
    } else {
        $estructuraInferida = 'monto_saldo'; // penúltimo=monto, último=saldo
    }

    return [
        'separador'          => $separador,
        'formato_fecha'      => $formatoFecha,
        'lineas_movimiento'  => $muestra,
        'lineas_ruido'       => array_slice($lineasRuido, 0, 20),
        'lineas_solo_fecha'  => array_slice($lineasSoloFecha, 0, 5),
        'total_movimiento'   => $totalMov,
        'total_conteo_fecha' => $totalConteo,
        'montos_con_signo'   => $montosSonSigno,
        'promedio_montos_por_linea' => $promedioMontos,
        'estructura_inferida'=> $estructuraInferida,
    ];
}

/**
 * Genera patrones REGEX para un banco desconocido.
 * Proceso:
 *   1. PHP pre-analiza el texto y clasifica líneas reales.
 *   2. IA recibe las líneas reales y genera regex precisos.
 *   3. PHP valida cada regex contra las mismas líneas reales.
 *   4. PHP auto-corrige fallos comunes (captura de fecha, regex iguales).
 * La IA NUNCA extrae datos.
 */
function _generarPatronesParaBancoNuevo(string $textoLimpio, string $banco): array {

    // ── Fase 1: PHP pre-analiza ──────────────────────────────────────────────
    $pre = _preanalizarTexto($textoLimpio);

    if (empty($pre['lineas_movimiento'])) {
        error_log("_generarPatronesParaBancoNuevo '{$banco}': sin líneas de movimiento detectadas");
        return [];
    }

    $lineasMovStr  = implode("\n", $pre['lineas_movimiento']);
    $lineasRuidoStr = implode("\n", array_slice($pre['lineas_ruido'], 0, 15));

    // ── Fase 2: llamada a IA con datos pre-clasificados ──────────────────────
    $estimPaginas  = max(1, (int)ceil($pre['total_movimiento'] / 25));
    $montoSignoStr = $pre['montos_con_signo'] ? 'true' : 'false';
    $lineasSFStr   = implode("\n", $pre['lineas_solo_fecha'] ?? []);

    $userMsg = <<<MSG
BANCO: {$banco}
SEPARADOR: {$pre['separador']}
FORMATO_FECHA_PHP: {$pre['formato_fecha']}
ESTRUCTURA_INFERIDA: {$pre['estructura_inferida']}
MONTOS_CON_SIGNO: {$montoSignoStr}
PROMEDIO_MONTOS_POR_LINEA: {$pre['promedio_montos_por_linea']}
TOTAL_MOVIMIENTOS: {$pre['total_movimiento']}
PAGINAS_ESTIMADAS: {$estimPaginas}

=== LINEAS_MOVIMIENTO (muestra distribuida — lineas reales con fecha y monto) ===
{$lineasMovStr}

=== LINEAS_SIN_MONTO (lineas con fecha pero sin monto — tambien son movimientos) ===
{$lineasSFStr}

=== LINEAS_RUIDO (encabezados, pie de pagina, textos legales) ===
{$lineasRuidoStr}

Seguí los 4 pasos. En PASO 1 usa exactamente FORMATO_FECHA_PHP. En PASO 2 usa exactamente ESTRUCTURA_INFERIDA y MONTOS_CON_SIGNO. RECORDATORIO: IVA y textos legales van en skip_linea, fin_tabla solo para cierres unicos.
MSG;

    $body = [
        'model'           => 'gpt-4o',
        'max_tokens'      => 1500,
        'temperature'     => 0,
        'response_format' => ['type' => 'json_object'],
        'messages'        => [
            ['role' => 'system', 'content' => _getPromptGeneradorRegex()],
            ['role' => 'user',   'content' => $userMsg],
        ],
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => OPENAI_URL,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_KEY],
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$resp) {
        error_log("_generarPatronesParaBancoNuevo: HTTP {$code}");
        return [];
    }

    $decoded = json_decode($resp, true);
    $raw     = $decoded['choices'][0]['message']['content'] ?? '';
    if (!$raw) return [];

    try {
        $p = parsearRespuestaJSON(trim($raw));
    } catch (\Throwable $e) {
        error_log("_generarPatronesParaBancoNuevo parse error: " . $e->getMessage());
        return [];
    }

    // ── Fase 3: validación PHP de cada regex ─────────────────────────────────
    $camposRegex = ['regex_linea_movimiento','regex_captura_fecha',
                    'regex_captura_debito','regex_captura_credito',
                    'regex_captura_monto_op','regex_captura_saldo'];
    foreach ($camposRegex as $campo) {
        $v = $p[$campo] ?? null;
        if ($v === null || $v === 'null' || trim($v) === '') { $p[$campo] = null; continue; }
        if (!_validarRegex($v)) {
            error_log("_generarPatronesParaBancoNuevo: regex inválido '{$campo}': {$v}");
            $p[$campo] = null;
        }
    }
    foreach (['patrones_inicio_tabla','patrones_fin_tabla'] as $lc) {
        $p[$lc] = is_array($p[$lc] ?? null)
            ? array_values(array_filter($p[$lc], fn($r) => is_string($r) && strlen($r) > 1 && _validarRegex($r)))
            : [];
    }
    if (!is_array($p['patrones_skip_linea'] ?? null)) $p['patrones_skip_linea'] = [];
    if (!is_array($p['col_tab'] ?? null))             $p['col_tab'] = [];

    // ── Fase 4: verificación contra líneas reales ─────────────────────────────

    // 4a. Self-test regex_linea_movimiento vs líneas reales pre-clasificadas
    $hitsReales = 0;
    $ejsCapturados = [];
    if (!empty($p['regex_linea_movimiento'])) {
        $re = _wrapRegex($p['regex_linea_movimiento']);
        foreach ($pre['lineas_movimiento'] as $lm) {
            if (@preg_match($re, $lm)) {
                $hitsReales++;
                if (count($ejsCapturados) < 5) $ejsCapturados[] = $lm;
            }
        }
    }

    // 4b. Si 0 hits → usar regex construido desde formato detectado por PHP (100% fiable)
    if ($hitsReales === 0) {
        error_log("_generarPatronesParaBancoNuevo '{$banco}': 0 hits en líneas reales → override con formato PHP");
        $fmtMap = [
            'dd-MMM-yyyy' => ['^\d{2}-[A-Z]{3}-\d{4}\s', '^(\d{2}-[A-Z]{3}-\d{4})\s'],
            'dd/mm/yyyy'  => ['^\d{2}\/\d{2}\/\d{4}\s',  '^(\d{2}\/\d{2}\/\d{4})\s'],
            'dd/mm/yy'    => ['^\d{2}\/\d{2}\/\d{2}\s',  '^(\d{2}\/\d{2}\/\d{2})\s'],
            'dd/mm'       => ['^\d{1,2}\/\d{2}\s',        '^(\d{1,2}\/\d{2})\s'],
            'dd-mm-yyyy'  => ['^\d{2}-\d{2}-\d{4}\s',     '^(\d{2}-\d{2}-\d{4})\s'],
            'yyyy-mm-dd'  => ['^\d{4}-\d{2}-\d{2}\s',     '^(\d{4}-\d{2}-\d{2})\s'],
        ];
        [$reLinea, $reFecha] = $fmtMap[$pre['formato_fecha']] ?? $fmtMap['dd/mm/yyyy'];
        $p['regex_linea_movimiento'] = $reLinea;
        $p['regex_captura_fecha']    = $reFecha;
        $p['formato_fecha']          = $pre['formato_fecha'];
        // Re-verificar
        $re = _wrapRegex($reLinea);
        foreach ($pre['lineas_movimiento'] as $lm) {
            if (@preg_match($re, $lm)) { $hitsReales++; $ejsCapturados[] = $lm; }
        }
    }

    // 4c. Verificar regex_captura_monto_op: NO debe capturar números ≤ 31 (dígitos de fecha)
    if (!empty($p['regex_captura_monto_op']) && !empty($ejsCapturados)) {
        $reMop = _wrapRegex($p['regex_captura_monto_op']);
        $capturasFechaCount = 0;
        foreach (array_slice($ejsCapturados, 0, 5) as $ej) {
            if (@preg_match($reMop, $ej, $mm)) {
                $v = _parsearMontoAR($mm[1] ?? '');
                if ($v !== null && abs($v) <= 31 && abs($v) == round(abs($v))) {
                    $capturasFechaCount++;
                }
            }
        }
        if ($capturasFechaCount >= 2) {
            // Regex captura dígito de fecha → reemplazar con regex seguro con decimal obligatorio
            error_log("_generarPatronesParaBancoNuevo '{$banco}': monto_op captura dígitos de fecha → aplicando override seguro");
            $p['regex_captura_monto_op'] = null; // forzar uso de _extraerMontosConDecimal
        }
    }

    // 4d. Verificar regex_captura_saldo: si igual a monto_op → descartar
    if (!empty($p['regex_captura_saldo']) && $p['regex_captura_saldo'] === ($p['regex_captura_monto_op'] ?? null)) {
        $p['regex_captura_saldo'] = null;
    }

    // 4e. Si debito == credito → forzar monto_saldo
    $reDb = $p['regex_captura_debito']  ?? null;
    $reCr = $p['regex_captura_credito'] ?? null;
    if ($reDb !== null && $reCr !== null && $reDb === $reCr) {
        $p['regex_captura_monto_op'] ??= $reDb;
        $p['regex_captura_debito']    = null;
        $p['regex_captura_credito']   = null;
        $p['estructura_montos']       = 'monto_saldo';
        $p['montos_con_signo']        = false;
    }

    // 4f. Forzar null en todos los regex de montos — el extractor universal no los necesita
    $p['regex_captura_debito']   = null;
    $p['regex_captura_credito']  = null;
    $p['regex_captura_monto_op'] = null;
    $p['regex_captura_saldo']    = null;

    // 4g. CRÍTICO: validar patrones_fin_tabla contra el texto completo
    // Un patrón de fin_tabla que aparece en múltiples lugares del texto
    // cortará el procesamiento en el primer match (antes de tiempo).
    // Si aparece más de 1 vez → moverlo a skip_linea automáticamente.
    $textoCompleto = implode("\n", array_merge(
        $pre['lineas_movimiento'],
        $pre['lineas_ruido']
    ));
    $finTablaFiltrados = [];
    foreach (($p['patrones_fin_tabla'] ?? []) as $patron) {
        if (!is_string($patron) || strlen($patron) < 3) continue;
        // Contar cuántas veces aparece en el texto completo del extracto
        $apariciones = substr_count(mb_strtolower($textoCompleto), mb_strtolower($patron));
        if ($apariciones <= 1) {
            // Aparece 0 o 1 vez → es un cierre real, mantener en fin_tabla
            $finTablaFiltrados[] = $patron;
        } else {
            // Aparece múltiples veces → es texto de cada página, mover a skip_linea
            error_log("_generarPatronesParaBancoNuevo: '{$patron}' aparece {$apariciones} veces → movido de fin_tabla a skip_linea");
            if (!in_array($patron, $p['patrones_skip_linea'])) {
                $p['patrones_skip_linea'][] = $patron;
            }
        }
    }
    $p['patrones_fin_tabla'] = $finTablaFiltrados;

    // 4h. Filtrar de skip_linea todo dato personal/específico del extracto.
    // Solo deben quedar patrones estructurales del banco, válidos para cualquier PDF.
    $skipFiltrado = [];
    foreach (($p['patrones_skip_linea'] ?? []) as $sk) {
        $sk = trim($sk);
        if (strlen($sk) < 3) continue;

        // Eliminar si contiene CUIL/DNI personal (20-, 27-, 23-, 24- seguido de dígitos)
        if (preg_match('/\b(20|27|23|24|30)-\d{8}-\d\b/', $sk)) {
            if (!preg_match('/^CUIT:\s*30-/i', $sk)) { // CUIT del banco (30-) sí se guarda
                error_log("skip_linea CUIL personal eliminado: '{$sk}'");
                continue;
            }
        }

        // Eliminar si es solo texto en mayúsculas sin palabras clave estructurales
        // (indica nombre propio o dirección del titular)
        $tienePalabraClave = preg_match(
            '/FECHA|CONCEPTO|CREDITO|DEBITO|SALDO|HOJA|RESUMEN|CUENTA|IVA|CBU|CUIT|SISTEMA|TIPO|SUCURSAL|CAJA|DOCUMENTAC|DOMICILIO|CIUDAD|BANCO|BRUTOS|ESTIMADO|BENEFICIO|PODES|PODÉS|REGIMEN|TRANSPARENCIA|INSCRIPTO|TITULAR|PERIODO|PERÍODO|EXTRACTO|MOVIMIENTO/i',
            $sk
        );

        // String en mayúsculas puro sin palabras clave → probablemente nombre/dirección
        if (!$tienePalabraClave
            && preg_match('/^[A-ZÁÉÍÓÚÑ0-9][A-ZÁÉÍÓÚÑ0-9\s\.\-\/]+$/', $sk)
            && mb_strlen($sk) < 50
        ) {
            error_log("skip_linea dato personal/dirección eliminado: '{$sk}'");
            continue;
        }

        // Eliminar patrones que empiezan con punto y nombre (ej: ".MACIEL NICOLAS")
        if (preg_match('/^\.[A-ZÁÉÍÓÚÑ]/', $sk)) {
            error_log("skip_linea titular al pie eliminado: '{$sk}'");
            continue;
        }

        $skipFiltrado[] = $sk;
    }
    $p['patrones_skip_linea'] = array_values(array_unique($skipFiltrado));

    // 4i. Eliminar de fin_tabla cualquier string con fecha concreta (no sirve para otros períodos)
    $finGenerico = [];
    foreach (($p['patrones_fin_tabla'] ?? []) as $patron) {
        if (!is_string($patron) || strlen(trim($patron)) < 3) continue;
        // Si tiene una fecha específica (dd/mm/yyyy, dd-mm-yyyy, etc.) → no es genérico
        if (preg_match('/\d{2}[\/-]\d{2}[\/-]\d{2,4}/', $patron)) {
            // Quitar la fecha y usar solo el prefijo (ej: "SALDO AL 27/02/2026" → "SALDO AL")
            $sinFecha = trim(preg_replace('/\s*\d{2}[\/-]\d{2}[\/-]\d{2,4}.*$/', '', $patron));
            if (strlen($sinFecha) >= 3) {
                error_log("fin_tabla con fecha específica → generalizado: '{$patron}' → '{$sinFecha}'");
                $finGenerico[] = $sinFecha;
            }
        } else {
            $finGenerico[] = $patron;
        }
    }
    $p['patrones_fin_tabla'] = array_values(array_unique($finGenerico));

    // Forzar formato_fecha detectado por PHP (más fiable que la IA)
    $p['formato_fecha'] = $pre['formato_fecha'];

    $p['self_test_hits']   = $hitsReales;
    $p['lineas_ejemplo']   = $ejsCapturados;
    $p['separador_php']    = $pre['separador'];

    error_log("_generarPatronesParaBancoNuevo OK '{$banco}'"
        . " hits={$hitsReales}/{$pre['total_movimiento']}"
        . " fmt={$p['formato_fecha']}"
        . " estructura=" . ($p['estructura_montos'] ?? '?')
        . " sep={$pre['separador']}");

    return $p;
}

/**
 * Guarda el perfil completo para un banco nuevo, usando los patrones
 * generados exclusivamente por _generarPatronesParaBancoNuevo().
 * No recibe movimientos (la IA no extrae datos en este flujo).
 *
 * @param string $textoLimpio       Texto limpio del extracto
 * @param string $bancoNombreReal   Nombre real del banco (extraído por la IA del encabezado)
 * @param string $bancoDetector     Nombre que devolvió detectarBanco()
 * @param array  $iaPatrones        Patrones generados por _generarPatronesParaBancoNuevo()
 */
function _guardarPerfilBancoNuevo(string $textoLimpio, string $bancoNombreReal, string $bancoDetector, array $iaPatrones): void {
    $lineas = explode("\n", str_replace(["\r\n", "\r"], "\n", $textoLimpio));

    // ── Detectar estadísticas básicas del formato (sin IA) ──────────────────
    // Formato de fecha — tomar del resultado IA o inferir
    $formatoFecha = $iaPatrones['formato_fecha'] ?? 'dd/mm/yyyy';

    // Separador de columnas
    $tabC = $pipeC = $spaceC = 0;
    foreach (array_slice($lineas, 0, 80) as $l) {
        if (strpos($l, "\t") !== false)    $tabC++;
        if (strpos($l, '|') !== false)     $pipeC++;
        if (preg_match('/  {2,}/', $l))    $spaceC++;
    }
    $separador = 'spaces';
    if ($tabC  >= $pipeC && $tabC  >= $spaceC) $separador = 'tab';
    if ($pipeC > $tabC   && $pipeC > $spaceC)  $separador = 'pipe';

    // Prefijo de monto ($)
    $dolarC = 0;
    foreach (array_slice($lineas, 0, 80) as $l) {
        if (preg_match('/\$\s*-?\d/', $l)) $dolarC++;
    }
    $prefijoMonto = ($dolarC > 5) ? '$' : '';

    // Montos con signo (tomar del resultado IA o inferir)
    $conSigno = (bool)($iaPatrones['montos_con_signo'] ?? false);

    // Ruido: líneas que no tienen fecha ni montos y se repiten
    $conteoRuido = [];
    foreach ($lineas as $l) {
        $t = trim($l);
        if (mb_strlen($t) < 3 || mb_strlen($t) > 120) continue;
        if (!preg_match('/\d{1,2}[\/-]\d{2}/', $t) && !preg_match('/\d{1,3}[.,]\d{3}/', $t)) {
            $key = mb_substr($t, 0, 30);
            $conteoRuido[$key] = ($conteoRuido[$key] ?? 0) + 1;
        }
    }
    $patronesRuido = array_keys(array_filter($conteoRuido, fn($c) => $c >= 2));

    // Keywords de encabezado de tabla
    $headerKeywords = [];
    foreach ($lineas as $l) {
        $t = trim($l);
        if (preg_match('/(?:Fecha|Date)/i', $t) && preg_match('/(?:Descripci|Concepto|Detalle)/i', $t)) {
            $headerKeywords = array_values(array_filter(preg_split('/[\t|]+/', $t)));
            break;
        }
    }

    // Muestras de líneas de movimiento (para fingerprint futuro)
    $muestras = [];
    $reLinea  = !empty($iaPatrones['regex_linea_movimiento'])
        ? _wrapRegex($iaPatrones['regex_linea_movimiento'])
        : '/^\d{1,2}[\/-]\d{2}/';
    foreach ($lineas as $l) {
        if (count($muestras) >= 5) break;
        $t = trim($l);
        if (@preg_match($reLinea, $t) && preg_match('/\d{1,3}[.,]\d{3}/', $t)) {
            $muestras[] = $t;
        }
    }

    // ── Construir perfil ─────────────────────────────────────────────────────
    $perfil = [
        'banco'                  => $bancoNombreReal,
        'banco_detector'         => $bancoDetector,
        'formato_fecha'          => $formatoFecha,
        'separador'              => $separador,
        'montos_con_signo'       => $conSigno,
        'prefijo_monto'          => $prefijoMonto,
        'sufijo_d_fecha'         => (bool)($iaPatrones['tiene_sufijo_D'] ?? false),
        'ruido'                  => array_slice($patronesRuido, 0, 20),
        'header_keywords'        => $headerKeywords,
        'muestras'               => $muestras,
        'movimientos_ia'         => 0,          // sin movimientos: la IA no los extrajo
        'aprendido_en'           => date('Y-m-d H:i:s'),
        'aprendido_via'          => 'generador_regex',   // distingue de auto-aprendizaje post-IA
        'veces_usado'            => 0,
        'ia_patrones'            => $iaPatrones,
        'ia_patrones_generados'  => true,
        'ia_patrones_usos'       => 0,
        'ia_patrones_ultimo_uso' => null,
    ];

    $patrones = _cargarPatrones();

    // Guardar bajo nombre real del banco
    $patrones[$bancoNombreReal] = $perfil;

    // Guardar alias bajo nombre del detector si difiere
    if ($bancoDetector && $bancoDetector !== $bancoNombreReal) {
        $alias                  = $perfil;
        $alias['banco']         = $bancoDetector;
        $alias['banco_ia']      = $bancoNombreReal;
        $patrones[$bancoDetector] = $alias;
        error_log("_guardarPerfilBancoNuevo: alias '{$bancoDetector}' → '{$bancoNombreReal}'");
    }

    _guardarPatrones($patrones);

    error_log(
        "_guardarPerfilBancoNuevo: perfil guardado para '{$bancoNombreReal}'"
        . ($bancoDetector !== $bancoNombreReal ? " + alias '{$bancoDetector}'" : '')
        . " via=generador_regex | hits=" . ($iaPatrones['self_test_hits'] ?? '?')
    );
}

// ═════════════════════════════════════════════════════════════════════════
//  ORQUESTADOR PRINCIPAL
// ═════════════════════════════════════════════════════════════════════════
function procesarTexto(string $texto, string $archivo): array {
    if (strlen(trim($texto)) < 40) throw new RuntimeException('Texto insuficiente.');

    $banco       = detectarBanco($texto);
    $tablaLimpia = limpiarTextoExtracto($texto, $banco);

    // Debug file para diagnóstico
    $debugLog = "=== PROCESARTEXTO DEBUG ===\n";
    $debugLog .= "Archivo: {$archivo}\nBanco: {$banco}\n";
    $debugLog .= "Chars original: " . strlen($texto) . " | Chars limpio: " . strlen($tablaLimpia) . "\n";
    $debugLog .= "Patrones file: " . PATRONES_FILE . "\n";
    $debugLog .= "Patrones exists: " . (file_exists(PATRONES_FILE) ? 'SÍ' : 'NO') . "\n";
    if (file_exists(PATRONES_FILE)) {
        $pp = _cargarPatrones();
        $debugLog .= "Patrones bancos: " . implode(', ', array_keys($pp)) . "\n";
    }
    $debugLog .= "\n";

    // ══ PASO 1: parser por etiquetas [DEBITO:] [CREDITO:] [SALDO:] ══
    $movsDirectos = _parsearTextoEtiquetado($tablaLimpia);

    $debugLog .= "PASO 1 (etiquetas): " . ($movsDirectos ? count($movsDirectos) . " movs" : "null") . "\n";

    if ($movsDirectos !== null && count($movsDirectos) >= 2) {
        $cabecera = _extraerCabeceraRegex($texto);
        if ($cabecera['saldo_final'] === null) {
            for ($i = count($movsDirectos) - 1; $i >= 0; $i--) {
                if ($movsDirectos[$i]['saldo'] !== null) {
                    $cabecera['saldo_final'] = $movsDirectos[$i]['saldo'];
                    break;
                }
            }
        }

        $totalC = array_sum(array_column($movsDirectos, 'credito'));
        $totalD = array_sum(array_column($movsDirectos, 'debito'));

        $debugLog .= "RESULTADO: PASO 1 → " . count($movsDirectos) . " movimientos\n";
        @file_put_contents(__DIR__ . '/uploads/temp/_debug_flow.txt', $debugLog, FILE_APPEND);

        return [
            'banco'        => $banco,
            'archivo'      => $archivo,
            'cabecera'     => $cabecera,
            'movimientos'  => $movsDirectos,
            'estadisticas' => [
                'total_movimientos' => count($movsDirectos),
                'total_creditos'    => round($totalC, 2),
                'total_debitos'     => round($totalD, 2),
                'neto'              => round($totalC - $totalD, 2),
            ],
            'impuestos' => [],
            'motor'     => 'Reglas (etiquetas)',
        ];
    }

    // ══ PASO 2: parser sin etiquetas (texto plano) ══
    $movsSinTag = _parsearTextoSinEtiquetas($tablaLimpia);

    $debugLog .= "PASO 2 (sin etiquetas): " . ($movsSinTag ? count($movsSinTag) . " movs" : "null") . "\n";

    if ($movsSinTag !== null && count($movsSinTag) >= 2) {
        $cabecera = _extraerCabeceraRegex($texto);
        if ($cabecera['saldo_final'] === null) {
            for ($i = count($movsSinTag) - 1; $i >= 0; $i--) {
                if ($movsSinTag[$i]['saldo'] !== null) {
                    $cabecera['saldo_final'] = $movsSinTag[$i]['saldo'];
                    break;
                }
            }
        }

        $totalC = array_sum(array_column($movsSinTag, 'credito'));
        $totalD = array_sum(array_column($movsSinTag, 'debito'));

        $debugLog .= "RESULTADO: PASO 2 → " . count($movsSinTag) . " movimientos\n";
        @file_put_contents(__DIR__ . '/uploads/temp/_debug_flow.txt', $debugLog, FILE_APPEND);

        return [
            'banco'        => $banco,
            'archivo'      => $archivo,
            'cabecera'     => $cabecera,
            'movimientos'  => $movsSinTag,
            'estadisticas' => [
                'total_movimientos' => count($movsSinTag),
                'total_creditos'    => round($totalC, 2),
                'total_debitos'     => round($totalD, 2),
                'neto'              => round($totalC - $totalD, 2),
            ],
            'impuestos' => [],
            'motor'     => 'Reglas (texto plano)',
        ];
    }

    // ══ PASO 2.3: parser con patrones regex GENERADOS POR IA (más preciso) ══
    $movsPatronesIA = _parsearConPatronesIA($tablaLimpia, $banco);

    $debugLog .= "PASO 2.3 (regex IA): " . ($movsPatronesIA ? count($movsPatronesIA) . " movs" : "null") . "\n";

    if ($movsPatronesIA !== null && count($movsPatronesIA) >= 2) {
        $cabecera = _extraerCabeceraRegex($texto);
        if ($cabecera['saldo_final'] === null) {
            for ($i = count($movsPatronesIA) - 1; $i >= 0; $i--) {
                if ($movsPatronesIA[$i]['saldo'] !== null) {
                    $cabecera['saldo_final'] = $movsPatronesIA[$i]['saldo'];
                    break;
                }
            }
        }

        $totalC = array_sum(array_column($movsPatronesIA, 'credito'));
        $totalD = array_sum(array_column($movsPatronesIA, 'debito'));

        $debugLog .= "RESULTADO: PASO 2.3 → " . count($movsPatronesIA) . " movimientos\n";
        @file_put_contents(__DIR__ . '/uploads/temp/_debug_flow.txt', $debugLog, FILE_APPEND);

        return [
            'banco'        => $banco,
            'archivo'      => $archivo,
            'cabecera'     => $cabecera,
            'movimientos'  => $movsPatronesIA,
            'estadisticas' => [
                'total_movimientos' => count($movsPatronesIA),
                'total_creditos'    => round($totalC, 2),
                'total_debitos'     => round($totalD, 2),
                'neto'              => round($totalC - $totalD, 2),
            ],
            'impuestos' => [],
            'motor'     => 'Reglas (regex aprendido de IA)',
        ];
    }

    // ══ PASO 2.5: parser con patrones APRENDIDOS de IA anterior (estadístico) ══
    $movsAprendidos = _parsearConPatronesAprendidos($tablaLimpia, $banco);

    $debugLog .= "PASO 2.5 (aprendido): " . ($movsAprendidos ? count($movsAprendidos) . " movs" : "null") . "\n";

    if ($movsAprendidos !== null && count($movsAprendidos) >= 2) {
        $cabecera = _extraerCabeceraRegex($texto);
        if ($cabecera['saldo_final'] === null) {
            for ($i = count($movsAprendidos) - 1; $i >= 0; $i--) {
                if ($movsAprendidos[$i]['saldo'] !== null) {
                    $cabecera['saldo_final'] = $movsAprendidos[$i]['saldo'];
                    break;
                }
            }
        }

        $totalC = array_sum(array_column($movsAprendidos, 'credito'));
        $totalD = array_sum(array_column($movsAprendidos, 'debito'));

        $debugLog .= "RESULTADO: PASO 2.5 → " . count($movsAprendidos) . " movimientos\n";
        @file_put_contents(__DIR__ . '/uploads/temp/_debug_flow.txt', $debugLog, FILE_APPEND);

        return [
            'banco'        => $banco,
            'archivo'      => $archivo,
            'cabecera'     => $cabecera,
            'movimientos'  => $movsAprendidos,
            'estadisticas' => [
                'total_movimientos' => count($movsAprendidos),
                'total_creditos'    => round($totalC, 2),
                'total_debitos'     => round($totalD, 2),
                'neto'              => round($totalC - $totalD, 2),
            ],
            'impuestos' => [],
            'motor'     => 'Reglas (aprendido de IA)',
        ];
    }

    $debugLog .= "CAYÓ A IA (ningún parser regex funcionó)\n";

    // ── Detectar si ya teníamos un perfil guardado y aún así cayó a IA ──────
    $perfilesExistentes = _cargarPatrones();
    $perfilPrevioExiste = isset($perfilesExistentes[$banco])
        || (bool)array_filter($perfilesExistentes, fn($p) => ($p['banco_detector'] ?? '') === $banco);
    if ($perfilPrevioExiste) {
        $debugLog .= "⚠ PERFIL PREVIO EXISTENTE para '{$banco}' pero falló → se regenerarán los patrones\n";
        error_log("ADVERTENCIA: IA invocada para '{$banco}' pese a tener perfil guardado — patrones anteriores inválidos, se regenerarán");
    }

    // ══ PASO 2.7: IA COMO GENERADOR PURO DE REGEX ════════════════════════════
    //  La IA NO extrae datos. Analiza la estructura del texto y emite
    //  exclusivamente patrones REGEX listos para que el motor PHP extraiga.
    //  Activa cuando: (a) banco sin patrones, o (b) perfil marcado como inválido.
    // ─────────────────────────────────────────────────────────────────────────
    $debugLog .= "PASO 2.7 (generador regex para banco desconocido)...\n";
    @file_put_contents(__DIR__ . '/uploads/temp/_debug_flow.txt', $debugLog, FILE_APPEND);

    // Pre-análisis PHP para saber cuántos movimientos debe encontrar
    $preAnalisis      = _preanalizarTexto($tablaLimpia);
    $totalEsperado    = $preAnalisis['total_movimiento'];
    $umbralAceptable  = max(2, (int)ceil($totalEsperado * 0.80)); // 80% mínimo

    $debugLog .= "PASO 2.7: total movimientos esperados={$totalEsperado}, umbral={$umbralAceptable}\n";

    $intentoMax  = 2; // máximo 2 generaciones de patrones antes de rendirse
    $bancoNombreReal = $banco;
    $mejorResultado  = null;
    $mejorPatrones   = null;

    for ($intento = 1; $intento <= $intentoMax; $intento++) {
        $debugLog .= "PASO 2.7 intento {$intento}/{$intentoMax}...\n";

        try {
            $patronesNuevo = _generarPatronesParaBancoNuevo($tablaLimpia, $banco);
        } catch (\Throwable $e) {
            $debugLog .= "PASO 2.7 ERROR generando patrones: " . $e->getMessage() . "\n";
            break;
        }

        if (empty($patronesNuevo)) {
            $debugLog .= "PASO 2.7: generación devolvió vacío\n";
            break;
        }

        $bancoNombreReal = $patronesNuevo['nombre_banco_detectado'] ?? $banco;
        unset($patronesNuevo['nombre_banco_detectado']);

        // Guardar patrones
        _guardarPerfilBancoNuevo($tablaLimpia, $bancoNombreReal, $banco, $patronesNuevo);
        $debugLog .= "PASO 2.7: patrones guardados para '{$bancoNombreReal}' (hits={$patronesNuevo['self_test_hits']})\n";

        // ── Verificación: extraer y contar ──────────────────────────────────
        $movsTest = _parsearConPatronesIA($tablaLimpia, $bancoNombreReal);
        if (($movsTest === null || count($movsTest) < 2) && $bancoNombreReal !== $banco) {
            $movsTest = _parsearConPatronesIA($tablaLimpia, $banco);
        }

        $cantExtraida = $movsTest ? count($movsTest) : 0;
        $cobertura    = $totalEsperado > 0
            ? round($cantExtraida / $totalEsperado * 100)
            : 0;

        $debugLog .= "PASO 2.7 verificacion: extraidos={$cantExtraida}/{$totalEsperado} cobertura={$cobertura}%\n";

        // Guardar el mejor resultado obtenido hasta ahora
        if ($movsTest !== null && $cantExtraida > ($mejorResultado ? count($mejorResultado) : 0)) {
            $mejorResultado = $movsTest;
            $mejorPatrones  = $patronesNuevo;
        }

        // ── Evaluación del resultado ─────────────────────────────────────────
        if ($cantExtraida >= $umbralAceptable) {
            // ✓ Cobertura suficiente → devolver resultado
            $debugLog .= "PASO 2.7: cobertura OK ({$cobertura}%) → devolviendo resultado\n";
            $cabecera = _extraerCabeceraRegex($texto);
            if ($cabecera['saldo_final'] === null) {
                for ($i = $cantExtraida - 1; $i >= 0; $i--) {
                    if ($movsTest[$i]['saldo'] !== null) {
                        $cabecera['saldo_final'] = $movsTest[$i]['saldo']; break;
                    }
                }
            }
            $totalC = array_sum(array_column($movsTest, 'credito'));
            $totalD = array_sum(array_column($movsTest, 'debito'));
            $debugLog .= "RESULTADO: PASO 2.7 → {$cantExtraida} movimientos ({$cobertura}% cobertura)\n";
            @file_put_contents(__DIR__ . '/uploads/temp/_debug_flow.txt', $debugLog, FILE_APPEND);
            return [
                'banco'        => $bancoNombreReal,
                'archivo'      => $archivo,
                'cabecera'     => $cabecera,
                'movimientos'  => $movsTest,
                'estadisticas' => [
                    'total_movimientos' => $cantExtraida,
                    'total_creditos'    => round($totalC, 2),
                    'total_debitos'     => round($totalD, 2),
                    'neto'              => round($totalC - $totalD, 2),
                ],
                'impuestos' => [],
                'motor'     => "Regex IA — {$bancoNombreReal} ({$cobertura}% cobertura, {$cantExtraida}/{$totalEsperado} movs)",
            ];

        } elseif ($intento < $intentoMax) {
            // ✗ Cobertura insuficiente → diagnosticar e indicar a la IA qué mejorar
            $debugLog .= "PASO 2.7: cobertura baja ({$cobertura}%), regenerando patrones...\n";

            // Forzar limpieza del perfil malo para que la próxima iteración regenere limpio
            $pp = _cargarPatrones();
            foreach ([$bancoNombreReal, $banco] as $bk) {
                if (isset($pp[$bk])) {
                    $pp[$bk]['ia_patrones_invalido']       = true;
                    $pp[$bk]['ia_patrones_invalido_razon'] = "Cobertura {$cobertura}% ({$cantExtraida}/{$totalEsperado}) — regenerando";
                }
            }
            _guardarPatrones($pp);
        }
    }

    // ── Post-intentos: usar el mejor resultado parcial si existe ────────────
    if ($mejorResultado !== null && count($mejorResultado) >= 2) {
        $cantExtraida = count($mejorResultado);
        $cobertura    = $totalEsperado > 0 ? round($cantExtraida / $totalEsperado * 100) : 0;
        $debugLog .= "PASO 2.7: usando mejor resultado parcial → {$cantExtraida} movs ({$cobertura}%)\n";

        // Limpiar flag de inválido del perfil que funcionó mejor
        $pp = _cargarPatrones();
        foreach ([$bancoNombreReal, $banco] as $bk) {
            if (isset($pp[$bk])) {
                unset($pp[$bk]['ia_patrones_invalido']);
                unset($pp[$bk]['ia_patrones_invalido_razon']);
            }
        }
        _guardarPatrones($pp);

        $cabecera = _extraerCabeceraRegex($texto);
        if ($cabecera['saldo_final'] === null) {
            for ($i = $cantExtraida - 1; $i >= 0; $i--) {
                if ($mejorResultado[$i]['saldo'] !== null) {
                    $cabecera['saldo_final'] = $mejorResultado[$i]['saldo']; break;
                }
            }
        }
        $totalC = array_sum(array_column($mejorResultado, 'credito'));
        $totalD = array_sum(array_column($mejorResultado, 'debito'));
        $debugLog .= "RESULTADO: PASO 2.7 parcial → {$cantExtraida} movimientos ({$cobertura}%)\n";
        @file_put_contents(__DIR__ . '/uploads/temp/_debug_flow.txt', $debugLog, FILE_APPEND);
        return [
            'banco'        => $bancoNombreReal,
            'archivo'      => $archivo,
            'cabecera'     => $cabecera,
            'movimientos'  => $mejorResultado,
            'estadisticas' => [
                'total_movimientos' => $cantExtraida,
                'total_creditos'    => round($totalC, 2),
                'total_debitos'     => round($totalD, 2),
                'neto'              => round($totalC - $totalD, 2),
            ],
            'impuestos' => [],
            'motor'     => "Regex IA parcial — {$bancoNombreReal} ({$cobertura}% cobertura, {$cantExtraida}/{$totalEsperado} movs)",
        ];
    }

    // ── Sin resultado: los patrones están guardados, próxima subida los usa ──
    @file_put_contents(__DIR__ . '/uploads/temp/_debug_flow.txt', $debugLog);
    throw new RuntimeException(
        "Patrones generados para {$bancoNombreReal}. "
        . "La próxima importación extraerá los datos directamente con regex."
    );
}

// ═════════════════════════════════════════════════════════════════════════
//  EXTRACCIÓN DE TEXTO PDF (lado servidor, fallback)
// ═════════════════════════════════════════════════════════════════════════
function extraerTextoPDFServidor(string $ruta): string {
    if (function_exists('exec')) {
        @exec('pdftotext -v 2>&1', $t, $r);
        if ($r === 0 || strpos(implode(' ', $t), 'pdftotext') !== false) {
            $tmp = tempnam(sys_get_temp_dir(), 'p_');
            @exec('pdftotext -layout -enc UTF-8 '.escapeshellarg($ruta).' '.escapeshellarg($tmp).' 2>/dev/null', $o, $r2);
            if ($r2 === 0 && is_file($tmp)) {
                $tx = file_get_contents($tmp);
                @unlink($tmp);
                if (strlen(trim($tx)) > 40) return $tx;
            }
        }
    }
    return '';
}

// ═════════════════════════════════════════════════════════════════════════
//  PROCESAMIENTO DE IMÁGENES (PDFs con fuentes custom)
// ═════════════════════════════════════════════════════════════════════════

/**
 * Prompt para vision API (imágenes de páginas de extracto)
 */
function getPromptVision(): string {
    return <<<'PROMPT'
Analizá esta imagen de un extracto bancario argentino y extraé los datos en JSON.

REGLAS CRÍTICAS:
- Extraer TODOS los movimientos visibles sin omitir ninguno.
- Identificar correctamente la columna DÉBITOS y CRÉDITOS según la cabecera visible.
- Si un monto está en la columna izquierda de montos → DÉBITO. Columna derecha → CRÉDITO.
- NUNCA poner el mismo monto en débito Y crédito.
- Si no hay columna de saldo o está vacía → "saldo": null. NO calcular.
- Si no estás seguro si es débito o crédito → ambos null + "observacion".
- Montos: float positivo con punto decimal. "1.234,56" → 1234.56
- Fechas: YYYY-MM-DD.
- NO inventar datos. Si no está en la imagen → null.
- "SALDO ANTERIOR" → va en cabecera.saldo_inicial, NO como movimiento.

JSON:
{"banco":"string","cabecera":{"titular":null,"cuit":null,"tipo_cuenta":null,"numero_cuenta":null,"cbu":null,"moneda":"ARS","sucursal":null,"periodo_desde":null,"periodo_hasta":null,"saldo_inicial":null,"saldo_final":null},"movimientos":[{"fecha":"YYYY-MM-DD","descripcion":"","comprobante":null,"debito":null,"credito":null,"saldo":null,"observacion":null}],"impuestos":{}}
PROMPT;
}

/**
 * Procesa imágenes base64 de páginas PDF usando vision API.
 */
function procesarImagenes(array $imagenesBase64, string $archivo, string $paginasInfo): array {
    // Construir el array de content con imágenes
    $userContent = [];
    $userContent[] = ['type' => 'text', 'text' => "ARCHIVO: {$archivo}\nPÁGINAS: {$paginasInfo}\n\nExtraé todos los movimientos de estas páginas del extracto bancario."];

    foreach ($imagenesBase64 as $b64) {
        // Asegurarse de que tiene el prefijo data:
        if (strpos($b64, 'data:') !== 0) {
            $b64 = 'data:image/jpeg;base64,' . $b64;
        }
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => [
                'url'    => $b64,
                'detail' => 'high',
            ],
        ];
    }

    $body = [
        'model'           => OPENAI_MODEL,
        'max_tokens'      => 16000,
        'temperature'     => 0,
        'messages'        => [
            ['role' => 'system', 'content' => getPromptVision()],
            ['role' => 'user',   'content' => $userContent],
        ],
    ];

    $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
        CURLOPT_TIMEOUT        => CURL_TIMEOUT_CH,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err || $code !== 200) {
        error_log("Vision API falló: HTTP $code | $err | " . substr($resp ?? '', 0, 300));
        throw new RuntimeException("Error de API de visión (HTTP $code)");
    }

    $decoded    = json_decode($resp, true);
    $rawContent = $decoded['choices'][0]['message']['content'] ?? null;
    if (!$rawContent) throw new RuntimeException('Vision API no devolvió contenido.');

    $dataIA = parsearRespuestaJSON(trim($rawContent));
    $banco  = $dataIA['banco'] ?? detectarBancoDesdeImagen($dataIA);

    $norm     = normalizarMovimientos($dataIA, $banco);
    $movs     = $norm['movimientos'];
    $cabecera = $norm['cabecera'];

    $totalC = array_sum(array_column($movs, 'credito'));
    $totalD = array_sum(array_column($movs, 'debito'));

    return [
        'banco'        => $banco,
        'archivo'      => $archivo,
        'cabecera'     => $cabecera,
        'movimientos'  => $movs,
        'estadisticas' => [
            'total_movimientos' => count($movs),
            'total_creditos'    => round($totalC, 2),
            'total_debitos'     => round($totalD, 2),
            'neto'              => round($totalC - $totalD, 2),
        ],
        'impuestos' => $norm['impuestos'],
        'motor'     => 'IA Vision (gpt-4o-mini, ' . count($imagenesBase64) . ' págs)',
    ];
}

function detectarBancoDesdeImagen(array $data): string {
    $banco = $data['banco'] ?? '';
    if (!$banco || $banco === '' || $banco === 'null') {
        // Intentar detectar del contenido
        $txt = json_encode($data);
        if (stripos($txt, 'galicia') !== false) return 'Banco Galicia';
        if (stripos($txt, 'patagonia') !== false) return 'Banco Patagonia';
        if (stripos($txt, 'nacion') !== false) return 'Banco Nación';
        if (stripos($txt, 'bbva') !== false || stripos($txt, 'frances') !== false) return 'BBVA Francés';
        if (stripos($txt, 'mercado pago') !== false) return 'Mercado Pago';
        return 'Banco Argentino';
    }
    return $banco;
}

// ═════════════════════════════════════════════════════════════════════════
//  MAIN
// ═════════════════════════════════════════════════════════════════════════
try {
    $resultados = [];

    // Asegurar que el directorio de uploads existe
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);

    // Debug marker — se escribe en CADA request para verificar que el código se ejecuta
    @file_put_contents(UPLOAD_DIR . '_debug_flow.txt',
        "=== REQUEST " . date('Y-m-d H:i:s') . " ===\n" .
        "POST keys: " . implode(', ', array_keys($_POST)) . "\n" .
        "FILES keys: " . implode(', ', array_keys($_FILES ?? [])) . "\n" .
        "texto_extraido length: " . strlen($_POST['texto_extraido'] ?? '') . "\n" .
        "modo: " . ($_POST['modo'] ?? 'default') . "\n\n"
    );

    // FLUJO A: texto pre-extraído por PDF.js (frontend)
    if (!empty($_POST['texto_extraido'])) {
        $texto  = utf8safe($_POST['texto_extraido']);
        $nombre = utf8safe($_POST['nombre_archivo'] ?? 'extracto.pdf');
        try {
            $data = procesarTexto($texto, $nombre);
            $resultados[] = [
                'success' => true,
                'data'    => $data,
                'motor'   => $data['motor'],
                'message' => count($data['movimientos']) . ' movimientos (' . $data['motor'] . ')',
            ];
        } catch (Exception $e) {
            $resultados[] = ['success' => false, 'message' => $e->getMessage()];
        }
        jsonOut(['success' => true, 'resultados' => $resultados]);
    }

    // FLUJO C: imágenes base64 de PDF (PDFs con fuentes custom)
    if (isset($_POST['modo']) && $_POST['modo'] === 'imagenes') {
        $nombre     = utf8safe($_POST['nombre_archivo'] ?? 'extracto.pdf');
        $paginasInfo = $_POST['paginas_info'] ?? '';
        $imagenesJson = $_POST['imagenes_json'] ?? '[]';
        $imagenes = json_decode($imagenesJson, true);

        if (empty($imagenes)) throw new RuntimeException('No se recibieron imágenes.');

        error_log("analizar_extracto v15 IMAGEN: archivo={$nombre} paginas={$paginasInfo} imgs=" . count($imagenes));

        try {
            $data = procesarImagenes($imagenes, $nombre, $paginasInfo);
            $resultados[] = [
                'success' => true,
                'data'    => $data,
                'motor'   => $data['motor'],
                'message' => count($data['movimientos']) . ' movimientos (' . $data['motor'] . ')',
            ];
        } catch (Exception $e) {
            $resultados[] = ['success' => false, 'message' => $e->getMessage()];
        }
        jsonOut(['success' => true, 'resultados' => $resultados]);
    }

    // FLUJO B: archivo PDF subido directamente
    $files = $_FILES['files'] ?? $_FILES['file'] ?? null;
    if (!$files) throw new RuntimeException('No se recibió archivo ni texto.');

    $fl = [];
    if (isset($files['name']) && is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            $fl[] = [
                'name'     => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }
    } else {
        $fl[] = $files;
    }

    foreach ($fl as $f) {
        $nom = $f['name'] ?? '?';
        try {
            if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Error subida: ' . $f['error']);
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            if ($mime !== 'application/pdf') throw new RuntimeException('Solo PDF soportado.');

            $path = UPLOAD_DIR . 'ext_' . bin2hex(random_bytes(8)) . '.pdf';
            if (!move_uploaded_file($f['tmp_name'], $path)) throw new RuntimeException('No se pudo guardar.');

            $texto = extraerTextoPDFServidor($path);
            @unlink($path);
            if (strlen(trim($texto)) < 40) throw new RuntimeException('No se pudo extraer texto del PDF.');

            $data = procesarTexto($texto, $nom);
            $resultados[] = [
                'success' => true,
                'data'    => $data,
                'motor'   => $data['motor'],
                'message' => count($data['movimientos']) . ' movimientos (' . $data['motor'] . ')',
            ];
        } catch (Exception $e) {
            $resultados[] = ['success' => false, 'message' => "[$nom] " . $e->getMessage()];
        }
    }

    jsonOut(['success' => true, 'resultados' => $resultados]);

} catch (Exception $e) {
    jsonOut(['success' => false, 'message' => $e->getMessage()], 500);
}