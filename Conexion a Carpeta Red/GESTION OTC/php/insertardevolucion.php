<?php
include("../../AutenticadorUser.php"); 

$id = $_GET['id'];
$url = "informeregistro.php";

if (isset($_POST['update'])) {

    $fecha_solicitud = $_POST['fecha_solicitud'];
    $responsable = $_POST['responsable'];
    $plazoentrega = $_POST['plazoentrega'];
    $tipo_tarea = $_POST['tipo_tarea'];
    $descripcion = $_POST['descripcion'];
    $dirigido_a = $_POST['dirigido_a'];
    $estado = $_POST['estado'];
    $id_persona_asignada = $_POST['id_persona_asignada'];

    // Manejo de archivos
    $archivo_temporal = $_FILES['archivo']['tmp_name'];
    $archivo_temporal2 = $_FILES['archivo2']['tmp_name'];

    // Generar nombres de archivo con timestamp para evitar conflictos de nombres
    $timestamp = time();
    $nombre_archivo = $timestamp . "_" . $_FILES['archivo']['name'];
    $nombre_archivo2 = $timestamp . "_" . $_FILES['archivo2']['name'];

    // Directorio local para guardar los archivos
    $directorio_local = "../../Almacenamiento/";

    // Directorio compartido para guardar los archivos
    $directorio_compartido = "\\\\10.70.150.4\\Grupos\\TablerosTableau\\GOCC NO BORRAR\\Nicolas Maciel\\Almacenamiento\\";

    // Rutas de destino para los archivos
    $destino_local = $directorio_local . $nombre_archivo;
    $destino_compartido = $directorio_compartido . $nombre_archivo;

    // Mover el primer archivo al directorio local
    if (move_uploaded_file($archivo_temporal, $destino_local)) {
        // Mover el primer archivo a la ubicación compartida
        if (copy($destino_local, $destino_compartido)) {
            // Archivo copiado correctamente a la ubicación compartida
        } else {
            // Error al copiar el archivo a la ubicación compartida
            echo "Error al copiar el primer archivo a la ubicación compartida.";
        }
    } else {
        // Error al mover el archivo al directorio local
        echo "Error al mover el primer archivo al directorio local.";
    }

    // Mover el segundo archivo al directorio local
    if (move_uploaded_file($archivo_temporal2, $directorio_local . $nombre_archivo2)) {
        // Mover el segundo archivo a la ubicación compartida
        if (copy($directorio_local . $nombre_archivo2, $directorio_compartido . $nombre_archivo2)) {
            // Archivo copiado correctamente a la ubicación compartida
        } else {
            // Error al copiar el archivo a la ubicación compartida
            echo "Error al copiar el segundo archivo a la ubicación compartida.";
        }
    } else {
        // Error al mover el archivo al directorio local
        echo "Error al mover el segundo archivo al directorio local.";
    }

    // Resto del código para la inserción en la base de datos
    $sql = "INSERT INTO registrodetareas VALUES(NULL, '$fecha_solicitud', '$plazoentrega', '$responsable', '$tipo_tarea', '$descripcion', '$nombre_archivo', '$nombre_archivo2', '$dirigido_a', '$estado', '0', '$id_persona_asignada')";

    $query = mysqli_query($con, $sql);

    if ($query) {
        Header("Location: informeregistro.php");
    } else {
        echo "Error al actualizar los datos en la base de datos";
    }
}

# obtener los datos de la fila específica que se va a editar

$sql1 = "SELECT 
    t1.id as id,
    t1.fecha_solicitud,
    t3.usuario as responsable,
    t1.responsable as idresponsable,
    t1.descripcion,
    t1.archivos,
    t1.archivos2,
    t4.tarea as tipo_tarea,
    t1.tipo_tarea as idtipo_tarea,
    t2.usuario as dirigido_a,
    t1.dirigido_a as iddirigido_a,
    t1.id_persona_asignada
  FROM registrodetareas as t1
  left join usuarios as t2 on(t2.id = t1.dirigido_a)
  left join usuarios as t3 on(t3.id = t1.responsable)
  left join tarea as t4 on(t4.id = t1.tipo_tarea)
  WHERE t1.id='$id'";

$query = mysqli_query($con,$sql1);
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
  <a href="informeregistro.php" class="volver-btn">Volver</a><br><br><br>

      <label for="id_persona_asignada">Registro Tarea:</label>
      <input type="text" name="id_persona_asignada" value="<?php echo $row['id_persona_asignada']; ?>" readonly>

      <label for="tipo_tarea">Tipo de Tarea:</label>
      <input type="text" name="tipo_tarea" value="<?php echo $row['idtipo_tarea']; ?>" readonly>

        <label for="fecha_solicitud">Fecha de Evaluacion</label>
        <input type="date" id="fecha_solicitud" name="fecha_solicitud">


         <label for="responsable">Responsable de Evaluar:</label>
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
        <label for="dirigido_a">Compartir tarea:</label>
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
        </select>


        <label for="descripcion">Descripcion:</label>
        <textarea id="descripcion" name="descripcion"></textarea>



        <label for="estado">Estado:</label>
        <select id="estado" name="estado">
          <option value="1">Finalizar</option>
          <option value="4">Devolver Finaliza</option>
          <option value="5">Derivar</option>
          <option value="6">Corregir</option>
        </select>

       <label for="archivo">Adjuntar archivos</label>
        <input type="file" id="archivo" name="archivo" multiple>
        <input type="file" id="archivo2" name="archivo2" multiple>
       

  <button type="submit" name="update">Evaluar</button>
</form>

      



</body>
</html>