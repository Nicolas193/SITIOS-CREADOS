<?php
function conectar(){

	$host="localhost:3306";
	$user="root";
	$pass="";

	$bd="portafolios";

	$con=mysqli_connect($host,$user,$pass) or die ("error al conectar a la base de datos".mysql_error());

	mysqli_select_db($con,$bd);

	return $con;
}

?>
