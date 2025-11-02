<?php

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user'])) {
    header("Location:../../index.php");
    exit();
}

// Incluir conexión una sola vez
require_once("conexion.php");
$con = conectar();

// Establecer nombre de usuario en la sesión
$_SESSION['username'] = $_SESSION['user'];

// Consultar tipo, sector y cargo del usuario
$usuario = mysqli_real_escape_string($con, $_SESSION['username']);
$sql = "SELECT tipo, sector, cargo, sitiocolor FROM usuarios WHERE usuario = '$usuario'";
$result = mysqli_query($con, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    $_SESSION['tipo']   = $row['tipo'];
    $_SESSION['sector'] = $row['sector']; 
    $_SESSION['cargo']  = $row['cargo'];  
    $_SESSION['sitiocolor']  = $row['sitiocolor'];  
} else {
    session_destroy();
    header("Location: ../../index.php");
    exit();
}
?>

