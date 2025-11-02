<?php
ob_start();
session_start();

include("../../conexion.php");
include("../../menu.php");

// Verificar permisos (opcional, igual que en tu ejemplo)
if (!isset($_SESSION['username']) || !isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'administrador') {
    header("Location: cartelaccesodenegado.php");
    exit();
}

$con = conectar();

// Agregar nueva tarea
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_tarea'])) {
    $nombre_tarea = mysqli_real_escape_string($con, trim($_POST['nombre_tarea']));
    if ($nombre_tarea !== '') {
        $sql = "INSERT INTO tareas (nombre_tarea) VALUES ('$nombre_tarea')";
        mysqli_query($con, $sql);
        header("Location: cargatareas.php");
        exit();
    }
}

// Actualizar tarea
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tarea'])) {
    $id_tarea = intval($_POST['id_tarea']);
    $nombre_tarea = mysqli_real_escape_string($con, trim($_POST['nombre_tarea']));
    if ($id_tarea > 0 && $nombre_tarea !== '') {
        $sql = "UPDATE tareas SET nombre_tarea = '$nombre_tarea' WHERE id_tarea = $id_tarea";
        mysqli_query($con, $sql);
        header("Location: cargatareas.php");
        exit();
    }
}

// Eliminar tarea
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql = "DELETE FROM tareas WHERE id_tarea = $delete_id";
    mysqli_query($con, $sql);
    header("Location: cargatareas.php");
    exit();
}

// Búsqueda
$busqueda = "";
if (isset($_GET['buscar'])) {
    $busqueda = mysqli_real_escape_string($con, $_GET['buscar']);
    $sql = "SELECT id_tarea, nombre_tarea FROM tareas WHERE nombre_tarea LIKE '%$busqueda%' ORDER BY id_tarea DESC";
} else {
    $sql = "SELECT id_tarea, nombre_tarea FROM tareas ORDER BY id_tarea DESC";
}
$query = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Gestion de Tareas</title>
  <link rel="stylesheet" href="../css/administradoruser.css" />
</head>
<body>

<div class="content1">

  <div class="form-container">
    <h2>Registrar Nueva Tarea</h2>
    <form method="POST" action="" class="form-wrapper" style="margin-bottom: 40px;">
      <label for="nombre_tarea">Nombre de la tarea:</label>
      <input type="text" name="nombre_tarea" id="nombre_tarea" required placeholder="Escribe la tarea aquí">
      <input type="submit" name="registrar_tarea" value="Registrar" class="btn-update">
    </form>
  </div>

  <div class="list-section">
    <h2>Lista de Tareas Registradas</h2>

    <form method="GET" action="" style="max-width: 400px; margin: 0 auto 20px; display: flex; gap: 8px;">
      <input 
        type="text" 
        name="buscar" 
        placeholder="Buscar por nombre de tarea" 
        value="<?php echo htmlspecialchars($busqueda); ?>" 
        class="input-buscar"
        style="flex: 1; padding: 6px 14px; border-radius: 20px 0 0 20px; border: 2px solid #0078d7; font-size: 13px; box-sizing: border-box;"
      >
      <input type="submit" value="Buscar" class="btn-update" style="border-radius: 0 20px 20px 0; width: auto; padding-left: 16px; padding-right: 16px;">
    </form>

    <table class="table" style="margin: 0 auto; width: 100%; max-width: 960px;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre Tarea</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($query)): ?>
        <form method="POST" action="">
          <tr>
            <td><?php echo $row['id_tarea']; ?></td>
            <td>
              <input type="text" name="nombre_tarea" value="<?php echo htmlspecialchars($row['nombre_tarea']); ?>" required style="width: 100%;">
            </td>
            <td style="white-space: nowrap;">
              <input type="hidden" name="id_tarea" value="<?php echo (int)$row['id_tarea']; ?>">
              <input type="submit" name="update_tarea" value="Actualizar" class="btn-update" style="margin-right: 8px;">

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

<?php
ob_end_flush();
?>
