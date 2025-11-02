<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once("../../conexion.php");

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuario no autenticado."]);
    exit;
}

date_default_timezone_set('America/Argentina/Buenos_Aires');
$mysqli = conectar();

$usuario = $mysqli->real_escape_string($_SESSION['username']);
$usuarioQuery = $mysqli->query("SELECT id_usuario FROM usuarios WHERE usuario = '$usuario'");
$id_usuario = 0;
if ($usuarioQuery && ($row = $usuarioQuery->fetch_assoc())) {
    $id_usuario = (int)$row['id_usuario'];
}
if (!$id_usuario) {
    http_response_code(404);
    echo json_encode(["error" => "No se encontró el ID del usuario actual ($usuario)"]);
    exit;
}

function contarTareasVinculadas($mysqli, $id_usuario, $estadoId = null) {
    $estadoFiltro = $estadoId !== null ? "AND et.id_estado = " . intval($estadoId) : "";
    $query = "
        SELECT COUNT(DISTINCT uv.id_registro) as total
        FROM usuarios_vinculados uv
        LEFT JOIN registro_de_tareas rt ON uv.id_registro = rt.id_registro
        LEFT JOIN (
            SELECT id_registro, id_estado
            FROM estado_tarea et1
            WHERE fecha_actualizacion = (
                SELECT MAX(fecha_actualizacion)
                FROM estado_tarea et2
                WHERE et1.id_registro = et2.id_registro
            )
        ) et ON uv.id_registro = et.id_registro
        WHERE uv.id_usuario = $id_usuario
        $estadoFiltro
    ";
    if ($res = $mysqli->query($query)) {
        return (int)$res->fetch_assoc()['total'];
    }
    return 0;
}

function contarTareasEnCursoVinculadas($mysqli, $id_usuario, $estados = []) {
    if (empty($estados)) return 0;
    $estadoFiltro = "AND et.id_estado IN (" . implode(',', array_map('intval', $estados)) . ")";
    $query = "
        SELECT COUNT(DISTINCT uv.id_registro) as total
        FROM usuarios_vinculados uv
        LEFT JOIN registro_de_tareas rt ON uv.id_registro = rt.id_registro
        LEFT JOIN (
            SELECT id_registro, id_estado
            FROM estado_tarea et1
            WHERE fecha_actualizacion = (
                SELECT MAX(fecha_actualizacion)
                FROM estado_tarea et2
                WHERE et1.id_registro = et2.id_registro
            )
        ) et ON uv.id_registro = et.id_registro
        WHERE uv.id_usuario = $id_usuario
        $estadoFiltro
    ";
    if ($res = $mysqli->query($query)) {
        return (int)$res->fetch_assoc()['total'];
    }
    return 0;
}

function tareasPorEstadoPieVinculadas($mysqli, $id_usuario) {
    return [
        'Listo' => contarTareasVinculadas($mysqli, $id_usuario, 1),
        'En curso' => contarTareasEnCursoVinculadas($mysqli, $id_usuario, [2,3,4,5,6,7,8]),
        'Vencido' => contarTareasVinculadas($mysqli, $id_usuario, 9),
        'Canceladas' => contarTareasVinculadas($mysqli, $id_usuario, 11),
    ];
}

function tareasVencidasVinculadas($mysqli, $id_usuario) {
    $query = "
        SELECT
            SUM(CASE WHEN DATEDIFF(plazo_entrega, NOW()) < 0 AND et.id_estado NOT IN (1,11) THEN 1 ELSE 0 END) AS vencidas,
            SUM(CASE WHEN DATEDIFF(plazo_entrega, NOW()) >= 0 AND et.id_estado NOT IN (1,11) THEN 1 ELSE 0 END) AS por_vencer
        FROM usuarios_vinculados uv
        LEFT JOIN registro_de_tareas rt ON uv.id_registro = rt.id_registro
        LEFT JOIN (
            SELECT id_registro, id_estado
            FROM estado_tarea et1
            WHERE fecha_actualizacion = (
                SELECT MAX(fecha_actualizacion)
                FROM estado_tarea et2
                WHERE et1.id_registro = et2.id_registro
            )
        ) et ON uv.id_registro = et.id_registro
        WHERE uv.id_usuario = $id_usuario
    ";
    if ($res = $mysqli->query($query)) {
        return $res->fetch_assoc();
    }
    return ['vencidas' => 0, 'por_vencer' => 0];
}

function tareasPorVencimientoMensualVinculadas($mysqli, $id_usuario) {
    $query = "
        SELECT
            DATE_FORMAT(plazo_entrega, '%Y-%m') as mes,
            et.id_estado,
            COUNT(DISTINCT uv.id_registro) as total
        FROM usuarios_vinculados uv
        LEFT JOIN registro_de_tareas rt ON uv.id_registro = rt.id_registro
        LEFT JOIN (
            SELECT id_registro, id_estado
            FROM estado_tarea et1
            WHERE fecha_actualizacion = (
                SELECT MAX(fecha_actualizacion)
                FROM estado_tarea et2
                WHERE et1.id_registro = et2.id_registro
            )
        ) et ON uv.id_registro = et.id_registro
        WHERE uv.id_usuario = $id_usuario
          AND DATEDIFF(plazo_entrega, NOW()) >= 0
        GROUP BY mes, et.id_estado
    ";
    $datos = [];
    if ($res = $mysqli->query($query)) {
        while ($row = $res->fetch_assoc()) {
            $datos[$row['mes']][$row['id_estado']] = (int)$row['total'];
        }
    }
    return $datos;
}

// Calcular totales usando funciones para tareas vinculadas
$todas = contarTareasVinculadas($mysqli, $id_usuario);
$listas = contarTareasVinculadas($mysqli, $id_usuario, 1);
$enCurso = contarTareasEnCursoVinculadas($mysqli, $id_usuario, [2,3,4,5,6,7,8]);
$detenidas = contarTareasVinculadas($mysqli, $id_usuario, 9);
$canceladas = contarTareasVinculadas($mysqli, $id_usuario, 11);
$porEstado = tareasPorEstadoPieVinculadas($mysqli, $id_usuario);
$vencidas = tareasVencidasVinculadas($mysqli, $id_usuario);
$mensual = tareasPorVencimientoMensualVinculadas($mysqli, $id_usuario);

// Respuesta JSON
echo json_encode([
    'totales' => [
        'todas' => $todas,
        'listas' => $listas,
        'enCurso' => $enCurso,
        'detenidas' => $detenidas,
        'canceladas' => $canceladas,
    ],
    'porEstado' => $porEstado,
    'vencidas' => $vencidas,
    'porMes' => $mensual,
]);
exit;
