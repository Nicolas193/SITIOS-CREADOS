<?php
include("../../conexion.php");
$con = conectar();
$sql = "SELECT 
    t1.id as id,
    t1.fecha_solicitud,
    t3.usuario as responsable,
    t1.descripcion,
    t1.archivos,
    t1.archivos2,
    t4.tarea as tipo_tarea,
    t2.usuario as dirigido_a
  FROM registrodetareas as t1
  left join usuarios as t2 on(t2.id = t1.dirigido_a)
  left join usuarios as t3 on(t3.id = t1.responsable)
  left join tarea as t4 on(t4.id = t1.tipo_tarea)
  where t1.campo1='0' ORDER BY t1.id DESC";

$query = mysqli_query($con, $sql);
include("../../menu.php");
// Verificar si el usuario está autenticado como "GermanArcos"
if ((isset($_SESSION['username']) && $_SESSION['username'] == 'NicolasMaciel') || (isset($_SESSION['username']) && $_SESSION['username'] == 'GermanArcos')) {

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"  type="text/css" href="../css/registro.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="shortcut icon" href="../imagenes/presentacion.ico" />
  <title>Asignación de Tareas GOCC</title>
</head>
<body>
  <div class="container">
    <div class="wrapper">
      <h1>Asignación de Tareas GOCC</h1>
      <form class="form-wrapper" action="insertar.php" method="POST" enctype="multipart/form-data">
        <label for="fecha_solicitud">Fecha Solicitud:</label>
        <input type="date" id="fecha_solicitud" name="fecha_solicitud">
        <label for="responsable">Responsable:</label>
          <select id="responsable" name="responsable">
                <?php
                // Verificar si el usuario está autenticado
                if (isset($_SESSION['username'])) {
                    $responsable = $_SESSION['username'];
                    $queryResponsable = mysqli_query($con, "SELECT id FROM usuarios WHERE usuario = '$responsable'");
                    if ($queryResponsable) {
                        $rowResponsable = mysqli_fetch_assoc($queryResponsable);
                        $idResponsable = $rowResponsable['id'];
                        echo '<option value="' . $idResponsable . '">' . $responsable . ' (ID: ' . $idResponsable . ')</option>';
                    }
                }
                ?>
          </select>
        <label for="tipo_tarea">Tipo de Tarea:</label>
       <select id="tipo_tarea" name="tipo_tarea">
          <option value="1">Administrativa</option>
          <option value="2">Mantenimiento</option>
          <option value="3">Control</option>
          <option value="4">Reporte</option>
          <option value="5">SaS</option>
          <option value="6">Otros</option>
        </select>

        <label for="descripcion">Descripcion:</label>
        <textarea id="descripcion" name="descripcion"></textarea>

        <label for="archivo">Adjuntar archivos</label>
        <input type="file" id="archivo" name="archivo" multiple>

        <input type="file" id="archivo2" name="archivo2" multiple>


        <label for="dirigido_a">Dirigido a:</label>
        <select id="dirigido_a" name="dirigido_a">
          <option value="2">LucasPalacio</option>
          <option value="3">CristianAdrian</option>
          <option value="4">NicolasMaciel</option>
          <option value="5">MariaBelen</option>
          <option value="6">SantiagoChamorro</option>
          <option value="">Todos</option>
        </select>


        <input type="submit" value="Enviar">
        
      </form>
    </div>
  </div>
  <!-- Tabla de registros -->
  <table class="table">
    <thead>
        <tr>
            <th>Fecha Solicitud</th>
            <th>Responsable</th>
            <th>Tipo de Tarea</th>
            <th>Descripción</th>
            <th>Archivos Adjuntos 1</th>
            <th>Archivos Adjuntos 2</th>
            <th>Dirigido a</th>
            <th>Opciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_array($query)): ?>
            <tr>
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
                            $nombre_archivo = basename($archivo2);
                            $fileUrl = "http://127.0.0.1/Carpeta%20Compartida/PHP/Grupos.php?dir=%5C%5C10.70.150.4%5CGrupos%5C%5CTablerosTableau%5CGOCC+NO+BORRAR%5CNicolas+Maciel%5CAlmacenamiento&download=$nombre_archivo";
                            echo "<li><a href='$fileUrl' download>$nombre_archivo</a></li>";
                        }
                        echo "</ul>";
                    }
                    ?>
                </td>
                <td><?php echo $row['dirigido_a']; ?></td>
                <td>
                    <a href="delete.php?id=<?php echo $row['id'] ?>" class="btn btn-danger" style="background-color:#AD1F1F; color:#fff;">Eliminar</a>
                    <a href="actualizar.php?id=<?php echo $row['id'] ?>" class="btn btn-primary" style="background-color:#2B57AD; color:#fff;">Editar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
<?php
} else {
    // Mostrar un aviso para usuarios que no sean "GermanArcos"
    include("cartelaccesodenegado.php");
}

?>


<script>
  const deleteBtn = document.getElementById('delete-btn');
  deleteBtn.addEventListener('click', (event) => {
    if (!confirm('¿Estás seguro de que deseas eliminar este registro?')) {
      event.preventDefault();
    }
  });
</script>
