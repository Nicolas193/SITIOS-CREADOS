<?php

function database ($base)
{

$servidor="Localhost";
$usuario="root";
$pass="";

// controlar el error la aplicaciopn
try {

$conexion=new PDO("mysql:host=$servidor;dbname=encuesta", $usuario,$pass );	
// si sucede algo  //PD0 nos permite conectar a la base de datos 
$conexion->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); // verificamos si se conecto corretamente
$sql="SELECT * FROM formulario";  // llamamos a la base de datos 
$conexion->exec($sql);
echo "Conexion establecida";
} catch (PDOException $error) {
	echo "Conexxion erronea".$error;
}

}
?>
