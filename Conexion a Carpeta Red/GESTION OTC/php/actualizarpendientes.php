<?php
include("../../AutenticadorUser.php"); 


$id = $_GET['id'];
$url = "pendientes.php";

if (isset($_POST['update'])) {
    // Actualización de los campos de la base de datos
    $tipo_tarea = $_POST['tipo_tarea'];
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    $fecha_fin = $_POST['fecha_fin'];
    $plazoentrega = $_POST['plazoentrega'];
    $responsable = $_POST['responsable'];
    $dirigido_a = $_POST['dirigido_a'];
    $descripcion = $_POST['descripcion'];
    $fecha = new DateTime();
    $archivos = $fecha->getTimestamp() . "_" . $_FILES['archivo']['name'];
    $archivos2 = $fecha->getTimestamp() . "_" . $_FILES['archivo2']['name'];
    // Manejo de archivos
    $archivo_temporal = $_FILES['archivo']['tmp_name'];
    $archivo_temporal2 = $_FILES['archivo2']['tmp_name'];

    // Directorio en el disco C: donde se guardará la carpeta del usuario
    $directorio_usuario = "../../Almacenamiento/{$responsable}/";

    // Crear la carpeta del usuario si no existe
    if (!file_exists($directorio_usuario)) {
        mkdir($directorio_usuario, 0777, true);
    }

    // Nombre de archivo para el primer archivo
    $nombre_archivo1 = "";
    if (!empty($_FILES['archivo']['name'])) {
        $nombre_archivo1 = $fecha->format('d-m-Y') . "_" . $id . "_Soli_" . $_FILES['archivo']['name'];
    }

    // Nombre de archivo para el segundo archivo
    $nombre_archivo2 = "";
    if (!empty($_FILES['archivo2']['name'])) {
        $nombre_archivo2 = $fecha->format('d-m-Y') . "_" . $id . "_Rest_" . $_FILES['archivo2']['name'];
    }

    // Rutas de destino para los archivos en el disco C:
    $destino_local1 = $directorio_usuario . $nombre_archivo1;
    $destino_local2 = $directorio_usuario . $nombre_archivo2;

    // Variable para verificar si se han movido ambos archivos localmente
    $archivos_movidos = false;

    // Mover el primer archivo al directorio del usuario en el disco C:
    if (!empty($nombre_archivo1) && move_uploaded_file($archivo_temporal, $destino_local1)) {
        $archivos_movidos = true;
    }

    // Mover el segundo archivo al directorio del usuario en el disco C si se ha subido
    if (!empty($nombre_archivo2) && move_uploaded_file($archivo_temporal2, $destino_local2)) {
        $archivos_movidos = true;
    }

    // Verificar si al menos un archivo se ha movido localmente
    if ($archivos_movidos) {
        // Rutas de destino para los archivos en la carpeta compartida
        $destino_red1 = "\\\\10.70.150.4\\Grupos\\TablerosTableau\\GOCC NO BORRAR\\Almacenamiento\\{$responsable}\\" . $nombre_archivo1;
        $destino_red2 = "\\\\10.70.150.4\\Grupos\\TablerosTableau\\GOCC NO BORRAR\\Almacenamiento\\{$responsable}\\" . $nombre_archivo2;

        // Crear la carpeta del usuario en la red si no existe
        if (!file_exists(dirname($destino_red1))) {
            mkdir(dirname($destino_red1), 0777, true);
        }

        // Copiar el primer archivo a la carpeta compartida si se ha movido localmente
        if (!empty($nombre_archivo1) && !empty($archivo_temporal) && !empty($destino_local1) && !empty($destino_red1) && !copy($destino_local1, $destino_red1)) {
            echo "Error al copiar el primer archivo a la carpeta compartida.";
            exit;
        }

        // Copiar el segundo archivo a la carpeta compartida si se ha movido localmente
        if (!empty($nombre_archivo2) && !empty($archivo_temporal2) && !empty($destino_local2) && !empty($destino_red2) && !copy($destino_local2, $destino_red2)) {
            echo "Error al copiar el segundo archivo a la carpeta compartida.";
            exit;
        }
    }

    // Resto del código para la inserción en la base de datos
    $sql = "INSERT INTO registrodetareas VALUES(NULL, '$fecha_fin','$plazoentrega', '$responsable', '$tipo_tarea', '$descripcion', '$nombre_archivo1', '$nombre_archivo2', '$dirigido_a', '$estado','1','$id')";
    $query = mysqli_query($con, $sql);

    if ($query) {
        header("Location: pendientes.php");
    } else {
        echo "ERROR AL INSERTAR VALORES";
    }
}

# obtener los datos de la fila específica que se va a editar
$sql = "SELECT * FROM registrodetareas WHERE id='$id'";
$query = mysqli_query($con, $sql);
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
<form method="POST" enctype="multipart/form-data">




  <a href="pendientes.php" class="volver-btn">Volver</a><br><br><br>

    <p> Identificador de Tarea </p>

      <input type="text" name="id" value="<?php echo $row['id_persona_asignada']; ?>" readonly>
      <input type="text" name="tipo_tarea" value="<?php echo $row['tipo_tarea']; ?>" readonly>

        <label for="fecha_solicitud">Respuesta de la tarea</label>
        <input type="date" id="fecha_fin" name="fecha_fin">

         <label for="archivo">Adjuntar archivos</label>
        <input type="file" id="archivo" name="archivo" multiple>
        <input type="file" id="archivo2" name="archivo2" multiple>
       

         <label for="responsable">responsable:</label>
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


        <label for="dirigido_a">Dirijido a:</label>
<select id="dirigido_a" name="dirigido_a">
    <?php
    // Consulta para obtener todos los usuarios de la tabla usuarios
    $queryUsuarios = mysqli_query($con, "SELECT id, usuario FROM usuarios");

    // Verificar si la consulta fue exitosa
    if ($queryUsuarios) {
        // Generar las opciones del select con los usuarios
        while ($rowUsuario = mysqli_fetch_assoc($queryUsuarios)) {
            // Generar una opción para cada usuario en la base de datos
            echo '<option value="' . $rowUsuario['id'] . '">' . $rowUsuario['usuario'] . ' (ID: ' . $rowUsuario['id'] . ')</option>';
        }
    } else {
        echo '<option value="">No hay usuarios disponibles</option>';
    }
    ?>
          <option value="">Todos</option>
        </select>


        <label for="descripcion">Descripcion:</label>
        <textarea id="descripcion" name="descripcion"></textarea>


        <label for="estado">Estado:</label>
        <select id="estado" name="estado">
          <option value="2">Devolucion</option>
          <option value="3">Devolucion Finalizado</option>
         <option value="5">Derivar Trabajo</option>
        </select>

  <button type="submit" name="update">Finalizar Tarea</button>
</form>

      



</body>
</html>