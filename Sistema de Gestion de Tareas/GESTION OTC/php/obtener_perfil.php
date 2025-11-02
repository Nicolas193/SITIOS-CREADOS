<?php
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Petición inválida. Se esperaba POST.');
    }

    if (!isset($_POST['usuario']) || empty(trim($_POST['usuario']))) {
        throw new Exception('No se recibió el parámetro usuario.');
    }

    require_once("../../conexion.php");
    $conn = conectar();

    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    $usuario = trim($_POST['usuario']);
    // Opcional: $usuario = htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8');

    $stmt = $conn->prepare("SELECT usuario, sector, cargo, contacto, email, interno FROM usuarios WHERE usuario = ?");
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conn->error);
    }

    $stmt->bind_param("s", $usuario);

    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar la consulta: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Usuario no encontrado.');
    }

    $data = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    echo json_encode($data);
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => 'Error en obtener_perfil.php: ' . $e->getMessage()]);
    exit;
}
?>
