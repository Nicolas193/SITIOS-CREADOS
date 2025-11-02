<?php

include("../../conexion.php");
$con = conectar();

$id = $_GET['id'];
$url = "registro.php";

if (isset($_POST['update'])) {
    # Actualización de los campos de la base de datos
    $id = $_POST['id'];
    $fecha_solicitud = $_POST['fecha_solicitud'];
    $plazoentrega = $_POST['plazoentrega'];
    $responsable = $_POST['responsable'];
    $tipo_tarea = $_POST['tipo_tarea'];
    $descripcion = $_POST['descripcion'];
    $dirigido_a = $_POST['dirigido_a'];

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

    $sql = "UPDATE registrodetareas SET fecha_solicitud='$fecha_solicitud', plazoentrega='$plazoentrega' , responsable='$responsable', tipo_tarea='$tipo_tarea', descripcion='$descripcion', dirigido_a='$dirigido_a', id_persona_asignada='$id_persona_asignada' WHERE id='$id'";
    $query = mysqli_query($con, $sql);

    if ($query) {
        Header("Location: registro.php");
    } else {
        echo "Error al actualizar los datos en la base de datos";
    }
}

# Obtener los datos de la fila específica que se va a editar
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
    <form method="POST">

        <a href="registro.php" class="volver-btn">Volver</a><br><br><br>
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
    <?php
    // Consulta para obtener todas las tareas de la tabla 'tarea'
    $tareatipo = mysqli_query($con, "SELECT id, tarea FROM tarea");

    // Verificar si la consulta fue exitosa
    if ($tareatipo) {
        // Generar las opciones del select con los resultados obtenidos
        while ($tareat = mysqli_fetch_assoc($tareatipo)) {
            // Generar una opción para cada tarea en la base de datos
            echo '<option value="' . $tareat['id'] . '">' . $tareat['tarea'] . '</option>';
        }
    } else {
        echo '<option value="">No hay tareas disponibles</option>';
    }
    ?>
    <option value="">Todos</option>
</select>


        <label for="descripcion">Descripcion:</label>
        <textarea id="descripcion" name="descripcion"><?php echo $row['descripcion']; ?></textarea>


        <label for="dirigido_a">Dirigido a:</label>
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
