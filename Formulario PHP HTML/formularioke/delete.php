<?php

include("conexion.php");
$con=conectar();

$ID=$_GET['id'];

$sql="DELETE FROM formulario  WHERE ID='$ID'";
$query=mysqli_query($con,$sql);

    if($query){
        Header("Location: tabla.php");
    }
?>