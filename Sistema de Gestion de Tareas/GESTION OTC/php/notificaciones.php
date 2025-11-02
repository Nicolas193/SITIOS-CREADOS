<?php
session_start();
header('Content-Type: application/json');
include("../../conexion.php");

$con = conectar();
$mensajes = [];

// Verificamos si hay usuario logueado
if (!isset($_SESSION['username'])) {
    echo json_encode([]);
    exit;
}

$responsable = $_SESSION['username'];
$queryResponsable = mysqli_query($con, "SELECT id_usuario FROM usuarios WHERE usuario = '$responsable'");

if (!$queryResponsable || mysqli_num_rows($queryResponsable) === 0) {
    echo json_encode([]);
    exit;
}

$rowResponsable = mysqli_fetch_assoc($queryResponsable);
$usuario = $rowResponsable['id_usuario'];

// ==============================
// CONSULTA 1: Tareas pendientes por responder (tipo Evaluador o Ambos)
// ==============================
$sqlPendientes = "
SELECT rt.id_registro
FROM registro_de_tareas rt
JOIN tareas t ON rt.id_tarea = t.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
JOIN (
    SELECT et1.*
    FROM estado_tarea et1
    INNER JOIN (
        SELECT id_registro, MAX(fecha_actualizacion) AS max_fecha
        FROM estado_tarea
        GROUP BY id_registro
    ) ult ON et1.id_registro = ult.id_registro AND et1.fecha_actualizacion = ult.max_fecha
) est ON est.id_registro = rt.id_registro
JOIN estados es ON es.id_estado = est.id_estado
WHERE es.nombre_estado NOT IN ('Finalizado', 'Detenida')
  AND (LOWER(TRIM(es.tipo)) IN ('evaluador', 'ambos'))
  AND uv.id_usuario = '$usuario'
GROUP BY rt.id_registro
";

$queryPendientes = mysqli_query($con, $sqlPendientes);
$cantidadPendientes = mysqli_num_rows($queryPendientes);

if ($cantidadPendientes > 0) {
    $mensajes[] = "Tienes $cantidadPendientes tareas pendientes por responder. Puedes verlas desde <a href='https://otcegestion.seguridadciudad.gob.ar/GESTION%20OTC/php/pendientes.php' target='_blank'>AQUÍ</a>.";
}

// ==============================
// CONSULTA 2: Tareas para corregir (tipo Evaluado o Ambos)
// ==============================
$sqlCorregir = "
SELECT rt.id_registro
FROM registro_de_tareas rt
JOIN tareas t ON rt.id_tarea = t.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
JOIN (
    SELECT et1.*
    FROM estado_tarea et1
    INNER JOIN (
        SELECT id_registro, MAX(fecha_actualizacion) AS max_fecha
        FROM estado_tarea
        GROUP BY id_registro
    ) ult ON et1.id_registro = ult.id_registro AND et1.fecha_actualizacion = ult.max_fecha
) est ON est.id_registro = rt.id_registro
JOIN estados es ON es.id_estado = est.id_estado
WHERE es.nombre_estado <> 'Finalizado'
  AND (LOWER(TRIM(es.tipo)) IN ('evaluado', 'ambos'))
  AND rt.id_usuario_rest = '$usuario'
GROUP BY rt.id_registro
";

$queryCorregir = mysqli_query($con, $sqlCorregir);
$cantidadCorregir = mysqli_num_rows($queryCorregir);

if ($cantidadCorregir > 0) {
    $mensajes[] = "Tienes $cantidadCorregir respuestas de tareas por corregir. Puedes acceder directamente desde <a href='https://otcegestion.seguridadciudad.gob.ar/GESTION%20OTC/php/respuestatareas.php' target='_blank'>AQUÍ</a>.";
}

// Devolver mensajes
echo json_encode($mensajes);
?>
