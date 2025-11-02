<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../../AutenticadorUser.php");
require_once("../../conexion.php");
$conn = conectar();

// Validar datos recibidos
$id_registro = isset($_POST['id_registro']) ? intval($_POST['id_registro']) : 0;
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

if ($id_registro <= 0 || empty($comentario)) {
    die("Datos inválidos o incompletos.");
}

// Obtener nombre de usuario desde sesión
$nombre_usuario = $_SESSION['username'] ?? $_SESSION['user'] ?? null;

if (!$nombre_usuario) {
    die("Usuario no autenticado.");
}

// Buscar id_usuario en base al nombre de usuario
$sql_usuario = "SELECT id_usuario FROM usuarios WHERE usuario = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("s", $nombre_usuario);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows === 0) {
    die("Usuario no encontrado.");
}

$row_usuario = $result_usuario->fetch_assoc();
$id_usuario = $row_usuario['id_usuario'];

// Insertar el comentario en la base de datos
$sql_insert = "INSERT INTO comentarios (id_registro, id_usuario, comentario, fecha_comentario) VALUES (?, ?, ?, NOW())";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("iis", $id_registro, $id_usuario, $comentario);

if ($stmt_insert->execute()) {
    // Redirigir al detalle de la tarea nuevamente
    header("Location: comentariospendientes.php?id=$id_registro");
    exit();
} else {
    die("Error al guardar el comentario.");
}
?>
