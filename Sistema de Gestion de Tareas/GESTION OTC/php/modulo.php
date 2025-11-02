<?php
ob_start();
session_start();

require_once("../../conexion.php");
require_once("../../menu.php");

// Validar sesión antes que nada
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    die(json_encode(["error" => "Usuario no autenticado."]));
}

// Zona horaria Argentina
date_default_timezone_set('America/Argentina/Buenos_Aires');

$mysqli = conectar();

$usuario = $mysqli->real_escape_string($_SESSION['username']);
$tipoUsuario = strtolower(trim($_SESSION['tipo'] ?? ''));
$sectorUsuario = trim($_SESSION['sector'] ?? '');

// Obtener ID de usuario
$usuarioQuery = $mysqli->query("SELECT id_usuario FROM usuarios WHERE usuario = '$usuario'");
$id_usuario = 0;
if ($usuarioQuery && ($row = $usuarioQuery->fetch_assoc())) {
    $id_usuario = (int)$row['id_usuario'];
}

if (!$id_usuario) {
    http_response_code(404);
    die(json_encode(["error" => "No se encontró el ID del usuario actual ($usuario)"]));
}

// FUNCIONES
function contarTareas($mysqli, $id_usuario, $estadoId = null) {
    $estadoFiltro = $estadoId !== null ? "AND et.id_estado = " . intval($estadoId) : "";
    $query = "
        SELECT COUNT(DISTINCT rt.id_registro) as total
        FROM registro_de_tareas rt
        LEFT JOIN (
            SELECT id_registro, id_estado
            FROM estado_tarea et1
            WHERE fecha_actualizacion = (
                SELECT MAX(fecha_actualizacion)
                FROM estado_tarea et2
                WHERE et1.id_registro = et2.id_registro
            )
        ) et ON rt.id_registro = et.id_registro
        WHERE rt.id_usuario_rest = $id_usuario
        $estadoFiltro
    ";
    if ($res = $mysqli->query($query)) {
        return (int)$res->fetch_assoc()['total'];
    }
    return 0;
}

function contarVinculadas($mysqli, $id_usuario) {
    $query = "SELECT COUNT(*) as total FROM usuarios_vinculados WHERE id_usuario = $id_usuario";
    if ($res = $mysqli->query($query)) {
        return (int)$res->fetch_assoc()['total'];
    }
    return 0;
}

function tareasPorEstadoPie($mysqli, $id_usuario) {
    $estados = [1 => 'Listo', 23456378 => 'En curso', 9 => 'Detenido'];
    $resultado = [];
    foreach ($estados as $id => $nombre) {
        $resultado[$nombre] = contarTareas($mysqli, $id_usuario, $id);
    }
    return $resultado;
}

function tareasPorResponsable($mysqli, $id_usuario) {
    $query = "
        SELECT uv.id_usuario, COUNT(*) as total
        FROM usuarios_vinculados uv
        JOIN registro_de_tareas rt ON uv.id_registro = rt.id_registro
        WHERE rt.id_usuario_rest = $id_usuario
        GROUP BY uv.id_usuario
    ";
    $datos = [];
    if ($res = $mysqli->query($query)) {
        while ($row = $res->fetch_assoc()) {
            $datos[$row['id_usuario']] = (int)$row['total'];
        }
    }
    return $datos;
}

function tareasVencidas($mysqli, $id_usuario) {
    $query = "
        SELECT
            SUM(CASE WHEN DATEDIFF(plazo_entrega, NOW()) < 0 AND et.id_estado NOT IN (1,11) THEN 1 ELSE 0 END) AS vencidas,
            SUM(CASE WHEN DATEDIFF(plazo_entrega, NOW()) >= 0 AND et.id_estado NOT IN (1,11) THEN 1 ELSE 0 END) AS por_vencer
        FROM registro_de_tareas rt
        LEFT JOIN (
            SELECT id_registro, id_estado
            FROM estado_tarea et1
            WHERE fecha_actualizacion = (
                SELECT MAX(fecha_actualizacion)
                FROM estado_tarea et2
                WHERE et1.id_registro = et2.id_registro
            )
        ) et ON rt.id_registro = et.id_registro
        WHERE rt.id_usuario_rest = $id_usuario
    ";
    if ($res = $mysqli->query($query)) {
        return $res->fetch_assoc();
    }
    return ['vencidas' => 0, 'por_vencer' => 0];
}

function tareasPorVencimientoMensual($mysqli, $id_usuario) {
    $query = "
        SELECT
            DATE_FORMAT(plazo_entrega, '%Y-%m') as mes,
            et.id_estado,
            COUNT(*) as total
        FROM registro_de_tareas rt
        LEFT JOIN (
            SELECT id_registro, id_estado
            FROM estado_tarea et1
            WHERE fecha_actualizacion = (
                SELECT MAX(fecha_actualizacion)
                FROM estado_tarea et2
                WHERE et1.id_registro = et2.id_registro
            )
        ) et ON rt.id_registro = et.id_registro
        WHERE rt.id_usuario_rest = $id_usuario
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

// DATOS
$todas = contarTareas($mysqli, $id_usuario);
$listas = contarTareas($mysqli, $id_usuario, 1);
$enCurso = contarTareas($mysqli, $id_usuario, 23456378);
$detenidas = contarTareas($mysqli, $id_usuario, 9);
$vinculadas = contarVinculadas($mysqli, $id_usuario);
$porEstado = tareasPorEstadoPie($mysqli, $id_usuario);
$responsables = tareasPorResponsable($mysqli, $id_usuario);
$vencidas = tareasVencidas($mysqli, $id_usuario);
$mensual = tareasPorVencimientoMensual($mysqli, $id_usuario);

// RESPUESTA JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'totales' => [
        'todas' => $todas,
        'listas' => $listas,
        'enCurso' => $enCurso,
        'detenidas' => $detenidas,
        'vinculadas' => $vinculadas
    ],
    'porEstado' => $porEstado,
    'responsables' => $responsables,
    'vencidas' => $vencidas,
    'porMes' => $mensual
]);

// DEBUG OPCIONAL: comentar si no quieres que se muestre
/*
$result = $mysqli->query("SELECT COUNT(*) as total FROM registro_de_tareas WHERE id_usuario_rest = $id_usuario");
echo "\nTareas del usuario registradas: ";
var_dump($result->fetch_assoc());
*/

ob_end_flush();
?>
