<?php
include("../../AutenticadorUser.php"); 

$id = intval($_GET['id']); // Sanitizar ID
$con = conectar(); // Asegurar que esté conectado

// Obtener ambos nombres de archivo en una sola consulta
$sqlArchivos = "SELECT archivos, archivos2 FROM registrodetareas WHERE id = $id";
$resultado = mysqli_query($con, $sqlArchivos);

if (!$resultado) {
    die('Error en la consulta de archivos: ' . mysqli_error($con));
}

$archivos = mysqli_fetch_assoc($resultado);

// Rutas absolutas
$baseRuta = "\\\\10.70.150.4\\Grupos\\TablerosTableau\\GOCC NO BORRAR\\Almacenamiento\\{$_SESSION['username']}\\";
$archivo1 = $baseRuta . $archivos['archivos'];
$archivo2 = $baseRuta . $archivos['archivos2'];

// Eliminar archivo 1 si existe
if (!empty($archivos['archivos']) && file_exists($archivo1)) {
    @unlink($archivo1); // @ para suprimir warning si falla
}

// Eliminar archivo 2 si existe
if (!empty($archivos['archivos2']) && file_exists($archivo2)) {
    @unlink($archivo2);
}

// Eliminar el registro
$sqlDelete = "DELETE FROM registrodetareas WHERE id = $id";
if (!mysqli_query($con, $sqlDelete)) {
    die('Error al eliminar registro: ' . mysqli_error($con));
}

// Redirigir
header("Location: registro.php");
exit;
?>
