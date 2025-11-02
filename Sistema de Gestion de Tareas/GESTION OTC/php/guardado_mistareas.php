<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once("../../conexion.php");
$mysqli = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mistareas.php");
    exit;
}

$fecha_solicitud = $_POST['fecha_solicitud'] ?? null;
$plazo_entrega = $_POST['plazo_entrega'] ?? null;
$asunto = $_POST['asunto'] ?? null;
$id_tarea = $_POST['id_tarea'] ?? null;
$id_usuario_rest = $_POST['id_usuario_rest'] ?? null;
$usuarios_vinculados = $_POST['usuarios_vinculados'] ?? [];

if (!$fecha_solicitud || !$plazo_entrega || !$asunto || !$id_tarea || !$id_usuario_rest) {
    $error = "Faltan datos obligatorios.";
    header("Location: mistareas.php?error=" . urlencode($error));
    exit;
}

$fecha_actual = date('Y-m-d H:i:s');

// Insertar en Registro_De_Tareas (sin id_persona_asignada)
$stmt_insert = $mysqli->prepare("INSERT INTO registro_de_tareas (fecha_solicitud, plazo_entrega, asunto, ultima_modificacion, id_tarea, id_usuario_rest) VALUES (?, ?, ?, ?, ?, ?)");
$stmt_insert->bind_param("ssssii", $fecha_solicitud, $plazo_entrega, $asunto, $fecha_actual, $id_tarea, $id_usuario_rest);
$stmt_insert->execute();
$id_registro = $stmt_insert->insert_id;
$stmt_insert->close();

// Obtener id del usuario logueado desde la sesión
$id_usuario_sesion = null;
if (isset($_SESSION['username'])) {
    $usuario_actual = $_SESSION['username'];
    $stmt_usuario = $mysqli->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
    $stmt_usuario->bind_param("s", $usuario_actual);
    $stmt_usuario->execute();
    $res_usuario = $stmt_usuario->get_result();
    if ($row_usuario = $res_usuario->fetch_assoc()) {
        $id_usuario_sesion = $row_usuario['id_usuario'];
    }
    $stmt_usuario->close();
}

// Insertar estado inicial en estado_tarea con id_usuario
$stmt_estado = $mysqli->prepare("INSERT INTO estado_tarea (id_registro, id_estado, fecha_actualizacion, id_usuario) VALUES (?, 1, ?, ?)");
$stmt_estado->bind_param("isi", $id_registro, $fecha_actual, $id_usuario_sesion);
$stmt_estado->execute();
$stmt_estado->close();

// Insertar usuarios vinculados con generación de id_persona_asignada
if (count($usuarios_vinculados) > 0) {
    // Preparar sentencias para obtener usuario
    $stmt_user = $mysqli->prepare("SELECT usuario FROM usuarios WHERE id_usuario = ?");
    // Insertar usuario vinculado con id_persona_asignada
    $stmtVinc = $mysqli->prepare("INSERT INTO usuarios_vinculados (id_usuario, id_registro, id_persona_asignada) VALUES (?, ?, ?)");

    foreach ($usuarios_vinculados as $id_usuario_vinc) {
        // Obtener nombre del usuario vinculado
        $stmt_user->bind_param("i", $id_usuario_vinc);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();
        $row_user = $res_user->fetch_assoc();
        $usuario_nombre = $row_user['usuario'];

        // Obtener el máximo número actual en la tabla usuarios_vinculados
            $stmt_max = $mysqli->prepare("
                SELECT MAX(CAST(SUBSTRING_INDEX(id_persona_asignada, '_', 1) AS UNSIGNED)) AS max_num
                FROM usuarios_vinculados
                WHERE id_usuario = ?
            ");
            $stmt_max->bind_param("i", $id_usuario_vinc);

        $stmt_max->execute();
        $res_max = $stmt_max->get_result();
        $row_max = $res_max->fetch_assoc();
        $ultimo_numero = (int)($row_max['max_num'] ?? 0);
        $stmt_max->close();

        // Sumar 1 y generar id_persona_asignada
        $next_id = str_pad($ultimo_numero + 1, 3, '0', STR_PAD_LEFT);
        $id_persona_asignada = $next_id . '_' . $usuario_nombre;

        // Insertar registro en usuarios_vinculados
        $stmtVinc->bind_param("iis", $id_usuario_vinc, $id_registro, $id_persona_asignada);
        $stmtVinc->execute();
    }

    $stmt_user->close();
    $stmtVinc->close();
}


// Redirigir con éxito
header("Location: mistareas.php?success=1");
exit;
