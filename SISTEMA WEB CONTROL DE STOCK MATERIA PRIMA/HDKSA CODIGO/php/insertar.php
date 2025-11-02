
<?php
include("conexion.php");
$con=conectar();


$tanque=$_POST['tanque'];
$medicion=$_POST['medicion'];
$fecharegistro=$_POST['fecharegistro'];
$fechadecarga = date('Y-m-d H:i:s');
$responsablemedicion=$_POST['responsablemedicion'];
$sql="INSERT INTO medicionesdetanques VALUES('id','$tanque','$medicion','$fecharegistro','$fechadecarga','$responsablemedicion','','')"; #insertar datos 

$query=mysqli_query($con,$sql);

if($query){

    Header("Location: registro2.php");
    
}else {

    echo "ERROR INSERT INTO VALUES";
}