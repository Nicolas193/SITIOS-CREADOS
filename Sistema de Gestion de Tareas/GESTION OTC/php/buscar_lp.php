<?php
header('Content-Type: application/json; charset=utf-8');

// Log de errores en archivo 
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/buscar_lp_errors.log');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Helper para responder JSON y terminar
function responder(array $data) {
    echo json_encode($data);
    exit;
}

// 1) Validar parámetro lp
if (empty($_GET['lp'])) {
    responder(['error' => 'Parámetro lp faltante']);
}
$lp = trim($_GET['lp']);

// 2) Conectar a la base
require_once(__DIR__ . '/../../conexion.php');
$mysqli = conectar();

if (!$mysqli) {
    responder(['error' => 'Error al conectar a la base de datos']);
}

// 3) Preparar y ejecutar consulta
$stmt = $mysqli->prepare("
    SELECT lp, grado, apellido, nombre, dni, correo, telasignado, dependencia
    FROM nominaddjj
    WHERE lp = ?
    LIMIT 1
");

if (!$stmt) {
    responder(['error' => 'Error en la preparación de la consulta: ' . $mysqli->error]);
}

$stmt->bind_param("s", $lp);
$stmt->execute();

$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    responder([
        'lp' => $row['lp'],
        'grado' => $row['grado'],
        'apellido' => $row['apellido'],
        'nombre' => $row['nombre'],
        'dni' => $row['dni'],
        'correo' => $row['correo'],
        'telasignado' => $row['telasignado'],
        'dependencia' => $row['dependencia'],
    ]);
} else {
    responder(['error' => 'LP no encontrado']);
}

$stmt->close();
$mysqli->close();
