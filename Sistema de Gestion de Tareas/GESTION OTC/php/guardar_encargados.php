<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");
$conn = conectar();

date_default_timezone_set('America/Argentina/Buenos_Aires');

// Verifico que el usuario esté logueado
if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

$id_registro = isset($_POST['id_registro']) ? (int)$_POST['id_registro'] : 0;
$id_usuario_a_agregar = isset($_POST['usuario_vinculado']) ? (int)$_POST['usuario_vinculado'] : 0;

if ($id_registro <= 0) {
    die("ID de registro inválido.");
}

if ($id_usuario_a_agregar <= 0) {
    die("No se seleccionó un usuario válido para agregar.");
}

$username = $_SESSION['username'];

// Obtener id_usuario del usuario logueado
$stmt_user = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$usuario_actual = $res_user->fetch_assoc();
$stmt_user->close();

if (!$usuario_actual) {
    die("Usuario no encontrado en la base de datos.");
}

$id_usuario_actual = $usuario_actual['id_usuario'];

// Verificar que la tarea existe
$stmt_check = $conn->prepare("SELECT COUNT(*) FROM registro_de_tareas WHERE id_registro = ?");
$stmt_check->bind_param("i", $id_registro);
$stmt_check->execute();
$stmt_check->bind_result($count);
$stmt_check->fetch();
$stmt_check->close();

if ($count == 0) {
    die("Error: El registro de tarea no existe.");
}

// Verificar que el usuario a agregar no esté ya vinculado
$stmt_exist = $conn->prepare("SELECT COUNT(*) FROM usuarios_vinculados WHERE id_registro = ? AND id_usuario = ?");
$stmt_exist->bind_param("ii", $id_registro, $id_usuario_a_agregar);
$stmt_exist->execute();
$stmt_exist->bind_result($exists);
$stmt_exist->fetch();
$stmt_exist->close();

if ($exists > 0) {
    die("El usuario ya está vinculado a esta tarea.");
}

// Obtener nombre del usuario a agregar
$stmt_nombre = $conn->prepare("SELECT usuario FROM usuarios WHERE id_usuario = ?");
$stmt_nombre->bind_param("i", $id_usuario_a_agregar);
$stmt_nombre->execute();
$res_nombre = $stmt_nombre->get_result();
$usuario_a_agregar = $res_nombre->fetch_assoc();
$stmt_nombre->close();

$nombre_usuario_a_agregar = $usuario_a_agregar ? $usuario_a_agregar['usuario'] : 'usuario';

// Contar cuántas veces ya fue asignado para crear id_persona_asignada
$stmt_count = $conn->prepare("SELECT COUNT(*) AS frecuencia FROM usuarios_vinculados WHERE id_usuario = ?");
$stmt_count->bind_param("i", $id_usuario_a_agregar);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$row_count = $res_count->fetch_assoc();
$stmt_count->close();

$frecuencia = ($row_count['frecuencia'] ?? 0) + 1;
$next_id = str_pad($frecuencia, 3, '0', STR_PAD_LEFT);
$id_persona_asignada = $next_id . '_' . $nombre_usuario_a_agregar;

// Insertar nuevo usuario vinculado
$stmt_insert = $conn->prepare("INSERT INTO usuarios_vinculados (id_usuario, id_registro, id_persona_asignada) VALUES (?, ?, ?)");
$stmt_insert->bind_param("iis", $id_usuario_a_agregar, $id_registro, $id_persona_asignada);

if (!$stmt_insert->execute()) {
    die("Error al insertar usuario vinculado: " . $stmt_insert->error);
}

$stmt_insert->close();

// Insertar comentario automático en comentarios
$fecha_actual = date("Y-m-d H:i:s");

$comentario = "El usuario " . $username . " agregó a " . $nombre_usuario_a_agregar . " como encargado.";

$stmt_com = $conn->prepare("
    INSERT INTO comentarios (id_registro, comentario, fecha_comentario, id_usuario)
    VALUES (?, ?, ?, ?)
");
$stmt_com->bind_param("sssi", $id_registro, $comentario, $fecha_actual, $id_usuario_actual);

if (!$stmt_com->execute()) {
    die("Error al insertar comentario: " . $stmt_com->error);
}

$stmt_com->close();

// Redirigir al detalle de la tarea
header("Location: comentariospendientes.php?id=" . $id_registro);
exit;
