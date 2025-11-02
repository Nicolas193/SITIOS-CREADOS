<?php
ob_start();
session_start();

include("../../conexion.php");
include("../../menu.php");

// Verificación de permisos
if (!isset($_SESSION['username']) || !isset($_SESSION['tipo']) || !in_array(strtolower($_SESSION['tipo']), ['administrador', 'gestor'])) {
    header("Location: cartelaccesodenegado.php");
    exit();
}

date_default_timezone_set('America/Argentina/Buenos_Aires');
$mysqli = conectar();

// Consulta tareas (tu consulta original)
$sql = "SELECT 
    rt.id_registro,
    rt.fecha_solicitud,
    rt.plazo_entrega,
    rt.asunto AS descripcion,
    t.nombre_tarea AS tipo_tarea,
    u_responsable.usuario AS responsable,
    u_responsable.cargo AS cargo_responsable,
    u_responsable.sector AS sector_responsable,
    GROUP_CONCAT(DISTINCT CONCAT(u_encargado.usuario, ' (', u_encargado.sector, ')') SEPARATOR ', ') AS encargados,
    e.id_estado,
    e.nombre_estado AS ultimo_estado
FROM registro_de_tareas rt
JOIN tareas t ON t.id_tarea = rt.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
LEFT JOIN estado_tarea et ON et.id_registro = rt.id_registro
    AND et.fecha_actualizacion = (
        SELECT MAX(et2.fecha_actualizacion)
        FROM estado_tarea et2
        WHERE et2.id_registro = rt.id_registro
    )
LEFT JOIN estados e ON e.id_estado = et.id_estado
WHERE e.nombre_estado <> 'Finalizado'  -- <- aquí se excluyen los Finalizados
GROUP BY rt.id_registro, rt.fecha_solicitud, rt.plazo_entrega, rt.asunto, t.nombre_tarea,
         u_responsable.usuario, u_responsable.cargo, u_responsable.sector,
         e.id_estado, e.nombre_estado
ORDER BY rt.fecha_solicitud DESC
";

$resultado = $mysqli->query($sql);


if (!$resultado) die("Error al ejecutar la consulta: " . $mysqli->error);

// Procesar tareas
$tareas = [];
$hoy = new DateTime();

while ($row = $resultado->fetch_assoc()) {
    $start = !empty($row['fecha_solicitud']) ? substr($row['fecha_solicitud'],0,10) : $hoy->format('Y-m-d');
    $end = !empty($row['plazo_entrega']) ? substr($row['plazo_entrega'],0,10) : $start;
    if (strtotime($end) < strtotime($start)) $end = $start;

    $plazo = DateTime::createFromFormat('Y-m-d', $end);
    $dias_restantes = null;
    $plazo_pasado = false;
    if ($plazo) {
        $intervalo = $hoy->diff($plazo);
        $dias_restantes = (int)$intervalo->format('%r%a');
        $plazo_pasado = $dias_restantes < 0;
    }

    // Determinar color por estado y días restantes
    $color_estado = "gray";
    if ($row['id_estado'] == 1) $color_estado = "green";
    elseif (in_array($row['id_estado'], [2,3,4,5,7])) $color_estado = "orange";
    elseif ($row['id_estado'] == 6) $color_estado = "red";

    $color_dias_restantes = $color_estado;
    if ($row['id_estado'] != 1 && $plazo) {
        if ($plazo_pasado || $dias_restantes <= 1) $color_dias_restantes = "red";
        elseif ($dias_restantes >= 2 && $dias_restantes <= 3) $color_dias_restantes = "orange";
        elseif ($dias_restantes >= 4) $color_dias_restantes = "green";
    }

    $barClass = 'g-gris';
    if ($color_dias_restantes === 'red') $barClass = 'g-rojo';
    if ($color_dias_restantes === 'orange') $barClass = 'g-naranja';
    if ($color_dias_restantes === 'green') $barClass = 'g-verde';

    $progress = 0;
    switch($row['id_estado']){
        case 1: $progress = 100; break;
        case 2: $progress = 30; break;
        case 3: $progress = 60; break;
        case 4: $progress = 70; break;
        case 5: $progress = 20; break;
        case 6: $progress = 50; break;
        case 7: $progress = 40; break;
        case 8: $progress = 10; break;
        case 11: $progress = 0; break;
        default: $progress = 0; break;
    }

    // IMPORTANTE: forzamos el id como string para evitar problemas de tipos al comparar
    $tareas[] = [
        "id" => (string)$row['id_registro'],
        "name" => $row['tipo_tarea']." - ".$row['descripcion'],
        "start" => $start,
        "end" => $end,
        "progress" => $progress,
        "responsable" => $row['responsable'],
        "cargo" => $row['cargo_responsable'],
        "sector" => $row['sector_responsable'],
        "encargados" => $row['encargados'],
        "ultimo_estado" => $row['ultimo_estado'] ?: 'Sin estado',
        "dias_restantes" => $dias_restantes,
        "color_dias_restantes" => $color_dias_restantes,
        "color" => $color_dias_restantes,
        "custom_class" => $barClass
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Diagrama de Gantt</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt/dist/frappe-gantt.css">
<link rel="stylesheet" href="../css/gantt.css" />
<style>
/* Tabla más compacta */
.table-container table { font-size: 12px; }
.table-container th, .table-container td { padding: 6px; }

/* Asegurate de esta altura por fila (ajustala si querés otra) */
#tabla-tareas tbody tr { height: 40px; }
</style>
</head>
<body>
<h2>📊 Diagrama de Gantt - Tareas</h2>

<div id="controls">
  <button onclick="cambiarVista('Day')">Día</button>
  <button onclick="cambiarVista('Week')">Semana</button>
  <button onclick="cambiarVista('Month')">Mes</button>
  <button onclick="cambiarVista('Year')">Año</button>
</div>
<br>
<!-- 🔹 Panel de Filtros -->
<div id="filtros" style="margin: 15px 0; padding: 10px; background: #f7f9fc; border: 1px solid #ddd; border-radius: 8px;">
  <label>
    Sector:
    <select id="filtro-sector">
      <option value="">Todos</option>
    </select>
  </label>

  <label>
    Responsable:
    <select id="filtro-responsable">
      <option value="">Todos</option>
    </select>
  </label>

  <label>
    Encargado:
    <select id="filtro-encargado">
      <option value="">Todos</option>
    </select>
  </label>

  <label>
    Estado:
    <select id="filtro-estado">
      <option value="">Todos</option>
    </select>
  </label>

  <label>
    Plazo desde:
    <input type="date" id="filtro-desde">
  </label>

  <label>
    hasta:
    <input type="date" id="filtro-hasta">
  </label>

  <button onclick="aplicarFiltros()">Aplicar</button>
  <button onclick="resetFiltros()">Reset</button>
</div>


<div class="layout">
  <!-- PANEL IZQUIERDO: TABLA -->
  <div class="table-container" id="tabla-tareas">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Tarea</th>
            <th>Responsable</th>
            <th>Sector</th>
            <th>Encargados</th>
            <th>Estado</th>
            <th>Plazo</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tareas as $t): ?>
          <tr data-id="<?= htmlspecialchars($t['id']) ?>" data-color="<?= htmlspecialchars($t['color_dias_restantes']) ?>">
            <td data-tooltip="<?= htmlspecialchars($t['name']) ?>">
                <?= htmlspecialchars($t['name']) ?>
            </td>
            <td data-tooltip="<?= htmlspecialchars($t['responsable']) ?>">
                <?= htmlspecialchars($t['responsable']) ?>
            </td>
            <td data-tooltip="<?= htmlspecialchars($t['sector']) ?>">
                <?= htmlspecialchars($t['sector']) ?>
            </td>
            <td data-tooltip="<?= htmlspecialchars($t['encargados']) ?>">
                <?= htmlspecialchars($t['encargados']) ?>
            </td>
            <!-- Nueva columna Estado -->
            <td data-tooltip="<?= htmlspecialchars($t['ultimo_estado']) ?>" style="color:<?= htmlspecialchars($t['color_dias_restantes']) ?>; font-weight:bold">
                <?= htmlspecialchars($t['ultimo_estado']) ?>
            </td>
            <!-- Nueva columna Plazo -->
            <td data-tooltip="<?= htmlspecialchars(
                "Fecha: {$t['start']} → {$t['end']}\n" .
                ($t['dias_restantes'] !== null 
                    ? ($t['dias_restantes'] < 0 
                        ? "Vencida hace " . abs($t['dias_restantes']) . " días" 
                        : ($t['dias_restantes'] === 0 
                            ? "Vence hoy" 
                            : $t['dias_restantes'] . " días restantes"))
                    : '-')
            ) ?>" style="color:<?= htmlspecialchars($t['color_dias_restantes']) ?>; font-weight:bold" > 
                <small>
                    📅 <?= htmlspecialchars($t['start']) ?> → <?= htmlspecialchars($t['end']) ?><br>
                    <?php 
                    if ($t['dias_restantes'] !== null) {
                        if ($t['dias_restantes'] < 0) echo "⏳ Vencida hace " . abs($t['dias_restantes']) . " días";
                        elseif ($t['dias_restantes'] === 0) echo "⏳ Vence hoy";
                        else echo "⏳ " . $t['dias_restantes'] . " días restantes";
                    } else echo "-";
                    ?>
                </small>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PANEL DERECHO: GANTT -->
  <div id="gantt-container">
    <svg id="gantt"></svg>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/frappe-gantt/dist/frappe-gantt.umd.js"></script>
<script>
/* ---------- Datos desde PHP ---------- */
const tareas = <?= json_encode($tareas, JSON_UNESCAPED_UNICODE); ?>;

/* ---------- Variables globales ---------- */
let ganttInstance = null;
const taskElements = new Map(); // id(string) -> elemento <g> del Gantt
let syncAttempts = 0;
let syncTimeout = null;

/* ---------- Inicializar Gantt y mapear elementos ---------- */
function initGantt(view = 'Year') {
  if (window._gantt_instance && typeof window._gantt_instance.clear === 'function') {
    try { window._gantt_instance.clear(); } catch(e){}
  }

  taskElements.clear();
  syncAttempts = 0;

  ganttInstance = new Gantt('#gantt', tareas, {
    view_mode: view,
    date_format: 'YYYY-MM-DD',
    language: 'es',
    draggable: false,
    custom_popup_html: task => `
      <div style="padding:8px; font-size:13px; line-height:1.4;">
        <strong>${task.name}</strong><br>
        <b>Responsable:</b> ${task.responsable || '-'} (${task.cargo || '-'}, ${task.sector || '-'})<br>
        <b>Encargados:</b> ${task.encargados || '-'}<br>
        <b>Estado:</b> ${task.ultimo_estado || '-'}<br>
        <b>Fecha:</b> ${task.start} → ${task.end}<br>
        <b>Días restantes:</b> ${
          task.dias_restantes !== null
            ? (task.dias_restantes < 0
                ? 'Vencida hace ' + Math.abs(task.dias_restantes) + ' días'
                : (task.dias_restantes === 0 ? 'Vence hoy' : task.dias_restantes + ' días restantes'))
            : '-'
        }
      </div>`,
    on_render: (task, element) => {
      try { taskElements.set(String(task.id), element); }
      catch(e) { console.warn('on_render mapping error', e); }
      scheduleSync(80);
    }
  });

  window._gantt_instance = ganttInstance;
  setTimeout(scheduleSync, 150);
}

/* ---------- Debounce scheduler ---------- */
function scheduleSync(delay = 80) {
  if (syncTimeout) clearTimeout(syncTimeout);
  syncTimeout = setTimeout(syncGanttToTable, delay);
}

/* ---------- Sincronizar Gantt con tabla ---------- */
function syncGanttToTable() {
  try {
    const svg = document.querySelector('#gantt svg');
    if (!svg || !ganttInstance) return retrySync();

    const tableRows = Array.from(document.querySelectorAll('#tabla-tareas tbody tr'));
    if (tableRows.length === 0) return retrySync();

    const rowHeight = Math.round(tableRows[0].getBoundingClientRect().height);

    if (ganttInstance.tasks && taskElements.size < ganttInstance.tasks.length) {
      return retrySync();
    }

    const elems = Array.from(taskElements.entries()).map(([id, el]) => {
      let t = el.getAttribute('transform') || '';
      if (!el.dataset.origTransform) {
        el.dataset.origTransform = t;
        const m = /translate\(\s*([-\d.]+)[,\s]+([-\d.]+)\s*\)/.exec(t);
        el.dataset.origX = m ? Number(m[1]) : 0;
        el.dataset.origY = m ? Number(m[2]) : 0;
      }
      return { id, el, origX: Number(el.dataset.origX), origY: Number(el.dataset.origY) };
    });

    if (elems.length === 0) return;

    const sortedByY = elems.slice().sort((a,b)=> a.origY - b.origY);
    let origRowHeight = sortedByY.length >= 2 ? Math.max(1, sortedByY[1].origY - sortedByY[0].origY) : 44;

    const scale = rowHeight / origRowHeight;

    elems.forEach(({el, origX, origY}) => {
      const newY = Math.round(origY * scale);
      el.setAttribute('transform', `translate(${origX}, ${newY})`);
    });

    const barHeight = Math.max(6, Math.round(rowHeight - 5));
    const barYOffset = Math.round((rowHeight - barHeight) / 2);

    elems.forEach(({el}) => {
      const rect = el.querySelector('rect.bar');
      const progressRect = el.querySelector('rect.progress');
      const text = el.querySelector('text');

      if (rect) {
        rect.setAttribute('height', String(barHeight));
        rect.setAttribute('y', String(barYOffset));
      }
      if (progressRect) {
        progressRect.setAttribute('height', String(barHeight));
        progressRect.setAttribute('y', String(barYOffset));
      }
      if (text) {
        text.setAttribute('y', String(barYOffset + barHeight/2 + 5));
        text.setAttribute('dominant-baseline', 'middle');
      }
    });
  } catch (err) {
    console.warn('syncGanttToTable error', err);
    retrySync();
  }
}

function retrySync() {
  syncAttempts++;
  if (syncAttempts <= 10) {
    setTimeout(scheduleSync, 120);
  } else {
    console.warn('syncGanttToTable: max attempts reached');
  }
}

/* ---------- Helpers ---------- */
function centerScrollOnTaskBar(taskId){
  const cont = document.getElementById('gantt-container');
  if(!cont) return;
  const element = taskElements.get(String(taskId));
  if(!element) return;
  const rect = element.querySelector('rect.bar');
  if(!rect) return;
  requestAnimationFrame(()=>{
    const barRect = rect.getBoundingClientRect();
    const contRect = cont.getBoundingClientRect();
    const offsetInside = (barRect.left - contRect.left) + cont.scrollLeft + (barRect.width / 2);
    cont.scrollTo({ left: Math.max(0, Math.round(offsetInside - cont.clientWidth / 2)), behavior: 'smooth' });
  });
}

function highlightTaskBar(taskId, color){
  const element = taskElements.get(String(taskId));
  if(!element) return;
  const rect = element.querySelector('rect.bar');
  if(!rect) return;
  rect.setAttribute('fill', color);
  rect.setAttribute('stroke', color);
  rect.classList.add('bar-highlight');
  setTimeout(()=> rect.classList.remove('bar-highlight'), 1400);
}

/* ---------- Tabla -> Gantt + redirección ---------- */
function bindTableClick(){
  document.querySelectorAll('#tabla-tareas tbody tr').forEach(row=>{
    row.addEventListener('click', ()=>{
      document.querySelectorAll('#tabla-tareas tbody tr').forEach(r=> r.classList.remove('selected'));
      row.classList.add('selected');
      const id = row.dataset.id;
      if(!id) return;
      highlightTaskBar(id, row.dataset.color || 'gray');
      centerScrollOnTaskBar(id);

      // Redirigir a comentariospendientes.php
      window.location.href = `comentariosmiusuario.php?id=${encodeURIComponent(id)}`;
    });
  });

  // También vinculamos click directo a barras de Gantt
  taskElements.forEach((el, id)=>{
    el.addEventListener('click', ()=>{
      window.location.href = `comentariosmiusuario.php.php?id=${encodeURIComponent(id)}`;
    });
  });
}

/* ---------- Filtros ---------- */
let filtroSector, filtroResponsable, filtroEncargado, filtroEstado, filtroDesde, filtroHasta;

function poblarFiltros() {
  const sectores = [...new Set(tareas.map(t => t.sector).filter(Boolean))];
  const responsables = [...new Set(tareas.map(t => t.responsable).filter(Boolean))];
  const encargados = [...new Set(tareas.map(t => t.encargados).filter(Boolean))];
  const estados = [...new Set(tareas.map(t => t.ultimo_estado).filter(Boolean))];

  sectores.forEach(s => filtroSector.innerHTML += `<option value="${s}">${s}</option>`);
  responsables.forEach(r => filtroResponsable.innerHTML += `<option value="${r}">${r}</option>`);
  encargados.forEach(e => filtroEncargado.innerHTML += `<option value="${e}">${e}</option>`);
  estados.forEach(est => filtroEstado.innerHTML += `<option value="${est}">${est}</option>`);
}

function aplicarFiltros() {
  const sectorVal = filtroSector.value;
  const respVal = filtroResponsable.value;
  const encVal = filtroEncargado.value;
  const estadoVal = filtroEstado.value;
  const desdeVal = filtroDesde.value;
  const hastaVal = filtroHasta.value;

  const filtradas = tareas.filter(t => {
    let ok = true;
    if (sectorVal && t.sector !== sectorVal) ok = false;
    if (respVal && t.responsable !== respVal) ok = false;
    if (encVal && (!t.encargados || !t.encargados.includes(encVal))) ok = false;
    if (estadoVal && t.ultimo_estado !== estadoVal) ok = false;
    if (desdeVal && t.end < desdeVal) ok = false;
    if (hastaVal && t.start > hastaVal) ok = false;
    return ok;
  });

  // Redibujar tabla
  const tbody = document.querySelector('#tabla-tareas tbody');
  tbody.innerHTML = '';
  filtradas.forEach(t => {
    tbody.innerHTML += `
      <tr data-id="${t.id}" data-color="${t.color_dias_restantes}">
        <td data-tooltip="${t.name}">${t.name}</td>
        <td data-tooltip="${t.responsable}">${t.responsable}</td>
        <td data-tooltip="${t.sector}">${t.sector}</td>
        <td data-tooltip="${t.encargados}">${t.encargados}</td>
        <td data-tooltip="${t.ultimo_estado}" style="color:${t.color_dias_restantes}; font-weight:bold">${t.ultimo_estado}</td>
<td data-tooltip="${
  'Fecha: ' + t.start + ' → ' + t.end + '\n' +
  (t.dias_restantes !== null 
    ? (t.dias_restantes < 0 
        ? 'Vencida hace ' + Math.abs(t.dias_restantes) + ' días'
        : (t.dias_restantes === 0 
            ? 'Vence hoy'
            : t.dias_restantes + ' días restantes'))
    : '-')
}" style="color:${t.color_dias_restantes}; font-weight:bold">
  <small>
    📅 ${t.start} → ${t.end}<br>
    ${
      t.dias_restantes !== null 
        ? (t.dias_restantes < 0 
            ? "⏳ Vencida hace " + Math.abs(t.dias_restantes) + " días"
            : (t.dias_restantes === 0 
                ? "⏳ Vence hoy" 
                : "⏳ " + t.dias_restantes + " días restantes"))
        : "-"
    }
  </small>
</td>


      </tr>`;
  });

  bindTableClick();
  if (ganttInstance) {
    ganttInstance.refresh(filtradas);
    setTimeout(scheduleSync, 200);
  }
}

function resetFiltros() {
  filtroSector.value = '';
  filtroResponsable.value = '';
  filtroEncargado.value = '';
  filtroEstado.value = '';
  filtroDesde.value = '';
  filtroHasta.value = '';
  aplicarFiltros();
}

/* ---------- Cambiar vista (botones) ---------- */
function cambiarVista(modo){
  if(!ganttInstance) return;
  ganttInstance.change_view_mode(modo);
  setTimeout(scheduleSync, 160);
}

/* ---------- Tooltip flotante ---------- */
const tooltip = document.createElement('div');
tooltip.style.position = 'fixed';
tooltip.style.background = '#2c3e50';
tooltip.style.color = '#fff';
tooltip.style.padding = '6px 10px';
tooltip.style.borderRadius = '6px';
tooltip.style.fontSize = '12px';
tooltip.style.lineHeight = '1.3';
tooltip.style.pointerEvents = 'none';
tooltip.style.whiteSpace = 'pre-line';
tooltip.style.boxShadow = '0 2px 8px rgba(0,0,0,0.25)';
tooltip.style.zIndex = 10000;
tooltip.style.display = 'none';
document.body.appendChild(tooltip);

document.addEventListener('mouseover', e => {
  const td = e.target.closest('td[data-tooltip]');
  if (td) {
    tooltip.textContent = td.dataset.tooltip || '';
    tooltip.style.display = 'block';
  }
});
document.addEventListener('mousemove', e => {
  if (tooltip.style.display === 'block') {
    tooltip.style.left = (e.clientX + 10) + 'px';
    tooltip.style.top = (e.clientY + 10) + 'px';
  }
});
document.addEventListener('mouseout', e => {
  if (e.target.closest && e.target.closest('td[data-tooltip]')) {
    tooltip.style.display = 'none';
  }
});

/* ---------- Exponer funciones a los botones inline ---------- */
window.cambiarVista = cambiarVista;
window.aplicarFiltros = aplicarFiltros;
window.resetFiltros = resetFiltros;

/* ---------- Iniciar todo cuando el DOM esté listo ---------- */
document.addEventListener('DOMContentLoaded', () => {
  // capturar refs de filtros
  filtroSector = document.getElementById('filtro-sector');
  filtroResponsable = document.getElementById('filtro-responsable');
  filtroEncargado = document.getElementById('filtro-encargado');
  filtroEstado = document.getElementById('filtro-estado');
  filtroDesde = document.getElementById('filtro-desde');
  filtroHasta = document.getElementById('filtro-hasta');

  initGantt('Year');
  bindTableClick();
  poblarFiltros();
  setTimeout(syncGanttToTable, 300);
});
</script>


</body>
</html>
