<?php

include("../../conexion.php");
$con=conectar();

$id = $_GET['id'];
$url = "mistareas.php";

if (isset($_POST['update'])) {
  # actualización de los campos de la base de datos
  $id = $_POST['id'];
  $fecha_solicitud = $_POST['fecha_solicitud'];
  $plazoentrega = $_POST['plazoentrega'];
  $responsable = $_POST['responsable'];
  $tipo_tarea = $_POST['tipo_tarea'];
  $descripcion = $_POST['descripcion'];
  $dirigido_a = $_POST['dirigido_a'];
  $fecha= new datetime();

  $sql = "UPDATE registrodetareas SET fecha_solicitud='$fecha_solicitud', plazoentrega='$plazoentrega' , responsable='$responsable', tipo_tarea='$tipo_tarea', descripcion='$descripcion', dirigido_a='$dirigido_a' WHERE id='$id'";
  $query = mysqli_query($con,$sql);
  
  if($query){
        Header("Location: mistareas.php");
  } else {
    echo "Error al actualizar los datos en la base de datos";
  }
}

# obtener los datos de la fila específica que se va a editar
$sql = "SELECT 
    t1.id as id,
    t1.fecha_solicitud,
    t1.plazoentrega,
    t3.usuario as responsable,
    t1.responsable as idresponsable,
    t1.descripcion,
    t1.archivos,
    t1.archivos2,
    t4.tarea as tipo_tarea,
    t1.tipo_tarea as idtipo_tarea,
    t2.usuario as dirigido_a,
    t1.dirigido_a as iddirigido_a
  FROM registrodetareas as t1
  left join usuarios as t2 on(t2.id = t1.dirigido_a)
  left join usuarios as t3 on(t3.id = t1.responsable)
  left join tarea as t4 on(t4.id = t1.tipo_tarea)
 WHERE t1.id='$id'";

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

  <a href="mistareas.php" class="volver-btn">Volver</a><br><br><br>
    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">


<label for="fecha_solicitud">Fecha Solicitud:</label>
        <input type="date" id="fecha_solicitud" name="fecha_solicitud" value="<?php echo $row['fecha_solicitud']; ?>">

<label for="plazoentrega">Plazo de Entrega:</label>
        <input type="date" id="plazoentrega" name="plazoentrega" value="<?php echo $row['plazoentrega']; ?>">
        
        <label for="responsable">Responsable:</label>
         <select id="responsable" name="responsable">
         <option value="<?php echo $row['idresponsable']; ?>"><?php echo $row['responsable']; ?></option>
          <option value="2">LucasPalacio</option>
          <option value="3">CristianAdrian</option>
          <option value="4">NicolasMaciel</option>
          <option value="5">MariaBelen</option>
          <option value="6">SantiagoChamorro</option>
          <option value="">Todos</option>
        </select>
        
        <label for="tipo_tarea">Tipo de Tarea:</label>
        <select id="tipo_tarea" name="tipo_tarea">
         <option value="<?php echo $row['idtipo_tarea']; ?>"><?php echo $row['tipo_tarea']; ?></option>
          <option value="1">SaS</option>
          <option value="2">Excel</option>
          <option value="3">Control</option>
          <option value="4">Analisis</option>
          <option value="5">Mantenimiento</option>
          <option value="6">Reporte</option>
         <option value="7"></option>
        </select>
        
        <label for="descripcion">Descripcion:</label>
        <textarea id="descripcion" name="descripcion"><?php echo $row['descripcion']; ?></textarea>
        
        
        <label for="dirigido_a">Dirijido a:</label>
        <select id="dirigido_a" name="dirigido_a">
        <option value="<?php echo $row['iddirigido_a']; ?>"><?php echo $row['dirigido_a']; ?></option>
          <option value="2">LucasPalacio</option>
          <option value="3">CristianAdrian</option>
          <option value="4">NicolasMaciel</option>
          <option value="5">MariaBelen</option>
          <option value="6">SantiagoChamorro</option>
          <option value="">Todos</option>
        </select>
  <button type="submit" name="update">Actualizar</button>
</form>




</body>
</html>