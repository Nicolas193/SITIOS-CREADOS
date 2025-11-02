<?php
ob_start();
session_start();

include("../../conexion.php");
include("../../menu.php");

if (!isset($_SESSION['username']) || !isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'administrador') {
    header("Location: cartelaccesodenegado.php");
    exit();
}

$con = conectar();

$tipos_validos = ['Evaluador', 'Evaluado', 'Ambos'];

// Agregar nuevo estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_estado'])) {
    $nombre_estado = mysqli_real_escape_string($con, trim($_POST['nombre_estado']));
    $tipo = mysqli_real_escape_string($con, trim($_POST['tipo']));

    if (!in_array($tipo, $tipos_validos)) {
        $error = "Tipo inválido.";
    } else {
        $sql = "INSERT INTO estados (nombre_estado, tipo) VALUES ('$nombre_estado', '$tipo')";
        if (!mysqli_query($con, $sql)) {
            $error = "Error al registrar estado: " . mysqli_error($con);
        } else {
            header("Location: estadotareas.php");
            exit();
        }
    }
}

// Editar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_estado'])) {
    $id_estado = (int)$_POST['id_estado'];
    $nombre_estado = mysqli_real_escape_string($con, trim($_POST['nombre_estado']));
    $tipo = mysqli_real_escape_string($con, trim($_POST['tipo']));

    if (!in_array($tipo, $tipos_validos)) {
        $error = "Tipo inválido.";
    } else {
        $sql = "UPDATE estados SET nombre_estado = '$nombre_estado', tipo = '$tipo' WHERE id_estado = $id_estado";
        if (!mysqli_query($con, $sql)) {
            $error = "Error al actualizar estado: " . mysqli_error($con);
        } else {
            header("Location: estadotareas.php");
            exit();
        }
    }
}

// Obtener todos los estados
$query = mysqli_query($con, "SELECT * FROM estados ORDER BY id_estado DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Estados</title>
  <link rel="stylesheet" href="../css/administradoruser.css">
</head>
<body>

<div class="content1">

  <?php if (!empty($error)): ?>
    <div style="color: red; font-weight: bold; margin-bottom: 20px;">
        <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>

  <div class="form-container">
    <h2>Registrar Nuevo Estado</h2>
    <form method="POST" action="" class="form-wrapper" style="margin-bottom: 40px;">
      <label for="nombre_estado">Nombre del Estado:</label>
      <input type="text" name="nombre_estado" required>

      <label for="tipo">Tipo:</label>
      <select name="tipo" required>
        <option value="">Seleccione...</option>
        <option value="Evaluador">Evaluador</option>
        <option value="Evaluado">Evaluado</option>
        <option value="Ambos">Ambos</option>
      </select>

      <input type="submit" name="registrar_estado" value="Registrar" class="btn-update">
    </form>
  </div>

  <div class="list-section">
    <h2>Lista de Estados</h2>
    <table class="table" style="margin: 0 auto;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre del Estado</th>
          <th>Tipo</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($query)): ?>
        <form method="POST" action="">
          <tr>
            <td><?php echo (int)$row['id_estado']; ?></td>
            <td>
              <input type="text" name="nombre_estado" value="<?php echo htmlspecialchars($row['nombre_estado']); ?>" required>
            </td>
            <td>
              <select name="tipo" required>
                <option value="Evaluador" <?php if ($row['tipo'] === 'Evaluador') echo 'selected'; ?>>Evaluador</option>
                <option value="Evaluado" <?php if ($row['tipo'] === 'Evaluado') echo 'selected'; ?>>Evaluado</option>
                <option value="Ambos" <?php if ($row['tipo'] === 'Ambos') echo 'selected'; ?>>Ambos</option>
              </select>
            </td>
            <td>
              <input type="hidden" name="id_estado" value="<?php echo (int)$row['id_estado']; ?>">
              <input type="submit" name="update_estado" value="Actualizar" class="btn-update">
            </td>
          </tr>
        </form>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

</body>
</html>

<?php ob_end_flush(); ?>
