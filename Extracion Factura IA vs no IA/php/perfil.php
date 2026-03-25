<?php
session_start();

// 🔒 SEGURIDAD: Verificar sesión iniciada
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = '';

// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar entradas
    $dni = trim($_POST['dni'] ?? '');
    $numerotel = trim($_POST['numerotel'] ?? '');
    $mail = trim($_POST['mail'] ?? '');

    try {
        // Actualizamos SOLO los campos permitidos
        $sql = "UPDATE usuarios SET dni = ?, numerotel = ?, mail = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$dni, $numerotel, $mail, $user_id])) {
            $mensaje = "¡Perfil actualizado correctamente!";
            $tipo_mensaje = "success";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// --- OBTENER DATOS ACTUALES ---
// Buscamos los datos frescos de la BD para mostrar en el formulario
$stmt = $pdo->prepare("SELECT username, nombre_completo, rol, dni, numerotel, mail FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Error: Usuario no encontrado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../css/menu.css"> 
    <link rel="stylesheet" href="../css/usuarios.css"> 
    
    <style>
        /* Ajuste específico para esta página */
        .profile-container {
            max-width: 600px; /* Más angosto que la tabla de usuarios para que se vea bien */
            margin: 0 auto;
        }
        .readonly-field {
            background-color: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<div class="main-container">
    <div class="header-section" style="text-align: center;">
        <h2><i class="fa-solid fa-id-card"></i> Mi Perfil</h2>
        <p>Visualiza y actualiza tu información de contacto.</p>
    </div>

    <div class="profile-container">
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>" style="text-align: center;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Datos Personales</h3>
            
            <form method="POST">
                
                <div class="input-group">
                    <label>Usuario (Login)</label>
                    <input type="text" value="<?php echo htmlspecialchars($usuario['username']); ?>" class="readonly-field" readonly>
                </div>

                <div class="input-group">
                    <label>Nombre Completo</label>
                    <input type="text" value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" class="readonly-field" readonly>
                    <small style="color: #9ca3af; font-size: 0.8rem;">Para cambiar su nombre, contacte al administrador.</small>
                </div>

                <div class="input-group">
                    <label>Rol</label>
                    <input type="text" value="<?php echo ucfirst($usuario['rol']); ?>" class="readonly-field" readonly>
                </div>

                <hr style="border: 0; border-top: 1px dashed #e5e7eb; margin: 25px 0;">

                <div class="input-group">
                    <label for="dni"><i class="fa-solid fa-address-card"></i> DNI</label>
                    <input type="text" name="dni" id="dni" value="<?php echo htmlspecialchars($usuario['dni']); ?>" placeholder="Ingrese su DNI">
                </div>

                <div class="input-group">
                    <label for="numerotel"><i class="fa-solid fa-phone"></i> Teléfono</label>
                    <input type="tel" name="numerotel" id="numerotel" value="<?php echo htmlspecialchars($usuario['numerotel']); ?>" placeholder="Ej: +54 9 11...">
                </div>

                <div class="input-group">
                    <label for="mail"><i class="fa-solid fa-envelope"></i> Email</label>
                    <input type="email" name="mail" id="mail" value="<?php echo htmlspecialchars($usuario['mail']); ?>" placeholder="ejemplo@email.com">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-main">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                    </button>
                    <a href="comprobante.php" class="btn-cancel" style="text-decoration: none;">
                        Volver al Inicio
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>