<?php
ob_start();
session_start();

include("../../conexion.php");
include("../../menu.php");

// Verificar permisos
if (
    !isset($_SESSION['username']) || 
    !isset($_SESSION['tipo']) || 
    !in_array(strtolower($_SESSION['tipo']), ['administrador', 'gestor'])
) {
    header("Location: cartelaccesodenegado.php");
    exit();
}

// Zona horaria Argentina
date_default_timezone_set('America/Argentina/Buenos_Aires');

$mysqli = conectar();

$usuario = $mysqli->real_escape_string($_SESSION['username']);
$tipoUsuario = strtolower(trim($_SESSION['tipo']));
$sectorUsuario = trim($_SESSION['sector']);

// Obtener usuarios según tipo
if ($tipoUsuario === 'operador') {
   $stmt = $mysqli->prepare("SELECT id_usuario, usuario, sector FROM usuarios WHERE sector = ? ORDER BY usuario");

    if (!$stmt) {
        die("Error en prepare: " . $mysqli->error);
    }
    $stmt->bind_param("s", $sectorUsuario);
    $stmt->execute();
    $resultUsuarios = $stmt->get_result();
    $usuarios = [];
    while ($row = $resultUsuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
    $stmt->close();
} else {
    $resultUsuarios = $mysqli->query("SELECT id_usuario, usuario, sector FROM usuarios ORDER BY usuario");
    $usuarios = [];
    while ($row = $resultUsuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// Obtener tareas
$resultTareas = $mysqli->query("SELECT id_tarea, nombre_tarea FROM tareas ORDER BY nombre_tarea");

$tareas = [];
while ($row = $resultTareas->fetch_assoc()) {
    $tareas[] = $row;
}

// Obtener responsable de la sesión
$idResponsable = null;
$nombreResponsable = null;
$queryResponsable = $mysqli->query("SELECT id_usuario FROM usuarios WHERE usuario = '$usuario' LIMIT 1");

if ($queryResponsable && $rowResponsable = $queryResponsable->fetch_assoc()) {
    $idResponsable = $rowResponsable['id_usuario'];
    $nombreResponsable = $_SESSION['username'];
}

// Consulta para traer tareas con encargados concatenados (sin paginación ni límite) y comentario

$sql = "
SELECT 
    rt.fecha_solicitud,
    rt.plazo_entrega,
    u_responsable.usuario AS responsable,
    u_responsable.cargo AS cargo_responsable,
    u_responsable.sector AS sector_responsable,
    t.nombre_tarea AS tipo_tarea,
    rt.asunto AS descripcion,
    rt.id_registro,
    GROUP_CONCAT(DISTINCT CONCAT(uv.id_persona_asignada) ORDER BY uv.id_persona_asignada) AS id_persona_asignada,
    GROUP_CONCAT(DISTINCT CONCAT(u_encargado.usuario, ' (', u_encargado.cargo, ', ', u_encargado.sector, ')') SEPARATOR ', ') AS encargados_concatenados,

    (
      SELECT GROUP_CONCAT(
        CONCAT(
          es2.nombre_estado, 
          ' (', DATE_FORMAT(et2.fecha_actualizacion, '%d/%m/%Y %H:%i'), ')'
        ) ORDER BY et2.fecha_actualizacion SEPARATOR '\n---\n'
      )
      FROM estado_tarea et2
      JOIN estados es2 ON es2.id_estado = et2.id_estado
      WHERE et2.id_registro = rt.id_registro
    ) AS historial_estados,

    (
      SELECT GROUP_CONCAT(
        CONCAT(
          u2.usuario, ': ', 
          c2.comentario, 
          ' (', DATE_FORMAT(c2.fecha_comentario, '%d/%m/%Y %H:%i'), ')'
        ) ORDER BY c2.fecha_comentario SEPARATOR '\n---\n'
      )
      FROM comentarios c2
      JOIN usuarios u2 ON u2.id_usuario = c2.id_usuario
      WHERE c2.id_registro = rt.id_registro
    ) AS comentarios

FROM registro_de_tareas rt
JOIN tareas t ON rt.id_tarea = t.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
JOIN usuarios u_asignado ON u_asignado.id_usuario = uv.id_usuario

GROUP BY 
    rt.id_registro,
    rt.fecha_solicitud,
    rt.plazo_entrega,
    u_responsable.usuario,
    u_responsable.cargo,
    u_responsable.sector,
    t.nombre_tarea,
    rt.asunto

ORDER BY rt.fecha_solicitud DESC
";



$resultado = $mysqli->query($sql);
if (!$resultado) {
    die("Error al ejecutar la consulta: " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Registrar Tarea</title>

<!-- jQuery y Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Tu CSS personalizado -->
<link rel="stylesheet" href="../css/estilos_mistareas.css" />
</head>

<body>

    <div class="main-container">
      <div class="wrapper-mistareas">
        <div class="container-mistareas table-container">

          <section class="task-list-section">
            <h2 class="section-title">Tareas Asignadas</h2>
            <a href="exportar_csv_movimientos.php" class="btn-download-csv" title="Descargar CSV">
              <i class="fas fa-file-csv"></i> Descargar CSV
            </a>

            <?php if ($resultado && $resultado->num_rows > 0): ?>
              <div class="table-wrapper">
                <table id="tablaTareas" class="task-table" border="1" cellspacing="0" cellpadding="5">
                  <thead>
                    <tr>
                      <th>id registrados</th>
                      <th>Fecha Solicitud</th>
                      <th>Fecha de Entrega</th>
                      <th>Responsable</th>
                      <th>Cargo Responsable</th>
                      <th>Sector Responsable</th>
                      <th>Tipo de Tarea</th>
                      <th>Descripción</th>
                      <th>Encargados</th>
                      <th>Comentario</th>
                      <th>Último Estado</th>
                      <th>Tiempo Restante</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                      <tr class="clickable-row" data-id="<?= htmlspecialchars($fila['id_registro']) ?>" style="cursor: pointer;">
                        <td><?= htmlspecialchars($fila['id_persona_asignada']) ?></td>
                        <td><?= htmlspecialchars($fila['fecha_solicitud']) ?></td>
                        <td><?= htmlspecialchars($fila['plazo_entrega']) ?></td>
                        <td><?= htmlspecialchars($fila['responsable']) ?></td>
                        <td><?= htmlspecialchars($fila['cargo_responsable']) ?></td>
                        <td><?= htmlspecialchars($fila['sector_responsable']) ?></td>
                        <td><?= htmlspecialchars($fila['tipo_tarea']) ?></td>
                        <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                        <td><?= htmlspecialchars($fila['encargados_concatenados']) ?></td>
                        <td style="white-space: pre-line;"><?= htmlspecialchars($fila['comentarios'] ?? '') ?></td>

                          <td style="white-space: pre-line;">
                            <?php
                              $estado = $fila['historial_estados'] ?? '';
                              
                              // Eliminar fechas entre paréntesis usando expresión regular
                              $solo_estados = preg_replace('/\s*\(.*?\)/', '', $estado);

                              echo htmlspecialchars($solo_estados);
                            ?>
                          </td>


                        <td>
                          <?php
                            $plazo = new DateTime($fila['plazo_entrega']);
                            $hoy = new DateTime();
                            $dias_restantes = (int)$hoy->diff($plazo)->format('%r%a');
                            echo ($dias_restantes < 0 ? 0 : $dias_restantes) . ' días';
                          ?>
                        </td>

                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>

              </div>

            <?php else: ?>
              <p class="no-tasks-message">No se encontraron tareas para mostrar.</p>
            <?php endif; ?>
          </section>

        </div>
      </div>
    </div>

  <script>
    $(document).ready(function() {
      $('#usuarios_vinculados').select2({
        placeholder: "Escribe para buscar usuarios",
        allowClear: true,
        width: 'resolve'
      });
    });
  </script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelector('#tablaTareas tbody').addEventListener('click', function(e) {
    const fila = e.target.closest('tr.clickable-row');
    if (!fila) return;

    if (['BUTTON','INPUT','A'].includes(e.target.tagName)) return;

    const id = fila.getAttribute('data-id');
    if (id) {
      window.location.href = `comentariosmiusuario.php?id=${encodeURIComponent(id)}`;
    }
  });
});
</script>


<script>
$(document).ready(function () {
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
      infoEmpty: "No hay tareas para mostrar",
      zeroRecords: "No se encontraron registros",
      loadingRecords: "Cargando...",
      paginate: { previous: "«", next: "»" }
    }
  });
});
</script>



</body>
</html>
