<?php
// Mostrar errores para debug (quitar en producción)
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
$tipoUsuario = strtolower(trim($_SESSION['tipo']));
$sectorUsuario = trim($_SESSION['sector']);
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

// Funciones
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

function contarTareasEnCurso($mysqli, $id_usuario, $estados = []) {
    if (empty($estados)) return 0;
    $estadoFiltro = "AND et.id_estado IN (" . implode(',', array_map('intval', $estados)) . ")";
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

function tareasPorResponsable($mysqli, $id_usuario) {
    $query = "
        SELECT u.usuario, COUNT(*) as total
        FROM usuarios_vinculados uv
        JOIN registro_de_tareas rt ON uv.id_registro = rt.id_registro
        JOIN usuarios u ON uv.id_usuario = u.id_usuario
        WHERE rt.id_usuario_rest = $id_usuario
        GROUP BY u.usuario
    ";
    $datos = [];
    if ($res = $mysqli->query($query)) {
        while ($row = $res->fetch_assoc()) {
            $datos[$row['usuario']] = (int)$row['total'];
        }
    }
    return $datos;
}

function tareasEnCursoPorResponsable($mysqli, $id_usuario) {
    $estadosEnCurso = [2,3,4,5,6,7,8];
    $estadoFiltro = "AND et.id_estado IN (" . implode(',', $estadosEnCurso) . ")";
    $query = "
        SELECT u.usuario, COUNT(*) as total
        FROM usuarios_vinculados uv
        JOIN registro_de_tareas rt ON uv.id_registro = rt.id_registro
        JOIN usuarios u ON uv.id_usuario = u.id_usuario
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
        GROUP BY u.usuario
    ";
    $datos = [];
    if ($res = $mysqli->query($query)) {
        while ($row = $res->fetch_assoc()) {
            $datos[$row['usuario']] = (int)$row['total'];
        }
    }
    return $datos;
}

function tareasFinalizadasPorResponsable($mysqli, $id_usuario) {
    $query = "
        SELECT u.usuario, COUNT(*) as total
        FROM usuarios_vinculados uv
        JOIN registro_de_tareas rt ON uv.id_registro = rt.id_registro
        JOIN usuarios u ON uv.id_usuario = u.id_usuario
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
        AND et.id_estado = 1
        GROUP BY u.usuario
    ";
    $datos = [];
    if ($res = $mysqli->query($query)) {
        while ($row = $res->fetch_assoc()) {
            $datos[$row['usuario']] = (int)$row['total'];
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

// Cálculo de totales
$todas = contarTareas($mysqli, $id_usuario);
$listas = contarTareas($mysqli, $id_usuario, 1);
$enCurso = contarTareasEnCurso($mysqli, $id_usuario, [2,3,4,5,6,7,8]);
$detenidas = contarTareas($mysqli, $id_usuario, 9);
$canceladas = contarTareas($mysqli, $id_usuario, 11); // NUEVO
$vinculadas = contarVinculadas($mysqli, $id_usuario);

// Pie por estado incluyendo canceladas
$porEstado = [
    'Listo' => $listas,
    'En curso' => $enCurso,
    'Vencido' => $detenidas,
    'Canceladas' => $canceladas // NUEVO
];

$responsables = tareasPorResponsable($mysqli, $id_usuario);
$enCursoResp = tareasEnCursoPorResponsable($mysqli, $id_usuario);
$finalizadasResp = tareasFinalizadasPorResponsable($mysqli, $id_usuario);
$vencidas = tareasVencidas($mysqli, $id_usuario);
$mensual = tareasPorVencimientoMensual($mysqli, $id_usuario);

// Salida JSON
echo json_encode([
    'totales' => [
        'todas' => $todas,
        'listas' => $listas,
        'enCurso' => $enCurso,
        'detenidas' => $detenidas,
        'canceladas' => $canceladas, // NUEVO
        'vinculadas' => $vinculadas
    ],
    'porEstado' => $porEstado,
    'responsables' => $responsables,
    'responsablesEnCurso' => $enCursoResp,
    'responsablesFinalizadas' => $finalizadasResp,
    'vencidas' => $vencidas,
    'porMes' => $mensual
]);
exit;
