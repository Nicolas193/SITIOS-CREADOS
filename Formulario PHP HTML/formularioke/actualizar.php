<?php

include("conexion.php");
$con=conectar();

$cod_estudiante=$_POST['ID'];
$dni=$_POST['Nombre'];
$nombres=$_POST['Email'];
$apellidos=$_POST['Comentario'];

$sql="UPDATE formulario SET  Nombre='$dni',Email='$nombres',Comentario='$apellidos' WHERE ID='$cod_estudiante'";
$query=mysqli_query($con,$sql);

    if($query){
        Header("Location: tabla.php");
    }
?>