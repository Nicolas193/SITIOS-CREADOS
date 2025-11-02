<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");
$mysqli = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id_registro'])) {
        die("No se indicó el ID de la tarea para actualizar.");
    }

    $id_registro = (int)$_POST['id_registro'];
    $fecha_solicitud = $_POST['fecha_solicitud'] ?? null;
    $plazo_entrega = $_POST['plazo_entrega'] ?? null;
    $asunto = $_POST['asunto'] ?? null;
    $id_tarea = $_POST['id_tarea'] ?? null;
    $id_usuario_rest = $_POST['id_usuario_rest'] ?? null;
    $usuarios_vinculados = $_POST['usuarios_vinculados'] ?? [];

    if (!$fecha_solicitud || !$plazo_entrega || !$asunto || !$id_tarea || !$id_usuario_rest) {
        die("Faltan datos obligatorios para actualizar.");
    }


    // Actualizar tarea principal
    $stmt = $mysqli->prepare("UPDATE registro_de_tareas SET fecha_solicitud=?, plazo_entrega=?, asunto=?, id_tarea=?, id_usuario_rest=? WHERE id_registro=?");
    $stmt->bind_param("ssssii", $fecha_solicitud, $plazo_entrega, $asunto, $id_tarea, $id_usuario_rest, $id_registro);
    $stmt->execute();
    $stmt->close();

    // Obtener vínculos actuales
    $vinculos_actuales = [];
    $stmtOld = $mysqli->prepare("SELECT id_usuario, id_persona_asignada FROM usuarios_vinculados WHERE id_registro=?");
    $stmtOld->bind_param("i", $id_registro);
    $stmtOld->execute();
    $resultOld = $stmtOld->get_result();
    while ($row = $resultOld->fetch_assoc()) {
        $vinculos_actuales[$row['id_usuario']] = $row['id_persona_asignada'];
    }
    $stmtOld->close();

// Definir correctamente los nuevos usuarios vinculados
$usuarios_vinculados_nuevos = $usuarios_vinculados ?? [];

// Determinar usuarios a eliminar (los que ya no están seleccionados)
$usuarios_a_eliminar = array_diff(array_keys($vinculos_actuales), $usuarios_vinculados_nuevos);
if (!empty($usuarios_a_eliminar)) {
    $in = implode(',', array_map('intval', $usuarios_a_eliminar));
    $mysqli->query("DELETE FROM usuarios_vinculados WHERE id_registro=$id_registro AND id_usuario IN ($in)");
}

// Asegurarse de que no haya duplicados
$usuarios_vinculados_nuevos = array_unique($usuarios_vinculados_nuevos);


if (count($usuarios_vinculados_nuevos) > 0) {
    $stmt_user = $mysqli->prepare("SELECT usuario FROM usuarios WHERE id_usuario=?");
    $stmtIns = $mysqli->prepare("
        INSERT INTO usuarios_vinculados (id_usuario, id_registro, id_persona_asignada)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE id_persona_asignada = VALUES(id_persona_asignada)
    ");

    foreach ($usuarios_vinculados_nuevos as $id_usuario_vinc) {
        $id_usuario_vinc = (int)$id_usuario_vinc;

        // Mantener si ya existía
        if (isset($vinculos_actuales[$id_usuario_vinc])) {
            $id_persona_asignada = $vinculos_actuales[$id_usuario_vinc];
        } else {
            // Generar nuevo id_persona_asignada
            $stmt_count = $mysqli->prepare("SELECT COUNT(*) AS frecuencia FROM usuarios_vinculados WHERE id_usuario=?");
            $stmt_count->bind_param("i", $id_usuario_vinc);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $frecuencia = ($result_count->fetch_assoc()['frecuencia'] ?? 0) + 1;
            $stmt_count->close();

            $stmt_user->bind_param("i", $id_usuario_vinc);
            $stmt_user->execute();
            $row_user = $stmt_user->get_result()->fetch_assoc();
            $usuario_nombre = $row_user['usuario'] ?? 'usuario';

            $id_persona_asignada = str_pad($frecuencia, 3, '0', STR_PAD_LEFT) . "_$usuario_nombre";
        }

        $stmtIns->bind_param("iis", $id_usuario_vinc, $id_registro, $id_persona_asignada);
        if (!$stmtIns->execute()) die("Error al insertar usuario vinculado: " . $stmtIns->error);
    }

    $stmt_user->close();
    $stmtIns->close();
}

    header("Location: registro.php?success=1");
    exit;
}

// --- GET ---
if (!isset($_GET['id'])) {
    die("No se indicó el ID de la tarea.");
}

$id_registro = (int) $_GET['id'];

// Datos de la tarea
$stmt = $mysqli->prepare("SELECT fecha_solicitud, plazo_entrega, asunto, id_tarea, id_usuario_rest FROM registro_de_tareas WHERE id_registro = ?");
$stmt->bind_param("i", $id_registro);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Tarea no encontrada.");
$tarea = $result->fetch_assoc();
$stmt->close();

// Usuarios vinculados y sus id_persona_asignada
$stmtVinc = $mysqli->prepare("SELECT id_usuario, id_persona_asignada FROM usuarios_vinculados WHERE id_registro = ?");
$stmtVinc->bind_param("i", $id_registro);
$stmtVinc->execute();
$resultVinc = $stmtVinc->get_result();
$usuarios_vinculados_actuales = [];
$ids_persona_actuales = [];
while ($row = $resultVinc->fetch_assoc()) {
    $usuarios_vinculados_actuales[] = $row['id_usuario'];
    $ids_persona_actuales[$row['id_usuario']] = $row['id_persona_asignada'];
}
$stmtVinc->close();

// Listas de tareas y usuarios
$resultTareas = $mysqli->query("SELECT id_tarea, nombre_tarea FROM tareas ORDER BY nombre_tarea");
$tareas = $resultTareas->fetch_all(MYSQLI_ASSOC);

$resultUsuarios = $mysqli->query("SELECT id_usuario, usuario FROM usuarios ORDER BY usuario");
$usuarios = $resultUsuarios->fetch_all(MYSQLI_ASSOC);

$idResponsable = $tarea['id_usuario_rest'];
$nombreResponsable = '';
foreach ($usuarios as $u) {
    if ($u['id_usuario'] == $idResponsable) {
        $nombreResponsable = $u['usuario'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Editar Tarea</title>

  <!-- jQuery y Select2 -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- Tu CSS personalizado -->
  <link rel="stylesheet" href="../css/estilos_mistareas.css" />
</head>
<body>

<div class="wrapper-mistareas">
  <div class="container-mistareas form-container">

        <button type="button" onclick="window.location.href='registro.php'" style="background: linear-gradient(135deg, #531f5d, #531f5d);
      border: none;
      color: white;
      padding: 12px 32px;
      font-weight: 700;
      font-size: 1.1rem;
      border-radius: 30px;
      cursor: pointer;
      transition: background 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 120, 215, 0.4);
      margin: 20px auto 0;
      display: block;
      user-select: none;">
      ← Volver
    </button>
    <h1 class="title">Editar Tarea</h1>
    <h2 class="subtitle">Modifique los campos que desee y luego guarde</h2>

    <form method="post" action="editar_registrotareas.php">
      <input type="hidden" name="id_registro" value="<?= htmlspecialchars($id_registro) ?>">

      <div class="form-dates">
        <div class="form-group">
          <label for="fecha_solicitud">Fecha de Solicitud</label>
          <input type="date" name="fecha_solicitud" value="<?= htmlspecialchars($tarea['fecha_solicitud']) ?>" required>
        </div>
        <div class="form-group">
          <label for="plazo_entrega">Plazo de Entrega</label>
          <input type="date" name="plazo_entrega" value="<?= htmlspecialchars($tarea['plazo_entrega']) ?>" required>
        </div>
      </div>

      <div class="form-group full-width">
        <label for="asunto">Asunto</label>
        <input type="text" name="asunto" value="<?= htmlspecialchars($tarea['asunto']) ?>" required>
      </div>

      <div class="form-group full-width">
        <label for="id_tarea">Tarea</label>
        <select name="id_tarea" required>
          <option value="">-- Seleccionar --</option>
          <?php foreach ($tareas as $t): ?>
            <option value="<?= htmlspecialchars($t['id_tarea']) ?>" <?= $t['id_tarea'] == $tarea['id_tarea'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['nombre_tarea']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group full-width">
        <label>Responsable:</label>
        <input type="hidden" name="id_usuario_rest" value="<?= htmlspecialchars($idResponsable) ?>">
        <strong><?= htmlspecialchars($nombreResponsable) ?> (ID: <?= $idResponsable ?>)</strong>
      </div>

      <div class="form-group full-width">
        <label>ID Persona Asignada</label>
        <?php foreach ($usuarios_vinculados_actuales as $id_user): ?>
          <input type="text" value="<?= htmlspecialchars($ids_persona_actuales[$id_user]) ?>" disabled>
        <?php endforeach; ?>
      </div>

      <div class="form-group full-width">
        <label for="usuarios_vinculados">Usuarios vinculados</label>
        <select id="usuarios_vinculados" name="usuarios_vinculados[]" multiple="multiple" style="width: 100%;">
          <?php foreach ($usuarios as $u): ?>
            <option value="<?= htmlspecialchars($u['id_usuario']) ?>" <?= in_array($u['id_usuario'], $usuarios_vinculados_actuales) ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['usuario']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit">Actualizar Tarea</button>
    </form>
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

</body>
</html>
