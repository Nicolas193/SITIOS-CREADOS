<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user'])) {
    header("Location:../../index.php");
    exit();
}

// Incluir conexión una sola vez
require_once("conexion.php"); // Ajustá la ruta si es necesario
$con = conectar();

// Establecer nombre de usuario en la sesión
$_SESSION['username'] = $_SESSION['user'];

// Consultar el tipo de usuario
$usuario = mysqli_real_escape_string($con, $_SESSION['username']);
$sql = "SELECT tipo FROM usuarios WHERE usuario = '$usuario'";
$result = mysqli_query($con, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    $_SESSION['tipo'] = $row['tipo'];
} else {
    session_destroy();
    header("Location: ../../index.php");
    exit();
}
?>
