<?php
include("conexion1.php");
$con=conectar();


$op1=$_POST['op1'];
$op2=$_POST['op2'];
$op3=$_POST['op3'];
$op12=$_POST['op12'];
$op22=$_POST['op22'];
$pregunta3=$_POST['pregunta3'];
$pregunta4=$_POST['pregunta4'];


$sql="INSERT INTO preguntaske VALUES('$ID','$op1','$op2','$op3','$op12','$op22','$pregunta3','$pregunta4')";
$query= mysqli_query($con,$sql);

if($query){
    Header("Location: Finalizar.html");
    
}else {
}
