<?php
include("conexion.php");
$con=conectar();


$nombre=$_POST['nombre'];
$descripcion=$_POST['descripcion'];
$fecha= new datetime();                 # tiempo por si cargamos imagenes repetidas
$imagen=$fecha->getTimestamp()."_".$_FILES['imagen']['name'];   #$fecha->getTimestamp()."_". para imagenes repetidas la diferencia por fecha 

$imagen_temporal=$_FILES['imagen']['tmp_name'];  #imagen temporar

move_uploaded_file($imagen_temporal, "imagenes/".$imagen);  #mueve la imagen a el archivoi magen 

$sql="INSERT INTO galeria VALUES('id','$nombre','$imagen','$descripcion')"; #insertar datos 

$query=mysqli_query($con,$sql);

if($query){

    Header("Location: portafolio.php");
    
}else {

    echo "ERROR INSERT INTO VALUES";
}


