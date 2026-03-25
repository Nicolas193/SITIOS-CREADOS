<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Carga de Comprobantes</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../css/estilos_formulario.css">

    <style>
        /* ── RESET BÁSICO ──────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }

        /* ── VARIABLES ─────────────────────────────────────── */
        :root {
            --azul:       #2563EB;
            --azul-dk:    #1D4ED8;
            --azul-lt:    #DBEAFE;
            --verde:      #059669;
            --verde-lt:   #D1FAE5;
            --rojo:       #DC2626;
            --rojo-lt:    #FEE2E2;
            --naranja:    #D97706;
            --naranja-lt: #FEF3C7;
            --gris-100:   #F3F4F6;
            --gris-200:   #E5E7EB;
            --gris-400:   #9CA3AF;
            --gris-600:   #4B5563;
            --gris-800:   #1F2937;
            --radio:      10px;
        }

        /* ── UTILITARIOS ───────────────────────────────────── */
        .hidden { display: none !important; }

        /* ── LOADER ────────────────────────────────────────── */
        .loader {
            border: 3px solid #e5e7eb;
            border-top: 3px solid var(--azul);
            border-radius: 50%; width: 22px; height: 22px;
            animation: spin 0.8s linear infinite;
            display: inline-block; vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── ALERTAS ───────────────────────────────────────── */
        .alert {
            padding: 13px 15px; border-radius: var(--radio); margin-bottom: 16px;
            display: flex; align-items: flex-start; gap: 11px;
            font-size: 0.88rem; line-height: 1.5;
        }
        .alert i { font-size: 1.1rem; margin-top: 2px; flex-shrink: 0; }
        .alert-warning { background: var(--naranja-lt); border: 1px solid #FCD34D; color: #92400E; }
        .alert-warning i { color: var(--naranja); }
        .alert-danger   { background: var(--rojo-lt);    border: 1px solid #FECACA; color: #991B1B; }
        .alert-danger i  { color: var(--rojo); }
        .alert-success  { background: var(--verde-lt);   border: 1px solid #6EE7B7; color: #065F46; }
        .alert-success i { color: var(--verde); }
        .alert-info     { background: var(--azul-lt);    border: 1px solid #93C5FD; color: #1E40AF; }
        .alert-info i    { color: var(--azul); }

        /* ── CAMPO COMPLETADO IA ───────────────────────────── */
        .campo-ok {
            border-color: var(--verde) !important;
            background-color: #F0FDF4 !important;
            transition: border-color .25s, background-color .25s;
        }

        /* ── BADGES ────────────────────────────────────────── */
        .badge-tipo {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 700; margin-left: 8px; vertical-align: middle;
        }
        .badge-a { background: var(--rojo-lt);    color: #991B1B; }
        .badge-b { background: var(--azul-lt);    color: #1E40AF; }
        .badge-c { background: var(--verde-lt);   color: #065F46; }

        /* ── DIVISOR ───────────────────────────────────────── */
        .section-divider { border: 0; border-top: 1px dashed var(--gris-200); margin: 16px 0; }

        /* ── ROW 2 COLUMNAS ────────────────────────────────── */
        .row-2 { display: flex; gap: 10px; }
        .row-2 .input-group { flex: 1; }

        /* ── LABELS ────────────────────────────────────────── */
        .label-sub  { font-size: .78rem; color: var(--gris-600); font-weight: 500; margin-bottom: 3px; display: block; }
        .label-main { font-size: .85rem; color: var(--gris-800); font-weight: 600; margin-bottom: 3px; display: block; }

        /* ── PANEL TOTALES ─────────────────────────────────── */
        .panel-totales {
            background: #EFF6FF; border-radius: var(--radio);
            padding: 14px; margin-bottom: 14px;
        }
        .fila-total {
            display: flex; justify-content: space-between; align-items: center;
            padding: 6px 0; font-size: .9rem; border-bottom: 1px solid var(--azul-lt);
        }
        .fila-total:last-child { border-bottom: none; }
        .fila-total label { color: var(--gris-800); font-weight: 500; }
        .fila-total input {
            width: 140px; text-align: right;
            border: 1px solid var(--gris-200); border-radius: 6px;
            padding: 5px 8px; font-size: .9rem;
        }
        .fila-total.fila-total-final {
            background: var(--azul); border-radius: 8px;
            padding: 10px 12px; margin-top: 8px;
        }
        .fila-total.fila-total-final label { color: #fff; font-weight: 700; font-size: 1rem; }
        .fila-total.fila-total-final input {
            background: transparent; border: 2px solid rgba(255,255,255,.5);
            color: #fff; font-weight: 700; font-size: 1.1rem; width: 160px;
        }

        /* ══════════════════════════════════════════════════════
           ZONA DE CARGA — ESTADOS
        ══════════════════════════════════════════════════════ */

        /* Estado 0: sin archivo */
        #stateEmpty { text-align: center; padding: 28px 16px; }
        #stateEmpty i { font-size: 2.8rem; color: var(--azul); margin-bottom: 10px; display: block; }
        #stateEmpty p { color: var(--gris-600); font-size: .9rem; margin: 0; }

        /* Estado 1: archivo cargado, esperando análisis */
        #stateReady {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 16px; background: var(--azul-lt);
            border-radius: var(--radio); border: 1px solid #93C5FD;
        }
        #stateReady .file-icon { font-size: 2rem; color: var(--azul); flex-shrink: 0; }
        #stateReady .file-info { flex: 1; min-width: 0; }
        #stateReady .file-name { font-weight: 700; color: var(--gris-800); font-size: .9rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #stateReady .file-size { font-size: .75rem; color: var(--gris-600); margin-top: 2px; }

        /* Estado 2: analizando */
        #stateProcessing {
            text-align: center; padding: 22px 16px;
            color: var(--azul); font-weight: 600; font-size: .95rem;
        }
        #stateProcessing .paso-txt { font-size: .8rem; color: var(--gris-600); margin-top: 6px; }

        /* Estado 3: error de análisis */
        #stateError { padding: 14px 16px; }

        /* ── BOTÓN ANALIZAR ────────────────────────────────── */
        .btn-analizar {
            display: flex; align-items: center; justify-content: center; gap: 9px;
            width: 100%; padding: 13px 20px; margin-top: 14px;
            background: var(--azul); color: #fff;
            border: none; border-radius: var(--radio);
            font-size: 1rem; font-weight: 700; cursor: pointer;
            transition: background .18s, transform .12s;
        }
        .btn-analizar:hover  { background: var(--azul-dk); }
        .btn-analizar:active { transform: scale(.98); }
        .btn-analizar i { font-size: 1.1rem; }

        /* ── BOTÓN CAMBIAR ARCHIVO ─────────────────────────── */
        .btn-cambiar {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 13px; border: 1px solid var(--gris-200);
            border-radius: 7px; background: #fff; color: var(--gris-600);
            font-size: .8rem; font-weight: 600; cursor: pointer;
            transition: all .15s; flex-shrink: 0;
        }
        .btn-cambiar:hover { border-color: var(--azul); color: var(--azul); }

        /* ── BOTÓN REINTENTAR ──────────────────────────────── */
        .btn-retry {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; background: var(--rojo); color: #fff;
            border: none; border-radius: 8px; font-weight: 700;
            font-size: .85rem; cursor: pointer; margin-top: 10px;
            transition: background .15s;
        }
        .btn-retry:hover { background: #B91C1C; }

        /* ── DROP ZONE ─────────────────────────────────────── */
        .drop-zone {
            border: 2px dashed var(--gris-200); border-radius: var(--radio);
            cursor: pointer; transition: all .2s; position: relative; overflow: hidden;
        }
        .drop-zone:hover, .drop-zone.drag-over {
            border-color: var(--azul); background: var(--azul-lt);
        }
        .drop-zone input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }

        /* ── PROGRESO ANÁLISIS ─────────────────────────────── */
        .progress-steps {
            display: flex; gap: 8px; margin-top: 10px; justify-content: center;
        }
        .step {
            display: flex; align-items: center; gap: 5px;
            font-size: .75rem; color: var(--gris-400); font-weight: 500;
        }
        .step.activo  { color: var(--azul); }
        .step.hecho   { color: var(--verde); }
        .step-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--gris-200); flex-shrink: 0;
        }
        .step.activo .step-dot  { background: var(--azul); animation: pulse 1s ease-in-out infinite; }
        .step.hecho  .step-dot  { background: var(--verde); }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        /* ── BADGE MODO ─────────────────────────────────────── */
        .badge-modo {
            display: inline-block; padding: 2px 9px; border-radius: 20px;
            font-size: .68rem; font-weight: 700;
            background: var(--verde-lt); color: #065F46; margin-left: 8px;
        }

        /* ── ESTADO PROCESANDO ─────────────────────────────── */
        #processingState {
            text-align: center; padding: 20px;
            color: var(--azul); font-weight: 600; font-size: .95rem;
        }

        /* Responsive */
        @media(max-width:480px) {
            .row-2 { flex-direction: column; }
            #stateReady { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<?php include '../menu.php'; ?>

<div class="main-container">

    <!-- ══ VISOR DESKTOP ══════════════════════════════════════ -->
    <div class="image-viewer" id="desktopViewer">
        <div class="empty-state" id="placeholderState">
            <i class="fas fa-file-invoice fa-3x"></i>
            <p>Aquí verás el comprobante<br>cuando lo cargues.</p>
        </div>
        <canvas id="pdfCanvas" class="hidden"></canvas>
        <img id="imgPreview" class="hidden" alt="Comprobante">
    </div>

    <div class="form-container">

        <!-- ══ SECCIÓN 1: SUBIR ══════════════════════════════ -->
        <div class="card">
            <div class="section-header">
                <div class="section-number">1</div>
                <div class="section-title">Subir Comprobante</div>
            </div>

            <!-- ESTADO 0: Sin archivo -->
            <div class="drop-zone" id="stateEmpty">
                <i class="fas fa-camera fa-2x" style="color:var(--azul);margin-bottom:10px"></i>
                <div style="font-weight:700;font-size:1.05rem;color:var(--gris-800)">
                    Tocá o arrastrá para subir
                </div>
                <div style="color:var(--gris-600);font-size:.85rem;margin-top:5px">
                    Foto · PDF · Ticket · Factura A/B/C · Combustible
                </div>
                <input type="file" id="fileInput" accept="image/*,application/pdf"
                       onchange="onFileSelected(this)">
            </div>

            <!-- ESTADO 1: Archivo listo para analizar -->
            <div id="stateReady" class="hidden">
                <i class="fas fa-file-alt file-icon"></i>
                <div class="file-info">
                    <div class="file-name" id="readyFileName">archivo.jpg</div>
                    <div class="file-size" id="readyFileSize">— KB</div>
                </div>
                <button class="btn-cambiar" onclick="resetFile()">
                    <i class="fas fa-times"></i> Cambiar
                </button>
            </div>

            <!-- Botón analizar (visible en estado 1) -->
            <button class="btn-analizar hidden" id="btnAnalizar" onclick="ejecutarAnalisis()">
                <i class="fas fa-magic"></i>
                Analizar con IA
            </button>

            <!-- ESTADO 2: Procesando -->
            <div id="stateProcessing" class="hidden">
                <div class="loader"></div>&nbsp; <span id="procesoMsg">Analizando comprobante…</span>
                <div class="progress-steps" id="progressSteps">
                    <div class="step" id="step1">
                        <div class="step-dot"></div> Subiendo archivo
                    </div>
                    <div class="step" id="step2">
                        <div class="step-dot"></div> Reconocimiento OCR
                    </div>
                    <div class="step" id="step3">
                        <div class="step-dot"></div> Estructurando datos
                    </div>
                </div>
            </div>

            <!-- ESTADO 3: Error de análisis -->
            <div id="stateError" class="hidden">
                <div class="alert alert-danger" style="margin:0">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Error al analizar</strong>
                        <div id="errorMsg" style="margin-top:4px;font-size:.85rem"></div>
                    </div>
                </div>
                <button class="btn-retry" onclick="ejecutarAnalisis()">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
                <button class="btn-cambiar" style="margin-left:8px;margin-top:10px" onclick="resetFile()">
                    Cambiar archivo
                </button>
            </div>
        </div>

        <!-- ══ FORMULARIO PRINCIPAL ══════════════════════════ -->
        <form id="formGuardar" class="hidden">
            <input type="hidden" id="rutaTemporal"   name="ruta_temporal">
            <input type="hidden" id="textoOCR"       name="texto_ocr">
            <input type="hidden" id="archivoNombre"  name="archivo_nombre">

            <!-- Alertas -->
            <div id="alertaNitidez" class="alert alert-danger hidden">
                <i class="fas fa-exclamation-circle"></i>
                <span><strong>Imagen poco nítida:</strong> No se pudieron leer todos los datos.
                Completá el formulario manualmente antes de guardar.</span>
            </div>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>
                    <strong>Revisá los datos</strong>: extraídos automáticamente con IA.
                    <span id="badgeModo"></span><br>
                    <b>Verificá y corregí</b> cualquier error antes de guardar.
                </span>
            </div>

            <!-- ── SECCIÓN 2: IDENTIFICACIÓN ─────────────────── -->
            <div class="card">
                <div class="section-header">
                    <div class="section-number">2</div>
                    <div class="section-title">Identificación del Comprobante</div>
                </div>

                <div class="row-2">
                    <div class="input-group">
                        <label class="label-main">Tipo</label>
                        <select name="tipo_comprobante" id="tipo_comprobante">
                            <option value="Factura A">Factura A</option>
                            <option value="Factura B" selected>Factura B</option>
                            <option value="Factura C">Factura C</option>
                            <option value="Factura M">Factura M</option>
                            <option value="Ticket">Ticket Fiscal</option>
                            <option value="Ticket Factura A">Ticket Factura A</option>
                            <option value="Ticket Factura B">Ticket Factura B</option>
                            <option value="Ticket Factura C">Ticket Factura C</option>
                            <option value="Nota de Crédito A">NC A</option>
                            <option value="Nota de Crédito B">NC B</option>
                            <option value="Nota de Débito A">ND A</option>
                            <option value="Nota de Débito B">ND B</option>
                            <option value="Recibo">Recibo</option>
                            <option value="Extracto bancario">Extracto Bancario</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="label-main">Moneda</label>
                        <select name="moneda" id="moneda">
                            <option value="ARS" selected>ARS - Pesos</option>
                            <option value="USD">USD - Dólares</option>
                            <option value="EUR">EUR - Euros</option>
                        </select>
                    </div>
                </div>

                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">Fecha Emisión</label>
                        <input type="date" name="fecha_emision" id="fecha_emision">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">Vto. CAE</label>
                        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento">
                    </div>
                </div>

                <div class="row-2">
                    <div class="input-group" style="flex:1">
                        <label class="label-sub">Punto de Venta</label>
                        <input type="number" name="punto_venta" id="punto_venta" placeholder="0001">
                    </div>
                    <div class="input-group" style="flex:2">
                        <label class="label-sub">Número</label>
                        <input type="number" name="numero_comprobante" id="numero_comprobante" placeholder="00037412">
                    </div>
                </div>

                <div class="input-group">
                    <label class="label-sub">CAE (Código Autorización Electrónica)</label>
                    <input type="text" name="cae" id="cae" placeholder="14 dígitos">
                </div>

                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">Método de Pago</label>
                        <select name="metodo_pago" id="metodo_pago">
                            <option value="">— Seleccionar —</option>
                            <option value="Efectivo">Efectivo / Contado</option>
                            <option value="Tarjeta">Tarjeta</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Mercado Pago">Mercado Pago</option>
                            <option value="Cuenta Corriente">Cuenta Corriente</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="label-sub">Condición IVA Emisor</label>
                        <select name="emisor_condicion_iva" id="emisor_condicion_iva">
                            <option value="">— Seleccionar —</option>
                            <option value="Responsable Inscripto">Resp. Inscripto</option>
                            <option value="Monotributista">Monotributista</option>
                            <option value="Exento">Exento</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 3: EMISOR ──────────────────────────── -->
            <div class="card">
                <div class="section-header">
                    <div class="section-number">3</div>
                    <div class="section-title">Datos del Emisor (Vendedor)</div>
                </div>
                <div class="input-group">
                    <label class="label-main">Razón Social / Nombre</label>
                    <input type="text" name="emisor_nombre" id="emisor_nombre"
                           placeholder="Ej: Operadora de Estaciones de Servicios S.A.">
                </div>
                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">CUIT</label>
                        <input type="tel" name="emisor_cuit" id="emisor_cuit" placeholder="Sin guiones · 11 dígitos">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">IIBB</label>
                        <input type="text" name="emisor_iibb" id="emisor_iibb" placeholder="Nro Ing. Brutos">
                    </div>
                </div>
                <div class="input-group">
                    <label class="label-sub">Dirección</label>
                    <input type="text" name="emisor_direccion" id="emisor_direccion"
                           placeholder="Calle, Número, Localidad">
                </div>
            </div>

            <!-- ── SECCIÓN 4: RECEPTOR ────────────────────────── -->
            <div class="card">
                <div class="section-header">
                    <div class="section-number">4</div>
                    <div class="section-title">Datos del Receptor (Comprador)</div>
                </div>
                <div class="input-group">
                    <label class="label-main">Razón Social / Nombre</label>
                    <input type="text" name="cliente_nombre" id="cliente_nombre" placeholder="Ej: DILDIY S.A.">
                </div>
                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">CUIT</label>
                        <input type="tel" name="cliente_cuit" id="cliente_cuit" placeholder="Sin guiones">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">Condición IVA</label>
                        <select name="cliente_condicion_iva" id="cliente_condicion_iva">
                            <option value="">— Seleccionar —</option>
                            <option value="Responsable Inscripto">Resp. Inscripto</option>
                            <option value="Consumidor Final">Consumidor Final</option>
                            <option value="Monotributista">Monotributista</option>
                            <option value="Exento">Exento</option>
                        </select>
                    </div>
                </div>
                <div class="input-group">
                    <label class="label-sub">Dirección</label>
                    <input type="text" name="cliente_direccion" id="cliente_direccion"
                           placeholder="Dirección del comprador">
                </div>
            </div>

            <!-- ── SECCIÓN 5: IMPORTES ────────────────────────── -->
            <div class="card" style="border:2px solid var(--azul)">
                <div class="section-header">
                    <div class="section-number">5</div>
                    <div class="section-title">Importes y Totales</div>
                </div>

                <p class="label-sub" style="text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px">
                    Base imponible
                </p>
                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">Subtotal Bruto</label>
                        <input type="number" step="0.01" name="subtotal_bruto" id="subtotal_bruto" placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">Descuentos (–)</label>
                        <input type="number" step="0.01" name="descuentos" id="descuentos" placeholder="0.00">
                    </div>
                </div>
                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">Subtotal Neto (sin IVA)</label>
                        <input type="number" step="0.01" name="subtotal_neto" id="subtotal_neto" placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">No Gravado</label>
                        <input type="number" step="0.01" name="no_gravado" id="no_gravado" placeholder="0.00">
                    </div>
                </div>
                <div class="input-group">
                    <label class="label-sub">Exento</label>
                    <input type="number" step="0.01" name="exento" id="exento" placeholder="0.00"
                           style="max-width:48%">
                </div>

                <hr class="section-divider">
                <p class="label-sub" style="text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px">IVA</p>

                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">Alícuota principal</label>
                        <select name="iva_porcentaje" id="iva_porcentaje">
                            <option value="">—</option>
                            <option value="21">21%</option>
                            <option value="10.5">10.5%</option>
                            <option value="27">27%</option>
                            <option value="0">Exento / 0%</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="label-sub">IVA Total ($)</label>
                        <input type="number" step="0.01" name="iva_importe" id="iva_importe" placeholder="0.00">
                    </div>
                </div>
                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">IVA 21% ($)</label>
                        <input type="number" step="0.01" name="iva_21" id="iva_21" placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">IVA 10.5% ($)</label>
                        <input type="number" step="0.01" name="iva_10_5" id="iva_10_5" placeholder="0.00">
                    </div>
                </div>
                <div class="input-group">
                    <label class="label-sub">IVA 27% ($)</label>
                    <input type="number" step="0.01" name="iva_27" id="iva_27" placeholder="0.00"
                           style="max-width:48%">
                </div>

                <hr class="section-divider">
                <p class="label-sub" style="text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px">
                    Otros impuestos y tasas
                </p>

                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">IIBB / Ing. Brutos ($)</label>
                        <input type="number" step="0.01" name="iibb_importe" id="iibb_importe" placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">Imp. Internos ($)</label>
                        <input type="number" step="0.01" name="imp_internos" id="imp_internos" placeholder="0.00">
                    </div>
                </div>
                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">ITC / Comb. Líquido ($)</label>
                        <input type="number" step="0.01" name="imp_combustible_liq" id="imp_combustible_liq"
                               placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">Dióxido de Carbono ($)</label>
                        <input type="number" step="0.01" name="imp_dioxido_carbono" id="imp_dioxido_carbono"
                               placeholder="0.00">
                    </div>
                </div>
                <div class="row-2">
                    <div class="input-group">
                        <label class="label-sub">Tasas ($)</label>
                        <input type="number" step="0.01" name="tasas" id="tasas" placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label class="label-sub">Total Otros Impuestos ($)</label>
                        <input type="number" step="0.01" name="otros_impuestos" id="otros_impuestos" placeholder="0.00">
                    </div>
                </div>

                <hr class="section-divider">

                <!-- Panel totales -->
                <div class="panel-totales">
                    <div class="fila-total">
                        <label>Total sin IVA</label>
                        <input type="number" step="0.01" name="total_sin_iva" id="total_sin_iva" placeholder="0.00">
                    </div>
                    <div class="fila-total">
                        <label>IVA</label>
                        <input type="number" step="0.01" name="iva_importe_panel" id="iva_importe_panel"
                               placeholder="0.00" readonly tabindex="-1" style="background:#f3f4f6">
                    </div>
                    <div class="fila-total">
                        <label>Otros impuestos</label>
                        <input type="number" step="0.01" name="otros_panel" id="otros_panel"
                               placeholder="0.00" readonly tabindex="-1" style="background:#f3f4f6">
                    </div>
                    <div class="fila-total fila-total-final">
                        <label><i class="fas fa-dollar-sign"></i> TOTAL FINAL</label>
                        <input type="number" step="0.01" name="total" id="total" placeholder="0.00">
                    </div>
                </div>

                <button type="button" onclick="guardarEnBD()" class="btn-main">
                    <i class="fas fa-check"></i> Guardar Comprobante
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ FAB ════════════════════════════════════════════════ -->
<div id="fabPreview" class="fab-preview" onclick="openMobileModal()">
    <div class="fab-label">Ver Comprobante</div>
    <i class="fas fa-eye"></i>
</div>

<!-- ══ MODAL MOBILE ═══════════════════════════════════════ -->
<div id="mobileModal" class="mobile-modal">
    <div class="modal-header">
        <span>Visor de Comprobante</span>
        <button onclick="closeMobileModal()"
                style="background:none;border:none;color:#fff;font-size:1.4rem;">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="modal-body" id="modalContent"></div>
</div>

<script>
// ════════════════════════════════════════════════════════════
//  ESTADO GLOBAL
// ════════════════════════════════════════════════════════════
let archivoActual   = null;   // File object
let yaAnalizado     = false;  // evitar doble envío

// ════════════════════════════════════════════════════════════
//  DRAG & DROP sobre la zona de carga
// ════════════════════════════════════════════════════════════
const dropZone = document.getElementById('stateEmpty');
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const files = e.dataTransfer?.files;
    if (files?.length) {
        document.getElementById('fileInput').files = files;
        onFileSelected(document.getElementById('fileInput'));
    }
});

// ════════════════════════════════════════════════════════════
//  SELECCIÓN DE ARCHIVO → mostrar preview + botón analizar
// ════════════════════════════════════════════════════════════
async function onFileSelected(input) {
    if (!input.files?.[0]) return;
    const file = input.files[0];

    // Validar tipo
    const tiposOK = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
    if (!tiposOK.includes(file.type)) {
        mostrarError('Tipo de archivo no permitido. Usá JPG, PNG, WEBP o PDF.');
        return;
    }
    // Validar tamaño (20 MB)
    if (file.size > 20 * 1024 * 1024) {
        mostrarError('El archivo supera el límite de 20 MB.');
        return;
    }

    archivoActual = file;
    yaAnalizado   = false;

    // Mostrar nombre y tamaño
    document.getElementById('readyFileName').textContent = file.name;
    document.getElementById('readyFileSize').textContent = formatBytes(file.size);

    // Cambiar a estado "ready"
    setEstado('ready');

    // Mostrar preview
    document.getElementById('placeholderState').classList.add('hidden');
    const imgP = document.getElementById('imgPreview');
    const canv = document.getElementById('pdfCanvas');
    imgP.classList.add('hidden');
    canv.classList.add('hidden');

    if (file.type === 'application/pdf') {
        canv.classList.remove('hidden');
        await renderPDF(file, 'pdfCanvas');
    } else {
        imgP.classList.remove('hidden');
        imgP.src = URL.createObjectURL(file);
    }
    document.getElementById('fabPreview').classList.add('visible');
}

// ════════════════════════════════════════════════════════════
//  EJECUTAR ANÁLISIS IA
// ════════════════════════════════════════════════════════════
async function ejecutarAnalisis() {
    if (!archivoActual) { alert('Seleccioná un archivo primero.'); return; }
    if (yaAnalizado) return;

    setEstado('processing');
    setStep(1);

    const formData = new FormData();
    formData.append('file', archivoActual);

    try {
        // Simular progreso de pasos
        await delay(600);  setStep(2);   // OCR
        await delay(800);  setStep(3);   // Estructurando

        const ctrl = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), 240000);  // 4 min timeout

        const resp = await fetch('analizar.php', {
            method: 'POST',
            body:   formData,
            signal: ctrl.signal,
        });
        clearTimeout(timer);

        if (!resp.ok) {
            const txt = await resp.text().catch(() => '');
            let msg = `El servidor respondió con error HTTP ${resp.status}.`;
            try { const j = JSON.parse(txt); msg = j.message || msg; } catch {}
            throw new Error(msg);
        }

        const json = await resp.json();

        if (!json.success) {
            throw new Error(json.message || 'El servidor devolvió un error desconocido.');
        }

        yaAnalizado = true;
        rellenarFormulario(json);
        setEstado('hidden');   // ocultar toda la zona de carga

    } catch (err) {
        if (err.name === 'AbortError') {
            mostrarError('El análisis tardó demasiado (>4 min). Intentá con una imagen más pequeña.');
        } else {
            mostrarError(err.message);
        }
    }
}

// ════════════════════════════════════════════════════════════
//  RELLENAR FORMULARIO CON DATOS DE LA IA
// ════════════════════════════════════════════════════════════
function rellenarFormulario(json) {
    const d = json.data || {};

    // Mostrar badge de modo
    if (json.modo) {
        document.getElementById('badgeModo').innerHTML =
            `<span class="badge-modo"><i class="fas fa-robot"></i> ${esc(json.modo)}</span>`;
    }

    // Alerta nitidez
    document.getElementById('alertaNitidez').classList.toggle('hidden', !json.ilegible);

    // Campos hidden
    document.getElementById('rutaTemporal').value  = d.temp_path     || '';
    document.getElementById('textoOCR').value      = d.raw_text       || '';
    document.getElementById('archivoNombre').value = d.original_name  || '';

    // Mapeo campo → id del formulario
    const campos = {
        tipo_comprobante:     'tipo_comprobante',
        moneda:               'moneda',
        fecha_emision:        'fecha_emision',
        fecha_vencimiento:    'fecha_vencimiento',
        punto_venta:          'punto_venta',
        numero_comprobante:   'numero_comprobante',
        cae:                  'cae',
        metodo_pago:          'metodo_pago',
        emisor_condicion_iva: 'emisor_condicion_iva',
        emisor_nombre:        'emisor_nombre',
        emisor_cuit:          'emisor_cuit',
        emisor_iibb:          'emisor_iibb',
        emisor_direccion:     'emisor_direccion',
        cliente_nombre:       'cliente_nombre',
        cliente_cuit:         'cliente_cuit',
        cliente_condicion_iva:'cliente_condicion_iva',
        cliente_direccion:    'cliente_direccion',
        subtotal_bruto:       'subtotal_bruto',
        descuentos:           'descuentos',
        subtotal_neto:        'subtotal_neto',
        no_gravado:           'no_gravado',
        exento:               'exento',
        iva_porcentaje:       'iva_porcentaje',
        iva_importe:          'iva_importe',
        iva_21:               'iva_21',
        iva_10_5:             'iva_10_5',
        iva_27:               'iva_27',
        iibb_importe:         'iibb_importe',
        imp_internos:         'imp_internos',
        imp_combustible_liq:  'imp_combustible_liq',
        imp_dioxido_carbono:  'imp_dioxido_carbono',
        tasas:                'tasas',
        otros_impuestos:      'otros_impuestos',
        total_sin_iva:        'total_sin_iva',
        total:                'total',
    };

    let camposRellenos = 0;
    for (const [key, id] of Object.entries(campos)) {
        const el  = document.getElementById(id);
        if (!el) continue;
        const val = d[key];
        if (val !== null && val !== undefined && val !== '') {
            el.value = val;
            el.classList.add('campo-ok');
            camposRellenos++;
        }
    }

    // Panel resumen
    const ivaPanel   = document.getElementById('iva_importe_panel');
    const otrosPanel = document.getElementById('otros_panel');
    if (ivaPanel   && d.iva_importe)     ivaPanel.value   = d.iva_importe;
    if (otrosPanel && d.otros_impuestos) otrosPanel.value = d.otros_impuestos;

    document.getElementById('formGuardar').classList.remove('hidden');
    document.getElementById('formGuardar').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ════════════════════════════════════════════════════════════
//  GUARDAR EN BASE DE DATOS
// ════════════════════════════════════════════════════════════
async function guardarEnBD() {
    const form   = document.getElementById('formGuardar');
    const btnG   = form.querySelector('.btn-main');
    const data   = {};

    form.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.name) data[el.name] = el.value || null;
    });

    // Aliases que espera guardar.php
    data.temp_path     = data.ruta_temporal  || null;
    data.original_name = data.archivo_nombre || null;
    data.raw_text      = data.texto_ocr      || null;

    // Validación mínima
    if (!data.total && !data.emisor_nombre) {
        alert('Por favor completá al menos el emisor y el total antes de guardar.');
        return;
    }

    const textoOrig = btnG.innerHTML;
    btnG.disabled   = true;
    btnG.innerHTML  = '<span class="loader"></span> Guardando…';

    try {
        const resp = await fetch('guardar.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data),
        });
        const json = await resp.json();

        if (json.success) {
            mostrarToast(`✅ Comprobante guardado correctamente (ID: ${json.id})`, 'ok');
            resetAll();
        } else {
            throw new Error(json.message || 'Error desconocido al guardar.');
        }
    } catch (err) {
        mostrarToast('❌ Error al guardar: ' + err.message, 'error');
        btnG.disabled  = false;
        btnG.innerHTML = textoOrig;
    }
}

// ════════════════════════════════════════════════════════════
//  HELPERS DE ESTADO
// ════════════════════════════════════════════════════════════
function setEstado(estado) {
    // Ocultar todo
    ['stateEmpty','stateReady','stateProcessing','stateError','btnAnalizar']
        .forEach(id => document.getElementById(id)?.classList.add('hidden'));

    if (estado === 'empty') {
        document.getElementById('stateEmpty').classList.remove('hidden');
    } else if (estado === 'ready') {
        document.getElementById('stateReady').classList.remove('hidden');
        document.getElementById('btnAnalizar').classList.remove('hidden');
    } else if (estado === 'processing') {
        document.getElementById('stateProcessing').classList.remove('hidden');
    } else if (estado === 'error') {
        document.getElementById('stateError').classList.remove('hidden');
        document.getElementById('stateReady').classList.remove('hidden');  // mantener info del archivo
    }
    // 'hidden' → todo oculto (formulario visible)
}

function setStep(n) {
    ['step1','step2','step3'].forEach((id, i) => {
        const el = document.getElementById(id);
        el.className = 'step' + (i + 1 < n ? ' hecho' : i + 1 === n ? ' activo' : '');
    });
    const msgs = ['', 'Subiendo archivo…', 'Reconociendo texto con OCR…', 'Estructurando datos…'];
    document.getElementById('procesoMsg').textContent = msgs[n] || '';
}

function mostrarError(msg) {
    document.getElementById('errorMsg').textContent = msg;
    setEstado('error');
}

function resetFile() {
    archivoActual = null;
    yaAnalizado   = false;
    document.getElementById('fileInput').value = '';
    setEstado('empty');
}

function resetAll() {
    resetFile();
    document.getElementById('formGuardar').classList.add('hidden');
    document.getElementById('formGuardar').querySelectorAll('.campo-ok')
        .forEach(el => el.classList.remove('campo-ok'));
    document.getElementById('placeholderState').classList.remove('hidden');
    document.getElementById('imgPreview').classList.add('hidden');
    document.getElementById('pdfCanvas').classList.add('hidden');
    document.getElementById('fabPreview').classList.remove('visible');
    document.getElementById('badgeModo').innerHTML = '';
}

// ════════════════════════════════════════════════════════════
//  TOAST
// ════════════════════════════════════════════════════════════
function mostrarToast(msg, tipo = 'info') {
    const t = document.createElement('div');
    const isOk = tipo === 'ok';
    t.style.cssText = `
        position:fixed;bottom:24px;right:24px;z-index:9999;
        padding:14px 18px;border-radius:10px;font-size:.88rem;font-weight:600;
        max-width:380px;box-shadow:0 4px 20px rgba(0,0,0,.18);
        background:${isOk ? '#D1FAE5' : '#FEE2E2'};
        color:${isOk ? '#065F46' : '#991B1B'};
        border-left:4px solid ${isOk ? '#059669' : '#DC2626'};
        animation:slideIn .25s ease;
    `;
    t.innerHTML = `<style>@keyframes slideIn{from{transform:translateX(20px);opacity:0}to{transform:translateX(0);opacity:1}}</style>${msg}`;
    document.body.appendChild(t);
    setTimeout(() => t.style.opacity = '0', 4500);
    setTimeout(() => t.remove(), 5000);
}

// ════════════════════════════════════════════════════════════
//  RENDER PDF
// ════════════════════════════════════════════════════════════
async function renderPDF(file, canvasId) {
    try {
        const pdf  = await pdfjsLib.getDocument(URL.createObjectURL(file)).promise;
        const page = await pdf.getPage(1);
        const vp   = page.getViewport({ scale: 1.5 });
        const c    = document.getElementById(canvasId);
        c.height   = vp.height;
        c.width    = vp.width;
        await page.render({ canvasContext: c.getContext('2d'), viewport: vp }).promise;
    } catch (e) {
        console.warn('PDF render error:', e);
    }
}

// ════════════════════════════════════════════════════════════
//  MODAL MOBILE
// ════════════════════════════════════════════════════════════
function openMobileModal() {
    const modal = document.getElementById('mobileModal');
    const cont  = document.getElementById('modalContent');
    cont.innerHTML = '';
    const img  = document.getElementById('imgPreview');
    const canv = document.getElementById('pdfCanvas');
    if (!img.classList.contains('hidden')) {
        const n = img.cloneNode(true); n.style.display = 'block'; cont.appendChild(n);
    } else if (!canv.classList.contains('hidden')) {
        cont.appendChild(canv);
    }
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeMobileModal() {
    const desk = document.getElementById('desktopViewer');
    const cont = document.getElementById('modalContent');
    while (cont.childNodes.length) desk.appendChild(cont.childNodes[0]);
    document.getElementById('mobileModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// ════════════════════════════════════════════════════════════
//  UTILS
// ════════════════════════════════════════════════════════════
const delay      = ms => new Promise(r => setTimeout(r, ms));
const formatBytes = b => b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : (b / 1024).toFixed(0) + ' KB';
const esc        = s => String(s ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
</script>
</body>
</html>
<?php ob_end_flush(); ?>