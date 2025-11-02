<?php
session_start();

require_once("conexion.php");

if(isset($_POST['user']) && isset($_POST['pass'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    $conn = conectar();

    // Consulta preparada para evitar inyección SQL
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario=?");
    $stmt->bind_param("s", $user); // Buscamos solo por el nombre de usuario
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Obtener los datos del usuario
        $row = $result->fetch_assoc();

        // Verificar si la contraseña ingresada coincide con el hash almacenado
        if (password_verify($pass, $row['contra'])) {
            // Usuario autenticado correctamente
            $_SESSION['user'] = $user;
            $_SESSION['tipo'] = $row['tipo']; // Guardar el tipo de usuario en la sesión
            header("location: bienvenida.php"); // Redireccionar al usuario a la página de bienvenida
            exit();
        } else {
            // Contraseña incorrecta
            $error = "Usuario o contraseña incorrectos";
        }
    } else {
        // Usuario no encontrado
        $error = "Usuario o contraseña incorrectos";
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Estilos/login1.css">
        <link rel="shortcut icon" href="imagen/presentacion.ico" />
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Iniciar Sesión</div>
                    <div class="card-body">
                        <!-- Aquí agregamos la imagen -->
                        <img src="imagen/intranetotcepcdad.png" alt="Logo" class="mb-3">
                        <form action="index.php" method="post">
                            <div class="mb-3">
                                <label for="user" class="form-label">Usuario:</label>
                                <input type="text" class="form-control" id="user" name="user" required>
                            </div>
                            <div class="mb-3">
                                <label for="pass" class="form-label">Contraseña:</label>
                                <input type="password" class="form-control" id="pass" name="pass" required>
                            </div>
                            <?php if(isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">Entrar al Panel</button>
                        </form>
                        <!-- Aquí agregamos el nombre -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
