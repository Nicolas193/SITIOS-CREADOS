<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de acceso solo para sector GOCI
if (!isset($_SESSION['username']) || !isset($_SESSION['sector']) || strtolower($_SESSION['sector']) !== 'goci') {
    header("Location: cartelaccesodenegado.php");
    exit();
}

require_once("../../conexion.php");
require_once("../../menu.php");

if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

$mysqli = conectar();
date_default_timezone_set('America/Argentina/Buenos_Aires');

$usuario = $mysqli->real_escape_string($_SESSION['username']);
$result = $mysqli->query("SELECT id_usuario, usuario FROM usuarios WHERE usuario = '$usuario' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $idUsuario = $row['id_usuario'];
    $nombreUsuario = $row['usuario'];
} else {
    $idUsuario = null;
    $nombreUsuario = null;
}

function cargarOpciones($mysqli, $tabla, $id, $nombre) {
    $data = [];
    // Solo trae los registros validados (id_validar = 1)
    $res = $mysqli->query("SELECT $id, $nombre FROM $tabla WHERE id_validar = 1 ORDER BY $nombre");
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}


$clasificaciones = cargarOpciones($mysqli, 'clasificacionddjj', 'id_clasificacion', 'descripcion');
$estados = cargarOpciones($mysqli, 'estadoddjj', 'id_estado', 'descripcion');
$anios = cargarOpciones($mysqli, 'anioestadoddjj', 'id_anioestado', 'anio');
$origenes = cargarOpciones($mysqli, 'origenddjj', 'id_origen', 'descripcion');
$consultas = cargarOpciones($mysqli, 'clasificacionesconsultaddjj', 'id_clasificacionconsulta', 'descripcion');
$acciones = cargarOpciones($mysqli, 'accionddjj', 'id_accion', 'descripcion');
$observaciones = cargarOpciones($mysqli, 'observacionesddjj', 'id_observaciones', 'observacion');

$query = "
    SELECT 
        d.id, d.fechaddjj, d.lp, d.detalle, d.respuesta, d.fecharespuesta, 
        d.listado_detalle_problematica,
        d.id_clasificacion, c.descripcion as clasificacion_desc,
        d.id_origen, o.descripcion as origen_desc,
        d.id_usuario, u.usuario as usuario_nombre,
        d.id_clasificacionconsulta, cc.descripcion as consulta_desc,
        d.id_accion, ac.descripcion as accion_desc,
        d.id_estado, es.descripcion as estado_desc,
        d.id_anioestado, ae.anio as anioestado_val,
        d.id_observaciones, ob.observacion as observacion_text,
        n.grado, n.apellido, n.nombre as nombre_nomina, n.dni, 
        n.correo, n.telasignado, n.dependencia
    FROM ddjj d
    LEFT JOIN clasificacionddjj c ON d.id_clasificacion = c.id_clasificacion
    LEFT JOIN origenddjj o ON d.id_origen = o.id_origen
    LEFT JOIN usuarios u ON d.id_usuario = u.id_usuario
    LEFT JOIN clasificacionesconsultaddjj cc ON d.id_clasificacionconsulta = cc.id_clasificacionconsulta
    LEFT JOIN accionddjj ac ON d.id_accion = ac.id_accion
    LEFT JOIN estadoddjj es ON d.id_estado = es.id_estado
    LEFT JOIN anioestadoddjj ae ON d.id_anioestado = ae.id_anioestado
    LEFT JOIN observacionesddjj ob ON d.id_observaciones = ob.id_observaciones
    LEFT JOIN nominaddjj n ON d.lp = n.lp
    ORDER BY d.id DESC
";

$res = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Formulario DDJJ</title>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../css/ddjjconsulta.css">

</head>
<body>
<div class="contenedormax">

<div class="wrapper-mistareas">
  <div class="container-mistareas form-container">

    <h1 class="title">Carga de DDJJ</h1>
    <h2 class="subtitle">(Registrar nueva Declaración Jurada)</h2>

    <?php if (isset($_GET['success'])): ?>
      <p class="success-message">La consulta DDJJ fue registrada correctamente.</p>
    <?php elseif (isset($_GET['error'])): ?>
      <p style="color:red;"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>

    <form method="post" action="guardar_ddjj.php">

      <div class="form-group">
        <label for="lp">Legajo / LP</label>
        <input type="text" name="lp" id="lp" required>
        <div id="loading-lp" style="color: blue; display: none;">Cargando datos del legajo...</div>
        <div id="info-lp" style="margin-top: 10px;"></div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="fechaddjj">Fecha de DDJJ</label>
          <input type="datetime-local" name="fechaddjj" id="fechaddjj" required>
        </div>

        <div class="form-group">
          <label for="id_clasificacion">Clasificación</label>
          <select name="id_clasificacion" id="id_clasificacion" required>
            <option value="">-- Seleccionar --</option>
            <?php foreach ($clasificaciones as $c): ?>
              <option value="<?= htmlspecialchars($c['id_clasificacion']) ?>">
                <?= htmlspecialchars($c['descripcion']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="id_origen">Origen</label>
          <select name="id_origen" id="id_origen">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($origenes as $o): ?>
              <option value="<?= htmlspecialchars($o['id_origen']) ?>">
                <?= htmlspecialchars($o['descripcion']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="id_usuario">Responsable</label>
          <select name="id_usuario" id="id_usuario" required>
            <option value="<?= htmlspecialchars($idUsuario) ?>" selected>
              <?= htmlspecialchars($nombreUsuario) ?>
            </option>
          </select>
        </div>

        <div class="form-group">
          <label for="fecharespuesta">Fecha Respuesta</label>
          <input type="date" name="fecharespuesta" id="fecharespuesta">
        </div>
      </div>

      <div class="form-group textarea-full">
        <label for="detalle">Detalle</label>
        <textarea name="detalle" id="detalle" rows="3" required></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="respuesta">Respuesta</label>
          <textarea name="respuesta" id="respuesta" rows="2"></textarea>
        </div>

        <div class="form-group">
          <label for="id_clasificacionconsulta">Clasificación de Consulta</label>
          <select name="id_clasificacionconsulta" id="id_clasificacionconsulta">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($consultas as $cc): ?>
              <option value="<?= htmlspecialchars($cc['id_clasificacionconsulta']) ?>">
                <?= htmlspecialchars($cc['descripcion']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="id_accion">Acción Tomada</label>
          <select name="id_accion" id="id_accion">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($acciones as $ac): ?>
              <option value="<?= htmlspecialchars($ac['id_accion']) ?>">
                <?= htmlspecialchars($ac['descripcion']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="id_estado">Estado</label>
          <select name="id_estado" id="id_estado">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($estados as $e): ?>
              <option value="<?= htmlspecialchars($e['id_estado']) ?>">
                <?= htmlspecialchars($e['descripcion']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="id_anioestado">Año Estado</label>
          <select name="id_anioestado" id="id_anioestado">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($anios as $a): ?>
              <option value="<?= htmlspecialchars($a['id_anioestado']) ?>">
                <?= htmlspecialchars($a['anio']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="id_observaciones">Observaciones</label>
          <select name="id_observaciones" id="id_observaciones" class="select2" style="width: 100%;">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($observaciones as $o): ?>
              <option value="<?= htmlspecialchars($o['id_observaciones']) ?>">
                <?= htmlspecialchars($o['observacion']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group textarea-full">
        <label for="listado_detalle_problematica">Listado De Detalle</label>
        <textarea name="listado_detalle_problematica" id="listado_detalle_problematica" rows="3"></textarea>
      </div>

      <button type="submit">Registrar Consulta DDJJ</button>
    </form>
  </div>
</div>


    <?php
// consulta para mostrar ddjj + nominaddjj
$query = "
    SELECT 
        d.id, d.fechaddjj, d.lp, d.detalle, d.respuesta, d.fecharespuesta, 
        d.listado_detalle_problematica,
        d.id_clasificacion, c.descripcion as clasificacion_desc,
        d.id_origen, o.descripcion as origen_desc,
        d.id_usuario, u.usuario as usuario_nombre,
        d.id_clasificacionconsulta, cc.descripcion as consulta_desc,
        d.id_accion, ac.descripcion as accion_desc,
        d.id_estado, es.descripcion as estado_desc,
        d.id_anioestado, ae.anio as anioestado_val,
        d.id_observaciones, ob.observacion as observacion_text,
        n.grado, n.apellido, n.nombre as nombre_nomina, n.dni, 
        n.correo, n.telasignado, n.dependencia
    FROM ddjj d
    LEFT JOIN clasificacionddjj c ON d.id_clasificacion = c.id_clasificacion
    LEFT JOIN origenddjj o ON d.id_origen = o.id_origen
    LEFT JOIN usuarios u ON d.id_usuario = u.id_usuario
    LEFT JOIN clasificacionesconsultaddjj cc ON d.id_clasificacionconsulta = cc.id_clasificacionconsulta
    LEFT JOIN accionddjj ac ON d.id_accion = ac.id_accion
    LEFT JOIN estadoddjj es ON d.id_estado = es.id_estado
    LEFT JOIN anioestadoddjj ae ON d.id_anioestado = ae.id_anioestado
    LEFT JOIN observacionesddjj ob ON d.id_observaciones = ob.id_observaciones
    LEFT JOIN nominaddjj n ON d.lp = n.lp
    ORDER BY d.id DESC
";

$res = $mysqli->query($query);
?>
<h2 class="subtitle">Listado de Declaraciones Juradas registradas</h2>
<div style="overflow-x: auto; width: 100%">

<div class="csv-button-container">
  <a href="exportar_csv_ddjj.php" class="btn-download-csv" title="Descargar CSV">
    <i class="fas fa-file-csv"></i> Descargar CSV
  </a>
</div>


<table id="tabla_ddjj" style="width:100%; border-collapse: collapse; margin-top:20px;">

    <thead style="background:#f0f0f0;">
        <tr>
            <th style="width: 220px;">Fecha de Consulta DDJJ</th>
            <th style="width: 120px;">LP</th>
            <th style="width: 220px;">Detalle</th>
            <th style="width: 180px;">Clasificación</th>
            <th style="width: 180px;">Origen</th>
            <th style="width: 180px;">Responsable</th>
            <th style="width: 220px;">Respuesta</th>
            <th style="width: 180px;">Fecha Respuesta</th>
            <th style="width: 180px;">Clasificación Consulta</th>
            <th style="width: 180px;">Acción</th>
            <th style="width: 180px;">Estado</th>
            <th style="width: 120px;">Año Estado</th>
            <th style="width: 220px;">Observaciones</th>
            <th style="width: 220px;">Listado Problemática</th>
            <!-- datos de nominaddjj -->
            <th style="width: 120px;">Grado</th>
            <th style="width: 150px;">Apellido</th>
            <th style="width: 150px;">Nombre</th>
            <th style="width: 120px;">DNI</th>
            <th style="width: 220px;">Correo</th>
            <th style="width: 150px;">Tel asignado</th>
            <th style="width: 180px;">Dependencia</th>
            <th style="width: 120px;">Opciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if($res && $res->num_rows > 0): ?>
            <?php while($row = $res->fetch_assoc()): ?>
                <tr style="word-break: break-word;">
                    <td><?= htmlspecialchars($row['fechaddjj']) ?></td>
                    <td><?= htmlspecialchars($row['lp']) ?></td>
                    <td><?= htmlspecialchars($row['detalle']) ?></td>
                    <td><?= htmlspecialchars($row['clasificacion_desc']) ?></td>
                    <td><?= htmlspecialchars($row['origen_desc']) ?></td>
                    <td><?= htmlspecialchars($row['usuario_nombre']) ?></td>
                    <td><?= htmlspecialchars($row['respuesta']) ?></td>
                    <td><?= htmlspecialchars($row['fecharespuesta']) ?></td>
                    <td><?= htmlspecialchars($row['consulta_desc']) ?></td>
                    <td><?= htmlspecialchars($row['accion_desc']) ?></td>
                    <td><?= htmlspecialchars($row['estado_desc']) ?></td>
                    <td><?= htmlspecialchars($row['anioestado_val']) ?></td>
                    <td><?= htmlspecialchars($row['observacion_text']) ?></td>
                    <td><?= htmlspecialchars($row['listado_detalle_problematica']) ?></td>
                    <td><?= htmlspecialchars($row['grado']) ?></td>
                    <td><?= htmlspecialchars($row['apellido']) ?></td>
                    <td><?= htmlspecialchars($row['nombre_nomina']) ?></td>
                    <td><?= htmlspecialchars($row['dni']) ?></td>
                    <td><?= htmlspecialchars($row['correo']) ?></td>
                    <td><?= htmlspecialchars($row['telasignado']) ?></td>
                    <td><?= htmlspecialchars($row['dependencia']) ?></td>
                    <td>
                        <a href="editar_ddjj.php?id=<?= $row['id'] ?>" style="color:blue;">Editar</a> |
                        <a href="eliminar_ddjj.php?id=<?= $row['id'] ?>" style="color:red;" onclick="return confirm('¿Seguro que deseas eliminar este registro?')">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="24">No hay DDJJ registradas.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
</div>

</body>

<script>
document.getElementById('lp').addEventListener('blur', function () {
  const lp = this.value.trim();
  if (!lp) return;

  const url = `buscar_lp.php?lp=${encodeURIComponent(lp)}`;
  const infoDiv = document.getElementById('info-lp');
  const loadingDiv = document.getElementById('loading-lp');

  infoDiv.innerHTML = '';
  loadingDiv.style.display = 'block'; // mostrar mensaje de carga

  fetch(url)
    .then(res => res.text())
    .then(text => {
      loadingDiv.style.display = 'none'; // ocultar mensaje de carga
      console.log("Respuesta raw:", text);

      try {
        const data = JSON.parse(text);
        if (data.error) {
          infoDiv.innerHTML = `<span style="color: red;">${data.error}</span>`;
        } else {
          infoDiv.innerHTML = `
            <strong>Nombre:</strong> ${data.nombre || ''} ${data.apellido || ''} <br>
            <strong>DNI:</strong> ${data.dni || ''} <br>
            <strong>Correo:</strong> ${data.correo || ''} <br>
            <strong>Tel. asignado:</strong> ${data.telasignado || ''} <br>
            <strong>Grado:</strong> ${data.grado || ''} <br>
            <strong>Dependencia:</strong> ${data.dependencia || ''} <br>
          `;
        }
      } catch (e) {
        console.error("Error parseando JSON:", e);
        infoDiv.innerHTML = 'Error al interpretar la respuesta del servidor.';
      }
    })
    .catch(err => {
      loadingDiv.style.display = 'none'; // ocultar mensaje de carga
      console.error("Error en fetch:", err);
      infoDiv.innerHTML = 'Error al consultar.';
    });
});

</script>

<script>
$(document).ready(function() {
  $('.select2').select2({
    placeholder: "-- Seleccionar --",
    allowClear: true
  });
});
</script>
<script>
$(document).ready(function() {
  $('#tabla_ddjj').DataTable({
    dom: "<'row'<'col-12 text-center'f>>" +  // Centra el buscador
         "<'row'<'col-12'tr>>" +
         "<'row'<'col-md-6'i><'col-md-6'p>>",
    language: {
      search: "Buscar:",
      lengthMenu: "Mostrar _MENU_ registros",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior"
      },
      zeroRecords: "No se encontraron registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)"
    },
    scrollX: true
  });
});

</script>


</html>

