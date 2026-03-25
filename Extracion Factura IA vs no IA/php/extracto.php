<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Extractos Bancarios — Motor IA</title>
<meta name="description" content="Extractor profesional de extractos bancarios argentinos con inteligencia artificial">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/menu.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --pri:#6366F1;--pri-dk:#4338CA;--pri-lt:#EEF2FF;
  --verde:#059669;--verde-lt:#D1FAE5;
  --rojo:#DC2626;--rojo-lt:#FEE2E2;
  --naranja:#D97706;--naranja-lt:#FEF3C7;
  --g50:#F9FAFB;--g100:#F3F4F6;--g200:#E5E7EB;--g300:#D1D5DB;
  --g400:#9CA3AF;--g500:#6B7280;--g600:#4B5563;--g700:#374151;--g800:#1F2937;--g900:#111827;
  --rad:14px;--sh:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.06);
  --sh-lg:0 10px 30px rgba(0,0,0,.12);
  --sh-xl:0 20px 50px rgba(0,0,0,.18);
}
body{font-family:'Inter',system-ui,sans-serif;background:linear-gradient(135deg,#0F172A 0%,#1E293B 50%,#0F172A 100%);color:var(--g800);min-height:100vh}
.pagina{max-width:1420px;margin:0 auto;padding:24px 16px 100px}

/* ══ HEADER HERO ══ */
.hero{text-align:center;padding:40px 20px 30px;color:#fff}
.hero h1{font-size:2.2rem;font-weight:900;background:linear-gradient(135deg,#818CF8,#6366F1,#A78BFA);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px;letter-spacing:-0.5px}
.hero p{font-size:.92rem;color:#94A3B8;max-width:600px;margin:0 auto;line-height:1.6}
.hero .badges{display:flex;gap:8px;justify-content:center;margin-top:14px;flex-wrap:wrap}
.hero .badge{padding:4px 14px;border-radius:20px;font-size:.72rem;font-weight:600;background:rgba(99,102,241,.15);color:#A5B4FC;border:1px solid rgba(99,102,241,.25)}

/* ══ UPLOAD CARD ══ */
.upload-card{background:rgba(255,255,255,.03);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.08);border-radius:var(--rad);padding:32px 28px 24px;margin-bottom:28px;transition:all .3s}
.upload-card:hover{border-color:rgba(99,102,241,.3);box-shadow:0 0 40px rgba(99,102,241,.08)}

.drop-zone{border:2px dashed rgba(255,255,255,.12);border-radius:var(--rad);padding:40px 24px;background:rgba(255,255,255,.02);cursor:pointer;transition:all .3s;display:flex;flex-direction:column;align-items:center;gap:12px;width:100%}
.drop-zone:hover,.drop-zone.drag-over{border-color:var(--pri);background:rgba(99,102,241,.06);transform:translateY(-2px);box-shadow:0 8px 30px rgba(99,102,241,.12)}
.drop-zone i{font-size:3rem;color:var(--pri);opacity:.8}
.drop-zone strong{font-size:1.05rem;color:#E2E8F0;font-weight:700}
.drop-zone small{font-size:.78rem;color:#64748B;text-align:center;line-height:1.6}
#fileInput{display:none}

/* ══ COLA ══ */
.cola-wrap{margin-top:22px;display:none}
.cola-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px}
.cola-top h3{font-size:.92rem;font-weight:700;color:#E2E8F0;display:flex;align-items:center;gap:8px}
.cola-lista{display:flex;flex-direction:column;gap:6px}

.item-cola{display:flex;align-items:center;gap:10px;padding:10px 16px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;font-size:.84rem;transition:all .2s;color:#CBD5E1}
.item-cola.procesando{background:rgba(217,119,6,.08);border-color:rgba(217,119,6,.3)}
.item-cola.listo{background:rgba(5,150,105,.08);border-color:rgba(5,150,105,.3)}
.item-cola.error{background:rgba(220,38,38,.08);border-color:rgba(220,38,38,.3)}

.qi-icono{font-size:1.2rem;color:var(--pri);flex-shrink:0}
.qi-nombre{flex:1;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px}
.qi-peso{font-size:.72rem;color:#64748B;white-space:nowrap}
.qi-badge{font-size:.71rem;font-weight:700;padding:3px 12px;border-radius:12px;white-space:nowrap}
.qi-badge.pendiente{background:rgba(255,255,255,.06);color:#94A3B8}
.qi-badge.procesando{background:rgba(217,119,6,.15);color:#F59E0B}
.qi-badge.listo{background:rgba(5,150,105,.15);color:#34D399}
.qi-badge.error{background:rgba(220,38,38,.15);color:#F87171}
.qi-quitar{background:none;border:none;color:#64748B;cursor:pointer;padding:4px 8px;border-radius:6px;transition:color .15s}
.qi-quitar:hover{color:#F87171}

.barra-wrap{height:4px;background:rgba(255,255,255,.06);border-radius:4px;margin-top:16px;overflow:hidden;display:none}
.barra{height:100%;background:linear-gradient(90deg,var(--pri),#A78BFA);border-radius:4px;width:0;transition:width .4s ease}

.progreso-multi{background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:10px 16px;font-size:.82rem;color:#A5B4FC;display:none;align-items:center;gap:10px;margin-top:10px}

/* ══ BOTONES ══ */
.btn{padding:10px 24px;border:none;border-radius:10px;font-weight:700;font-size:.86rem;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .2s;white-space:nowrap;font-family:inherit}
.btn:disabled{opacity:.4;cursor:not-allowed}
.btn-pri{background:linear-gradient(135deg,var(--pri),var(--pri-dk));color:#fff;box-shadow:0 4px 12px rgba(99,102,241,.3)}
.btn-pri:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 6px 20px rgba(99,102,241,.4)}
.btn-verde{background:var(--verde);color:#fff}
.btn-verde:hover:not(:disabled){background:#047857}
.btn-mora{background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;box-shadow:0 4px 12px rgba(124,58,237,.3)}
.btn-mora:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 6px 20px rgba(124,58,237,.4)}

/* ══ RESULTADOS ══ */
#resultadosWrap{display:none}
.tabs-row{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.tab-btn{padding:8px 18px;border:1px solid rgba(255,255,255,.1);border-radius:20px;background:rgba(255,255,255,.04);font-size:.81rem;font-weight:600;cursor:pointer;color:#94A3B8;transition:all .2s;display:flex;align-items:center;gap:6px;font-family:inherit}
.tab-btn:hover{border-color:var(--pri);color:#C7D2FE}
.tab-btn.activo{background:var(--pri);border-color:var(--pri);color:#fff;box-shadow:0 4px 12px rgba(99,102,241,.3)}
.tab-badge{padding:1px 8px;border-radius:10px;font-size:.69rem;background:rgba(255,255,255,.2)}
.tab-btn:not(.activo) .tab-badge{background:rgba(255,255,255,.06)}

/* ══ BANCO HEADER ══ */
.banco-header{background:linear-gradient(130deg,#1E293B 0%,#334155 50%,#1E293B 100%);border-radius:var(--rad);padding:24px 28px;margin-bottom:16px;color:#fff;display:flex;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:var(--sh-lg);border:1px solid rgba(255,255,255,.06)}
.bh-icono{width:56px;height:56px;background:linear-gradient(135deg,var(--pri),#A78BFA);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;box-shadow:0 4px 12px rgba(99,102,241,.3)}
.bh-datos h2{font-size:1.3rem;font-weight:800;letter-spacing:-0.3px}
.bh-datos p{font-size:.82rem;color:#94A3B8;margin-top:4px}
.bh-chip{display:inline-block;padding:3px 12px;border-radius:20px;font-size:.7rem;font-weight:700;background:rgba(99,102,241,.15);color:#A5B4FC;margin-left:8px}
.bh-archivo{margin-left:auto;text-align:right;font-size:.78rem;color:#64748B;flex-shrink:0}
.bh-motor{font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:8px;background:rgba(99,102,241,.15);color:#A5B4FC;margin-top:4px;display:inline-block}

/* ══ STATS ══ */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px}
.stat{background:rgba(255,255,255,.03);border-radius:var(--rad);padding:18px 20px;border:1px solid rgba(255,255,255,.06);border-left:4px solid var(--pri);transition:all .2s}
.stat:hover{background:rgba(255,255,255,.05);transform:translateY(-1px)}
.stat.verde{border-left-color:var(--verde)}
.stat.rojo{border-left-color:var(--rojo)}
.stat.gris{border-left-color:var(--g500)}
.stat label{font-size:.67rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px}
.stat .valor{font-size:1.2rem;font-weight:800;color:#E2E8F0}
.valor.pos{color:#34D399} .valor.neg{color:#F87171}

/* ══ SECTION CARD ══ */
.sc{background:rgba(255,255,255,.03);border-radius:var(--rad);border:1px solid rgba(255,255,255,.06);margin-bottom:16px;overflow:hidden}
.sc-head{padding:14px 20px;background:rgba(255,255,255,.02);border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:8px;font-weight:700;color:#C7D2FE;font-size:.9rem}
.sc-head i{color:var(--pri);width:18px;text-align:center}
.sc-head .badge-n{margin-left:auto;font-size:.74rem;font-weight:500;color:#64748B}
.sc-body{padding:18px}

.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
.info-item label{font-size:.66rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:3px}
.info-item .iv{font-size:.86rem;font-weight:600;color:#E2E8F0;word-break:break-all}

/* ══ FILTROS ══ */
.filtros{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:12px 20px;border-bottom:1px solid rgba(255,255,255,.04)}
.filtros input,.filtros select{padding:7px 12px;border:1px solid rgba(255,255,255,.1);border-radius:8px;font-size:.81rem;background:rgba(255,255,255,.04);color:#E2E8F0;outline:none;transition:all .15s;font-family:inherit}
.filtros input:focus,.filtros select:focus{border-color:var(--pri);background:rgba(99,102,241,.06)}
.filtros select option{background:#1E293B;color:#E2E8F0}

/* ══ TABLA ══ */
.tabla-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.79rem}
thead tr{background:rgba(99,102,241,.1)}
thead th{padding:10px 14px;text-align:left;font-weight:600;white-space:nowrap;font-size:.75rem;color:#A5B4FC;text-transform:uppercase;letter-spacing:.04em}
thead th.r{text-align:right}
tbody tr{border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s}
tbody tr:hover{background:rgba(99,102,241,.04)}
tbody td{padding:8px 14px;vertical-align:middle;color:#CBD5E1}
tbody td.r{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.td-desc{font-weight:500;color:#E2E8F0}
.td-comp{font-size:.7rem;color:#64748B;margin-top:2px}
.monto-d{color:#F87171;font-weight:700}
.monto-c{color:#34D399;font-weight:700}
.monto-s{color:#93C5FD;font-weight:600}
.chip{display:inline-block;padding:2px 10px;border-radius:10px;font-size:.68rem;font-weight:700}
.chip-c{background:rgba(5,150,105,.12);color:#34D399}
.chip-d{background:rgba(220,38,38,.12);color:#F87171}
.chip-i{background:rgba(255,255,255,.06);color:#94A3B8}
.fila-vacia td{text-align:center;padding:30px;color:#64748B;font-style:italic}

/* ══ PAGINACIÓN ══ */
.paginacion{display:flex;justify-content:flex-end;align-items:center;gap:4px;padding:10px 20px;border-top:1px solid rgba(255,255,255,.04);font-size:.78rem}
.paginacion span{color:#64748B;margin-right:8px}
.paginacion button{padding:5px 11px;border:1px solid rgba(255,255,255,.1);border-radius:6px;background:rgba(255,255,255,.04);cursor:pointer;font-size:.78rem;transition:all .15s;font-family:inherit;color:#CBD5E1}
.paginacion button:disabled{opacity:.25;cursor:not-allowed}
.paginacion button.activo{background:var(--pri);color:#fff;border-color:var(--pri)}

/* ══ TABLA IMPUESTOS ══ */
.tax-table{width:100%;border-collapse:collapse;font-size:.84rem}
.tax-table td{padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.04);color:#CBD5E1}
.tax-table td:last-child{text-align:right;font-weight:700;color:#A5B4FC}
.tax-table tr:last-child td{border-bottom:none}
.tax-table tr:hover td{background:rgba(99,102,241,.04)}

.acciones{display:flex;gap:10px;justify-content:flex-end;margin-top:6px;flex-wrap:wrap}

@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.pulse{animation:pulse 2s infinite}

@media(max-width:640px){
  .stats-grid{grid-template-columns:1fr 1fr}
  .info-grid{grid-template-columns:1fr 1fr}
  .drop-zone{min-width:0;padding:24px 16px}
  .cola-top{flex-direction:column;align-items:flex-start}
  .hero h1{font-size:1.6rem}
}
</style>
</head>
<body>
<?php @include '../menu.php'; ?>

<div class="pagina">

<!-- HERO -->
<div class="hero">
  <h1><i class="fas fa-brain" style="margin-right:8px"></i>Extractor de Extractos Bancarios</h1>
  <p>Motor de inteligencia artificial v15 con chunking paralelo. Precisión máxima en débitos/créditos. Sin límite de páginas.</p>
  <div class="badges">
    <span class="badge"><i class="fas fa-bolt"></i> GPT-4o-mini</span>
    <span class="badge"><i class="fas fa-infinity"></i> Sin límite de hojas</span>
    <span class="badge"><i class="fas fa-shield-halved"></i> 20+ bancos</span>
    <span class="badge"><i class="fas fa-gauge-high"></i> Chunking paralelo</span>
  </div>
</div>

<!-- UPLOAD -->
<div class="upload-card" id="uploadCard">
  <div class="drop-zone" id="dropZone">
    <i class="fas fa-cloud-arrow-up"></i>
    <strong>Arrastrá archivos aquí o hacé clic para seleccionar</strong>
    <small>PDF digital · JPG · PNG · WEBP<br>
      PDFs con múltiples períodos se procesan automáticamente.<br>
      Compatible con: Nación · Galicia · Santander · Patagonia · BBVA · HSBC · Macro · MP y más</small>
  </div>
  <input type="file" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.webp">

  <div class="cola-wrap" id="colaWrap">
    <div class="cola-top">
      <h3><i class="fas fa-layer-group" style="color:var(--pri)"></i>Archivos en cola <span id="colaContador"></span></h3>
      <button class="btn btn-pri" id="btnProcesar" onclick="procesarCola()">
        <i class="fas fa-play"></i> Analizar todos
      </button>
    </div>
    <div class="cola-lista" id="colaLista"></div>
    <div class="progreso-multi" id="progresoMulti">
      <i class="fas fa-spinner fa-spin"></i>
      <span id="progresoMultiTxt">Procesando períodos...</span>
    </div>
    <div class="barra-wrap" id="barraWrap"><div class="barra" id="barra"></div></div>
  </div>
</div>

<!-- RESULTADOS -->
<div id="resultadosWrap">
  <div class="tabs-row" id="tabsRow"></div>
  <div id="tabContenido"></div>
</div>

</div>

<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

/**
 * Extrae texto de PDF con DETECCIÓN DE COLUMNAS POR COORDENADAS.
 * 
 * En vez de dejar que la IA adivine qué columna es cada monto,
 * detectamos las posiciones X de las columnas DEBITOS, CREDITOS y SALDO
 * desde la fila de cabecera, y etiquetamos explícitamente cada monto.
 * 
 * Output format para montos:
 *   "10/02/25 IMP.DB/CR BANCARIOS P/DEBITOS  [DEBITO:3.659,51]  [SALDO:11.337.503,48]"
 */
async function extraerTextoPDFjs(file){
  const buf=await file.arrayBuffer();
  const pdf=await pdfjsLib.getDocument({data:buf}).promise;
  let out='';
  
  // Columnas detectadas (persistentes entre páginas)
  let colDebito=null, colCredito=null, colSaldo=null;
  
  for(let p=1;p<=pdf.numPages;p++){
    const page=await pdf.getPage(p);
    const content=await page.getTextContent();
    
    // Agrupar por Y con tolerancia de 3px
    const filas=new Map();
    for(const item of content.items){
      if(!item.str||item.str.trim()==='') continue;
      const y=Math.round(item.transform[5]);
      let key=null;
      for(const k of filas.keys()){if(Math.abs(k-y)<=3){key=k;break;}}
      if(key===null){filas.set(y,[]);key=y;}
      filas.get(key).push({
        x:Math.round(item.transform[4]),
        txt:item.str,
        width:item.width||0
      });
    }
    
    // Ordenar filas de arriba a abajo
    const sorted=[...filas.entries()].sort((a,b)=>b[0]-a[0]);
    
    for(const [y,items] of sorted){
      items.sort((a,b)=>a.x-b.x);
      const fullText=items.map(it=>it.txt).join(' ').toUpperCase();
      
      // ══ DETECTAR CABECERA DE COLUMNAS ══
      // Buscar fila que contenga "DEBITO" y "CREDITO" (headers de la tabla)
      if((fullText.includes('DEBITO') && fullText.includes('CREDITO')) ||
         (fullText.includes('DÉBITO') && fullText.includes('CRÉDITO'))){
        for(const it of items){
          const u=it.txt.toUpperCase().trim();
          if(u.match(/^D[EÉ]BITO[S]?$/)) colDebito=it.x;
          if(u.match(/^CR[EÉ]DITO[S]?$/)) colCredito=it.x;
          if(u.match(/^SALDO[S]?$/) || u.match(/^SALDO\s+EN\s+CUENTA$/)) colSaldo=it.x;
        }
        // If saldo not found yet, try "Saldo en cuenta" as multi-word across items
        if(colSaldo===null && fullText.includes('SALDO')){
          for(const it of items){
            if(it.txt.toUpperCase().trim()==='SALDO'){
              colSaldo=it.x;
              break;
            }
          }
        }
        // Detectar si crédito y débito comparten la misma columna (formato con signo, ej: Galicia)
        if(colDebito!==null && colCredito!==null && Math.abs(colDebito-colCredito)<30){
          colDebito=Math.min(colDebito,colCredito);
          colCredito=colDebito;
        }
        let linea='';let prevEndX=0;
        for(const it of items){
          const gap=it.x-prevEndX;
          if(prevEndX>0){if(gap>15)linea+='\t';else if(gap>3)linea+=' ';}
          linea+=it.txt;
          prevEndX=it.x+(it.width>0?it.width:it.txt.length*5.5);
        }
        out+=linea.trim()+'\n';
        continue;
      }
      
      // ══ CONSTRUIR LÍNEA CON ETIQUETAS DE COLUMNA ══
      let linea='';let prevEndX=0;
      for(const it of items){
        const gap=it.x-prevEndX;
        if(prevEndX>0){if(gap>15)linea+='\t';else if(gap>3)linea+=' ';}
        
        // ¿Es un monto? Incluye negativos con - y formatos como 1.234,56
        const txtClean=it.txt.trim().replace(/^\$\s*/,'').replace(/^-\$\s*/,'-');
        const esMonto=/^-?\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})$/.test(txtClean);
        
        if(esMonto && colDebito!==null && colCredito!==null){
          const distD=Math.abs(it.x-colDebito);
          const distC=Math.abs(it.x-colCredito);
          const distS=colSaldo!==null?Math.abs(it.x-colSaldo):9999;
          const TH=60;
          
          // Si débito y crédito comparten columna (formato con signo)
          if(colDebito===colCredito){
            if(distS<TH && distS<=distD){
              linea+='[SALDO:'+txtClean+']';
            }else if(distD<TH){
              // Clasificar por signo: negativo=débito, positivo=crédito
              if(txtClean.startsWith('-')){
                linea+='[DEBITO:'+txtClean.substring(1)+']';
              }else{
                linea+='[CREDITO:'+txtClean+']';
              }
            }else if(distS<TH){
              linea+='[SALDO:'+txtClean+']';
            }else{
              linea+=it.txt;
            }
          }else{
            // Columnas separadas (Patagonia, Nación, Galicia, etc.)
            // Strip minus from amounts since the tag identifies the type
            const absVal=txtClean.startsWith('-')?txtClean.substring(1):txtClean;
            if(distS<TH && distS<=distD && distS<=distC){
              linea+='[SALDO:'+absVal+']';
            }else if(distD<TH && distD<=distC){
              linea+='[DEBITO:'+absVal+']';
            }else if(distC<TH){
              linea+='[CREDITO:'+absVal+']';
            }else if(distS<TH){
              linea+='[SALDO:'+absVal+']';
            }else{
              linea+=it.txt;
            }
          }
        }else{
          linea+=it.txt;
        }
        prevEndX=it.x+(it.width>0?it.width:it.txt.length*5.5);
      }
      out+=linea.trim()+'\n';
    }
  }
  return out;
}

/**
 * Limpieza de ruido universal — elimina encabezados, pies, legales.
 * El backend hace limpieza adicional por banco, aquí va lo genérico.
 */
function limpiarTexto(texto){
  const ruido=[
    // === Genéricos ===
    /^[_\-=]{3,}$/,
    /^P[aá]gina[:\s]*\d/i,
    /^PAGINA[:\s]*\d/i,
    /^HOJA[:\s]*\d/i,
    /^www\./i,
    /^http:\/\//i,
    /^https:\/\//i,
    /^Tel[eé]fono/i,
    /^\d{9,}[A-Z]\d{8}$/,
    /^Ley\s+\d{4,}/i,
    /^De conformidad/i,
    /^Los dep[oó]sitos/i,
    /^Se presumir/i,
    /^Los accionistas/i,
    /^Todos los valores/i,
    /^A partir del/i,
    /^Podes solicitar/i,
    /^PODES CONSULTAR/i,
    /^USTED PUEDE/i,
    /^ESTIMAREMOS/i,
    /^SIN PERJUICIO/i,
    /^POR RAZONES/i,
    /^LAS NORMAS/i,
    /^ARGENTINA SOBRE/i,
    /^LOS COSTOS/i,
    /^Estimado Cliente/i,
    /^Seg[uú]n modalidad/i,
    /^Relacionados/i,
    /^Cajas de Seguridad/i,
    // === Banco Nación ===
    /^BANCO DE LA$/i,
    /^NACION ARGENTINA$/i,
    /^CUIT 30-50001091/i,
    /^SUC:\s*\d+$/i,
    /^CONVENIO COLECTIVO/i,
    /^AV BELGRANO/i,
    /^\d{4}\s+CAP FED$/i,
    /^CAPITAL FEDERAL$/i,
    /^OPERACIONES DEL BANCO/i,
    /^TRANSPORTE\s+[\d.,]+$/i,
    /^SIGUIENTE\s*-+>$/i,
    /^<-+\s*FIN/i,
    /^TA\) DIAS/i,
    /^TRARSE ACREDITADAS/i,
    // === Banco Patagonia ===
    /^Si usted reviste/i,
    /^el monto de IVA/i,
    /^Banco Patagonia S\.A\./i,
    /^CUIT 30-500/i,
    /^\*\s+Patagonia/i,
    // === Banco Galicia ===
    /^Resumen de Cuenta Corriente/i,
    /^Resumen de Cuenta de Ahorro/i,
    /^\d{14,}H$/,
    /^Cantidad de cotitulares/i,
    /^Dispon[eé]s de \d+ d[ií]as/i,
    /^El cr[eé]dito fiscal/i,
    /^Tasa Extraordinaria/i,
    /^Saldos Deudores Promedio/i,
    /^Intereses\s+\$/i,
    /^CUIT del Responsable/i,
    /^IVA:\s*Responsable/i,
    // === Otros ===
    /^Banco de Galicia/i,
    /^Galicia y Buenos Aires/i,
    /^BBVA Argentina/i,
    /^Santander R[ií]o/i,
    /^\d{9}$/,
    // === Santander Río ===
    /^Banco Santander Río/i,
    /^Saldo total \$/i,
    /^Saldo total en cuentas/i,
    /^Centro de atenci[oó]n/i,
    /^Acuerdo de giro/i,
    /^Garant[ií]a de dep[oó]sito/i,
    /^Intercambio de informaci/i,
    /^CUIT\s*:\s*30-500/i,
    /^0810[-\s]/i,
    // === Mercado Pago ===
    /^Mercado Pago S\.?R\.?L/i,
    /^CVU[:\s]*\d/i,
    /^Actividad en tu cuenta/i,
    /^mercadopago\.com/i,
    /^Saldo disponible\s*(al|en)/i,
    /^Tu dinero en/i,
    // === BBVA Francés ===
    /^BBVA Franc[eé]s/i,
    /^Banco Franc[eé]s/i,
    /^RESUMEN DE EMISION MENSUAL/i,
    /^RESUMEN DE CUENTA\s/i,
    /^Referencia BCRA/i,
    /^Referencia de pago/i,
    /^Banca Electr[oó]nica/i,
    /^www\.bbva/i,
    /^Su clave de/i,
    /^Centro de contacto/i,
    /^Si desea realizar/i,
    /^Saldo promedio/i,
    /^TOTAL DE OPERACIONES/i,
    /^Tipo de cambio/i,
    /^Tasa nominal anual/i,
    /^Tasa efectiva anual/i,
    /^Costo financiero total/i,
  ];
  const lineas=texto.split('\n');
  const limpias=[];
  let bc=0;
  for(const l of lineas){
    const t=l.trim();
    if(t===''){bc++;if(bc<=1)limpias.push('');continue;}
    bc=0;
    if(!ruido.some(r=>r.test(t)))limpias.push(l);
  }
  return limpias.join('\n').trim();
}

/**
 * Procesa texto de PDF como un único documento unificado.
 * Ya no divide en períodos — mantiene todo junto para PDFs anuales o multi-mes.
 */
function dividirEnPeriodos(texto,nombre){
  return[{texto:limpiarTexto(texto),label:nombre}];
}
</script>

<script>
/* ══ ESTADO ══ */
const cola=[],resultados=[];
let tabActivo=0,procesando=false;
const POR_PAG=50,pagState={};

/* ══ DRAG & DROP ══ */
const dropZone=document.getElementById('dropZone');
dropZone.addEventListener('dragover',e=>{e.preventDefault();dropZone.classList.add('drag-over');});
dropZone.addEventListener('dragleave',()=>dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop',e=>{e.preventDefault();dropZone.classList.remove('drag-over');agregarArchivos([...e.dataTransfer.files]);});
dropZone.addEventListener('click',()=>document.getElementById('fileInput').click());
document.getElementById('fileInput').addEventListener('change',e=>{agregarArchivos([...e.target.files]);e.target.value='';});

function agregarArchivos(files){
  const ok=['pdf','jpg','jpeg','png','webp'];
  let rej=0;
  files.forEach(f=>{
    const ext=f.name.split('.').pop().toLowerCase();
    if(!ok.includes(ext)){rej++;return;}
    if(f.size>50*1024*1024){alert(`"${f.name}" supera 50 MB.`);return;}
    cola.push({file:f,id:'q_'+Date.now()+'_'+Math.random().toString(36).slice(2,6),estado:'pendiente'});
  });
  if(rej)alert(`${rej} archivo(s) rechazado(s).`);
  renderCola();
}

function renderCola(){
  const w=document.getElementById('colaWrap'),l=document.getElementById('colaLista');
  if(!cola.length){w.style.display='none';return;}
  w.style.display='block';
  document.getElementById('colaContador').textContent=`(${cola.length})`;
  l.innerHTML=cola.map(q=>`
    <div class="item-cola ${q.estado!=='pendiente'?q.estado:''}" id="${q.id}">
      <i class="fas ${q.file.name.toLowerCase().endsWith('.pdf')?'fa-file-pdf':'fa-file-image'} qi-icono"></i>
      <span class="qi-nombre" title="${esc(q.file.name)}">${esc(q.file.name)}</span>
      <span class="qi-peso">${fmtBytes(q.file.size)}</span>
      <span class="qi-badge ${q.estado}">${labelEstado(q.estado)}</span>
      ${q.estado==='pendiente'?`<button class="qi-quitar" onclick="quitarDeCola('${q.id}')"><i class="fas fa-times"></i></button>`:''}
    </div>`).join('');
}
function quitarDeCola(id){const i=cola.findIndex(q=>q.id===id);if(i!==-1&&cola[i].estado==='pendiente')cola.splice(i,1);renderCola();}
function setEstado(id,estado){
  const q=cola.find(q=>q.id===id);if(q)q.estado=estado;
  const el=document.getElementById(id);if(!el)return;
  el.className=`item-cola ${estado!=='pendiente'?estado:''}`;
  el.querySelector('.qi-badge').className=`qi-badge ${estado}`;
  el.querySelector('.qi-badge').textContent=labelEstado(estado);
  const btn=el.querySelector('.qi-quitar');if(btn&&estado!=='pendiente')btn.remove();
}
function setEstadoMsg(id,msg){
  const q=cola.find(q=>q.id===id);if(q)q.estado='procesando';
  const el=document.getElementById(id);if(!el)return;
  el.className='item-cola procesando';
  const b=el.querySelector('.qi-badge');if(b){b.className='qi-badge procesando';b.textContent=msg;}
}

/* ══ PROCESAMIENTO ══ */
async function procesarCola(){
  if(procesando)return;
  const pend=cola.filter(q=>q.estado==='pendiente');
  if(!pend.length){alert('No hay archivos pendientes.');return;}
  procesando=true;
  document.getElementById('btnProcesar').disabled=true;
  document.getElementById('barraWrap').style.display='block';
  const barra=document.getElementById('barra');
  let h=0;
  for(const item of pend){
    setEstado(item.id,'procesando');
    try{await procesarItem(item);setEstado(item.id,'listo');}
    catch(err){setEstado(item.id,'error');console.error(err);mostrarToast(`"${item.file.name}": ${err.message}`,'error');}
    h++;barra.style.width=Math.round(h/pend.length*100)+'%';
  }
  document.getElementById('progresoMulti').style.display='none';
  procesando=false;document.getElementById('btnProcesar').disabled=false;
  if(resultados.length)renderTabs();
}

async function procesarItem(item){
  const esPDF=item.file.name.toLowerCase().endsWith('.pdf');
  if(!esPDF){
    setEstadoMsg(item.id,'Analizando imagen con IA…');
    const fd=new FormData();fd.append('files[]',item.file);
    await enviarFormData(fd,item.id,item.file.name);return;
  }
  setEstadoMsg(item.id,'Extrayendo texto del PDF…');
  let texto='';
  try{texto=await extraerTextoPDFjs(item.file);}catch(e){console.warn('PDF.js falló:',e);}
  
  // Detectar si texto es ilegible (fuentes custom como Galicia)
  const legible=esTextoLegible(texto);
  
  if(!texto||texto.trim().length<80||!legible){
    // MODO IMAGEN: renderizar páginas del PDF como imágenes
    setEstadoMsg(item.id,'PDF con fuentes especiales → modo imagen…');
    try{
      await procesarPDFComoImagenes(item);
    }catch(e){
      // Último fallback: enviar binario
      console.warn('Modo imagen falló, enviando binario:',e);
      setEstadoMsg(item.id,'Enviando PDF binario…');
      const fd=new FormData();fd.append('files[]',item.file);
      await enviarFormData(fd,item.id,item.file.name);
    }
    return;
  }
  // MODO TEXTO: flujo normal con etiquetas de columna
  const periodos=dividirEnPeriodos(texto,item.file.name);
  if(periodos.length>1){
    const pm=document.getElementById('progresoMulti'),pt=document.getElementById('progresoMultiTxt');
    pm.style.display='flex';
    const PARALELO=3;
    let completados=0;
    for(let lote=0;lote<periodos.length;lote+=PARALELO){
      const batch=periodos.slice(lote,lote+PARALELO);
      const promesas=batch.map((per,bi)=>{
        const{texto:t,label}=per;
        pt.textContent=`Períodos ${lote+1}-${Math.min(lote+PARALELO,periodos.length)}/${periodos.length}…`;
        setEstadoMsg(item.id,`Procesando ${completados+1}-${Math.min(completados+batch.length,periodos.length)}/${periodos.length}…`);
        const fd=new FormData();fd.append('texto_extraido',t);fd.append('nombre_archivo',label+' — '+item.file.name);
        return enviarFormData(fd,item.id,label).catch(e=>{console.error(`Error ${label}:`,e);mostrarToast(`${label}: ${e.message}`,'error');});
      });
      await Promise.all(promesas);
      completados+=batch.length;
    }
    pm.style.display='none';
  }else{
    setEstadoMsg(item.id,'Analizando con IA (v15 chunking)…');
    const fd=new FormData();fd.append('texto_extraido',periodos[0].texto);fd.append('nombre_archivo',item.file.name);
    await enviarFormData(fd,item.id,item.file.name);
  }
}

/**
 * Detecta si el texto extraído es legible (¿contiene palabras bancarias reales?)
 * PDFs con fuentes custom (Galicia, etc.) producen texto ilegible tipo "EacTEU"
 */
function esTextoLegible(texto){
  if(!texto||texto.trim().length<50) return false;
  const muestra=texto.substring(0,3000).toUpperCase();
  // Buscar al menos 2 keywords bancarias
  const keywords=['FECHA','SALDO','DEBITO','CREDITO','CUENTA','MOVIMIENTO','PERIODO',
    'TRANSFERENCIA','CONCEPTO','BANCO','CUIT','CBU','CORRIENTE','AHORRO','PESOS',
    'ANTERIOR','ACTUAL','COMISION','IMPUESTO','IVA','GRAVAMEN','TRANSPORTE'];
  let hits=0;
  for(const kw of keywords){if(muestra.includes(kw))hits++;}
  return hits>=2;
}

/**
 * Renderiza páginas del PDF como imágenes y las envía al backend en lotes paralelos.
 * Útil para PDFs con fuentes custom que no permiten extracción de texto.
 */
async function procesarPDFComoImagenes(item){
  const buf=await item.file.arrayBuffer();
  const pdf=await pdfjsLib.getDocument({data:buf}).promise;
  const totalPages=pdf.numPages;
  
  // Renderizar todas las páginas a base64 (escala 1.5 para buena legibilidad sin ser enorme)
  setEstadoMsg(item.id,`Renderizando ${totalPages} páginas…`);
  const imagenes=[];
  const canvas=document.createElement('canvas');
  const ctx=canvas.getContext('2d');
  
  for(let p=1;p<=totalPages;p++){
    const page=await pdf.getPage(p);
    const viewport=page.getViewport({scale:1.5});
    canvas.width=viewport.width;
    canvas.height=viewport.height;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    await page.render({canvasContext:ctx,viewport}).promise;
    // JPEG a 75% calidad para reducir tamaño
    const dataUrl=canvas.toDataURL('image/jpeg',0.75);
    imagenes.push({pagina:p,base64:dataUrl});
  }
  
  // Enviar en lotes de 4 páginas (óptimo para vision API)
  const PAGES_PER_BATCH=4;
  const pm=document.getElementById('progresoMulti'),pt=document.getElementById('progresoMultiTxt');
  pm.style.display='flex';
  
  for(let i=0;i<imagenes.length;i+=PAGES_PER_BATCH){
    const batch=imagenes.slice(i,i+PAGES_PER_BATCH);
    const batchNum=Math.floor(i/PAGES_PER_BATCH)+1;
    const totalBatches=Math.ceil(imagenes.length/PAGES_PER_BATCH);
    pt.textContent=`Lote ${batchNum}/${totalBatches} (págs ${i+1}-${Math.min(i+PAGES_PER_BATCH,totalPages)})…`;
    setEstadoMsg(item.id,`Analizando págs ${i+1}-${Math.min(i+PAGES_PER_BATCH,totalPages)}/${totalPages}…`);
    
    const fd=new FormData();
    fd.append('modo','imagenes');
    fd.append('nombre_archivo',item.file.name);
    fd.append('imagenes_json',JSON.stringify(batch.map(img=>img.base64)));
    fd.append('paginas_info',`${i+1}-${Math.min(i+PAGES_PER_BATCH,totalPages)} de ${totalPages}`);
    
    try{await enviarFormData(fd,item.id,`Págs ${i+1}-${Math.min(i+PAGES_PER_BATCH,totalPages)}`);}
    catch(e){console.error(`Error lote ${batchNum}:`,e);mostrarToast(`Lote ${batchNum}: ${e.message}`,'error');}
  }
  pm.style.display='none';
}

async function enviarFormData(fd,itemId,label){
  const ctrl=new AbortController();
  const timer=setTimeout(()=>ctrl.abort(),600000);
  let resp;
  try{resp=await fetch('analizar_extracto.php',{method:'POST',body:fd,signal:ctrl.signal});}
  catch(e){clearTimeout(timer);throw new Error(e.name==='AbortError'?'Timeout (10min)':'Red: '+e.message);}
  clearTimeout(timer);
  let json;
  const rawText = await resp.text();
  try{json=JSON.parse(rawText);}
  catch(_){throw new Error(`HTTP ${resp.status}: respuesta no válida. ${rawText.substring(0,200)}`);}
  if(resp.status===413)throw new Error(json.message||'Texto demasiado grande.');
  if(!resp.ok)throw new Error(json.message||`HTTP ${resp.status}`);
  if(!json.success)throw new Error(json.message||'Error del servidor');
  let added=0;
  (json.resultados||[]).forEach(r=>{
    if(r.success&&r.data){
      const idx=resultados.length;
      resultados.push({...r.data,_motor:r.motor||r.data.motor||'Reglas'});
      pagState[idx]={pag:1,filtrados:[...(r.data.movimientos||[])]};
      added++;renderTabs();
    }
  });
  const errs=(json.resultados||[]).filter(r=>!r.success).map(r=>r.message).join('; ');
  if(!added&&errs)throw new Error(errs);
}

function mostrarToast(msg,tipo='info'){
  const t=document.createElement('div');
  t.style.cssText=`position:fixed;bottom:24px;right:24px;z-index:9999;padding:14px 20px;border-radius:12px;
    font-size:.84rem;font-weight:600;max-width:420px;box-shadow:0 8px 30px rgba(0,0,0,.3);
    background:${tipo==='error'?'linear-gradient(135deg,#7F1D1D,#991B1B)':'linear-gradient(135deg,#064E3B,#065F46)'};
    color:#fff;border-left:4px solid ${tipo==='error'?'#F87171':'#34D399'}`;
  t.textContent=msg;document.body.appendChild(t);setTimeout(()=>t.remove(),7000);
}

/* ══ TABS ══ */
function renderTabs(){
  if(!resultados.length)return;
  document.getElementById('resultadosWrap').style.display='block';
  document.getElementById('tabsRow').innerHTML=resultados.map((r,i)=>`
    <button class="tab-btn ${i===tabActivo?'activo':''}" onclick="cambiarTab(${i})">
      <i class="fas ${iconoBanco(r.banco)}"></i>${esc(r.banco)}
      <span class="tab-badge">${r.estadisticas.total_movimientos}</span>
    </button>`).join('');
  renderTabContenido(tabActivo);
  if(resultados.length===1)document.getElementById('resultadosWrap').scrollIntoView({behavior:'smooth',block:'start'});
}
function cambiarTab(i){tabActivo=i;document.querySelectorAll('.tab-btn').forEach((b,idx)=>b.classList.toggle('activo',idx===i));renderTabContenido(i);}

function renderTabContenido(i){
  const d=resultados[i];
  document.getElementById('tabContenido').innerHTML=`
    ${htmlBancoHeader(d)}${htmlStats(d)}${htmlCabecera(d.cabecera)}
    ${htmlTablaMovs(d,i)}${htmlImpuestos(d.impuestos)}
    <div class="acciones">
      <button class="btn btn-mora" onclick="guardarBD(${i})"><i class="fas fa-database"></i> Guardar en base de datos</button>
    </div>`;
  aplicarFiltros(i);
}

function htmlBancoHeader(d){
  const c=d.cabecera,per=(c.periodo_desde&&c.periodo_hasta)?` · ${c.periodo_desde} → ${c.periodo_hasta}`:'';
  return `<div class="banco-header">
    <div class="bh-icono"><i class="fas ${iconoBanco(d.banco)}"></i></div>
    <div class="bh-datos"><h2>${esc(d.banco)}<span class="bh-chip">${esc(c.tipo_cuenta||'Cuenta')}</span></h2>
      <p>${esc(c.titular||'')}${c.cuit?' · CUIT '+c.cuit:''}${per}</p>
      <span class="bh-motor" style="background:${d._motor==='IA'?'rgba(217,119,6,.15)':'rgba(5,150,105,.15)'};color:${d._motor==='IA'?'#F59E0B':'#34D399'}"><i class="fas ${d._motor==='IA'?'fa-brain':'fa-bolt'}"></i> ${d._motor==='IA'?'Motor IA (fallback)':'Motor Reglas (instantáneo)'}</span></div>
    <div class="bh-archivo"><div><i class="fas fa-file-alt"></i> ${esc(d.archivo)}</div>
      ${c.sucursal?`<div>Suc. ${esc(c.sucursal)}</div>`:''}</div></div>`;
}

function htmlStats(d){
  const e=d.estadisticas,c=d.cabecera;
  const items=[
    {l:'Saldo Inicial',v:c.saldo_inicial!=null?fmt(c.saldo_inicial):'—',cls:'',col:''},
    {l:'Saldo Final',v:c.saldo_final!=null?fmt(c.saldo_final):'—',cls:'',col:(c.saldo_final??0)>=0?'pos':'neg'},
    {l:'Total Créditos',v:fmt(e.total_creditos),cls:'verde',col:'pos'},
    {l:'Total Débitos',v:fmt(e.total_debitos),cls:'rojo',col:'neg'},
    {l:'Neto Período',v:fmt(e.neto),cls:e.neto>=0?'verde':'rojo',col:e.neto>=0?'pos':'neg'},
    {l:'Movimientos',v:e.total_movimientos,cls:'gris',col:''},
  ];
  return `<div class="stats-grid">${items.map(it=>`
    <div class="stat ${it.cls}"><label>${it.l}</label><div class="valor ${it.col}">${it.v}</div></div>`).join('')}</div>`;
}

function htmlCabecera(c){
  const campos=[['Titular',c.titular],['CUIT',c.cuit],['Tipo Cuenta',c.tipo_cuenta],['N° Cuenta',c.numero_cuenta],
    ['CBU',c.cbu],['Período Desde',c.periodo_desde],['Período Hasta',c.periodo_hasta],
    ['Saldo Inicial',c.saldo_inicial!=null?fmt(c.saldo_inicial):null],['Saldo Final',c.saldo_final!=null?fmt(c.saldo_final):null],
    ['Condición IVA',c.condicion_iva],['Sucursal',c.sucursal],['Moneda',c.moneda]
  ].filter(([,v])=>v!=null&&v!=='');
  return `<div class="sc"><div class="sc-head"><i class="fas fa-id-card"></i> Datos de la cuenta</div>
    <div class="sc-body"><div class="info-grid">${campos.map(([l,v])=>`
      <div class="info-item"><label>${l}</label><div class="iv">${esc(String(v))}</div></div>`).join('')}
    </div></div></div>`;
}

function htmlTablaMovs(d,i){
  return `<div class="sc" id="scMovs_${i}">
    <div class="sc-head"><i class="fas fa-table-list"></i> Movimientos
      <span class="badge-n" id="badgeN_${i}">${d.movimientos.length} registros</span></div>
    <div class="filtros">
      <input type="text" id="fBusc_${i}" placeholder="🔍 Buscar…" oninput="aplicarFiltros(${i})" style="min-width:190px">
      <select id="fTipo_${i}" onchange="aplicarFiltros(${i})">
        <option value="">Todos</option><option value="C">Créditos</option><option value="D">Débitos</option></select>
      <input type="date" id="fDesd_${i}" onchange="aplicarFiltros(${i})" title="Desde">
      <input type="date" id="fHast_${i}" onchange="aplicarFiltros(${i})" title="Hasta">
      <button class="btn btn-verde" onclick="exportarCSV(${i})" style="margin-left:auto"><i class="fas fa-file-csv"></i> CSV</button>
    </div>
    <div class="tabla-wrap"><table><thead><tr>
      <th>Fecha</th><th>Descripción</th><th class="r">Débito</th><th class="r">Crédito</th><th class="r">Saldo</th><th>Tipo</th>
    </tr></thead><tbody id="tbody_${i}"></tbody></table></div>
    <div class="paginacion" id="pag_${i}"></div></div>`;
}

function htmlImpuestos(imp){
  if(!imp||!Object.keys(imp).length)return'';
  const labels={iva_debitos:'IVA Alícuota General',iva_percepcion:'IVA Percepción',
    imp_deb_cred_banc:'Imp. Déb./Cred. Bancario',ret_ley25413_creditos:'Ret. Ley 25.413 s/Créditos',
    ret_ley25413_debitos:'Ret. Ley 25.413 s/Débitos',credito_computable:'Crédito Computable (33%)',
    iibb_tucuman:'IIBB Tucumán',iibb_sircreb:'IIBB SIRCREB',
    retencion_sircreb:'Retención SIRCREB',comision_cuenta:'Comisión Mantenimiento'};
  const rows=Object.entries(imp).filter(([,v])=>v!=null&&v>0)
    .map(([k,v])=>`<tr><td>${labels[k]||k}</td><td>${fmt(v)}</td></tr>`).join('');
  if(!rows)return'';
  return `<div class="sc"><div class="sc-head"><i class="fas fa-receipt"></i> Consolidado Impositivo</div>
    <div style="padding:0"><table class="tax-table">${rows}</table></div></div>`;
}

/* ══ FILTROS & PAGINACIÓN ══ */
function aplicarFiltros(i){
  if(!resultados[i])return;
  const busc=(document.getElementById(`fBusc_${i}`)?.value||'').toLowerCase();
  const tipo=document.getElementById(`fTipo_${i}`)?.value||'';
  const desde=document.getElementById(`fDesd_${i}`)?.value||'';
  const hasta=document.getElementById(`fHast_${i}`)?.value||'';
  pagState[i].filtrados=resultados[i].movimientos.filter(m=>{
    if(busc&&!(m.descripcion||'').toLowerCase().includes(busc))return false;
    if(tipo&&m.tipo!==tipo)return false;
    if(desde&&m.fecha&&m.fecha<desde)return false;
    if(hasta&&m.fecha&&m.fecha>hasta)return false;
    return true;
  });
  pagState[i].pag=1;renderFilas(i);
  const bn=document.getElementById(`badgeN_${i}`);
  if(bn)bn.textContent=pagState[i].filtrados.length+' registros';
}

function renderFilas(i){
  const ps=pagState[i],total=ps.filtrados.length;
  const desde=(ps.pag-1)*POR_PAG,hasta=Math.min(desde+POR_PAG,total);
  const pagina=ps.filtrados.slice(desde,hasta);
  const tbody=document.getElementById(`tbody_${i}`);if(!tbody)return;
  tbody.innerHTML=!pagina.length
    ?'<tr class="fila-vacia"><td colspan="6">Sin resultados con los filtros aplicados.</td></tr>'
    :pagina.map(m=>`<tr>
      <td style="white-space:nowrap;color:#94A3B8">${esc(m.fecha||'—')}</td>
      <td><div class="td-desc">${esc(m.descripcion||'—')}</div>${m.comprobante?`<div class="td-comp">#${esc(m.comprobante)}</div>`:''}</td>
      <td class="r monto-d">${m.debito?fmt(m.debito):'—'}</td>
      <td class="r monto-c">${m.credito?fmt(m.credito):'—'}</td>
      <td class="r monto-s">${m.saldo!=null?(m.saldo<0?`<span style="color:#F87171">-${fmt(Math.abs(m.saldo))}</span>`:fmt(m.saldo)):'<span style="color:#475569">—</span>'}</td>
      <td>${chipTipo(m.tipo)}</td></tr>`).join('');
  const pagEl=document.getElementById(`pag_${i}`),totPag=Math.ceil(total/POR_PAG);
  if(!pagEl||totPag<=1){if(pagEl)pagEl.innerHTML='';return;}
  let html=`<span>${desde+1}–${hasta} de ${total}</span>`;
  html+=`<button onclick="irPag(${i},${ps.pag-1})" ${ps.pag===1?'disabled':''}>‹</button>`;
  for(let p=Math.max(1,ps.pag-2);p<=Math.min(totPag,ps.pag+2);p++)
    html+=`<button class="${p===ps.pag?'activo':''}" onclick="irPag(${i},${p})">${p}</button>`;
  html+=`<button onclick="irPag(${i},${ps.pag+1})" ${ps.pag===totPag?'disabled':''}>›</button>`;
  pagEl.innerHTML=html;
}
function irPag(i,p){const t=Math.ceil(pagState[i].filtrados.length/POR_PAG);if(p<1||p>t)return;pagState[i].pag=p;renderFilas(i);}

/* ══ EXPORTAR CSV ══ */
function exportarCSV(i){
  const movs=pagState[i].filtrados;
  const enc=s=>'"'+String(s??'').replace(/"/g,'""')+'"';
  const head=['Fecha','Descripcion','Comprobante','Debito','Credito','Saldo','Tipo'];
  const rows=movs.map(m=>[m.fecha,m.descripcion,m.comprobante,m.debito,m.credito,m.saldo,
    m.tipo==='C'?'Credito':m.tipo==='D'?'Debito':'Info'].map(enc).join(','));
  const csv=[head.map(enc).join(','),...rows].join('\n');
  const blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8'});
  const url=URL.createObjectURL(blob);
  const a=Object.assign(document.createElement('a'),{href:url,
    download:`extracto_${resultados[i].banco.replace(/\s+/g,'_')}_${resultados[i].cabecera.periodo_hasta||'export'}.csv`});
  a.click();URL.revokeObjectURL(url);
}

/* ══ GUARDAR BD ══ */
async function guardarBD(i){
  const btn=document.querySelector('.btn-mora');if(!btn)return;
  btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Guardando…';
  try{
    const resp=await fetch('guardar_extracto.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(resultados[i])});
    const json=await resp.json();
    if(json.success){btn.innerHTML='<i class="fas fa-check"></i> Guardado';btn.style.background='var(--verde)';
      mostrarToast(`${json.movimientos_guardados} movimientos guardados.`,'ok');
    }else throw new Error(json.message);
  }catch(e){btn.disabled=false;btn.innerHTML='<i class="fas fa-database"></i> Guardar en base de datos';mostrarToast('Error: '+e.message,'error');}
}

/* ══ HELPERS ══ */
const fmt=n=>n==null?'—':new Intl.NumberFormat('es-AR',{style:'currency',currency:'ARS',minimumFractionDigits:2}).format(n);
const fmtBytes=b=>b>1048576?(b/1048576).toFixed(1)+' MB':(b/1024).toFixed(0)+' KB';
const esc=s=>String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const labelEstado=e=>({pendiente:'Pendiente',procesando:'Procesando…',listo:'Listo ✓',error:'Error ✗'}[e]||e);
const chipTipo=t=>t==='C'?'<span class="chip chip-c">Crédito</span>':t==='D'?'<span class="chip chip-d">Débito</span>':'<span class="chip chip-i">Info</span>';
const iconoBanco=b=>({'Banco Patagonia':'fa-fish','Banco Galicia':'fa-landmark','Banco Nación':'fa-flag',
  'Banco Santander':'fa-fire-flame-curved','BBVA':'fa-globe','BBVA Francés':'fa-globe',
  'HSBC':'fa-h','Banco Macro':'fa-building','Banco Ciudad':'fa-city',
  'Banco Supervielle':'fa-s','ICBC':'fa-dragon','Mercado Pago':'fa-wallet',
  'Brubank':'fa-mobile-screen'}[b]||'fa-university');
</script>
</body>
</html>
<?php ob_end_flush(); ?>
