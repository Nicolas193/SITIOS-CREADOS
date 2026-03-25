<?php
// ============================================================
//  CargaFacturas/php/conciliacion_view.php
//  TODA la lógica PHP va AQUÍ ARRIBA, antes de cualquier HTML.
//  menu.php llama auth.php que usa session_start()/header() →
//  debe incluirse antes de emitir cualquier byte de HTML.
// ============================================================

// menu.php está en la misma carpeta php/
require_once __DIR__ . '/../menu.php';

// El controlador ya fue cargado desde conciliacion.php,
// pero por si se llama directamente:
if (!class_exists('DashboardController')) {
    require_once __DIR__ . '/conciliacion_controller.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Conciliación Contable | GestoriaCristianR</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,500;9..40,700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<!-- menu.css está una carpeta arriba: php/../css/ -->
<link rel="stylesheet" href="../css/menu.css">

<style>
/* ── Reset: evitar que Bootstrap o el browser desplacen el menú ── */
html, body {
  margin:  0 !important;
  padding: 0 !important;
}
/* El menú fijo debe quedar pegado al borde superior */
body > nav,
body > header,
body > #menu,
body > .menu,
body > .navbar,
body > .topbar,
body > .sidebar {
  position: fixed !important;
  top:      0     !important;
  left:     0     !important;
  right:    0     !important;
  z-index:  1040  !important;
  /* Asegurar que no haya margin propio que lo baje */
  margin-top: 0   !important;
}

:root {
  --c-bg:        #f0f4f8;
  --c-surface:   #ffffff;
  --c-border:    #e2e8f0;
  --c-primary:   #1e3a5f;
  --c-primary-l: #3b82f6;
  --c-success:   #059669;
  --c-warning:   #d97706;
  --c-danger:    #dc2626;
  --c-text:      #1e293b;
  --c-muted:     #64748b;
  --mono: 'JetBrains Mono', monospace;
  --radius: 12px;
  --shadow: 0 4px 24px rgba(0,0,0,.07);
}
body { background: var(--c-bg); font-family: 'DM Sans', sans-serif; color: var(--c-text); }
.page-wrapper { max-width: 1400px; margin: 80px auto 40px; padding: 0 20px; }

/* ── Header ── */
.page-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:28px; }
.page-header-left { display:flex; align-items:center; gap:16px; }
.page-icon { width:52px; height:52px; background:linear-gradient(135deg,#1e3a5f,#3b82f6); border-radius:14px; display:grid; place-items:center; color:#fff; font-size:22px; box-shadow:0 6px 20px rgba(59,130,246,.35); }
.page-title { font-size:1.6rem; font-weight:700; margin:0; }
.page-subtitle { color:var(--c-muted); font-size:.9rem; margin:3px 0 0; }

/* ── KPI ── */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.kpi-card { background:var(--c-surface); border:1px solid var(--c-border); border-radius:var(--radius); padding:20px 24px; box-shadow:var(--shadow); position:relative; overflow:hidden; }
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.kpi-card.kpi-total::before  { background:var(--c-primary-l); }
.kpi-card.kpi-green::before  { background:var(--c-success); }
.kpi-card.kpi-yellow::before { background:var(--c-warning); }
.kpi-card.kpi-red::before    { background:var(--c-danger); }
.kpi-label { font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; color:var(--c-muted); font-weight:600; }
.kpi-value { font-size:2rem; font-weight:700; line-height:1.2; font-family:var(--mono); margin:6px 0 2px; }
.kpi-sub   { font-size:.8rem; color:var(--c-muted); }
.kpi-card.kpi-green  .kpi-value { color:var(--c-success); }
.kpi-card.kpi-yellow .kpi-value { color:var(--c-warning); }
.kpi-card.kpi-red    .kpi-value { color:var(--c-danger); }
.kpi-card.kpi-total  .kpi-value { color:var(--c-primary); }

/* ── Filtros ── */
.filtros-card { background:var(--c-surface); border:1px solid var(--c-border); border-radius:var(--radius); box-shadow:var(--shadow); padding:20px 24px; margin-bottom:24px; }
.filtros-title { font-size:.82rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--c-muted); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.filtros-row { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.filtro-group { display:flex; flex-direction:column; gap:4px; flex:1; min-width:140px; }
.filtro-group label { font-size:.78rem; font-weight:600; color:var(--c-muted); text-transform:uppercase; letter-spacing:.05em; }
.filtro-group input,
.filtro-group select { padding:9px 12px; border:1.5px solid var(--c-border); border-radius:8px; font-size:.9rem; font-family:'DM Sans',sans-serif; color:var(--c-text); background:#fff; transition:border-color .18s,box-shadow .18s; }
.filtro-group input:focus,
.filtro-group select:focus { outline:none; border-color:var(--c-primary-l); box-shadow:0 0 0 3px rgba(59,130,246,.14); }
.btn-filtrar { padding:9px 20px; background:var(--c-primary); color:#fff; border:none; border-radius:8px; font-weight:600; font-size:.9rem; cursor:pointer; font-family:'DM Sans',sans-serif; transition:background .18s; display:flex; align-items:center; gap:8px; }
.btn-filtrar:hover { background:#1e40af; }
.btn-limpiar { padding:9px 16px; background:transparent; color:var(--c-muted); border:1.5px solid var(--c-border); border-radius:8px; font-size:.9rem; cursor:pointer; font-family:'DM Sans',sans-serif; transition:all .18s; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-limpiar:hover { border-color:#94a3b8; color:var(--c-text); }
.btn-export { padding:9px 18px; background:#059669; color:#fff; border:none; border-radius:8px; font-weight:600; font-size:.9rem; cursor:pointer; font-family:'DM Sans',sans-serif; transition:background .18s; display:flex; align-items:center; gap:8px; text-decoration:none; }
.btn-export:hover { background:#047857; color:#fff; }

/* ── Tabla ── */
.table-card { background:var(--c-surface); border:1px solid var(--c-border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
.table-card-header { padding:16px 24px; border-bottom:1px solid var(--c-border); display:flex; align-items:center; justify-content:space-between; background:#f8fafc; }
.table-card-header h2 { font-size:.95rem; font-weight:700; margin:0; }
.table-count { font-size:.82rem; color:var(--c-muted); font-family:var(--mono); }
table#tbl-conciliacion { width:100% !important; font-size:.82rem; }
table#tbl-conciliacion thead th { background:#f8fafc !important; color:var(--c-muted) !important; font-weight:700 !important; text-transform:uppercase !important; font-size:.72rem !important; letter-spacing:.05em !important; border-bottom:2px solid var(--c-border) !important; white-space:nowrap; padding:10px 12px !important; }
table#tbl-conciliacion tbody td { padding:9px 12px !important; vertical-align:middle !important; border-bottom:1px solid var(--c-border) !important; }
table#tbl-conciliacion tbody tr:hover td { background:#f8fafc !important; }

/* ── Estado vacío (fuera de la tabla) ── */
.empty-state { padding:48px 24px; text-align:center; color:var(--c-muted); }
.empty-state i { font-size:2.4rem; display:block; margin-bottom:12px; opacity:.4; }
.empty-state p  { margin:0; font-size:.92rem; }

/* ── Badges ── */
.badge-estado { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:.72rem; font-weight:700; white-space:nowrap; }
.badge-conciliado { background:#d1fae5; color:#065f46; }
.badge-parcial    { background:#fef3c7; color:#92400e; }
.badge-pendiente  { background:#fee2e2; color:#991b1b; }
.tag-tipo { display:inline-block; padding:2px 8px; border-radius:6px; font-size:.72rem; font-weight:700; font-family:var(--mono); }
.tag-compra { background:#fef9c3; color:#854d0e; }
.tag-venta  { background:#dcfce7; color:#166534; }
.monto { font-family:var(--mono); font-size:.82rem; text-align:right; }
.monto-neg { color:var(--c-danger); }
.match-icon { font-size:.9rem; }
.match-ok  { color:var(--c-success); }
.match-no  { color:#cbd5e1; }

/* ── Paginación ── */
.pag-wrapper { padding:16px 24px; border-top:1px solid var(--c-border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.pag-info  { font-size:.82rem; color:var(--c-muted); }
.pag-links { display:flex; gap:6px; }
.pag-btn { padding:6px 12px; border:1.5px solid var(--c-border); border-radius:7px; font-size:.82rem; background:#fff; color:var(--c-text); text-decoration:none; transition:all .14s; }
.pag-btn:hover   { border-color:var(--c-primary-l); color:var(--c-primary); }
.pag-btn.active  { background:var(--c-primary); color:#fff; border-color:var(--c-primary); }
.pag-btn.disabled{ opacity:.4; pointer-events:none; }

@media(max-width:768px){
  .kpi-grid{ grid-template-columns:1fr 1fr; }
  .filtros-row{ flex-direction:column; }
  .page-header{ flex-direction:column; align-items:flex-start; }
}
</style>
</head>
<body>

<div class="page-wrapper">

  <!-- Header -->
  <div class="page-header">
    <div class="page-header-left">
      <div class="page-icon"><i class="fa-solid fa-scale-balanced"></i></div>
      <div>
        <h1 class="page-title">Conciliación Contable</h1>
        <p class="page-subtitle">Cruce entre AFIP · Comprobantes OCR · Movimientos Bancarios</p>
      </div>
    </div>
    <a href="conciliacion.php?action=export&<?= http_build_query(array_filter($filtros)) ?>"
       class="btn-export" id="btn-export">
      <i class="fa-solid fa-file-excel"></i> Exportar Excel
    </a>
  </div>

  <!-- KPI Cards -->
  <?php
  $totalCompras  = $resumen['compra']['conciliado'] + $resumen['compra']['parcial'] + $resumen['compra']['pendiente'];
  $totalVentas   = $resumen['venta']['conciliado']  + $resumen['venta']['parcial']  + $resumen['venta']['pendiente'];
  $totConciliado = $resumen['compra']['conciliado'] + $resumen['venta']['conciliado'];
  $totParcial    = $resumen['compra']['parcial']    + $resumen['venta']['parcial'];
  $totPendiente  = $resumen['compra']['pendiente']  + $resumen['venta']['pendiente'];
  $grandTotal    = $totalCompras + $totalVentas;
  ?>
  <div class="kpi-grid">
    <div class="kpi-card kpi-total">
      <div class="kpi-label"><i class="fa fa-layer-group"></i> Total Registros</div>
      <div class="kpi-value"><?= number_format($grandTotal) ?></div>
      <div class="kpi-sub"><?= $totalCompras ?> compras · <?= $totalVentas ?> ventas</div>
    </div>
    <div class="kpi-card kpi-green">
      <div class="kpi-label"><i class="fa fa-circle-check"></i> Conciliados</div>
      <div class="kpi-value"><?= number_format($totConciliado) ?></div>
      <div class="kpi-sub"><?= $grandTotal > 0 ? round($totConciliado/$grandTotal*100,1) : 0 ?>% del total</div>
    </div>
    <div class="kpi-card kpi-yellow">
      <div class="kpi-label"><i class="fa fa-circle-half-stroke"></i> Parciales</div>
      <div class="kpi-value"><?= number_format($totParcial) ?></div>
      <div class="kpi-sub">Coincidencia incompleta</div>
    </div>
    <div class="kpi-card kpi-red">
      <div class="kpi-label"><i class="fa fa-circle-xmark"></i> Pendientes</div>
      <div class="kpi-value"><?= number_format($totPendiente) ?></div>
      <div class="kpi-sub">Sin match en ninguna fuente</div>
    </div>
    <div class="kpi-card kpi-total">
      <div class="kpi-label"><i class="fa fa-peso-sign"></i> Total Compras</div>
      <div class="kpi-value" style="font-size:1.3rem;">
        $&nbsp;<?= number_format($resumen['compra']['total_importe'], 0, ',', '.') ?>
      </div>
      <div class="kpi-sub">IVA: $<?= number_format($resumen['compra']['total_iva'], 0, ',', '.') ?></div>
    </div>
    <div class="kpi-card kpi-total">
      <div class="kpi-label"><i class="fa fa-peso-sign"></i> Total Ventas</div>
      <div class="kpi-value" style="font-size:1.3rem;">
        $&nbsp;<?= number_format($resumen['venta']['total_importe'], 0, ',', '.') ?>
      </div>
      <div class="kpi-sub">IVA: $<?= number_format($resumen['venta']['total_iva'], 0, ',', '.') ?></div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="filtros-card">
    <div class="filtros-title"><i class="fa fa-sliders"></i> Filtros</div>
    <form method="GET" action="conciliacion.php">
      <div class="filtros-row">
        <div class="filtro-group">
          <label>Tipo</label>
          <select name="tipo">
            <option value="">Todos</option>
            <option value="compra" <?= $filtros['tipo']==='compra' ? 'selected' : '' ?>>Compras</option>
            <option value="venta"  <?= $filtros['tipo']==='venta'  ? 'selected' : '' ?>>Ventas</option>
          </select>
        </div>
        <div class="filtro-group">
          <label>Estado</label>
          <select name="estado">
            <option value="">Todos</option>
            <option value="conciliado" <?= $filtros['estado']==='conciliado' ? 'selected' : '' ?>>🟢 Conciliado</option>
            <option value="parcial"    <?= $filtros['estado']==='parcial'    ? 'selected' : '' ?>>🟡 Parcial</option>
            <option value="pendiente"  <?= $filtros['estado']==='pendiente'  ? 'selected' : '' ?>>🔴 Pendiente</option>
          </select>
        </div>
        <div class="filtro-group">
          <label>Desde</label>
          <input type="date" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
        </div>
        <div class="filtro-group">
          <label>Hasta</label>
          <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
        </div>
        <div class="filtro-group" style="flex:2;min-width:200px;">
          <label>Razón social / CUIT</label>
          <input type="text" name="busqueda" placeholder="Buscar..."
                 value="<?= htmlspecialchars($filtros['busqueda']) ?>" maxlength="100">
        </div>
        <button type="submit" class="btn-filtrar">
          <i class="fa fa-magnifying-glass"></i> Filtrar
        </button>
        <a href="conciliacion.php" class="btn-limpiar"><i class="fa fa-xmark"></i> Limpiar</a>
      </div>
    </form>
  </div>

  <!-- Tabla -->
  <div class="table-card">
    <div class="table-card-header">
      <h2><i class="fa fa-table" style="color:var(--c-primary-l);margin-right:8px;"></i>Registros</h2>
      <span class="table-count">
        <?= number_format($totalFilas) ?> registros — pág. <?= $pagina ?> de <?= $totalPags ?>
      </span>
    </div>

    <?php if (empty($registros)): ?>
    <!-- FIX: estado vacío FUERA de la tabla para no romper DataTables con colspan -->
    <div class="empty-state">
      <i class="fa fa-inbox"></i>
      <p>No se encontraron registros con los filtros seleccionados.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table id="tbl-conciliacion" class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Fecha</th>
            <th>Comprobante</th>
            <th>CUIT</th>
            <th>Razón Social</th>
            <th style="text-align:right;">Importe</th>
            <th style="text-align:right;">IVA</th>
            <th style="text-align:center;" title="Comprobante OCR">📄 OCR</th>
            <th style="text-align:center;" title="Movimiento Banco">🏦 Banco</th>
            <th>Fecha Banco</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registros as $r):
            $importe    = (float)($r['importe_total'] ?? 0);
            $montoClass = $importe < 0 ? 'monto-neg' : '';
            $compId     = $r['comp_id'] ?? null;
            $movId      = $r['mov_id']  ?? null;
            $estado     = $r['estado_conciliacion'] ?? 'pendiente';
            $tipo       = $r['tipo'] ?? 'compra';
          ?>
          <tr>
            <td>
              <span class="tag-tipo tag-<?= htmlspecialchars($tipo) ?>">
                <?= $tipo === 'compra' ? 'COMPRA' : 'VENTA' ?>
              </span>
            </td>
            <td style="white-space:nowrap;font-family:var(--mono);font-size:.78rem;">
              <?= $r['fecha_emision'] ? date('d/m/Y', strtotime($r['fecha_emision'])) : '—' ?>
            </td>
            <td style="font-family:var(--mono);font-size:.76rem;color:var(--c-muted);">
              <?= htmlspecialchars($r['tipo_comprobante'] ?? '') ?>
              <?php if (!empty($r['punto_venta']) && !empty($r['numero_comprobante'])): ?>
                <br><span style="color:var(--c-text);">
                  <?= str_pad((string)$r['punto_venta'],4,'0',STR_PAD_LEFT) ?>-<?= str_pad((string)$r['numero_comprobante'],8,'0',STR_PAD_LEFT) ?>
                </span>
              <?php endif; ?>
            </td>
            <td style="font-family:var(--mono);font-size:.76rem;">
              <?= htmlspecialchars($r['cuit_contraparte'] ?? '—') ?>
            </td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                title="<?= htmlspecialchars($r['nombre_contraparte'] ?? '') ?>">
              <?= htmlspecialchars(mb_strimwidth($r['nombre_contraparte'] ?? '—', 0, 35, '…')) ?>
            </td>
            <td class="monto <?= $montoClass ?>">
              <?= DashboardController::formatMonto($importe) ?>
            </td>
            <td class="monto" style="color:var(--c-muted);">
              <?= DashboardController::formatMonto((float)($r['total_iva'] ?? 0)) ?>
            </td>
            <td style="text-align:center;">
              <?php if ($compId): ?>
                <span class="match-icon match-ok" title="Comprobante OCR (ID: <?= $compId ?>)"><i class="fa fa-circle-check"></i></span>
              <?php else: ?>
                <span class="match-icon match-no" title="Sin comprobante OCR"><i class="fa fa-circle-xmark"></i></span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <?php if ($movId): ?>
                <span class="match-icon match-ok" title="Movimiento bancario (ID: <?= $movId ?>)"><i class="fa fa-circle-check"></i></span>
              <?php else: ?>
                <span class="match-icon match-no" title="Sin movimiento bancario"><i class="fa fa-circle-xmark"></i></span>
              <?php endif; ?>
            </td>
            <td style="font-family:var(--mono);font-size:.76rem;white-space:nowrap;color:var(--c-muted);">
              <?= $r['mov_fecha'] ? date('d/m/Y', strtotime($r['mov_fecha'])) : '—' ?>
            </td>
            <td><?= DashboardController::estadoBadge($estado) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- Paginación -->
    <?php if ($totalPags > 1): ?>
    <div class="pag-wrapper">
      <span class="pag-info">
        Mostrando <?= number_format(($pagina-1)*50+1) ?>–<?= number_format(min($pagina*50,$totalFilas)) ?>
        de <?= number_format($totalFilas) ?> registros
      </span>
      <div class="pag-links">
        <?php $urlBase = 'conciliacion.php?' . http_build_query(array_filter($filtros)); ?>
        <a href="<?= $urlBase ?>&pagina=1"                          class="pag-btn <?= $pagina<=1        ? 'disabled':'' ?>">«</a>
        <a href="<?= $urlBase ?>&pagina=<?= max(1,$pagina-1) ?>"    class="pag-btn <?= $pagina<=1        ? 'disabled':'' ?>">‹</a>
        <?php for ($p=max(1,$pagina-2); $p<=min($totalPags,$pagina+2); $p++): ?>
          <a href="<?= $urlBase ?>&pagina=<?= $p ?>"
             class="pag-btn <?= $p===$pagina ? 'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a href="<?= $urlBase ?>&pagina=<?= min($totalPags,$pagina+1) ?>" class="pag-btn <?= $pagina>=$totalPags ? 'disabled':'' ?>">›</a>
        <a href="<?= $urlBase ?>&pagina=<?= $totalPags ?>"                class="pag-btn <?= $pagina>=$totalPags ? 'disabled':'' ?>">»</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
  // FIX: solo inicializar DataTables si la tabla existe en el DOM
  // (cuando no hay registros, la tabla no se renderiza)
  if (!$.fn.DataTable || !$('#tbl-conciliacion').length) return;

  $('#tbl-conciliacion').DataTable({
    paging:    false,
    info:      false,
    searching: true,
    language: {
      search:            '',
      searchPlaceholder: 'Buscar en esta página…',
      zeroRecords:       'Sin resultados'
    },
    // FIX: columnas 7 y 8 (OCR y Banco) no son ordenables
    columnDefs: [{ orderable: false, targets: [7, 8] }],
    dom: '<"dt-search-wrapper"f>t',
  });

  $('.dataTables_filter input').css({
    padding:    '8px 12px',
    borderRadius: '8px',
    border:     '1.5px solid #e2e8f0',
    fontFamily: 'DM Sans,sans-serif'
  });
});
</script>
</body>
</html>