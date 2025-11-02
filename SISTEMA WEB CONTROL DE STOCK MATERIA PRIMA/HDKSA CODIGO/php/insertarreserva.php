
<?php
include("conexion.php");
$con=conectar();




$fecharegistro=$_POST['fecharegistro'];
$reserva=$_POST['reserva'];

$sql="INSERT INTO tanquereserva VALUES('id','$fecharegistro','$reserva')"; #insertar datos 

$query=mysqli_query($con,$sql);

if($query){

    Header("Location: ingresoreserva.php");
    
}else {

    echo "ERROR INSERT INTO VALUES";
}