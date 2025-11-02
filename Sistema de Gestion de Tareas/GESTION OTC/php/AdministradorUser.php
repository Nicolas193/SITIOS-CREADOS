<?php
ob_start();
session_start();

include("../../conexion.php");
include("../../menu.php");

// Verificar permisos
if (!isset($_SESSION['username']) || !isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'administrador') {
    header("Location: cartelaccesodenegado.php");
    exit();
}

// Procesar formulario de registro de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_usuario'])) {
    $usuario = mysqli_real_escape_string($con, trim($_POST['usuario']));
    $contra = $_POST['contra'];
    $tipo = mysqli_real_escape_string($con, trim(strtolower($_POST['tipo'])));
    $sector = mysqli_real_escape_string($con, trim($_POST['sector']));
    $cargo = mysqli_real_escape_string($con, trim($_POST['cargo']));
    $contacto = mysqli_real_escape_string($con, trim($_POST['contacto']));
    $interno = intval($_POST['interno']);
    $email = mysqli_real_escape_string($con, trim($_POST['mail']));

    $checkUser = mysqli_query($con, "SELECT 1 FROM usuarios WHERE usuario = '$usuario' LIMIT 1");
    if (mysqli_num_rows($checkUser) > 0) {
        $error = "El nombre de usuario ya está registrado. Por favor, elige otro.";
    } else {
        $hashed_password = password_hash($contra, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (usuario, contrasena, tipo, sector, cargo, contacto, interno, email)
                VALUES ('$usuario', '$hashed_password', '$tipo', '$sector', '$cargo', '$contacto', $interno, '$email')";
        if (mysqli_query($con, $sql)) {
            header("Location: AdministradorUser.php");
            exit();
        } else {
            $error = "Error al registrar usuario: " . mysqli_error($con);
        }
    }
}

// Procesar actualización de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $nuevo_tipo = mysqli_real_escape_string($con, trim(strtolower($_POST['tipo'])));
    $nuevo_sector = mysqli_real_escape_string($con, trim($_POST['sector']));
    $nuevo_cargo = mysqli_real_escape_string($con, trim($_POST['cargo']));
    $nuevo_contacto = mysqli_real_escape_string($con, trim($_POST['contacto']));
    $nuevo_interno = intval($_POST['interno']);
    $nuevo_email = mysqli_real_escape_string($con, trim($_POST['mail']));

    if ($id_usuario > 0) {
        $sqlUpdate = "UPDATE usuarios SET tipo = '$nuevo_tipo', sector = '$nuevo_sector', cargo = '$nuevo_cargo', contacto = '$nuevo_contacto', interno = $nuevo_interno, email = '$nuevo_email' WHERE id_usuario = $id_usuario";
        if (mysqli_query($con, $sqlUpdate)) {
            header("Location: AdministradorUser.php");
            exit();
        } else {
            $error = "Error al actualizar el usuario: " . mysqli_error($con);
        }
    } else {
        $error = "ID de usuario inválido.";
    }
}

// Procesar eliminación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']);

    if ($id_usuario > 0) {
        $sqlDelete = "DELETE FROM usuarios WHERE id_usuario = $id_usuario";
        if (mysqli_query($con, $sqlDelete)) {
            header("Location: AdministradorUser.php");
            exit();
        } else {
            $error = "Error al eliminar el usuario: " . mysqli_error($con);
        }
    } else {
        $error = "ID de usuario inválido para eliminar.";
    }
}

$busqueda = "";
if (isset($_GET['buscar'])) {
    $busqueda = mysqli_real_escape_string($con, $_GET['buscar']);
    $sql = "SELECT * FROM usuarios WHERE usuario LIKE '%$busqueda%' ORDER BY id_usuario DESC";
} else {
    $sql = "SELECT * FROM usuarios ORDER BY id_usuario DESC";
}
$query = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrador de Usuarios</title>
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
      <h2>Registrar Nuevo Usuario</h2>
      <form method="POST" action="" class="form-wrapper">
        <label>Nombre de Usuario:</label>
        <input type="text" name="usuario" required>

        <label>Contraseña:</label>
        <input type="password" name="contra" required>

        <label>Tipo de Usuario:</label>
        <select name="tipo" required>
          <option value="administrador">Administrador</option>
          <option value="gestor">Gestor</option>
          <option value="operador">Operador</option>
          <option value="secretario">Secretario</option> <!-- nueva opción -->
        </select>


        <label>Sector:</label>
        <input type="text" name="sector" required>

        <label>Cargo:</label>
        <input type="text" name="cargo" required>

        <label>Contacto:</label>
        <input type="text" name="contacto">

        <label>Interno:</label>
        <input type="number" name="interno" value="0">

        <label>Email:</label>
        <input type="email" name="mail">

        <input type="submit" name="registrar_usuario" value="Registrar" class="btn-update">
      </form>
    </div>

    <div class="list-section">
      <h2>Lista de Usuarios Registrados</h2>

      <form method="GET" action="" style="max-width: 400px; margin: 0 auto 20px; display: flex; gap: 8px;">
        <input type="text" name="buscar" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar usuario..." class="input-buscar">
        <input type="submit" value="Buscar" class="btn-update">
      </form>

      <table class="table">
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Tipo</th>
            <th>Sector</th>
            <th>Cargo</th>
            <th>Contacto</th>
            <th>Interno</th>
            <th>Email</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($query)): ?>
          <tr>
            <form method="POST" action="">
              <td><?php echo htmlspecialchars($row['usuario']); ?></td>
              <td>
                <select name="tipo">
                  <option value="administrador" <?php if ($row['tipo']==='administrador') echo 'selected'; ?>>Administrador</option>
                  <option value="gestor" <?php if ($row['tipo']==='gestor') echo 'selected'; ?>>Gestor</option>
                  <option value="operador" <?php if ($row['tipo']==='operador') echo 'selected'; ?>>Operador</option>
                  <option value="secretario" <?php if ($row['tipo']==='secretario') echo 'selected'; ?>>Secretario</option>
                </select>
              </td>
              <td><input type="text" name="sector" value="<?php echo htmlspecialchars($row['sector']); ?>"></td>
              <td><input type="text" name="cargo" value="<?php echo htmlspecialchars($row['cargo']); ?>"></td>
              <td><input type="text" name="contacto" value="<?php echo htmlspecialchars($row['contacto']); ?>"></td>
              <td><input type="number" name="interno" value="<?php echo (int)$row['interno']; ?>"></td>
              <td><input type="email" name="mail" value="<?php echo htmlspecialchars($row['email']); ?>"></td>
              <td>
                <input type="hidden" name="id_usuario" value="<?php echo (int)$row['id_usuario']; ?>">
                <input type="submit" name="update_usuario" value="Actualizar" class="btn-update"><br>
              </form>
              <form method="POST" action="" onsubmit="return confirm('¿Estás seguro que deseas eliminar este usuario?');">
                <input type="hidden" name="id_usuario" value="<?php echo (int)$row['id_usuario']; ?>">
                <input type="submit" name="eliminar_usuario" value="Eliminar" class="btn-delete">
              </form>
              </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
</div>
</body>
</html>

<?php ob_end_flush(); ?>
