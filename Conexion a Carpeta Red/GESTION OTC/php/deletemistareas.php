<?php
include("../../AutenticadorUser.php"); 



$id=$_GET['id'];

#borrar archivos imagen 
$archivos="SELECT archivos AS archi, archivos2 AS archi2 FROM registrodetareas WHERE id=$id";
$conectar=mysqli_query ($con,$archivos);
$op1=mysqli_fetch_array($conectar);

if (!$op1) {
    die('Error en la consulta: ' . mysqli_error($con));
}

$archivo1 = "C:/".$_SESSION['username']."/".$op1[0];
if (!unlink($archivo1)) {
    echo ("Error al eliminar $archivo1");
} else {
    echo ("Eliminado $archivo1");
}

$archivos2="SELECT archivos2 AS archi2 FROM registrodetareas WHERE id=$id";
$conectar=mysqli_query ($con,$archivos2);
$op2=mysqli_fetch_array($conectar);

if (!$op2) {
    die('Error en la consulta: ' . mysqli_error($con));
}

$archivo2 = "C:/".$_SESSION['username']."/".$op2[0];
if (!unlink($archivo2)) {
    echo ("Error al eliminar $archivo2");
} else {
    echo ("Eliminado $archivo2");
}

#-------------------------------------- borrado filas de tabla 
$sql="DELETE FROM registrodetareas  WHERE id='$id'";
$query=mysqli_query($con,$sql);

if(!$query){
    die('Error en la consulta: ' . mysqli_error($con));
} else {
    Header("Location: mistareas.php");
}
?>
