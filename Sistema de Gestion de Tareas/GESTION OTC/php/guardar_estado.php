<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");
$conn = conectar();

date_default_timezone_set('America/Argentina/Buenos_Aires');

// Verifico que el usuario esté logueado (por username)
if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

$id_registro = $_POST['id_registro'] ?? null;
$id_estado = $_POST['id_estado'] ?? null;

if (!$id_registro || !$id_estado) {
    die("Faltan datos obligatorios para cambiar el estado.");
}

$username = $_SESSION['username'];

// Obtener id_usuario del usuario logueado
$stmt_user = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$usuario = $res_user->fetch_assoc();
$stmt_user->close();

if (!$usuario) {
    die("Usuario no encontrado en la base de datos.");
}

$id_usuario = $usuario['id_usuario'];

// Obtener nombre del usuario para el comentario
$nombre_usuario = $username;

// Obtener nombre del nuevo estado
$stmt_estado = $conn->prepare("SELECT nombre_estado FROM estados WHERE id_estado = ?");
$stmt_estado->bind_param("i", $id_estado);
$stmt_estado->execute();
$res_estado = $stmt_estado->get_result();
$estado = $res_estado->fetch_assoc();
$stmt_estado->close();

if (!$estado) {
    die("Estado no encontrado.");
}

$nombre_estado = $estado['nombre_estado'];

// Insertar nuevo estado en estado_tarea
$fecha_actual = date("Y-m-d H:i:s");
$stmt_insert = $conn->prepare("
    INSERT INTO estado_tarea (id_registro, id_estado, fecha_actualizacion, id_usuario)
    VALUES (?, ?, ?, ?)
");
$stmt_insert->bind_param("iisi", $id_registro, $id_estado, $fecha_actual, $id_usuario);
$stmt_insert->execute();
$stmt_insert->close();

// Insertar comentario automático en comentarios
$comentario = "El usuario " . $nombre_usuario . " cambió el estado a " . $nombre_estado . ".";

$stmt_com = $conn->prepare("
    INSERT INTO comentarios (id_registro, comentario, fecha_comentario, id_usuario)
    VALUES (?, ?, ?, ?)
");
$stmt_com->bind_param("sssi", $id_registro, $comentario, $fecha_actual, $id_usuario);
$stmt_com->execute();
$stmt_com->close();


// Redirigir al detalle de la tarea
header("Location: comentariospendientes.php?id=" . $id_registro);
exit;
