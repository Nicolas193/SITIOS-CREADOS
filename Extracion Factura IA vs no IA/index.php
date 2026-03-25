<?php
// ─────────────────────────────────────────────
//  SESSION
// ─────────────────────────────────────────────
session_start();

// ✅ Si ya está logueado, redirigir al sistema
//    CORREGIDO: era 'php/index.php' (no existe) → 'php/comprobante.php'
if (!empty($_SESSION['user_id'])) {
    header("Location: php/comprobante.php");
    exit;
}

// ─────────────────────────────────────────────
//  DEPENDENCIAS
//  db.php está en la misma carpeta que index.php (ROOT)
// ─────────────────────────────────────────────
require __DIR__ . '/db.php';

$error = '';

// ─────────────────────────────────────────────
//  PROCESO DE LOGIN
// ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Por favor complete todos los campos.';
    } else {
        // Prepared statement — nunca interpolar variables en SQL
        $stmt = $pdo->prepare(
            'SELECT id, username, password, rol FROM usuarios WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Prevenir session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol']      = $user['rol'];

            // Registrar último acceso
            $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')
                ->execute([$user['id']]);

            header('Location: php/comprobante.php');
            exit;
        }

        // Mensaje genérico para no revelar si el usuario existe
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — GestoriaCristianR</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    /* ════════════════════════════════════════
       VARIABLES
    ════════════════════════════════════════ */
    :root {
        --bg:            #080e1a;
        --panel:         #0f172a;
        --panel-border:  rgba(255,255,255,.07);
        --input-bg:      #1e293b;
        --input-border:  rgba(255,255,255,.1);
        --focus:         #3b82f6;
        --accent:        #3b82f6;
        --accent-dark:   #2563eb;
        --accent-glow:   rgba(59,130,246,.3);
        --text:          #f1f5f9;
        --muted:         #94a3b8;
        --subtle:        #475569;
        --err-bg:        rgba(239,68,68,.1);
        --err-border:    rgba(239,68,68,.3);
        --err-text:      #fca5a5;
        --font:          'DM Sans', sans-serif;
        --font-display:  'DM Serif Display', serif;
        --r:             12px;
        --ease:          .2s cubic-bezier(.4,0,.2,1);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: var(--font);
        background: var(--bg);
        color: var(--text);
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    /* Fondo con gradientes radiales */
    body::before {
        content: '';
        position: fixed; inset: 0;
        background:
            radial-gradient(ellipse 70% 55% at 15% 10%,  rgba(59,130,246,.13) 0%, transparent 60%),
            radial-gradient(ellipse 55% 45% at 88% 82%,  rgba(99,102,241,.10) 0%, transparent 55%);
        pointer-events: none;
    }

    /* Grid de puntos */
    body::after {
        content: '';
        position: fixed; inset: 0;
        background-image: radial-gradient(circle, rgba(255,255,255,.055) 1px, transparent 1px);
        background-size: 26px 26px;
        pointer-events: none;
    }

    /* ════════════════════════════════════════
       CARD (split layout)
    ════════════════════════════════════════ */
    .card {
        position: relative;
        z-index: 1;
        display: flex;
        width: min(900px, 94vw);
        min-height: 520px;
        border-radius: 20px;
        border: 1px solid var(--panel-border);
        overflow: hidden;
        box-shadow: 0 40px 100px rgba(0,0,0,.55);
        animation: rise .5s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes rise {
        from { opacity: 0; transform: translateY(24px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Panel izquierdo (marca) ── */
    .side-brand {
        flex: 1;
        background: linear-gradient(145deg, #0d1f3c 0%, #091525 60%, #060d1c 100%);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 44px 40px;
        position: relative;
        overflow: hidden;
    }

    /* Círculos decorativos */
    .side-brand::before,
    .side-brand::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }
    .side-brand::before {
        width: 340px; height: 340px;
        border: 1px solid rgba(59,130,246,.14);
        top: -70px; right: -110px;
    }
    .side-brand::after {
        width: 200px; height: 200px;
        border: 1px solid rgba(59,130,246,.09);
        bottom: 30px; left: -50px;
    }

    .brand-logo {
        position: relative; z-index: 1;
    }

    .brand-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 50px; height: 50px;
        background: var(--accent);
        border-radius: 14px;
        font-size: 1.3rem;
        box-shadow: 0 8px 28px var(--accent-glow);
        margin-bottom: 22px;
    }

    .brand-logo h1 {
        font-family: var(--font-display);
        font-size: 1.75rem;
        line-height: 1.2;
        margin-bottom: 10px;
    }

    .brand-logo p {
        font-size: .855rem;
        color: var(--muted);
        line-height: 1.6;
        max-width: 250px;
    }

    /* Features list */
    .brand-features {
        position: relative; z-index: 1;
        display: flex; flex-direction: column; gap: 10px;
    }

    .feat {
        display: flex; align-items: center; gap: 10px;
        font-size: .815rem; color: var(--muted);
    }
    .feat-icon {
        width: 28px; height: 28px;
        display: grid; place-items: center;
        background: rgba(59,130,246,.12);
        border-radius: 7px;
        font-size: .72rem; color: #60a5fa;
        flex-shrink: 0;
    }

    /* ── Panel derecho (formulario) ── */
    .side-form {
        width: 360px;
        flex-shrink: 0;
        background: var(--panel);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 48px 38px;
        gap: 22px;
    }

    /* ════════════════════════════════════════
       FORMULARIO
    ════════════════════════════════════════ */
    .form-head { text-align: center; }
    .form-head h2 {
        font-size: 1.3rem; font-weight: 600;
        letter-spacing: -.02em; margin-bottom: 5px;
    }
    .form-head p { font-size: .84rem; color: var(--muted); }

    /* Error */
    .alert {
        display: flex; align-items: center; gap: 9px;
        background: var(--err-bg);
        border: 1px solid var(--err-border);
        color: var(--err-text);
        border-radius: 9px;
        padding: 10px 13px;
        font-size: .835rem; font-weight: 500;
        animation: shake .3s ease;
    }
    @keyframes shake {
        0%,100% { transform: translateX(0); }
        30%      { transform: translateX(-5px); }
        70%      { transform: translateX(5px); }
    }

    /* Campos */
    .fields { display: flex; flex-direction: column; gap: 15px; }

    .field { display: flex; flex-direction: column; gap: 5px; }

    .field label {
        font-size: .775rem; font-weight: 600;
        color: var(--muted);
        text-transform: uppercase; letter-spacing: .07em;
    }

    .input-wrap { position: relative; }

    .input-wrap .ico {
        position: absolute; left: 13px; top: 50%;
        transform: translateY(-50%);
        color: var(--subtle); font-size: .83rem;
        pointer-events: none;
        transition: color var(--ease);
    }

    .input-wrap input {
        width: 100%;
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: var(--r);
        color: var(--text);
        font-family: var(--font); font-size: .89rem;
        padding: 11px 40px 11px 38px;
        outline: none;
        transition: border-color var(--ease), box-shadow var(--ease), background var(--ease);
    }
    .input-wrap input::placeholder { color: var(--subtle); }
    .input-wrap input:focus {
        border-color: var(--focus);
        box-shadow: 0 0 0 3px rgba(59,130,246,.17);
        background: #253149;
    }
    .input-wrap:focus-within .ico { color: var(--accent); }

    /* Toggle contraseña */
    .toggle-pw {
        position: absolute; right: 11px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        color: var(--subtle); cursor: pointer;
        padding: 4px; border-radius: 5px; font-size: .8rem;
        transition: color var(--ease);
    }
    .toggle-pw:hover { color: var(--muted); }

    /* Submit */
    .btn-submit {
        width: 100%;
        padding: 12px;
        background: var(--accent); border: none;
        border-radius: var(--r);
        color: #fff; font-family: var(--font);
        font-size: .9rem; font-weight: 600;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 7px;
        box-shadow: 0 4px 18px var(--accent-glow);
        transition: background var(--ease), box-shadow var(--ease), transform var(--ease);
        margin-top: 4px;
    }
    .btn-submit:hover {
        background: var(--accent-dark);
        box-shadow: 0 6px 24px var(--accent-glow);
        transform: translateY(-1px);
    }
    .btn-submit:active { transform: translateY(0); }

    /* Footer */
    .form-footer {
        text-align: center;
        font-size: .76rem; color: var(--subtle);
        padding-top: 4px;
        border-top: 1px solid var(--panel-border);
    }

    /* ════════════════════════════════════════
       RESPONSIVE — stack en mobile
    ════════════════════════════════════════ */
    @media (max-width: 680px) {
        .card { flex-direction: column; min-height: auto; }
        .side-brand { padding: 30px 28px; min-height: auto; }
        .brand-features { display: none; }
        .side-form { width: 100%; padding: 32px 28px; }
    }
    </style>
</head>
<body>

<div class="card">

    <!-- ── Panel de marca ── -->
    <div class="side-brand">
        <div class="brand-logo">
            <div class="brand-icon">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <h1>Gestoria<br>CristianR</h1>
            <p>Sistema integral de gestión de facturación y conciliación contable.</p>
        </div>

        <div class="brand-features">
            <div class="feat">
                <div class="feat-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                Carga de comprobantes AFIP
            </div>
            <div class="feat">
                <div class="feat-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                Conciliación automática
            </div>
            <div class="feat">
                <div class="feat-icon"><i class="fa-solid fa-file-excel"></i></div>
                Exportación a Excel
            </div>
            <div class="feat">
                <div class="feat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                Acceso por roles y permisos
            </div>
        </div>
    </div>

    <!-- ── Panel de formulario ── -->
    <div class="side-form">

        <div class="form-head">
            <h2>Iniciar sesión</h2>
            <p>Ingresá tus credenciales para acceder</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>

            <div class="fields">

                <div class="field">
                    <label for="username">Usuario</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user ico"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Tu nombre de usuario"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username"
                            required>
                    </div>
                </div>

                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock ico"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Tu contraseña"
                            autocomplete="current-password"
                            required>
                        <button type="button" class="toggle-pw" aria-label="Mostrar contraseña" id="toggle-pw">
                            <i class="fa-solid fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

            </div>

            <button type="submit" class="btn-submit" style="margin-top: 20px;">
                <i class="fa-solid fa-right-to-bracket"></i>
                Ingresar al sistema
            </button>

        </form>

        <p class="form-footer">
            &copy; <?= date('Y') ?> GestoriaCristianR &mdash; Acceso restringido
        </p>

    </div>
</div>

<script>
// Toggle mostrar/ocultar contraseña
document.getElementById('toggle-pw').addEventListener('click', function () {
    const input   = document.getElementById('password');
    const icon    = document.getElementById('eye-icon');
    const visible = input.type === 'text';
    input.type    = visible ? 'password' : 'text';
    icon.className = visible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    this.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
});
</script>

</body>
</html>
