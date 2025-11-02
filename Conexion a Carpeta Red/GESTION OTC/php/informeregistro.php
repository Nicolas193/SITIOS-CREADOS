<?php
include("../../conexion.php");

$con = conectar();
$tipo_tarea = isset($_POST['tipo_tarea']) ? $_POST['tipo_tarea'] : null;
$fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
$hora_desde = isset($_POST['hora_desde']) ? $_POST['hora_desde'] : "00:00:00";
$fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;
$hora_hasta = isset($_POST['hora_hasta']) ? $_POST['hora_hasta'] : "23:59:59";
$dirigido_a = isset($_POST['dirigido_a']) ? $_POST['dirigido_a'] : null;
$estado = isset($_POST['estado']) ? $_POST['estado'] : null;



$sql = "SELECT 
    t1.id as id,
    t1.fecha_solicitud,
    t2.usuario as dirigido_a,
    t3.usuario as responsable,
    t1.descripcion,
    t1.archivos,
    t1.archivos2,
    t4.tarea as tipo_tarea,
    t1.campo1,
    t1.id_persona_asignada
  FROM registrodetareas as t1
  LEFT JOIN usuarios as t2 ON t2.id = t1.dirigido_a
  LEFT JOIN usuarios as t3 ON t3.id = t1.responsable
  LEFT JOIN tarea as t4 ON t4.id = t1.tipo_tarea
  INNER JOIN (
      SELECT id_persona_asignada, MAX(id) AS id
      FROM registrodetareas
      GROUP BY id_persona_asignada
  ) AS subconsulta
  ON t1.id_persona_asignada = subconsulta.id_persona_asignada
  AND t1.id = subconsulta.id
  WHERE t1.campo1 IN (2, 3)
  ORDER BY t1.id DESC";

$where = array();

if (!empty($tipo_tarea)) {
    $where[] = "tipo_tarea='$tipo_tarea'";
}

if (!empty($fecha_desde)) {
    $fecha_desde = date('Y-m-d H:i:s', strtotime("$fecha_desde $hora_desde"));
    $where[] = "fecha_solicitud >= '$fecha_desde'";
}

if (!empty($fecha_hasta)) {
    $fecha_hasta = date('Y-m-d H:i:s', strtotime("$fecha_hasta $hora_hasta"));
    $where[] = "fecha_solicitud <= '$fecha_hasta'";
}

if (!empty($dirigido_a)) {
    $where[] = "dirigido_a='$dirigido_a'";
}

if (!empty($estado)) {
    $where[] = "campo1='$estado'";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$query = mysqli_query($con, $sql);

include("../../menu.php");



// Verificar si el usuario está autenticado y tiene tipo Administrador
if (isset($_SESSION['username']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Administrador') {

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"  type="text/css" href="../css/informeregistro.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="shortcut icon" href="../imagenes/presentacion.ico" />
  <!-- bustrap es para mejor los estilos -->
  <title>Editar Registros</title>
</head>
<body>

<div class="contenedorinforegistro">

    <form action="informeregistro.php" method="post">
     <div> 
    <label>Tipo de tarea</label>
    <input type="text" name="tipo_tarea" value="<?php echo isset($tipo_tarea) ? $tipo_tarea : ''; ?>">
  </div>
  <div>
    <label>Desde:</label>
   <input type="date" name="fecha_desde" value="<?php echo isset($fecha_desde) ? $fecha_desde : ''; ?>">
  </div>
  <div>
    <label>Hasta:</label>
    <input type="date" name="fecha_hasta" value="<?php echo isset($fecha_hasta) ? $fecha_hasta : ''; ?>">
  </div>
  <div>
    <label>Asignado</label>
      <select name="dirigido_a" class="select-motivo">
         <option value="">Todos</option>
          <option value="Nicolas">Nicolas</option>
          <option value="Belen">Belen</option>
          <option value="Cristian">Cristian</option>
          <option value="Lucas">Lucas</option>
          <option value="Santiago">Santiago</option>
          <option value="Indistinto">Indistinto</option>
  </select>
  <label>Estado</label>
     <select name="estado" class="select-motivo">
          <option value="">Todos</option>
          <option value="1">Finalizado</option>
          <option value="null">Proceso</option>
    </select>
</div>
    <input type="submit" value="Filtrar">
  </form>

  <div class="container-fluid">
      <div class="row">
        <div class="col-mid-6">
          <br/>
          <table class="table table-responsive">
            <thead>
               <tr>
                <th>Codigo de Tarea</th>
                <th>Fecha de Respuesta</th>
                <th>Solicitador</th>
                <th>Responsable de Tarea</th>
                <th>Tipo de Tarea</th>
                <th>Descripción</th>
                <th>Archivo 1</th>
                <th>Archivo 2</th>
                <th>Estado</th>
                <th>Opciones</th>
              </tr> 
            </thead>
            <tbody>

              <?php
                while($row=mysqli_fetch_array($query)){
           if($row["fecha_solicitud"]>=$fecha_desde && $row["fecha_solicitud"]<=$fecha_hasta || $fecha_hasta==""){
              ?>
                    <tr>
                    <td><?php echo $row['id_persona_asignada']; ?></td>
                    <td><?php echo $row['fecha_solicitud']; ?></td>
                    <td><?php echo $row['responsable']; ?></td>
                     <td><?php echo $row['dirigido_a']; ?></td>
                    <td><?php echo $row['tipo_tarea']; ?></td>
                    <td><?php echo $row['descripcion']; ?></td>
                    <td>
                        <?php
                        $archivos = explode(',', $row['archivos']);
                        if (!empty($archivos)) {
                            echo "<ul>";
                            foreach ($archivos as $archivo) {
                                // Obtener solo el nombre del archivo
                                $nombre_archivo = basename($archivo);
                                // Construir la URL de descarga
                                $fileUrl = "http://127.0.0.1/Carpeta%20Compartida/PHP/Grupos.php?dir=%5C%5C10.70.150.4%5CGrupos%5C%5CTablerosTableau%5CGOCC+NO+BORRAR%5CNicolas+Maciel%5CAlmacenamiento&download=$nombre_archivo";
                                echo "<li><a href='$fileUrl' download>$nombre_archivo</a></li>";
                            }
                            echo "</ul>";
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        $archivos2 = explode(',', $row['archivos2']);
                        if (!empty($archivos2)) {
                            echo "<ul>";
                            foreach ($archivos2 as $archivo2) {
                                // Obtener solo el nombre del archivo
                                $nombre_archivo = basename($archivo2);
                                // Construir la URL de descarga
                                $fileUrl = "http://127.0.0.1/Carpeta%20Compartida/PHP/Grupos.php?dir=%5C%5C10.70.150.4%5CGrupos%5C%5CTablerosTableau%5CGOCC+NO+BORRAR%5CNicolas+Maciel%5CAlmacenamiento&download=$nombre_archivo";
                                echo "<li><a href='$fileUrl' download>$nombre_archivo</a></li>";
                            }
                            echo "</ul>";
                        }
                        ?>
                    </td>

                      <td>
                          <?php
                              switch($row['campo1']) {
                                  case 0:
                                      echo "Proceso";
                                      break;
                                  case 1:
                                      echo "Finalizado";
                                      break;
                                  case 2:
                                      echo "Devolucion";
                                      break;
                                  case 3:
                                      echo "Devolucion Finalizado";
                                      break;
                                  case 4:
                                      echo "Devolver Finalizar";
                                      break;
                                   case 5:
                                      echo "Derivar";
                                      break;
                                   case 6:
                                      echo "Corregir";
                                      break;
                                  default:
                                      echo "Estado desconocido";
                              }
                          ?>
                      </td>

                    <td>
                        <a href="insertardevolucion.php?id=<?php echo $row['id'] ?>" class="btn btn-actualizar" style="background-color:#ffcc00; color:#ffff;">Corregir</a>
                    </td>

                  </tr>
<?php

  }
             }
           ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<script>
  const deleteBtn = document.getElementById('delete-btn');
  deleteBtn.addEventListener('click', (event) => {
    if (!confirm('¿Estás seguro de que deseas eliminar este registro?')) {
      event.preventDefault();
    }
  });
</script>






</body>
</html>
<?php
} else {
    // Mostrar un aviso para usuarios que no sean "GermanArcos"
    include("cartelaccesodenegado.php");
}

?>