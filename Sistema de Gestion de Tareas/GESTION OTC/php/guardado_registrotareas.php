<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once("../../conexion.php");
$mysqli = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: registro.php");
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
    header("Location: registro.php?error=" . urlencode($error));
    exit;
}

$fecha_actual = date('Y-m-d H:i:s');

// 1. Insertar en registro_de_tareas
$stmt_insert = $mysqli->prepare("
    INSERT INTO registro_de_tareas 
    (fecha_solicitud, plazo_entrega, asunto, ultima_modificacion, id_tarea, id_usuario_rest) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt_insert->bind_param("ssssii", $fecha_solicitud, $plazo_entrega, $asunto, $fecha_actual, $id_tarea, $id_usuario_rest);
$stmt_insert->execute();
$id_registro = $stmt_insert->insert_id;
$stmt_insert->close();

// 2. Obtener id_usuario de sesión
$id_usuario_sesion = null;
if (isset($_SESSION['username'])) {
    $usuario_actual = $_SESSION['username'];
    $stmt_user_sesion = $mysqli->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
    $stmt_user_sesion->bind_param("s", $usuario_actual);
    $stmt_user_sesion->execute();
    $res_user_sesion = $stmt_user_sesion->get_result();
    if ($row = $res_user_sesion->fetch_assoc()) {
        $id_usuario_sesion = $row['id_usuario'];
    }
    $stmt_user_sesion->close();
}

// 3. Insertar estado inicial en estado_tarea (ID = 8 por ejemplo)
if ($id_usuario_sesion !== null) {
    $stmt_estado = $mysqli->prepare("
        INSERT INTO estado_tarea (id_registro, id_estado, fecha_actualizacion, id_usuario)
        VALUES (?, 8, ?, ?)
    ");
    $stmt_estado->bind_param("isi", $id_registro, $fecha_actual, $id_usuario_sesion);
    $stmt_estado->execute();
    $stmt_estado->close();
} else {
    $stmt_estado = $mysqli->prepare("
        INSERT INTO estado_tarea (id_registro, id_estado, fecha_actualizacion)
        VALUES (?, 8, ?)
    ");
    $stmt_estado->bind_param("is", $id_registro, $fecha_actual);
    $stmt_estado->execute();
    $stmt_estado->close();
}

// 4. Insertar usuarios vinculados con id_persona_asignada individual
if (count($usuarios_vinculados) > 0) {
    $stmt_user = $mysqli->prepare("SELECT usuario FROM usuarios WHERE id_usuario = ?");
    $stmtVinc = $mysqli->prepare("INSERT INTO usuarios_vinculados (id_usuario, id_registro, id_persona_asignada) VALUES (?, ?, ?)");

    foreach ($usuarios_vinculados as $id_usuario_vinc) {
        // Obtener nombre del usuario
        $stmt_user->bind_param("i", $id_usuario_vinc);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();
        $row_user = $res_user->fetch_assoc();
        $usuario_nombre = $row_user['usuario'];

        // Obtener máximo número individual por usuario
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

        // Generar id_persona_asignada
        $next_id = str_pad($ultimo_numero + 1, 3, '0', STR_PAD_LEFT);
        $id_persona_asignada = $next_id . '_' . $usuario_nombre;

        // Insertar
        $stmtVinc->bind_param("iis", $id_usuario_vinc, $id_registro, $id_persona_asignada);
        $stmtVinc->execute();
    }

    $stmt_user->close();
    $stmtVinc->close();
}

// 5. Redirigir
header("Location: registro.php?success=1");
exit;
