<?php
ob_start(); // <-- Solución al problema de headers

include("../../menu.php");

// Procesar formulario de registro de usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_usuario'])) {
    $usuario = mysqli_real_escape_string($con, $_POST['usuario']);
    $contra = mysqli_real_escape_string($con, $_POST['contra']);
    $tipo = mysqli_real_escape_string($con, $_POST['tipo']);

    // Encriptar contraseña
    $hashed_password = password_hash($contra, PASSWORD_DEFAULT);

    // Insertar usuario
    $sql = "INSERT INTO usuarios (usuario, contra, tipo) VALUES ('$usuario', '$hashed_password', '$tipo')";
    $query = mysqli_query($con, $sql);

    if ($query) {
        header("Location: AdministradorUser.php");
        exit();
    } else {
        echo "<script>alert('Error al registrar usuario');</script>";
    }
}

// Procesar formulario de actualización de rol
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_rol'])) {
    $id_usuario = $_POST['id_usuario'];
    $nuevo_tipo = mysqli_real_escape_string($con, $_POST['tipo']);

    if (!empty($nuevo_tipo) && !empty($id_usuario)) {
        $sqlUpdate = "UPDATE usuarios SET tipo = '$nuevo_tipo' WHERE id = $id_usuario";

        if (mysqli_query($con, $sqlUpdate)) {
            header("Location: AdministradorUser.php");
            exit();
        } else {
            echo "<script>alert('Error al actualizar el usuario');</script>";
        }
    } else {
        echo "<script>alert('Por favor, complete todos los campos correctamente.');</script>";
    }
}

// Obtener lista de usuarios
$sql = "SELECT id, usuario, tipo, contra FROM usuarios ORDER BY id DESC";
$query = mysqli_query($con, $sql);

// Verificar si el usuario está autenticado y tiene tipo Administrador
if (isset($_SESSION['username']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Administrador') {
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registro de Usuarios</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/registro.css">
</head>
<body>

<div class="container">
    <h2>Registrar Nuevo Usuario</h2>
    <form method="POST" action="">
        <label for="usuario">Nombre de Usuario:</label>
        <input type="text" name="usuario" required>

        <label for="contra">Contraseña:</label>
        <input type="text" name="contra" required>

        <label for="tipo">Tipo de Usuario:</label>
        <select name="tipo" required>
            <option value="Administrador">Administrador</option>
            <option value="Gestion">Gestión</option>
        </select>

        <input type="submit" name="registrar_usuario" value="Registrar">
    </form>
</div>

<div class="container">
    <h2>Lista de Usuarios Registrados</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Tipo de Usuario (Editable)</th>
                <th>Contraseña (No Editable)</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <form method="POST" action="">
                        <td><?php echo $row['usuario']; ?></td>
                        <td>
                            <select name="tipo">
                                <option value="Administrador" <?php if ($row['tipo'] == 'Administrador') echo 'selected'; ?>>Administrador</option>
                                <option value="Gestion" <?php if ($row['tipo'] == 'Gestion') echo 'selected'; ?>>Gestión</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" value="<?php echo $row['contra']; ?>" disabled>
                        </td>
                        <td>
                            <input type="hidden" name="id_usuario" value="<?php echo $row['id']; ?>">
                            <input type="submit" name="update_rol" value="Actualizar">
                        </td>
                    </form>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
} else {
    // Usuario no autorizado
    include("cartelaccesodenegado.php");
}

ob_end_flush(); // <-- Cierre del buffer
?>
