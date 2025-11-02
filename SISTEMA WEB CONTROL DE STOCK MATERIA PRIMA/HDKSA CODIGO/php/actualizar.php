<?php

include("conexion.php");
$con=conectar();

$id = $_GET['id'];
$url = "registro.php";

if (isset($_POST['update'])) {
  # actualización de los campos de la base de datos
  $id = $_POST['id'];
  $tanque = $_POST['tanque'];
  $medicion = $_POST['medicion'];
  $fecharegistro = $_POST['fecharegistro'];
  $responsablemedicion = $_POST['responsablemedicion'];
  $motivo = $_POST['motivo'];
  $fasonselec = $_POST['fasonselec'];
  
  $sql = "UPDATE medicionesdetanques SET tanque='$tanque', medicion='$medicion', fecharegistro='$fecharegistro', responsablemedicion='$responsablemedicion', motivo='$motivo' ,fasonselec='$fasonselec' WHERE id='$id'";
  $query = mysqli_query($con,$sql);
  
  if($query){
    header("Location: informeregistro.php");
  } else {
    echo "Error al actualizar los datos en la base de datos";
  }
}

# obtener los datos de la fila específica que se va a editar
$sql = "SELECT * FROM medicionesdetanques WHERE id='$id'";
$query = mysqli_query($con,$sql);
$row = mysqli_fetch_array($query);

?>


<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="../css/actualizar.css">
  <title></title>
</head>
<body>



<!-- Formulario para editar los campos -->
<form method="POST">

  <a href="informeregistro.php" class="volver-btn">Volver</a><br><br><br>
  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
  <p>Tanque</p>
  <select name="tanque" class="select-motivo">
     <option value="<?php echo $row['tanque']; ?>"><?php echo $row['tanque']; ?></option>
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="A">A</option>
    <option value="B">B</option>
    <option value="C">C</option>
    <option value="E">E</option>
    <option value="11">11</option>
    <option value="14">14</option>
    <option value="15">15</option>
    <option value="16">16</option>
    <option value="18">18</option>
    <option value="19">19</option>
    <option value="24">21</option>
    <option value="22">22</option>
    <option value="23">23</option>
    <option value="24">24</option>
    <option value="25">25</option>
    <option value="26">26</option>
    <option value="31">31</option>
    <option value="32">32</option>
    <option value="33">33</option>
    <option value="34">34</option>
  </select>
  <p>Medicion</p>
   <input type="number" id="price" name="medicion" step="0.01" required value="<?php echo $row['medicion']; ?>">
   <p>Fecha de Medicion</p>
   <input type="datetime-local" id="start_time" name="fecharegistro" class="calendario" value="<?php echo $row['fecharegistro']; ?>" required>

<p>Responsable de la medicion</p>
  <input type="text" name="responsablemedicion" value="<?php echo $row['responsablemedicion']; ?>" required>


<p>Motivo</p>


<select name="motivo" class="select-motivo" id="motivo">
 <option value="<?php echo $row['motivo']; ?>"><?php echo $row['motivo']; ?></option>
  <option value="venta">Venta</option>
  <option value="gvc">GVC</option>
  <option value="limpieza">Limpieza</option>
  <option value="fason">Fason</option>
  <option value="produccion">Producción</option>
  <option value="trasbordo">Trasbordo</option>
  <option value="devolucion">Devolución</option>
  <option value="compras">Compras</option>
</select>

<p>SubMotivo actual</p>

<?php echo $row['fasonselec']; ?>
<div id="campo-seleccion" style="display: none;">
  <label for="seleccion">SubMotivo a Actualizar:</label>
  <select name="fasonselec" class="select-motivo" id="seleccion">
    <option value="null"></option>
  </select>
</div>


   <script src="../js/registrosubcampos.js"></script>
  <button type="submit" name="update">Actualizar</button>
</form>




</body>
</html>