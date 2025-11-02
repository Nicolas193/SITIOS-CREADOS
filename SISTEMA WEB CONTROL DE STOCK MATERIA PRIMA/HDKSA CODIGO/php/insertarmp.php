<?php
include("conexion.php");

$con=conectar();

$fecharegistro=$_POST['fecharegistro'];
$fechacarga=date('Y-m-d H:i:s');
$cantidad=$_POST['cantidad'];
$hueso=$_POST['hueso'];

$sql="INSERT INTO materiaprima VALUES ('id','$fecharegistro','$fechacarga','$cantidad','$hueso')";


$query=mysqli_query($con,$sql);

if($query){

  Header("Location: materiaprima.php");

}else{

  echo "ERROR INSERT INTO VALUES";
}
?>