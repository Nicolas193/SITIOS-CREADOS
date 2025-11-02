<?php
include("../../conexion.php");
$con = conectar();

// Manejo de la inserción de tarea
if (isset($_POST['tarea']) && !empty($_POST['tarea'])) {
    $nombre = mysqli_real_escape_string($con, $_POST['tarea']);
    $sql = "INSERT INTO tarea (tarea) VALUES ('$nombre')";
    mysqli_query($con, $sql);
    header("Location: cargatareas.php");
    exit;
}

// Manejo de la eliminación de tarea
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM tarea WHERE id = $delete_id";
    mysqli_query($con, $sql);
    header("Location: cargatareas.php");
    exit;
}

// Manejo de la actualización de tarea
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    if (isset($_POST['nombre_edit'])) {
        $nombre_edit = mysqli_real_escape_string($con, $_POST['nombre_edit']);
        $sql = "UPDATE tarea SET tarea = '$nombre_edit' WHERE id = $edit_id";
        mysqli_query($con, $sql);
        header("Location: cargatareas.php");
        exit;
    }

    // Obtener los datos de la tarea a editar
    $sql = "SELECT * FROM tarea WHERE id = $edit_id";
    $result = mysqli_query($con, $sql);
    $task = mysqli_fetch_assoc($result);
}

// Consulta para obtener las tareas existentes
$sql = "SELECT id, tarea FROM tarea ORDER BY id DESC";
$query = mysqli_query($con, $sql);

include("../../menu.php"); // Menú de navegación (si existe)
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="../css/registro.css">
  <title>Registro de Tareas</title>
</head>
<body>
  <div class="container">
    <h1>Registro de Tareas</h1>
    
    <!-- Formulario para agregar una nueva tarea -->
    <form action="cargatareas.php" method="POST">
      <label for="tarea">Nombre de la tarea:</label>
      <input type="text" id="tarea" name="tarea" required>
      <input type="submit" value="Agregar">
    </form>

    <br><br>

    <!-- Tabla de tareas -->
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tarea</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_array($query)): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['tarea']); ?></td>
            <td>
              <!-- Enlace para eliminar tarea -->
              <a href="cargatareas.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas eliminar esta tarea?');">Eliminar</a>
              <!-- Enlace para editar tarea -->
              <a href="cargatareas.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-primary">Editar</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <!-- Formulario para editar una tarea si está en modo edición -->
    <?php if (isset($task)): ?>
      <h2>Editar tarea</h2>
      <form action="cargatareas.php?edit_id=<?php echo $task['id']; ?>" method="POST">
        <label for="nombre_edit">Nuevo nombre:</label>
        <input type="text" id="nombre_edit" name="nombre_edit" value="<?php echo htmlspecialchars($task['tarea']); ?>" required>
        <input type="submit" value="Actualizar">
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
