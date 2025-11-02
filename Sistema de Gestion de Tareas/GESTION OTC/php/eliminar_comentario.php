<?php
session_start();
require_once("../../conexion.php");
$conn = conectar();

if (!isset($_SESSION['username'])) {
  die("Acceso no autorizado");
}

$usuario = mysqli_real_escape_string($conn, $_SESSION['username']);
$id_registro = intval($_POST['id_registro']);
$fecha = mysqli_real_escape_string($conn, $_POST['fecha_comentario']);

// Verificar si el comentario pertenece al usuario
$sql = "
  DELETE c FROM comentarios c
  JOIN usuarios u ON c.id_usuario = u.id_usuario
  WHERE c.id_registro = ? AND c.fecha_comentario = ? AND u.usuario = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $id_registro, $fecha, $usuario);

if ($stmt->execute()) {
  header("Location: comentariospendientes.php?id=$id_registro"); // o tu archivo de redirección
  exit();
} else {
  echo "Error al eliminar el comentario.";
}
?>
