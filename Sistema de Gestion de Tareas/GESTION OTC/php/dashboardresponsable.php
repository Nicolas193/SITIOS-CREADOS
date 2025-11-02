<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
require_once("../../menu.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard de Tareas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/dashboard.css" />
</head>
<body>

<div class="contenedor1">

<h1>Vista general de tareas vinculadas</h1>

<div class="dashboard">
  <div class="card">
    <h3>Todas las tareas</h3>
    <div class="number" id="totalTodas">-</div>
  </div>
  <div class="card">
    <h3><span style="color:#4caf50;">●</span> Listo</h3>
    <div class="number" id="totalListo">-</div>
  </div>
  <div class="card">
    <h3><span style="color:#ffb300;">●</span> En curso</h3>
    <div class="number" id="totalEnCurso">-</div>
  </div>
  <div class="card">
    <h3><span style="color:#f44336;">●</span> Vencido</h3>
    <div class="number" id="totalDetenido">-</div>
  </div>
  <div class="card">
    <h3><span style="color:#9c27b0;">●</span> Canceladas</h3>
    <div class="number" id="totalCanceladas">-</div>
  </div>
</div>

<div class="grid-2x2">
  <div class="chart-container">
    <h3>Tareas por estado</h3>
    <canvas id="pieEstado"></canvas>
  </div>

  <div class="chart-container">
    <h3>Tareas vencidas</h3>
    <canvas id="barVencidas"></canvas>
  </div>

  <div class="chart-container">
    <h3>Tareas por vencimiento</h3>
    <canvas id="barPorVencimiento"></canvas>
  </div>
</div>

</div>
<script>
async function cargarDatos() {
  try {
    const response = await fetch('tareas_dataresponsable.php');
    if (!response.ok) throw new Error('Error en la respuesta: ' + response.status);
    const data = await response.json();

    if(data.error){
      document.body.innerHTML = `<p style="color:red;">Error: ${data.error}</p>`;
      return;
    }

    // Actualizar totales
    document.getElementById('totalTodas').textContent = data.totales.todas;
    document.getElementById('totalListo').textContent = data.totales.listas;
    document.getElementById('totalEnCurso').textContent = data.totales.enCurso;
    document.getElementById('totalDetenido').textContent = data.totales.detenidas;
    document.getElementById('totalCanceladas').textContent = data.totales.canceladas;

    // Pie: Tareas por estado
    const ctxPie = document.getElementById('pieEstado').getContext('2d');
    new Chart(ctxPie, {
      type: 'pie',
      data: {
        labels: Object.keys(data.porEstado),
        datasets: [{
          data: Object.values(data.porEstado),
          backgroundColor: ['#4caf50', '#ffb300', '#f44336', '#9c27b0'],
          borderWidth: 1,
          borderColor: '#fff',
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'right' } }
      }
    });

    // Barras: Vencidas vs por vencer
    const ctxVenc = document.getElementById('barVencidas').getContext('2d');
    new Chart(ctxVenc, {
      type: 'bar',
      data: {
        labels: ['Por vencer', 'Vencido'],
        datasets: [{
          label: 'Cantidad',
          data: [data.vencidas.por_vencer || 0, data.vencidas.vencidas || 0],
          backgroundColor: ['#ffb300', '#f44336'],
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } }
      }
    });

    // Barras apiladas: Vencimiento mensual
    const ctxVencMes = document.getElementById('barPorVencimiento').getContext('2d');
    const meses = Object.keys(data.porMes).sort();
    const estadosKeys = [1, 23456378, 9]; // Si luego querés incluir 11 también, agregalo aquí
    const coloresEstados = {
      1: '#4caf50',
      23456378: '#ffb300',
      9: '#f44336'
    };
    const datasetsPorEstado = estadosKeys.map(id => ({
      label: id === 1 ? 'Listo' : id === 23456378 ? 'En curso' : 'Vencido',
      data: meses.map(m => data.porMes[m]?.[id] || 0),
      backgroundColor: coloresEstados[id],
      stack: 'stack1'
    }));
    const mesesEtiquetas = meses.map(m => {
      const [y, mo] = m.split('-');
      return ['Ene.', 'Feb.', 'Mar.', 'Abr.', 'May.', 'Jun.', 'Jul.', 'Ago.', 'Sep.', 'Oct.', 'Nov.', 'Dic.'][+mo - 1] + "'" + y.slice(2);
    });
    new Chart(ctxVencMes, {
      type: 'bar',
      data: {
        labels: mesesEtiquetas,
        datasets: datasetsPorEstado
      },
      options: {
        responsive: true,
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
        plugins: { legend: { position: 'bottom' } }
      }
    });

  } catch (e) {
    document.body.innerHTML = `<p style="color:red;">Error al cargar datos: ${e.message}</p>`;
  }
}

window.onload = cargarDatos;
</script>

</body>
</html>
