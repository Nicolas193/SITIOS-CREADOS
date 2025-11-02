<?php
require_once("../../conexion.php");

header('Content-Type: application/json; charset=utf-8');

$mysqli = conectar();
$datos = [];

$query = "SELECT lp, grado, apellido, nombre, dni, correo, telasignado, dependencia FROM nominaddjj ORDER BY lp DESC LIMIT 100";

if ($result = $mysqli->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
    $result->free();
} else {
    // Enviar mensaje de error en caso de fallo
    http_response_code(500);
    echo json_encode([
        'error'   => 'Error al ejecutar la consulta SQL.',
        'detalle' => $mysqli->error
    ]);
    $mysqli->close();
    exit;
}

$mysqli->close();

echo json_encode($datos);
exit;
