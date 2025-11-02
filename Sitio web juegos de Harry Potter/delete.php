<?php

include("conexion.php");
$con=conectar();


$id=$_GET['id'];
$url="portafolio.php";

#borrar archivos imagen 

$imagen="SELECT imagen AS imag FROM galeria WHERE id=$id";

$conectar=mysqli_query ($con,$imagen);
$op1=mysqli_fetch_array($conectar);

unlink("imagenes/".$op1[0]);

#-------------------------------------- borrado filas de tabla 
$sql="DELETE FROM galeria  WHERE id='$id'";
$query=mysqli_query($con,$sql);

       
    
    if($query&&$op1){
        Header("Location: portafolio.php");
    }
?>

