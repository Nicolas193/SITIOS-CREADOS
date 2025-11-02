<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");

if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

date_default_timezone_set('America/Argentina/Buenos_Aires');
$mysqli = conectar();

$usuario = $mysqli->real_escape_string($_SESSION['username']);

// 🔁 BLOQUE DE ACTUALIZACIÓN Y REDIRECCIÓN
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['guardar'])) {
    $sector = $mysqli->real_escape_string($_POST['sector']);
    $cargo = $mysqli->real_escape_string($_POST['cargo']);
    $contacto = $mysqli->real_escape_string($_POST['contacto']);
    $interno = $mysqli->real_escape_string($_POST['interno']);
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email inválido.");
    }

    $sitiocolor = (int)$_POST['sitiocolor'];
    // Permitimos 1, 2 o 3
    if (!in_array($sitiocolor, [1, 2, 3])) {
        $sitiocolor = 1;
    }

    $sql_update = "
        UPDATE usuarios SET
            sector = '$sector',
            cargo = '$cargo',
            contacto = '$contacto',
            interno = '$interno',
            email = '$email',
            sitiocolor = $sitiocolor
        WHERE usuario = '$usuario'
    ";
    $mysqli->query($sql_update);

    // 🚀 REDIRECCIÓN para aplicar cambios de tema
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ✅ Cargamos datos del usuario
$result = $mysqli->query("
    SELECT usuario, tipo, sector, cargo, contacto, interno, email, sitiocolor 
    FROM usuarios 
    WHERE usuario = '$usuario' 
    LIMIT 1
");

if (!$result || $result->num_rows === 0) {
    die("Usuario no encontrado.");
}

$data = $result->fetch_assoc();

// ✅ Cargar menú después de header()
require_once("../../menu.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/miusuario.css" />
    <title>Mi Perfil</title>
</head>
<body>
<div class="perfil-container">
  <div class="avatar-circle"><?= strtoupper(substr($data['usuario'], 0, 1)) ?></div>
  <h2>Mi Perfil</h2>

  <form method="post">
    <div class="campos-grid">
      <div class="campo">
        <label>Usuario</label>
        <input type="text" value="<?= htmlspecialchars($data['usuario']) ?>" disabled>
      </div>

      <div class="campo">
        <label>Tipo (no editable)</label>
        <input type="text" value="<?= htmlspecialchars($data['tipo']) ?>" disabled>
      </div>

      <div class="campo">
        <label>Sector</label>
        <input type="text" name="sector" value="<?= htmlspecialchars($data['sector']) ?>">
      </div>

      <div class="campo">
        <label>Cargo</label>
        <input type="text" name="cargo" value="<?= htmlspecialchars($data['cargo']) ?>">
      </div>

      <div class="campo">
        <label>Contacto</label>
        <input type="text" name="contacto" value="<?= htmlspecialchars($data['contacto']) ?>">
      </div>

      <div class="campo">
        <label>Interno</label>
        <input type="text" name="interno" value="<?= htmlspecialchars($data['interno']) ?>">
      </div>

      <div class="campo">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>">
      </div>


      <div class="campo" style="align-items:flex-start;">
        <label>Tema del sitio</label>
        <div>
          <div class="tema-opcion">
            <input type="radio" id="tema_claro" name="sitiocolor" value="1" <?= $data['sitiocolor'] == 1 ? 'checked' : '' ?>>
            <label for="tema_claro">
              <img src="../../imagen/tema_claro_preview.png" alt="Tema Claro">
              Claro
            </label>
          </div>

          <div class="tema-opcion">
            <input type="radio" id="tema_negro" name="sitiocolor" value="2" <?= $data['sitiocolor'] == 2 ? 'checked' : '' ?>>
            <label for="tema_negro">
              <img src="../../imagen/tema_negro_preview.png" alt="Tema Negro">
              Negro
            </label>
          </div>

          <div class="tema-opcion">
            <input type="radio" id="tema_moderno" name="sitiocolor" value="3" <?= $data['sitiocolor'] == 3 ? 'checked' : '' ?>>
            <label for="tema_moderno">
              <img src="../../imagen/tema_moderno_preview.png" alt="Tema Moderno">
              Moderno
            </label>
          </div>
        </div>
      </div>
    </div>

    <button type="submit" name="guardar" class="boton-guardar">Guardar Cambios</button>
  </form>
</div>
</body>
</html>
