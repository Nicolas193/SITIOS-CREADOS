<?php
include("conexion.php");
$con = conectar();
$tipo_tarea = isset($_POST['tipo_tarea']) ? $_POST['tipo_tarea'] : null;
$responsable = isset($_POST['responsable']) ? $_POST['responsable'] : null;
// Construir la consulta SQL
$sql = "SELECT 
t1.registrodetareas_id,
t1.fecha_fin,
t1.responsable,
t1.archivos,
t1.archivos2,
t2.fecha_solicitud,
t2.responsable as solicitante,
t2.descripcion,
t2.dirigido_a,
t2.tipo_tarea
from finalizado as t1 
left join registrodetareas as t2 on(t2.id = t1.registrodetareas_id)
 ";

if (!empty($tipo_tarea)) {
  $where[] = "t2.tipo_tarea='$tipo_tarea'";
}

if (!empty($responsable)) {
  $where[] = "t1.responsable='$responsable'";
}


if (!empty($where)) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

// Ejecutar la consulta
$query = mysqli_query($con, $sql);




?>
<?php include("copiadosinfo.php"); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"  type="text/css" href="../css/indexx.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
   <link rel="shortcut icon" href="../imagenes/presentacion.ico" />
  <!-- bustrap es para mejor los estilos -->
  <title>Registo Tareas Finalizadas</title>
</head>
<body >
  <div class="wrap">
	<div class="contenedor">
	<hr class="my-2">
	<h1 class="display-3">Registro de tareas Finalizadas</h1>
	<p class="lead"	>Informacion Principal</p>
	<hr class="my-2">
	<p>Tareas</p>
	</div> 
  <form action="index.php" method="post">
    <label>Ingrese la tarea:</label>
    <input type="text" name="tipo_tarea" value="<?php echo isset($tipo_tarea) ? $tipo_tarea : ''; ?>">
    <label>Responsable:</label>
   <select name="responsable" class="select-motivo">
         <option value="">Todos</option>
          <option value="Nicolas">Nicolas</option>
          <option value="Belen">Belen</option>
          <option value="Cristian">Cristian</option>
          <option value="Lucas">Lucas</option>
          <option value="Santiago">Santiago</option>
          <option value="Indistinto">Indistinto</option>
  </select>
    <input type="submit" value="Filtrar">

  </form>

<br><br><br><br><br><br>
	<div class="row row-cols-1 row-cols-md-3 g-4">
			<?php
				while($row=mysqli_fetch_array($query)){
			?>

				<div class="carpetas">
 					 <div class="col">
 					   <div class="card">
              <h5>Archivos Cargados</h5>
                        <?php
                        $archivos = explode(',', $row['archivos']);
                        if (!empty($archivos)) {
                            echo "<ul>";
                            foreach ($archivos as $archivo) {
                                echo "<li><a href='../Finalizados/$archivo' download>$archivo</a></li>";
                            }
                            echo "</ul>";
                        }
                        ?>                  
                        <?php
                        $archivos2 = explode(',', $row['archivos2']);
                        if (!empty($archivos2)) {
                            echo "<ul>";
                            foreach ($archivos2 as $archivo2) {
                                echo "<li><a href='../Finalizados/$archivo2' download>$archivo2</a></li>";
                            }
                            echo "</ul>";
                        }
                        ?>
 					     <hr class="my-1">
 					     <div class="card-body">
 					 <h5 class="card-title">Responsable: <?php echo $row['responsable'];?></h5>
               <h5 class="card-title">Tipo de Tarea: <?php echo $row['tipo_tarea']?></h5>
 					       <h5 class="card-title">Fecha de Inicio: <?php echo $row['fecha_solicitud']?></h5>
                 <h5 class="card-title">Fecha de Finalizado: <?php  echo $row['fecha_fin']?></h5>

 					     </div>
 					   </div>
 					 </div>
				</div>	
			
			</br>

			<?php
       									
             }         
      ?>
              
  </div>

  </div>
</body>
</html>

