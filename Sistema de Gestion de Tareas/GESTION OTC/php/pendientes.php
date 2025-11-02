<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../conexion.php");

// Manejo POST para cambiar vista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_vista'])) {
    $mysqli = conectar();
    $usuario = $mysqli->real_escape_string($_SESSION['username']);
    $resVista = $mysqli->query("SELECT vistasistema FROM usuarios WHERE usuario = '$usuario' LIMIT 1");
    $actual = $resVista->fetch_assoc()['vistasistema'] ?? 0;
    $nuevo_valor = $actual == 0 ? 1 : 0;
    $mysqli->query("UPDATE usuarios SET vistasistema = $nuevo_valor WHERE usuario = '$usuario'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

require_once("../../menu.php");

if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

date_default_timezone_set('America/Argentina/Buenos_Aires');

$mysqli = conectar();
$usuario = $mysqli->real_escape_string($_SESSION['username']);

// Obtener id_usuario del usuario actual, solo uno (limitar a 1)
$resIdUsuario = $mysqli->query("SELECT id_usuario FROM usuarios WHERE usuario = '$usuario' LIMIT 1");
if (!$resIdUsuario || $resIdUsuario->num_rows === 0) {
    die("Usuario no encontrado.");
}
$id_usuario_actual = (int)$resIdUsuario->fetch_assoc()['id_usuario'];

// Parámetros paginación (solo para ficha)
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 6; // ✅ MOSTRAR 6 FICHAS POR PÁGINA

$vista_sistema = 0;
$resVista = $mysqli->query("SELECT vistasistema FROM usuarios WHERE usuario = '$usuario' LIMIT 1");
if ($resVista && $rowVista = $resVista->fetch_assoc()) {
    $vista_sistema = (int)$rowVista['vistasistema'];
}

$sql = "
SELECT 
    MAX(rt.fecha_solicitud) AS fecha_solicitud,
    MAX(rt.plazo_entrega) AS plazo_entrega,
    MAX(u_responsable.usuario) AS responsable,
    MAX(u_responsable.cargo) AS cargo_responsable,
    MAX(u_responsable.sector) AS sector_responsable,
    MAX(t.nombre_tarea) AS tipo_tarea,
    MAX(rt.asunto) AS descripcion,
    rt.id_registro,
    GROUP_CONCAT(DISTINCT CONCAT(u_encargado.usuario, ' (', u_encargado.cargo, ', ', u_encargado.sector, ')') SEPARATOR ', ') AS encargados_concatenados,
    GROUP_CONCAT(DISTINCT CASE WHEN uv.id_usuario = $id_usuario_actual THEN uv.id_persona_asignada ELSE NULL END SEPARATOR '') AS id_persona_asignada,
    MAX(est.id_estado) AS id_estado,
    MAX(es.nombre_estado) AS ultimo_estado,
    MAX(est.fecha_actualizacion) AS fecha_actualizacion
FROM registro_de_tareas rt
JOIN tareas t ON rt.id_tarea = t.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
JOIN (
    SELECT et1.*
    FROM estado_tarea et1
    INNER JOIN (
        SELECT id_registro, MAX(fecha_actualizacion) AS max_fecha
        FROM estado_tarea
        GROUP BY id_registro
    ) ult ON et1.id_registro = ult.id_registro AND et1.fecha_actualizacion = ult.max_fecha
) est ON est.id_registro = rt.id_registro
JOIN estados es ON es.id_estado = est.id_estado
WHERE es.nombre_estado NOT IN ('Finalizado', 'Detenida')
  AND (LOWER(TRIM(es.tipo)) = 'evaluador' OR LOWER(TRIM(es.tipo)) = 'ambos')
  AND rt.id_registro IN (
    SELECT uv2.id_registro
    FROM usuarios_vinculados uv2
    JOIN usuarios u2 ON u2.id_usuario = uv2.id_usuario
    WHERE u2.usuario = '$usuario'
  )
GROUP BY rt.id_registro
ORDER BY fecha_solicitud DESC
";


$resultado = $mysqli->query($sql);
if (!$resultado) {
    die("Error al ejecutar la consulta: " . $mysqli->error);
}

// Convertir resultado a array para poder usarlo en ficha y tabla
$tareas_array = [];
while ($fila = $resultado->fetch_assoc()) {
    $tareas_array[] = $fila;
}

// PAGINACIÓN SOLO PARA VISTA FICHA
if ($vista_sistema === 1) {
    $total_tareas = count($tareas_array);
    $total_paginas = max(1, ceil($total_tareas / $registros_por_pagina)); // evitar dividir por cero
    if ($pagina < 1) $pagina = 1;
    if ($pagina > $total_paginas) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $registros_por_pagina;
    $tareas_paginadas = array_slice($tareas_array, $offset, $registros_por_pagina);
} else {
    // Si estás en vista tabla, no se usa paginación manual en PHP
    $total_paginas = 1;
    $tareas_paginadas = $tareas_array;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Mis Pendientes</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <link rel="stylesheet" href="../css/estilos_mistareas.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<div class="main-container">
  <div class="wrapper-mistareas">
    <div class="container-mistareas table-container">
      <section class="task-list-section">
        <h2 class="section-title">Mis Pendientes</h2>
        <br>

        <?php if (count($tareas_array) > 0): ?>

          <!-- Botón fijo para cambiar vista -->
<div id="iconoVistaContainer" style="width: 100%; display: flex; justify-content: flex-end; gap: 6px; margin: 10px 0; align-items: center;">

    <!-- Botón cambiar vista -->
    <form method="post" action="?pagina=<?= $pagina ?>" style="margin: 0; padding: 0; display: flex; align-items: center;">
        <input type="hidden" name="cambiar_vista" value="1">
        <button type="submit" title="Cambiar Vista"
          style="margin: 0; padding: 0; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px;
                 width: 26px; height: 26px; font-size: 14px; color: #ffff;
                 display: flex; align-items: center; justify-content: center;
                 cursor: pointer;">
          <?= $vista_sistema === 0 ? '<i class="fas fa-id-card"></i>' : '<i class="fas fa-table"></i>' ?>
        </button>
    </form>

<!-- Botón Vista Gantt -->
<a href="gantttareas.php?origen=pendientes" title="Vista Gantt"
   style="margin: 0; padding: 0; background: #4CAF50; color: white; border: 1px solid #ccc; border-radius: 4px;
          width: 26px; height: 26px; font-size: 14px; display: flex;
          align-items: center; justify-content: center; cursor: pointer;">
    <i class="fas fa-chart-bar"></i>
</a>


</div>

          <!-- Buscador visible SOLO para ficha -->
          <?php if ($vista_sistema === 1): ?>
            <input type="text" id="buscadorFichas" placeholder="Buscar tareas..." style="margin-bottom: 10px;">
          <?php endif; ?>

          <?php if ($vista_sistema === 0): ?>
            <!-- VISTA TABLA -->
            <div class="table-wrapper">
              <table id="tablaTareas" class="task-table" border="1" cellspacing="0" cellpadding="5" >
                <thead>
                  <tr>
                    <th>ID Persona Asignada</th>
                    <th>Fecha Solicitud</th>
                    <th>Fecha de Entrega</th>
                    <th>Responsable</th>
                    <th>Cargo Responsable</th>
                    <th>Sector Responsable</th>
                    <th>Tipo de Tarea</th>
                    <th>Descripción</th>
                    <th>Encargados</th>
                    <th>Último Estado</th>
                    <th>Tiempo Restante</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($tareas_array as $fila): ?>
                    <tr class="clickable-row" data-id="<?= htmlspecialchars($fila['id_registro']) ?>" style="cursor: pointer;">
                      <td><?= htmlspecialchars($fila['id_persona_asignada']) ?></td>
                      <td><?= date('d/m/Y', strtotime($fila['fecha_solicitud'])) ?></td>
                      <td><?= date('d/m/Y', strtotime($fila['plazo_entrega'])) ?></td>
                      <td><?= htmlspecialchars($fila['responsable']) ?></td>
                      <td><?= htmlspecialchars($fila['cargo_responsable']) ?></td>
                      <td><?= htmlspecialchars($fila['sector_responsable']) ?></td>
                      <td><?= htmlspecialchars($fila['tipo_tarea']) ?></td>
                      <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                      <td><?= htmlspecialchars($fila['encargados_concatenados']) ?></td>
                      <td>
                        <?php
                          $estado = $fila['ultimo_estado'];
                          $id_estado = $fila['id_estado'];
                          $plazo = new DateTime($fila['plazo_entrega']);
                          $hoy = new DateTime();
                          $diff_dias = (int)$hoy->diff($plazo)->format('%r%a');
                          $color = 'gray';

                          if ($id_estado == 1) {
                            $color = 'green';
                          } else {
                            if ($diff_dias >= 4) {
                              $color = 'green';
                            } elseif ($diff_dias >= 2) {
                              $color = 'orange';
                            } else {
                              $color = 'red';
                            }
                          }
                        ?>
                        <span style="color: <?= $color ?>; font-weight: bold;">
                          <?= htmlspecialchars($estado) ?>
                        </span>
                      </td>
                      <td><?= ($diff_dias < 0 ? 0 : $diff_dias) . ' días' ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          <?php else: ?>
            <!-- VISTA FICHA -->
            <div class="ficha-container">
             <?php foreach ($tareas_array as $fila): ?>

                <?php 
                  $estado = $fila['ultimo_estado'];
                  $id_estado = $fila['id_estado'];
                  $plazo = new DateTime($fila['plazo_entrega']);
                  $hoy = new DateTime();
                  $diff_dias = (int)$hoy->diff($plazo)->format('%r%a');

                  if ($id_estado == 1) {
                    $clase_estado = 'green';
                  } else {
                    if ($diff_dias >= 4) {
                      $clase_estado = 'green';
                    } elseif ($diff_dias >= 2) {
                      $clase_estado = 'orange';
                    } else {
                      $clase_estado = 'red';
                    }
                  }
                ?>
                <div class="ficha" data-id="<?= htmlspecialchars($fila['id_registro']) ?>">
                  <strong class="responsable"><?= htmlspecialchars($fila['responsable']) ?></strong>
                  <div><span class="label">ID Persona Asignada:</span> <span class="value"><?= htmlspecialchars($fila['id_persona_asignada']) ?></span></div>
                  <div><span class="label">Fecha Solicitud:</span> <span class="value"><?= (new DateTime($fila['fecha_solicitud']))->format('d/m/Y') ?></span></div>
                  <div><span class="label">Fecha de Entrega:</span> <span class="value"><?= (new DateTime($fila['plazo_entrega']))->format('d/m/Y') ?></span></div>
                  <div><span class="label">Cargo Responsable:</span> <span class="value"><?= htmlspecialchars($fila['cargo_responsable']) ?></span></div>
                  <div><span class="label">Sector Responsable:</span> <span class="value"><?= htmlspecialchars($fila['sector_responsable']) ?></span></div>
                  <div><span class="label">Tipo de Tarea:</span> <span class="value"><?= htmlspecialchars($fila['tipo_tarea']) ?></span></div>
                  <div><span class="label">Descripción:</span> <span class="value"><?= htmlspecialchars($fila['descripcion']) ?></span></div>
                  <div><span class="label">Encargados:</span> <span class="value"><?= htmlspecialchars($fila['encargados_concatenados']) ?></span></div>
                  <div class="estado <?= $clase_estado ?>"><?= htmlspecialchars($estado) ?></div>
                  <div><span class="label">Tiempo Restante:</span> <span class="value"><?= ($diff_dias < 0 ? 0 : $diff_dias) . ' días' ?></span></div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Contenedor para paginación ficha -->
            <div id="paginacionFichas" style="margin-top:10px; text-align:center;"></div>

          <?php endif; ?>

        <?php else: ?>
          <p class="no-tasks-message">No se encontraron tareas para mostrar.</p>
        <?php endif; ?>

      </section>
    </div>
  </div>
</div>

<script>
$(document).ready(function () {
  if (<?= $vista_sistema ?> === 0) {
    // Inicializar DataTables para tabla
    $('#tablaTareas').DataTable({
      paging: true,
      pageLength: 10,
      lengthChange: true,
      searching: true,
      ordering: true,
      info: true,
      pagingType: 'simple_numbers',
      language: {
        search: "Buscar:",
        lengthMenu: "Mostrar _MENU_ filas",
        info: "Mostrando _START_ a _END_ de _TOTAL_ tareas",
        zeroRecords: "No se encontraron registros",
        loadingRecords: "Cargando...",
        paginate: { previous: "«", next: "»" }
      }
    });
  }

  // Click fila tabla o ficha para ir a detalle
  $('.clickable-row, .ficha').on('click', function () {
    const id = $(this).data('id');
    if (id) {
      window.location.href = `comentariospendientes.php?id=${encodeURIComponent(id)}`;
    }
  });

  // Buscador fichas solo si vista ficha
  if (<?= $vista_sistema ?> === 1) {
    const fichasPorPagina = 6; // Ajusta el número de fichas por página
    let paginaActual = 1;

    function mostrarFichas() {
      const filtro = $('#buscadorFichas').val().toLowerCase();
      const fichas = $('.ficha');
      let fichasFiltradas = [];

      fichas.each(function () {
        const texto = $(this).text().toLowerCase();
        if (texto.includes(filtro)) {
          fichasFiltradas.push(this);
        }
      });

      const totalPaginas = Math.ceil(fichasFiltradas.length / fichasPorPagina);
      if (paginaActual > totalPaginas) paginaActual = totalPaginas || 1;

      fichas.hide();

      const inicio = (paginaActual - 1) * fichasPorPagina;
      const fin = inicio + fichasPorPagina;
      for (let i = inicio; i < fin && i < fichasFiltradas.length; i++) {
        $(fichasFiltradas[i]).show();
      }

      let htmlPaginas = '';
      if (totalPaginas > 1) {
        if (paginaActual > 1) {
          htmlPaginas += `<a href="#" class="pagina-link" data-pagina="${paginaActual - 1}">&laquo; Anterior</a> `;
        }
        htmlPaginas += ` Página ${paginaActual} de ${totalPaginas} `;
        if (paginaActual < totalPaginas) {
          htmlPaginas += `<a href="#" class="pagina-link" data-pagina="${paginaActual + 1}">Siguiente &raquo;</a>`;
        }
      }
      $('#paginacionFichas').html(htmlPaginas);
    }

    $('#buscadorFichas').on('input', function () {
      paginaActual = 1;
      mostrarFichas();
    });

    $('#paginacionFichas').on('click', 'a.pagina-link', function (e) {
      e.preventDefault();
      const pagina = parseInt($(this).data('pagina'));
      if (!isNaN(pagina)) {
        paginaActual = pagina;
        mostrarFichas();
        $('html, body').animate({ scrollTop: $('.ficha-container').offset().top }, 200);
      }
    });

    mostrarFichas();
  }
});
</script>

</body>
</html>
