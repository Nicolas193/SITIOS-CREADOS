<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");
require_once("../../menu.php");


// Verificar permisos
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['tipo']) ||
    !in_array(strtolower($_SESSION['tipo']), ['administrador', 'secretario'])
) {
    header("Location: cartelaccesodenegado.php");
    exit();
}


date_default_timezone_set('America/Argentina/Buenos_Aires');

$mysqli = conectar();

$usuario = $mysqli->real_escape_string($_SESSION['username']);

$tipoUsuario = $_SESSION['tipo'];
$sectorUsuario = trim($_SESSION['sector']);

// DEBUG para ver qué trae el sector
//echo "Sector usuario: '$sectorUsuario'<br>";

if (strtolower(trim($tipoUsuario)) === 'operador') {
$stmt = $mysqli->prepare("SELECT id_usuario, usuario, sector FROM usuarios WHERE sector = ? ORDER BY usuario");

    if (!$stmt) {
        die("Error en prepare: " . $mysqli->error);
    }
    $stmt->bind_param("s", $sectorUsuario);
} else {
    $stmt = $mysqli->prepare("SELECT id_usuario, usuario, sector FROM usuarios ORDER BY usuario");

    if (!$stmt) {
        die("Error en prepare: " . $mysqli->error);
    }
}

$usuarios = [];
if ($stmt && $stmt->execute()) {
    $resultUsuarios = $stmt->get_result();
    while ($row = $resultUsuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
} else {
    die("Error en ejecución: " . $mysqli->error);
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

// Parámetros paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina - 1) * $registros_por_pagina;

// Consulta para traer tareas con encargados concatenados
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
    GROUP_CONCAT(DISTINCT uv.id_persona_asignada SEPARATOR ', ') AS id_persona_asignada,
    GROUP_CONCAT(DISTINCT CONCAT(u_encargado.usuario, ' (', u_encargado.cargo, ', ', u_encargado.sector, ')') SEPARATOR ', ') AS encargados_concatenados,
    MAX(u_registrador.usuario) AS registrado_por,
    MAX(et.id_estado) AS ultimo_estado
FROM registro_de_tareas rt
JOIN tareas t ON rt.id_tarea = t.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
JOIN (
    SELECT et1.id_registro, et1.id_estado, et1.id_usuario, et1.fecha_actualizacion
    FROM estado_tarea et1
    INNER JOIN (
        SELECT id_registro, MAX(fecha_actualizacion) AS fecha_max
        FROM estado_tarea
        GROUP BY id_registro
    ) ultimo ON et1.id_registro = ultimo.id_registro AND et1.fecha_actualizacion = ultimo.fecha_max
) et ON et.id_registro = rt.id_registro
LEFT JOIN usuarios u_registrador ON u_registrador.id_usuario = et.id_usuario
WHERE (
    u_registrador.usuario = '$usuario'
    OR u_responsable.usuario = '$usuario'
    OR u_encargado.usuario = '$usuario'
)
GROUP BY rt.id_registro
ORDER BY fecha_solicitud DESC

";



$resultado = $mysqli->query($sql);
if (!$resultado) {
    die("Error al ejecutar la consulta: " . $mysqli->error);
}

// Contar total registros para paginación
$total_sql = "
SELECT COUNT(DISTINCT rt.id_registro) AS total
FROM registro_de_tareas rt
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
JOIN estado_tarea et ON et.id_registro = rt.id_registro
WHERE et.id_estado = 1
  AND rt.id_registro IN (
    SELECT uv2.id_registro
    FROM usuarios_vinculados uv2
    JOIN usuarios u2 ON u2.id_usuario = uv2.id_usuario
    WHERE u2.usuario = '$usuario'
  )
";

$total_result = $mysqli->query($total_sql);
$total_fila = $total_result->fetch_assoc();
$total_registros = $total_fila['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Mis Pendientes</title>

  <!-- jQuery (único include) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- DataTables CSS y JS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <!-- Select2 (si lo sigues usando en otro punto) -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- Tu CSS personalizado -->
  <link rel="stylesheet" href="../css/estilos_mistareas.css" />
</head>
<body>
<div class="main-container">

<div class="wrapper-mistareas">
  <div class="container-mistareas form-container">

      <h1 class="title">Asignacion de Tarea</h1>
      <h2 class="subtitle">(Asigna la tarea que solicito alguien mas a los usuarios)</h2>

      <?php if (isset($_GET['success'])): ?>
        <p class="success-message">Tarea registrada correctamente.</p>
      <?php elseif (isset($_GET['error'])): ?>
        <p style="color:red;"><?= htmlspecialchars($_GET['error']) ?></p>
      <?php endif; ?>

      <form method="post" action="guardar_tarea_secretario.php">

          <div class="form-dates">
          <div class="form-group">
            <label for="fecha_solicitud">Fecha de Solicitud</label>
            <input type="date" name="fecha_solicitud" id="fecha_solicitud" required>
          </div>
          <div class="form-group">
            <label for="plazo_entrega">Plazo de Entrega</label>
            <input type="date" name="plazo_entrega" id="plazo_entrega" required>
          </div>
        </div>

        <div class="form-group full-width">
          <label for="asunto">Asunto</label>
          <input type="text" name="asunto" id="asunto" required>
        </div>

        <div class="form-group full-width">
          <label for="id_tarea">Tarea</label>
          <select name="id_tarea" id="id_tarea" required>
            <option value="">-- Seleccionar --</option>
            <?php foreach ($tareas as $t): ?>
              <option value="<?= htmlspecialchars($t['id_tarea']) ?>"><?= htmlspecialchars($t['nombre_tarea']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>


<div class="form-group full-width">
  <label for="id_usuario_rest">Quien te Solicito la Tarea:</label>
  <?php if (!empty($usuarios)): ?>
    <select name="id_usuario_rest" id="id_usuario_rest" required>
      <option value="">-- Seleccionar usuario responsable --</option>
      <?php foreach ($usuarios as $u): ?>
        <option value="<?= htmlspecialchars($u['id_usuario']) ?>" 
          <?= ($u['id_usuario'] == $idResponsable) ? 'selected' : '' ?>>
          <?= htmlspecialchars($u['usuario']) ?> (<?= htmlspecialchars($u['sector']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  <?php else: ?>
    <p style="color:red;">No se encontraron usuarios en el sistema.</p>
  <?php endif; ?>
</div>

          <div class="form-group full-width">
    <label for="usuarios_vinculados">Usuarios que deben realizar la tarea:</label>
    <select id="usuarios_vinculados" name="usuarios_vinculados[]" multiple="multiple" style="width: 100%;">
      <?php foreach ($usuarios as $u): ?>
        <option value="<?= htmlspecialchars($u['id_usuario']) ?>">
          <?= htmlspecialchars($u['usuario']) ?> (<?= htmlspecialchars($u['sector']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
</div>


        <button type="submit" class="boton-guardar">Guardar Tarea</button>
      </form>

    </div>
  </div>

  <div class="wrapper-mistareas">
    <div class="container-mistareas table-container">

      <section class="task-list-section">
        <h2 class="section-title">TAREAS</h2>
        <br>

        <?php if ($resultado && $resultado->num_rows > 0): ?>
<div class="table-wrapper">
  <table id="tablaTareas" class="task-table" border="1" cellspacing="0" cellpadding="5">
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
        <th><strong>Registrado por</strong></th> <!-- ✅ NUEVA COLUMNA -->
        <th>Opciones</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($fila = $resultado->fetch_assoc()): ?>
        <tr class="clickable-row" data-id="<?= htmlspecialchars($fila['id_registro']) ?>">
          <td><?= htmlspecialchars($fila['id_persona_asignada']) ?></td>
          <td><?= (new DateTime($fila['fecha_solicitud']))->format('d/m/Y') ?></td>
          <td><?= (new DateTime($fila['plazo_entrega']))->format('d/m/Y') ?></td>
          <td><?= htmlspecialchars($fila['responsable']) ?></td>
          <td><?= htmlspecialchars($fila['cargo_responsable']) ?></td>
          <td><?= htmlspecialchars($fila['sector_responsable']) ?></td>
          <td><?= htmlspecialchars($fila['tipo_tarea']) ?></td>
          <td><?= htmlspecialchars($fila['descripcion']) ?></td>
          <td><?= htmlspecialchars($fila['encargados_concatenados']) ?></td>
          <td><?= htmlspecialchars($fila['registrado_por']) ?></td> <!-- ✅ NUEVA COLUMNA -->
          <td class="td-opciones">
            <a href="editar_secretario.php?id=<?= urlencode($fila['id_registro']) ?>" title="Editar">
              <i class="fas fa-pen"></i>
            </a>
            <a href="borrar_tarea_secretario.php?id=<?= urlencode($fila['id_registro']) ?>" title="Borrar" onclick="return confirm('¿Seguro que quieres borrar esta tarea?');">
              <i class="fas fa-trash"></i>
            </a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <nav class="pagination" aria-label="Paginación de tareas">
    <?php if ($pagina > 1): ?>
      …
    <?php endif; ?>
    <?php if ($pagina < $total_paginas): ?>
      …
    <?php endif; ?>
  </nav>
</div>




        <?php else: ?>
          <p class="no-tasks-message">No se encontraron tareas para mostrar.</p>
        <?php endif; ?>
      </section>

    </div>
  </div>
</div>

<!-- Al final, justo antes de </body> -->
<script>
$(document).ready(function() {
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
});
</script>



<script> // Inicializar select2
$('#usuarios_vinculados').select2({
  placeholder: "Escribe para buscar usuarios",
  allowClear: true,
  width: 'resolve'
});

// Bloquear eliminación del usuario logueado
$('#usuarios_vinculados').on('select2:unselecting', function (e) {
  const fixedId = $('#usuarios_vinculados option[data-fixed="true"]').val();
  if (e.params.args.data.id == fixedId) {
    e.preventDefault();
  }
});
</script>



</body>



</html>
