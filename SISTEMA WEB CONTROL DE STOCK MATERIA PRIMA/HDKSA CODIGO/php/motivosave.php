<?php 

// Conexión a la base de datos
include("conexion.php");
$conn=conectar();

// Obtener el último registro de la tabla motivo
$resultado = mysqli_query($conn, "SELECT * FROM medicionesdetanques ORDER BY id DESC LIMIT 1");
$registro = mysqli_fetch_array($resultado);

// Obtener el valor seleccionado del menú
$motivo = $_POST['motivo'];
$fasonselec = $_POST['fasonselec'];

// Actualizar la columna motivo del último registro con el valor del menú
$id = $registro['id'];
$sql = "UPDATE medicionesdetanques SET motivo = '$motivo',fasonselec = '$fasonselec' WHERE id = $id";
$query=mysqli_query($conn, $sql);


// Cerrar la conexión a la base de datos
mysqli_close($conn);

if($query){

    Header("Location: finalregistro.php");
    
}else {

    echo "ERROR INSERT INTO VALUES";
}

 ?>