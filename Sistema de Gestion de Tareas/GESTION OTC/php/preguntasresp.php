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

// Agregar nueva pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pregunta'])) {
    $pregunta = mysqli_real_escape_string($con, trim($_POST['pregunta']));
    $respuesta = mysqli_real_escape_string($con, trim($_POST['respuesta']));

    $sql = "INSERT INTO preguntas (pregunta, respuesta) VALUES ('$pregunta', '$respuesta')";
    if (!mysqli_query($con, $sql)) {
        $error = "Error al registrar pregunta: " . mysqli_error($con);
    } else {
        header("Location: preguntasresp.php");
        exit();
    }
}

// Actualizar pregunta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pregunta'])) {
    $id_pregunta = (int)$_POST['id_pregunta'];
    $pregunta = mysqli_real_escape_string($con, trim($_POST['pregunta']));
    $respuesta = mysqli_real_escape_string($con, trim($_POST['respuesta']));

    $sql = "UPDATE preguntas SET pregunta = '$pregunta', respuesta = '$respuesta' WHERE id_pregunta = $id_pregunta";
    if (!mysqli_query($con, $sql)) {
        $error = "Error al actualizar pregunta: " . mysqli_error($con);
    } else {
        header("Location: preguntasresp.php");
        exit();
    }
}

// Eliminar pregunta
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    mysqli_query($con, "DELETE FROM preguntas WHERE id_pregunta = $delete_id");
    header("Location: preguntasresp.php");
    exit();
}

// Obtener todas las preguntas
$query = mysqli_query($con, "SELECT * FROM preguntas ORDER BY id_pregunta DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Preguntas</title>
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
    <h2>Agregar Nueva Pregunta</h2>
    <form method="POST" action="" class="form-wrapper" style="margin-bottom: 40px;">
      <label for="pregunta">Pregunta:</label>
      <input type="text" name="pregunta" required>

      <label for="respuesta">Respuesta:</label>
      <textarea name="respuesta" rows="3" required></textarea>

      <input type="submit" name="registrar_pregunta" value="Agregar" class="btn-update">
    </form>
  </div>

  <div class="list-section">
    <h2>Lista de Preguntas</h2>
    <table class="table" style="margin: 0 auto;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Pregunta</th>
          <th>Respuesta</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($query)): ?>
        <form method="POST" action="">
          <tr>
            <td><?php echo (int)$row['id_pregunta']; ?></td>
            <td><input type="text" name="pregunta" value="<?php echo htmlspecialchars($row['pregunta']); ?>" required></td>
            <td><textarea name="respuesta" rows="2" required><?php echo htmlspecialchars($row['respuesta']); ?></textarea></td>
            <td>
              <input type="hidden" name="id_pregunta" value="<?php echo (int)$row['id_pregunta']; ?>">
              <input type="submit" name="update_pregunta" value="Actualizar" class="btn-update">
              <a href="?delete_id=<?php echo $row['id_pregunta']; ?>" class="btn-delete" onclick="return confirm('¿Eliminar esta pregunta?')">Eliminar</a>
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
