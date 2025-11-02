<?php
include("../../conexion.php");
include('../../menu.php');

$con = conectar();

// Obtener el nombre de usuario actualmente autenticado

if (isset($_SESSION['username'])) {

   $responsable = $_SESSION['username'];
   $queryResponsable = mysqli_query($con, "SELECT id FROM usuarios WHERE usuario = '$responsable'");
   if ($queryResponsable) {
   $rowResponsable = mysqli_fetch_assoc($queryResponsable);
   $usuario = $rowResponsable['id'];

}
}

$sql1 = "SELECT 
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
  WHERE t1.campo1 IN (0, 4, 5, 6)
  AND t1.dirigido_a = '$usuario' or t1.dirigido_a = '0'
  ORDER BY t1.id DESC";


    $query = mysqli_query($con, $sql1);


?>


<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"  type="text/css" href="../css/pendientes.css">
  <script>
  function table() {
    var html = document.getElementById('contenido').outerHTML;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'download.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.responseType = 'blob';
    xhr.onload = function() {
      if (this.status == 200) {
        var blob = new Blob([this.response], { type: 'application/pdf' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'Tabla Fecha:<?php echo date('Y-m-d')?>.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }
    };
    xhr.send('html=' + encodeURIComponent(html));
  }
  </script>
  <title>Pendientes</title>
</head>
<body>
  <br><br> 
  <br>  <br>  <br>
  <table class="table table-responsive" id="contenido">
    <thead>
      <tr>
        <th>ID</th>
        <th>Fecha Solicitud</th>
        <th>Responsable</th>
        <th>Tipo de Tarea</th>
        <th>Descripción</th>
        <th>Archivos Adjuntos 1</th>
        <th>Archivos Adjuntos 2</th>
        <th>Dirigido a</th>
        <th>Estado</th>                
        <th>Opciones</th>
      </tr>
    </thead>
    <tbody>
      <?php  
      while ($row = mysqli_fetch_array($query)) { 
      ?>
      <tr>
        <td><?php echo $row['id_persona_asignada']; ?></td>
        <td><?php echo $row['fecha_solicitud']; ?></td>
        <td><?php echo $row['responsable']; ?></td>
        <td><?php echo $row['tipo_tarea']; ?></td>
        <td><?php echo $row['descripcion']; ?></td>
            <td>
                    <?php
                    $archivos = explode(',', $row['archivos']);
                    if (!empty($archivos)) {
                        echo "<ul>";
                        foreach ($archivos as $archivo) {
                            $nombre_archivo = basename($archivo);
                            $fileUrl = "http://127.0.0.1/Carpeta%20Compartida/PHP/Grupos.php?dir=%5C%5C10.70.150.4%5CGrupos%5CTablerosTableau%5CGOCC+NO+BORRAR%5CAlmacenamiento%5C{$row['responsable']}&download=$nombre_archivo";
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
                            $nombre_archivo = basename($archivo2);
                           $fileUrl = "http://127.0.0.1/Carpeta%20Compartida/PHP/Grupos.php?dir=%5C%5C10.70.150.4%5CGrupos%5CTablerosTableau%5CGOCC+NO+BORRAR%5CAlmacenamiento%5C{$row['responsable']}&download=$nombre_archivo";
                            echo "<li><a href='$fileUrl' download>$nombre_archivo</a></li>";
                        }
                        echo "</ul>";
                    }
                    ?>
            </td>
        <td><?php echo $row['dirigido_a']; ?></td> 
        <td>
          <?php
          switch ($row['campo1']) {
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
          <a href="actualizarpendientes.php?id=<?php echo $row['id'] ?>" class="btn btn-actualizar" style="background-color:#f39c12; color:#ffff;">Devolucion</a>
        </td>
      </tr>
      <?php } ?>

</table>
</body>
</html>