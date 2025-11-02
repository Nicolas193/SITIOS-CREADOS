<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");

if (!isset($_SESSION['username'])) {
    die("Acceso denegado.");
}

$mysqli = conectar();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ID inválido.");
}

// Función para cargar opciones de select
function cargarOpciones($mysqli, $tabla, $idCampo, $campoDescripcion) {
    $data = [];
    $res = $mysqli->query("SELECT $idCampo, $campoDescripcion FROM $tabla ORDER BY $campoDescripcion");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Cargar listas para selects
$clasificaciones = cargarOpciones($mysqli, 'clasificacionddjj', 'id_clasificacion', 'descripcion');
$estados = cargarOpciones($mysqli, 'estadoddjj', 'id_estado', 'descripcion');
$anios = cargarOpciones($mysqli, 'anioestadoddjj', 'id_anioestado', 'anio');
$origenes = cargarOpciones($mysqli, 'origenddjj', 'id_origen', 'descripcion');
$consultas = cargarOpciones($mysqli, 'clasificacionesconsultaddjj', 'id_clasificacionconsulta', 'descripcion');
$acciones = cargarOpciones($mysqli, 'accionddjj', 'id_accion', 'descripcion');
$observaciones = cargarOpciones($mysqli, 'observacionesddjj', 'id_observaciones', 'observacion');

// Obtener registro actual
$stmt = $mysqli->prepare("SELECT * FROM ddjj WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$ddjj = $res->fetch_assoc();
$stmt->close();

if (!$ddjj) {
    die("DDJJ no encontrada.");
}

// Procesar POST para actualizar
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validaciones básicas (puedes extender según necesites)
    $fechaddjj = $_POST['fechaddjj'] ?? '';
    $lp = trim($_POST['lp'] ?? '');
    $detalle = trim($_POST['detalle'] ?? '');
    $id_clasificacion = $_POST['id_clasificacion'] ?: null;
    $id_origen = $_POST['id_origen'] ?: null;
    // No actualizamos id_usuario porque debe ser el mismo usuario que hace la edición, 
    // o podrías permitir cambiarlo si querés.
    $respuesta = trim($_POST['respuesta'] ?? '');
    $fecharespuesta = $_POST['fecharespuesta'] ?: null;
    $id_clasificacionconsulta = $_POST['id_clasificacionconsulta'] ?: null;
    $id_accion = $_POST['id_accion'] ?: null;
    $id_estado = $_POST['id_estado'] ?: null;
    $id_anioestado = $_POST['id_anioestado'] ?: null;
    $id_observaciones = $_POST['id_observaciones'] ?: null;
    $listado_detalle_problematica = trim($_POST['listado_detalle_problematica'] ?? '');

    // Convertir fechaddjj de datetime-local (string) a formato MySQL DATETIME
    // datetime-local input devuelve: 2025-07-03T15:45
    // Lo convertimos a "2025-07-03 15:45:00"
    if (!empty($fechaddjj)) {
        $fechaddjj = str_replace('T', ' ', $fechaddjj) . ':00';
    } else {
        $fechaddjj = null;
    }

    // Validar LP no vacío
    if (!$lp) {
        $error = "El campo LP es obligatorio.";
    }

    if (!$error) {
        // Preparar consulta para update
        $stmt = $mysqli->prepare("UPDATE ddjj SET 
            fechaddjj = ?, 
            lp = ?, 
            detalle = ?, 
            id_clasificacion = ?, 
            id_origen = ?, 
            respuesta = ?, 
            fecharespuesta = ?, 
            id_clasificacionconsulta = ?, 
            id_accion = ?, 
            id_estado = ?, 
            id_anioestado = ?, 
            id_observaciones = ?, 
            listado_detalle_problematica = ?
            WHERE id = ?");

        // Fecha respuesta puede ser NULL
        $fecharespuesta_param = !empty($fecharespuesta) ? $fecharespuesta : null;

        $stmt->bind_param("sssiissiiiiisi",
            $fechaddjj,
            $lp,
            $detalle,
            $id_clasificacion,
            $id_origen,
            $respuesta,
            $fecharespuesta_param,
            $id_clasificacionconsulta,
            $id_accion,
            $id_estado,
            $id_anioestado,
            $id_observaciones,
            $listado_detalle_problematica,
            $id
        );

        if ($stmt->execute()) {
            header("Location: ddjjconsulta.php?success=1");
            exit;
        } else {
            $error = "Error al actualizar: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Función para setear atributo selected en opciones
function selected($val1, $val2) {
    return ($val1 == $val2) ? 'selected' : '';
}

// Función para formatear datetime MySQL a formato para datetime-local HTML5
function datetimeLocalFormat($datetime) {
    if (!$datetime) return '';
    $dt = new DateTime($datetime);
    return $dt->format('Y-m-d\TH:i');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Editar DDJJ</title>

<!-- jQuery y Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../css/ddjjconsulta.css">

</head>
<body>

  <div class="wrapper-mistareas">
    <div class="container-mistareas form-container">

      <h1 class="title">Editar DDJJ ID <?= htmlspecialchars($ddjj['id']) ?></h1>

      <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post" action="editar_ddjj.php?id=<?= $ddjj['id'] ?>">

        <div class="form-row">
          <div class="form-group fechaddjj">
            <label for="fechaddjj">Fecha de DDJJ</label>
            <input type="datetime-local" name="fechaddjj" id="fechaddjj" 
              value="<?= datetimeLocalFormat($ddjj['fechaddjj']) ?>" required>
          </div>
          <div class="form-group lp">
            <label for="lp">Legajo / LP</label>
            <input type="text" name="lp" id="lp" required value="<?= htmlspecialchars($ddjj['lp']) ?>">
          </div>
        </div>

        <div class="form-group detalle">
          <label for="detalle">Detalle</label>
          <textarea name="detalle" id="detalle" rows="3" required><?= htmlspecialchars($ddjj['detalle']) ?></textarea>
        </div>

        <div class="form-row clasif-origen">
          <div class="form-group clasificacion">
            <label for="id_clasificacion">Clasificación</label>
            <select name="id_clasificacion" id="id_clasificacion" required>
              <option value="">-- Seleccionar --</option>
              <?php foreach ($clasificaciones as $c): ?>
                <option value="<?= htmlspecialchars($c['id_clasificacion']) ?>" <?= selected($ddjj['id_clasificacion'], $c['id_clasificacion']) ?>>
                  <?= htmlspecialchars($c['descripcion']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group origen">
            <label for="id_origen">Origen</label>
            <select name="id_origen" id="id_origen">
              <option value="">-- Seleccionar --</option>
              <?php foreach ($origenes as $o): ?>
                <option value="<?= htmlspecialchars($o['id_origen']) ?>" <?= selected($ddjj['id_origen'], $o['id_origen']) ?>>
                  <?= htmlspecialchars($o['descripcion']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row usuario-respuesta">
          <div class="form-group usuario">
            <label for="id_usuario">Responsable (no editable)</label>
            <input type="text" disabled value="<?= htmlspecialchars($_SESSION['username']) ?>">
          </div>

          <div class="form-group respuesta">
            <label for="respuesta">Respuesta</label>
            <textarea name="respuesta" id="respuesta" rows="2"><?= htmlspecialchars($ddjj['respuesta']) ?></textarea>
          </div>
        </div>

        <div class="form-row fecha-consulta">
          <div class="form-group fecharespuesta">
            <label for="fecharespuesta">Fecha Respuesta</label>
            <input type="date" name="fecharespuesta" id="fecharespuesta" value="<?= htmlspecialchars($ddjj['fecharespuesta']) ?>">
          </div>

          <div class="form-group clasificacion-consulta">
            <label for="id_clasificacionconsulta">Clasificación de Consulta</label>
            <select name="id_clasificacionconsulta" id="id_clasificacionconsulta">
              <option value="">-- Seleccionar --</option>
              <?php foreach ($consultas as $cc): ?>
                <option value="<?= htmlspecialchars($cc['id_clasificacionconsulta']) ?>" <?= selected($ddjj['id_clasificacionconsulta'], $cc['id_clasificacionconsulta']) ?>>
                  <?= htmlspecialchars($cc['descripcion']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row accion-estado-anio">
          <div class="form-group accion">
            <label for="id_accion">Acción Tomada</label>
            <select name="id_accion" id="id_accion">
              <option value="">-- Seleccionar --</option>
              <?php foreach ($acciones as $ac): ?>
                <option value="<?= htmlspecialchars($ac['id_accion']) ?>" <?= selected($ddjj['id_accion'], $ac['id_accion']) ?>>
                  <?= htmlspecialchars($ac['descripcion']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group estado">
            <label for="id_estado">Estado</label>
            <select name="id_estado" id="id_estado">
              <option value="">-- Seleccionar --</option>
              <?php foreach ($estados as $e): ?>
                <option value="<?= htmlspecialchars($e['id_estado']) ?>" <?= selected($ddjj['id_estado'], $e['id_estado']) ?>>
                  <?= htmlspecialchars($e['descripcion']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group anioestado">
            <label for="id_anioestado">Año Estado</label>
            <select name="id_anioestado" id="id_anioestado">
              <option value="">-- Seleccionar --</option>
              <?php foreach ($anios as $a): ?>
                <option value="<?= htmlspecialchars($a['id_anioestado']) ?>" <?= selected($ddjj['id_anioestado'], $a['id_anioestado']) ?>>
                  <?= htmlspecialchars($a['anio']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group observaciones">
          <label for="id_observaciones">Observaciones</label>
          <select name="id_observaciones" id="id_observaciones" class="select2" style="width: 100%;">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($observaciones as $o): ?>
              <option value="<?= htmlspecialchars($o['id_observaciones']) ?>" <?= selected($ddjj['id_observaciones'], $o['id_observaciones']) ?>>
                <?= htmlspecialchars($o['observacion']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group listado-problematica">
          <label for="listado_detalle_problematica">Listado Detalle Problemática</label>
          <textarea name="listado_detalle_problematica" id="listado_detalle_problematica" rows="3"><?= htmlspecialchars($ddjj['listado_detalle_problematica']) ?></textarea>
        </div>

        <button type="submit">Guardar cambios</button>
        <a href="ddjjconsulta.php" style="margin-left: 10px;">Cancelar</a>

      </form>
    </div>
  </div>

<script>
$(document).ready(function() {
  $('.select2').select2({
    placeholder: "-- Seleccionar --",
    allowClear: true
  });
});
</script>

</body>
</html>
