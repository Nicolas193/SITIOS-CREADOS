<?php
include("../../conexion.php");

$con = conectar();

$fecha_solicitud = $_POST['fecha_solicitud'];
$plazoentrega = $_POST['plazoentrega'];
$responsable = $_POST['responsable'];
$tipo_tarea = $_POST['tipo_tarea'];
$descripcion = $_POST['descripcion'];
$dirigido_a = $_POST['dirigido_a'];
$fecha = new DateTime($fecha_solicitud); // Convertir la fecha de solicitud a un objeto DateTime
$fecha_formateada = $fecha->format('d-m-Y'); // Formatear la fecha al formato dd-mm-yyyy

// Manejo de archivos
$archivo_temporal = $_FILES['archivo']['tmp_name'];
$archivo_temporal2 = $_FILES['archivo2']['tmp_name'];

// Obtener el nombre de usuario correspondiente al dirigido_a
$query_usuario = "SELECT usuario FROM usuarios WHERE id = '$dirigido_a'";
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

// Nombre de archivo para el primer archivo
$nombre_archivo1 = "";
if (!empty($_FILES['archivo']['name'])) {
    $nombre_archivo1 = $fecha_formateada . "_" . $id_persona_asignada  . "_" . "Soli" . "_" . $_FILES['archivo']['name'];
}

// Nombre de archivo para el segundo archivo
$nombre_archivo2 = "";
if (!empty($_FILES['archivo2']['name'])) {
    $nombre_archivo2 = $fecha_formateada . "_" . $id_persona_asignada  . "_" . "Rest". "_" . $_FILES['archivo2']['name'];
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
    $destino_red1 = "\\\\10.70.150.4\\Grupos\\TablerosTableau\\GOCC NO BORRAR\\Almacenamiento\\{$usuario}\\" . $nombre_archivo1;
    $destino_red2 = "\\\\10.70.150.4\\Grupos\\TablerosTableau\\GOCC NO BORRAR\\Almacenamiento\\{$usuario}\\" . $nombre_archivo2;

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
$sql = "INSERT INTO registrodetareas VALUES(NULL, '$fecha_solicitud', '$plazoentrega', '$responsable', '$tipo_tarea', '$descripcion', '$nombre_archivo1', '$nombre_archivo2', '$dirigido_a', '0', '0', '$id_persona_asignada')";
$query = mysqli_query($con, $sql);

if ($query) {
    header("Location: registro.php");
} else {
    echo "ERROR AL INSERTAR VALORES";
}
?>
