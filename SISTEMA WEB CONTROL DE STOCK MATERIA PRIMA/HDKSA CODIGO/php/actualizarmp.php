<?php

include("conexion.php");
$con=conectar();

$id = $_GET['id'];
$url = "registro.php";

if (isset($_POST['update'])) {
  # actualización de los campos de la base de datos
  $id = $_POST['id'];
  $cantidad = $_POST['cantidad'];
  $hueso = $_POST['hueso'];
  $fecharegistro = $_POST['fecharegistro'];
  
  $sql = "UPDATE materiaprima SET cantidad='$cantidad', hueso='$hueso', fecharegistro='$fecharegistro' WHERE id='$id'";
  $query = mysqli_query($con,$sql);
  
  if($query){
    header("Location: materiaprima.php");
  } else {
    echo "Error al actualizar los datos en la base de datos";
  }
}

# obtener los datos de la fila específica que se va a editar
$sql = "SELECT * FROM materiaprima WHERE id='$id'";
$query = mysqli_query($con,$sql);
$row = mysqli_fetch_array($query);

?>


<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="../css/actualizar.css">
  <title>Actualizar Mp</title>
</head>
<body>



<!-- Formulario para editar los campos -->
<form method="POST">

  <a href="materiaprima.php" class="volver-btn">Volver</a><br><br><br>
    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

  <label for="start_time">Fecha de Ingreso</label>
  <input type="datetime-local" id="start_time" name="fecharegistro" value="<?php echo $row['fecharegistro']; ?>" required>

  <label for="cantidad">Ingreso de Materia Prima (en kg)</label>
  <input type="number" id="cantidad" name="cantidad" step="0.01" min="0" value="<?php echo $row['cantidad']; ?>" required>

    <label for="cantidad">Ingreso de Hueso (en kg)</label>
  <input type="number" id="hueso" name="hueso" step="0.01" min="0" value="<?php echo $row['hueso']; ?>"value="<?php echo $row['fecharegistro']; ?>" >


  <button type="submit" name="update">Actualizar</button>
</form>




</body>
</html>