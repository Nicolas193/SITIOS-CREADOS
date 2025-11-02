<?php

include("conexion.php");
$con=conectar();


$id=$_GET['id'];

#borrar archivos imagen 




#-------------------------------------- borrado filas de tabla 
$sql="DELETE FROM medicionesdetanques  WHERE id='$id'";
$query=mysqli_query($con,$sql);

       
    
    if($query){
        Header("Location: informeregistro.php");
    }
?>

