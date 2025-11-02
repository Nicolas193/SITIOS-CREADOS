<?php
session_start();
require_once("conexion.php");

if(isset($_POST['user']) && isset($_POST['pass'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    $conn = conectar();

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario=?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['contrasena'])) {
            $_SESSION['user'] = $user;
            $_SESSION['tipo'] = $row['tipo'];
            header("location: bienvenida.php");
            exit();
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    } else {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Estilos/login1.css">
    <link rel="shortcut icon" href="imagen/presentacion.ico" />
</head>
<body>

    <!-- Fondo -->
    <div class="background-overlay"></div>

    <!-- Encabezado -->
    <header class="d-flex justify-content-between align-items-center p-3 bg-dark bg-opacity-75 fixed-top">
        <div class="d-flex align-items-center">
            <img src="imagen/Logos BS AS blanco.png" alt="BA Logo" class="ba-logo me-2">
            <h1 class="text-white fs-5 m-0">GESTOR DE TAREAS OTCEPCEDAD</h1>
        </div>
        <button   onclick="window.location.href='https://otcepcdad.seguridadciudad.gob.ar/'"
        class="btn btn-outline-light btn-sm px-3">
            Accesos Directos
        </button>

    </header>

    <!-- Contenido central -->
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-card shadow-lg text-center">
            <div class="circle-logo mx-auto mb-4">
                <img src="imagen/intranetotcepcdad.png" alt="Logo circular">
            </div>

            <form action="index.php" method="post" class="px-4">
                <div class="mb-3 text-start">
                    <label for="user" class="form-label">USUARIO</label>
                    <input type="text" class="form-control" id="user" name="user" required>
                </div>
                <div class="mb-3 text-start">
                    <label for="pass" class="form-label">CONTRASEÑA</label>
                    <input type="password" class="form-control" id="pass" name="pass" required>
                </div>

                <button type="submit" class="btn btn-success w-100 mb-3">INICIAR SESIÓN</button>
                <a style="color: #ffff; " > En caso de olvidar la contraseña o requerir un usuario, deberá comunicarse con el sector GOCC.</a>
            </form>
        </div>
    </div>

</body>
</html>
