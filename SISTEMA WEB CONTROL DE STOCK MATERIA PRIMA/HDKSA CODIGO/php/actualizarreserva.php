<?php

include("conexion.php");
$con=conectar();

$id = $_GET['id'];

if (isset($_POST['update'])) {
  # actualización de los campos de la base de datos
  $reserva = $_POST['reserva'];
  $fecharegistro = $_POST['fecharegistro'];
  
  $sql = "UPDATE tanquereserva SET reserva='$reserva', fecharegistro='$fecharegistro' WHERE id='$id'";
  $query = mysqli_query($con,$sql);
  
  if($query){
    header("Location: ingresoreserva.php");
  } else {
    echo "Error al actualizar los datos en la base de datos";
  }
}

# obtener los datos de la fila específica que se va a editar
$sql = "SELECT * FROM tanquereserva WHERE id='$id'";
$query = mysqli_query($con,$sql);
$row = mysqli_fetch_array($query);

?>


<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="../css/ingresoreserva.css">
  <title>Actualizar Reserva</title>
</head>
<body>

  <form action="ingresoreserva.php">
  <button>VOLVER</button>
</form>


<br><br>
<!-- Formulario para editar los campos -->
  <form class="form-wrapper" method="POST" >

     <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
    <label for="start_time">Fecha de Ingreso</label>
    <input type="datetime-local" id="start_time" value="<?php echo $row['fecharegistro']; ?>"name="fecharegistro" required>
    <label for="cantidad">Ingreso reserva</label>
    <input type="number" id="reserva" name="reserva" step="0.01" min="0" value="<?php echo $row['reserva']; ?>" required>
   <button type="submit" name="update">Actualizar Reserva</button>
  </form>




</body>
</html>