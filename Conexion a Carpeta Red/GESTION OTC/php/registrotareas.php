<?php 

include("../../conexion.php");

$con = conectar();
$sql1 = "SELECT 
    t1.id,
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
    t1.dirigido_a as iddirigido_a,
    t1.id_persona_asignada,
    t1.campo1
FROM registrodetareas as t1
LEFT JOIN usuarios as t2 ON t2.id = t1.dirigido_a
LEFT JOIN usuarios as t3 ON t3.id = t1.responsable
LEFT JOIN tarea as t4 ON t4.id = t1.tipo_tarea
JOIN (
    SELECT MAX(id) as max_id
    FROM registrodetareas
    GROUP BY id_persona_asignada
) AS max_ids
ON t1.id = max_ids.max_id";

$query = mysqli_query($con, $sql1);

include('../../menu.php');


// Verificar si el usuario está autenticado y tiene tipo Administrador
if (isset($_SESSION['username']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Administrador') {

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
        <th>Plazo de Entrega</th>
        <th>Responsable de Enviar Tarea</th>
        <th>Tipo de Tarea</th>
        <th>Descripción</th>
        <th>Archivos Adjuntos 1</th>
        <th>Archivos Adjuntos 2</th>
        <th>Responsable de Realizar Tarea</th>
        <th>Estado</th>                
      </tr>
    </thead>
    <tbody>
      <?php  
      while ($row = mysqli_fetch_array($query)) { 
      ?>
      <tr>
        <td><?php echo $row['id_persona_asignada']; ?></td>
        <td><?php echo $row['fecha_solicitud']; ?></td>
        <td><?php echo $row['plazoentrega']; ?></td>
        <td><?php echo $row['responsable']; ?></td>
        <td><?php echo $row['tipo_tarea']; ?></td>
        <td><?php echo $row['descripcion']; ?></td>
        <td><?php echo $row['archivos']; ?></td>
        <td><?php echo $row['archivos2']; ?></td>
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
      </tr>
      <?php } ?>

</table>
</body>
</html>

<?php
} else {
    // Mostrar un aviso para usuarios que no sean "GermanArcos"
    include("cartelaccesodenegado.php");
}

?>