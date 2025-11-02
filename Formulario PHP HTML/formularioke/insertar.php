<?php
include("conexion.php");
$con=conectar();


$dni=$_POST['Nombre'];
$nombres=$_POST['Email'];
$apellidos=$_POST['Comentario'];


$sql="INSERT INTO formulario VALUES('$ID','$dni','$nombres','$apellidos')";
$query= mysqli_query($con,$sql);

if($query){
    Header("Location: tabla.php");
    
}else {
}
?>