<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../conexion.php");

if (!isset($_SESSION['username'])) {
    die("Acceso denegado.");
}

$mysqli = conectar();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID inválido.");
}

// validación extra opcional
$stmt = $mysqli->prepare("SELECT id FROM ddjj WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Registro no encontrado.");
}
$stmt->close();

// eliminar
$stmt = $mysqli->prepare("DELETE FROM ddjj WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ddjjconsulta.php?deleted=1");
    exit;
} else {
    die("Error al eliminar: " . $stmt->error);
}
?>
