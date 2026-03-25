<?php
session_start();

// 🔒 SEGURIDAD: Solo permite el acceso si está logueado y es 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

require '../db.php';

$mensaje = '';
$tipo_mensaje = '';

// --- LÓGICA DE PROCESAMIENTO (CRUD) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id = $_POST['user_id'] ?? '';
        
        // Datos obligatorios
        $username = trim($_POST['username']);
        $nombre_completo = trim($_POST['nombre_completo']);
        $rol = $_POST['rol'];
        
        // Datos opcionales (Nuevos)
        $numerotel = trim($_POST['numerotel'] ?? '');
        $dni = trim($_POST['dni'] ?? '');
        $mail = trim($_POST['mail'] ?? '');
        
        if (empty($id)) {
            // CREAR NUEVO USUARIO
            $password = trim($_POST['password']);
            if (empty($username) || empty($password) || empty($nombre_completo)) {
                $mensaje = "Todos los campos obligatorios deben completarse.";
                $tipo_mensaje = "error";
            } else {
                // Verificar si el username ya existe
                $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
                $stmtCheck->execute([$username]);
                if ($stmtCheck->fetch()) {
                    $mensaje = "El nombre de usuario '$username' ya está en uso.";
                    $tipo_mensaje = "error";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    // Query actualizada con campos opcionales
                    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nombre_completo, rol, numerotel, dni, mail, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    if ($stmt->execute([$username, $hash, $nombre_completo, $rol, $numerotel, $dni, $mail])) {
                        $mensaje = "Usuario creado exitosamente.";
                        $tipo_mensaje = "success";
                    }
                }
            }
        } else {
            // EDITAR USUARIO EXISTENTE
            $password = trim($_POST['password']);
            if (!empty($password)) {
                // Actualizar con nueva contraseña y nuevos campos
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nombre_completo = ?, rol = ?, numerotel = ?, dni = ?, mail = ?, password = ? WHERE id = ?");
                $ex = $stmt->execute([$username, $nombre_completo, $rol, $numerotel, $dni, $mail, $hash, $id]);
            } else {
                // Actualizar SIN cambiar contraseña, pero actualizando datos opcionales
                $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nombre_completo = ?, rol = ?, numerotel = ?, dni = ?, mail = ? WHERE id = ?");
                $ex = $stmt->execute([$username, $nombre_completo, $rol, $numerotel, $dni, $mail, $id]);
            }
            $mensaje = "Usuario actualizado correctamente.";
            $tipo_mensaje = "success";
        }
    } elseif ($accion === 'borrar') {
        // BORRAR USUARIO
        $id_borrar = $_POST['id_borrar'] ?? '';
        if ($id_borrar == $_SESSION['user_id']) {
            $mensaje = "No puedes borrar tu propio usuario mientras estás en sesión.";
            $tipo_mensaje = "error";
        } elseif (!empty($id_borrar)) {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            if ($stmt->execute([$id_borrar])) {
                $mensaje = "Usuario eliminado.";
                $tipo_mensaje = "success";
            }
        }
    }
}

// Obtener la lista de usuarios para la tabla (Incluyendo los nuevos campos)
$stmt = $pdo->query("SELECT id, username, nombre_completo, rol, numerotel, dni, mail, ultimo_acceso, creado_en FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/menu.css"> 
    <link rel="stylesheet" href="../css/usuarios.css"> 
</head>
<body>

<?php include '../menu.php'; ?>

<div class="main-container">
    <div class="header-section">
        <h2><i class="fa-solid fa-users-gear"></i> Gestión de Usuarios</h2>
        <p>Administra los accesos al sistema. Las contraseñas se encriptan automáticamente.</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="grid-usuarios">
        <div class="card form-card">
            <h3 id="formTitle">Nuevo Usuario</h3>
            <form method="POST" id="formUsuario">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="user_id" id="user_id" value="">

                <div class="input-group">
                    <label>Nombre Completo *</label>
                    <input type="text" name="nombre_completo" id="nombre_completo" required placeholder="Ej: Juan Pérez">
                </div>

                <div class="input-group">
                    <label>Nombre de Usuario (Login) *</label>
                    <input type="text" name="username" id="username" required placeholder="Ej: jperez">
                </div>

                <div class="input-group">
                    <label>DNI (Opcional)</label>
                    <input type="text" name="dni" id="dni" placeholder="Ej: 12345678">
                </div>

                <div class="input-group">
                    <label>Teléfono (Opcional)</label>
                    <input type="text" name="numerotel" id="numerotel" placeholder="Ej: +54 9 11...">
                </div>

                <div class="input-group">
                    <label>Email (Opcional)</label>
                    <input type="email" name="mail" id="mail" placeholder="ejemplo@email.com">
                </div>
                <div class="input-group">
                    <label>Contraseña *</label>
                    <input type="password" name="password" id="password" placeholder="******">
                    <small id="helpPassword" style="color: #6b7280; font-size: 0.8rem;">Obligatorio para nuevos. Déjalo en blanco para mantener la actual al editar.</small>
                </div>

                <div class="input-group">
                    <label>Rol de Acceso *</label>
                    <select name="rol" id="rol" required>
                        <option value="operador">Usuario Estándar</option>
                        <option value="admin">Administrador General</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-main"><i class="fa-solid fa-floppy-disk"></i> Guardar Usuario</button>
                    <button type="button" class="btn-cancel hidden" id="btnCancelar" onclick="resetForm()">Cancelar Edición</button>
                </div>
            </form>
        </div>

        <div class="card table-card">
            <div class="table-responsive">
                <table class="table-usuarios">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre / DNI</th>
                            <th>Contacto</th>
                            <th>Rol</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($u['nombre_completo']); ?></div>
                                <?php if(!empty($u['dni'])): ?>
                                    <div style="font-size: 0.8rem; color: #6b7280;">DNI: <?php echo htmlspecialchars($u['dni']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($u['mail'])): ?>
                                    <div style="font-size: 0.85rem;"><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($u['mail']); ?></div>
                                <?php endif; ?>
                                <?php if(!empty($u['numerotel'])): ?>
                                    <div style="font-size: 0.85rem;"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($u['numerotel']); ?></div>
                                <?php endif; ?>
                                <?php if(empty($u['mail']) && empty($u['numerotel'])): ?>
                                    <span style="color: #ccc;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['rol'] === 'admin'): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user">Usuario</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.85rem; color: #6b7280;">
                                <?php echo $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca'; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon edit" title="Editar" 
                                        onclick="editarUsuario(
                                            <?php echo $u['id']; ?>, 
                                            '<?php echo htmlspecialchars($u['username']); ?>', 
                                            '<?php echo htmlspecialchars($u['nombre_completo']); ?>', 
                                            '<?php echo htmlspecialchars($u['rol']); ?>',
                                            '<?php echo htmlspecialchars($u['numerotel'] ?? ''); ?>',
                                            '<?php echo htmlspecialchars($u['dni'] ?? ''); ?>',
                                            '<?php echo htmlspecialchars($u['mail'] ?? ''); ?>'
                                        )">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de ELIMINAR este usuario? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="accion" value="borrar">
                                        <input type="hidden" name="id_borrar" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn-icon delete" title="Borrar">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($usuarios) === 0): ?>
                            <tr><td colspan="6" style="text-align:center;">No hay usuarios registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Lógica para rellenar el formulario al presionar "Editar"
    function editarUsuario(id, username, nombre, rol, numerotel, dni, mail) {
        document.getElementById('formTitle').innerText = "Editar Usuario";
        document.getElementById('user_id').value = id;
        document.getElementById('username').value = username;
        document.getElementById('nombre_completo').value = nombre;
        document.getElementById('rol').value = rol;
        
        // Rellenar nuevos campos opcionales
        document.getElementById('numerotel').value = numerotel;
        document.getElementById('dni').value = dni;
        document.getElementById('mail').value = mail;
        
        // Quitar required del password porque al editar no es obligatorio
        document.getElementById('password').required = false; 
        document.getElementById('btnCancelar').classList.remove('hidden');
        
        // Scroll hacia el formulario (útil en móviles)
        document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('formTitle').innerText = "Nuevo Usuario";
        document.getElementById('user_id').value = "";
        document.getElementById('formUsuario').reset();
        document.getElementById('password').required = true;
        document.getElementById('btnCancelar').classList.add('hidden');
    }

    // Por defecto el password es requerido para usuarios nuevos
    document.getElementById('password').required = true;
</script>

</body>
</html>