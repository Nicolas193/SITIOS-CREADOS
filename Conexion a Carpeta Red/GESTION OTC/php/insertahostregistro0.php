<?php
include("../../conexion.php");

$con = conectar();

$fecha_solicitud = $_POST['fecha_solicitud'];
$plazoentrega = $_POST['plazoentrega'];
$responsable = $_POST['responsable'];
$tipo_tarea = $_POST['tipo_tarea'];
$descripcion = $_POST['descripcion'];
$dirigido_a = $_POST['dirigido_a'];
$fecha= new datetime();
$timestamp = $fecha->getTimestamp();
$archivos=$timestamp."_".$_FILES['archivo']['name'];
$archivos2=$timestamp."_".$_FILES['archivo2']['name'];


// Manejo de archivos
$archivo_temporal = $_FILES['archivo']['tmp_name'];
$archivo_temporal2 = $_FILES['archivo2']['tmp_name'];

// Obtener el nombre de usuario correspondiente al dirigido_a
$query_usuario = "SELECT usuario FROM usuarios WHERE id = '$responsable'";
$result_usuario = mysqli_query($con, $query_usuario);
$row_usuario = mysqli_fetch_assoc($result_usuario);
$usuario = $row_usuario['usuario'];

// Lógica para asignar id_persona_asignada
$query_frecuencia = "SELECT COUNT(*) as frecuencia FROM registrodetareas as t1 
                    LEFT JOIN usuarios as t2 ON t2.id = t1.dirigido_a
                    WHERE t2.usuario = '$usuario'";
$result_frecuencia = mysqli_query($con, $query_frecuencia);
$row_frecuencia = mysqli_fetch_assoc($result_frecuencia);
$frecuencia = $row_frecuencia['frecuencia'];

// Incrementamos la frecuencia obtenida
$next_id = str_pad($frecuencia + 1, 3, '0', STR_PAD_LEFT);

// Creamos el nuevo id_persona_asignada utilizando el nombre de usuario
$id_persona_asignada = $next_id . '_' . $usuario;

// Directorio en el disco C: donde se guardará la carpeta del usuario
$directorio_usuario = "../../Almacenamiento/{$usuario}/";

// Crear la carpeta del usuario si no existe
if (!file_exists($directorio_usuario)) {
    mkdir($directorio_usuario, 0777, true);
}

// ID de tarea para incluir en el nombre del archivo
$id_tarea = str_pad($tipo_tarea, 3, '0', STR_PAD_LEFT);

// Nombre de archivo para el primer archivo
$nombre_archivo1 = $fecha_formateada . "_" . $id_persona_asignada  . "_" . "Soli" . "_" . $archivos;

// Nombre de archivo para el segundo archivo
$nombre_archivo2 = $fecha_formateada . "_" . $id_persona_asignada  . "_" . "Rest". "_" . $archivos2;

// Rutas de destino para los archivos
$destino1 = $directorio_usuario . $nombre_archivo1;
$destino2 = $directorio_usuario . $nombre_archivo2;

// Mover los archivos al directorio del usuario en el disco C:
move_uploaded_file($archivo_temporal, $destino1);
move_uploaded_file($archivo_temporal2, $destino2);

// Resto del código para la inserción en la base de datos
$sql = "INSERT INTO registrodetareas VALUES(NULL, '$fecha_solicitud','$plazoentrega', '$dirigido_a', '$tipo_tarea', '$descripcion', '$nombre_archivo1', '$nombre_archivo2', '$responsable', '1', '0', '$id_persona_asignada')";
$query = mysqli_query($con, $sql);

if ($query) {
    header("Location: insertar.php");
} else {
    echo "ERROR AL INSERTAR VALORES";
}
?>
