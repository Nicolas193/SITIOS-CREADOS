<?php
ob_start();
session_start();

include("../../conexion.php");
include("../../menu.php");

// Verificar sesión
if (!isset($_SESSION['username']) || !isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'administrador') {
    header("Location: cartelaccesodenegado.php");
    exit();
}

$con = conectar();

// Eliminar comentario si se recibe `delete_id`
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql_delete = "DELETE FROM comentarios WHERE id_comentario = $delete_id";
    mysqli_query($con, $sql_delete);
    header("Location: controlcomentarios.php"); // Cambiar según el nombre del archivo actual
    exit();
}

// Buscar por comentario
$busqueda = "";
if (isset($_GET['buscar'])) {
    $busqueda = mysqli_real_escape_string($con, trim($_GET['buscar']));
    $sql = "SELECT id_comentario, id_registro, id_usuario, comentario, fecha_comentario 
            FROM comentarios 
            WHERE comentario LIKE '%$busqueda%' 
            ORDER BY fecha_comentario DESC";
} else {
    $sql = "SELECT id_comentario, id_registro, id_usuario, comentario, fecha_comentario 
            FROM comentarios 
            ORDER BY fecha_comentario DESC";
}
$query = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Lista de Comentarios</title>
  <link rel="stylesheet" href="../css/administradoruser.css" />
</head>
<body>

<div class="content1">
  <h2>Lista de Comentarios</h2>

  <form method="GET" action="" style="max-width: 400px; margin: 0 auto 20px; display: flex; gap: 8px;">
    <input 
      type="text" 
      name="buscar" 
      placeholder="Buscar comentario..." 
      value="<?= htmlspecialchars($busqueda) ?>" 
      class="input-buscar"
      style="flex: 1; padding: 6px 14px; border-radius: 20px 0 0 20px; border: 2px solid #0078d7; font-size: 13px; box-sizing: border-box;"
    >
    <input 
      type="submit" 
      value="Buscar" 
      class="btn-update" 
      style="border-radius: 0 20px 20px 0; width: auto; padding-left: 16px; padding-right: 16px;"
    >
  </form>

  <div class="list-section">

  <table class="table" style="margin: 0 auto; width: 100%; max-width: 960px; color: #000;">
    <thead>
      <tr>
        <th>ID</th>
        <th>ID Registro</th>
        <th>ID Usuario</th>
        <th>Comentario</th>
        <th>Fecha</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = mysqli_fetch_assoc($query)): ?>
      <tr>
        <td><?= $row['id_comentario'] ?></td>
        <td><?= $row['id_registro'] ?></td>
        <td><?= $row['id_usuario'] ?></td>
        <td><?= htmlspecialchars($row['comentario']) ?></td>
        <td><?= date('d/m/Y', strtotime($row['fecha_comentario'])) ?></td>
        <td>
          <a href="?delete_id=<?= $row['id_comentario'] ?>" onclick="return confirm('¿Estás seguro de que querés eliminar este comentario?')" class="btn-update">Eliminar</a>
        </td>
      </tr>
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
