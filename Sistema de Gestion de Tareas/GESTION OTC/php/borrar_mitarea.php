<?php
require_once("../../conexion.php");

$conexion = conectar();

if (!$conexion) {
    die("Error: No se pudo conectar a la base de datos.");
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "DELETE FROM registro_de_tareas WHERE id_registro = ?";
    $stmt = mysqli_prepare($conexion, $sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (mysqli_stmt_execute($stmt)) {
        header('Location: mistareas.php?msg=delete_success');
        exit;
    } else {
        echo "Error al borrar la tarea: " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
} else {
    echo "ID de tarea no especificado.";
}

mysqli_close($conexion);
?>
