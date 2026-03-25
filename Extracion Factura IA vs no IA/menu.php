<?php
// ─────────────────────────────────────────────
//  menu.php — ROOT (mismo nivel que index.php)
//  Se incluye desde /php/*.php con:
//      require_once __DIR__ . '/../menu.php';
//
//  RUTAS CORREGIDAS:
//  • Redirect sesión → /index.php  (absoluta)
//  • auth.php        → __DIR__ . '/auth.php'  (__DIR__ = ROOT)
// ─────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ CORREGIDO: ruta absoluta para que funcione
//    al ser incluido desde cualquier subcarpeta
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

// ✅ CORREGIDO: __DIR__ apunta a ROOT (donde está este archivo)
//    así funciona sin importar desde dónde se incluya
require_once __DIR__ . '/auth.php';

$pagina = basename($_SERVER['PHP_SELF']);
$rol    = $_SESSION['rol']      ?? 'user';
$user   = $_SESSION['username'] ?? 'Usuario';

// ─────────────────────────────────────────────
//  HELPERS
// ─────────────────────────────────────────────

/** Clase 'active' por nombre de página */
function active(array|string $pages, string $current): string {
    return is_array($pages)
        ? (in_array($current, $pages, true) ? 'active' : '')
        : ($pages === $current ? 'active' : '');
}

/** Clase 'active' por página + parámetro GET */
function activeParam(string $page, string $param, string $value, string $current): string {
    return ($current === $page && ($_GET[$param] ?? '') === $value) ? 'active' : '';
}

$isAdmin    = ($rol === 'admin');
$isContador = ($rol === 'admin' || $rol === 'contador');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ════════════════════════════════════════════
   VARIABLES
════════════════════════════════════════════ */
:root {
    --nav-h:        60px;
    --nav-bg:       #0f172a;
    --nav-border:   rgba(255,255,255,.07);
    --accent:       #3b82f6;
    --accent-dark:  #2563eb;
    --accent-glow:  rgba(59,130,246,.25);
    --text:         #f1f5f9;
    --muted:        #94a3b8;
    --subtle:       #475569;
    --dd-bg:        #1e293b;
    --dd-border:    rgba(255,255,255,.08);
    --dd-shadow:    0 20px 60px rgba(0,0,0,.55);
    --dd-hover:     rgba(59,130,246,.12);
    --font:         'DM Sans', sans-serif;
    --font-mono:    'DM Mono', monospace;
    --r:            10px;
    --ease:         .2s cubic-bezier(.4,0,.2,1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: var(--font); }

/* ════════════════════════════════════════════
   NAVBAR
════════════════════════════════════════════ */
.navbar {
    position: fixed;
    inset: 0 0 auto;
    height: var(--nav-h);
    background: var(--nav-bg);
    border-bottom: 1px solid var(--nav-border);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    z-index: 1000;
    box-shadow: 0 4px 24px rgba(0,0,0,.3);
}

.nav-inner {
    display: flex;
    align-items: center;
    height: 100%;
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 20px;
    gap: 4px;
}

/* ── Brand ── */
.nav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--text);
    font-weight: 600;
    font-size: .94rem;
    white-space: nowrap;
    margin-right: 10px;
    padding: 6px 10px 6px 6px;
    border-radius: var(--r);
    transition: background var(--ease);
}
.nav-brand:hover { background: rgba(255,255,255,.05); }

.brand-icon {
    display: grid; place-items: center;
    width: 32px; height: 32px;
    background: var(--accent);
    border-radius: 8px;
    font-size: .88rem;
    box-shadow: 0 0 0 0 var(--accent-glow);
    transition: box-shadow var(--ease);
    flex-shrink: 0;
}
.nav-brand:hover .brand-icon { box-shadow: 0 0 0 5px var(--accent-glow); }

.brand-sub {
    line-height: 1;
}
.brand-sub small {
    display: block;
    font-size: .62rem;
    color: var(--muted);
    font-weight: 400;
    letter-spacing: .05em;
    text-transform: uppercase;
    margin-top: 2px;
}

/* ── Nav list ── */
.nav-list {
    display: flex;
    align-items: center;
    list-style: none;
    gap: 2px;
    flex: 1;
}

/* ── Link base ── */
.nav-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: var(--r);
    color: var(--muted);
    text-decoration: none;
    font-size: .87rem;
    font-weight: 500;
    white-space: nowrap;
    position: relative;
    transition: color var(--ease), background var(--ease);
}
.nav-link .ni { font-size: .8rem; }
.nav-link .caret { font-size: .62rem; margin-left: 2px; transition: transform var(--ease); }

.nav-link:hover,
.nav-link.active {
    color: var(--text);
    background: rgba(255,255,255,.07);
}
/* Subrayado activo */
.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 12px; right: 12px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: var(--accent);
}

/* ── Dropdown ── */
.has-dd { position: relative; }

.has-dd:hover > .nav-link,
.has-dd.open  > .nav-link {
    color: var(--text);
    background: rgba(255,255,255,.07);
}
.has-dd:hover > .nav-link .caret,
.has-dd.open  > .nav-link .caret { transform: rotate(180deg); }

.dd-panel {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    min-width: 210px;
    background: var(--dd-bg);
    border: 1px solid var(--dd-border);
    border-radius: var(--r);
    box-shadow: var(--dd-shadow);
    padding: 6px;
    list-style: none;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-6px);
    transition: opacity var(--ease), transform var(--ease), visibility var(--ease);
    z-index: 200;
}
.has-dd:hover .dd-panel,
.has-dd.open  .dd-panel {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Items del dropdown */
.dd-panel li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 7px;
    color: var(--muted);
    text-decoration: none;
    font-size: .84rem;
    font-weight: 500;
    transition: background var(--ease), color var(--ease);
}
.dd-panel li a:hover,
.dd-panel li a.active {
    background: var(--dd-hover);
    color: var(--text);
}
.dd-panel li a .ddi { width: 18px; text-align: center; font-size: .78rem; flex-shrink: 0; }

.dd-sep { height: 1px; background: var(--dd-border); margin: 5px 4px; }

/* ── Badges de rol ── */
.role-badge {
    padding: 2px 8px;
    border-radius: 99px;
    font-size: .63rem;
    font-weight: 600;
    letter-spacing: .05em;
    text-transform: uppercase;
    font-family: var(--font-mono);
    flex-shrink: 0;
}
.role-badge.admin    { background: rgba(239,68,68,.18);  color: #fca5a5; }
.role-badge.contador { background: rgba(59,130,246,.18); color: #93c5fd; }

/* ── Links de admin ── */
.nav-link.is-admin { color: #fca5a5 !important; }
.nav-link.is-admin:hover,
.nav-link.is-admin.active { background: rgba(239,68,68,.1) !important; }
.nav-link.is-admin.active::after { background: #f87171; }

/* ── User area (desktop) ── */
.user-area {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
    padding-left: 14px;
    border-left: 1px solid var(--nav-border);
    flex-shrink: 0;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 12px 5px 8px;
    border-radius: 99px;
    background: rgba(255,255,255,.05);
    border: 1px solid var(--nav-border);
    color: var(--text);
    text-decoration: none;
    font-size: .84rem;
    font-weight: 500;
    transition: background var(--ease), border-color var(--ease);
}
.user-chip:hover { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.15); }
.user-chip i { color: var(--accent); }

.btn-logout {
    display: grid; place-items: center;
    width: 34px; height: 34px;
    border-radius: 8px;
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.2);
    color: #f87171;
    text-decoration: none;
    font-size: .88rem;
    transition: background var(--ease), border-color var(--ease), transform var(--ease);
}
.btn-logout:hover { background: rgba(239,68,68,.22); border-color: rgba(239,68,68,.38); transform: scale(1.07); }

/* ── Hamburger ── */
.hamburger {
    display: none;
    background: none;
    border: 1px solid var(--nav-border);
    color: var(--text);
    width: 38px; height: 38px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    transition: background var(--ease);
    margin-left: auto;
    flex-shrink: 0;
}
.hamburger:hover { background: rgba(255,255,255,.08); }

/* ════════════════════════════════════════════
   MOBILE (≤ 900px)
════════════════════════════════════════════ */
@media (max-width: 900px) {
    .hamburger { display: grid; place-items: center; }

    .nav-list-wrap {
        position: fixed;
        inset: var(--nav-h) 0 0 0;
        background: rgba(9,14,26,.97);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 14px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 4px;
        transform: translateX(100%);
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        z-index: 999;
    }
    .nav-list-wrap.open { transform: translateX(0); }

    .nav-list {
        flex-direction: column;
        align-items: stretch;
        gap: 2px;
        width: 100%;
    }

    .nav-link { padding: 11px 14px; font-size: .91rem; }
    .nav-link.active::after { display: none; }

    /* Dropdown accordion en mobile */
    .dd-panel {
        position: static !important;
        opacity: 1 !important;
        visibility: hidden;
        transform: none !important;
        max-height: 0;
        overflow: hidden;
        transition: max-height .28s ease, visibility .28s, padding .28s;
        box-shadow: none;
        border: none;
        background: rgba(255,255,255,.04);
        margin-top: 2px;
        border-radius: 8px;
        padding: 0 4px;
    }
    .has-dd.open .dd-panel {
        visibility: visible;
        max-height: 500px;
        padding: 6px 4px;
    }

    .user-area { display: none !important; }

    /* Usuario en mobile */
    .mobile-user {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        margin-top: auto;
        border-top: 1px solid var(--nav-border);
        border-radius: var(--r);
        background: rgba(255,255,255,.04);
    }
    .mobile-user .mu-profile {
        display: flex; align-items: center; gap: 10px;
        text-decoration: none; color: var(--text);
        font-weight: 600; font-size: .9rem;
    }
    .mobile-user .mu-profile i { font-size: 1.25rem; color: var(--accent); }
    .mobile-user .mu-profile small {
        display: block; color: var(--muted);
        font-size: .73rem; font-weight: 400; margin-top: 1px;
    }
}

@media (min-width: 901px) {
    .mobile-user { display: none !important; }
    .nav-list-wrap { display: contents; }
}

/* Spacer para contenido bajo la navbar fija */
.nav-spacer { height: var(--nav-h); }
</style>

<nav class="navbar" role="navigation" aria-label="Menú principal">
    <div class="nav-inner">

        <!-- Brand -->
        <a href="comprobante.php" class="nav-brand" title="Inicio">
            <span class="brand-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
            <span class="brand-sub">
                GestoriaCristianR
                <small>Sistema de Gestión</small>
            </span>
        </a>

        <!-- Hamburger (mobile) -->
        <button class="hamburger" id="nav-ham" aria-label="Abrir menú" aria-expanded="false">
            <i class="fa-solid fa-bars" id="ham-icon"></i>
        </button>

        <!-- Drawer -->
        <div class="nav-list-wrap" id="nav-drawer">
            <ul class="nav-list">

                <!-- Inicio -->
                <li>
                    <a href="comprobante.php" class="nav-link <?= active('comprobante.php', $pagina) ?>">
                        <i class="fa-solid fa-house ni"></i> Inicio
                    </a>
                </li>

                <!-- Facturación -->
                <li class="has-dd" id="dd-fact">
                    <a href="#" class="nav-link <?= active(['cargar_factura.php','comprobante.php','miscargas.php'], $pagina) ?>"
                       data-dd="dd-fact" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-folder-open ni"></i>
                        Facturación
                        <i class="fa-solid fa-chevron-down caret"></i>
                    </a>
                    <ul class="dd-panel" role="menu">
                        <li role="menuitem">
                            <a href="comprobante.php" class="<?= active('comprobante.php', $pagina) ?>">
                                <i class="fa-solid fa-cloud-arrow-up ddi" style="color:#60a5fa;"></i>
                                Cargar Comprobante
                            </a>
                        </li>
                        <li role="menuitem">
                            <a href="miscargas.php" class="<?= active('miscargas.php', $pagina) ?>">
                                <i class="fa-solid fa-list-check ddi" style="color:#34d399;"></i>
                                Mis Cargas
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Extracto -->
                <li>
                    <a href="extracto.php" class="nav-link <?= active('extracto.php', $pagina) ?>">
                        <i class="fa-solid fa-file-invoice ni"></i> Extracto
                    </a>
                </li>

                <!-- Importar (admin + contador) -->
                <?php if ($isContador): ?>
                <li class="has-dd" id="dd-imp">
                    <a href="#" class="nav-link <?= active(['importar_excel.php','importar_historial.php'], $pagina) ?>"
                       data-dd="dd-imp" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-file-arrow-up ni"></i>
                        Importar
                        <i class="fa-solid fa-chevron-down caret"></i>
                    </a>
                    <ul class="dd-panel" role="menu">
                        <li role="menuitem">
                            <a href="importar_excel.php?tipo=afip_compras"
                               class="<?= activeParam('importar_excel.php', 'tipo', 'afip_compras', $pagina) ?>">
                                <i class="fa-solid fa-cart-shopping ddi" style="color:#f59e0b;"></i>
                                Compras AFIP
                            </a>
                        </li>
                        <li role="menuitem">
                            <a href="importar_excel.php?tipo=afip_ventas"
                               class="<?= activeParam('importar_excel.php', 'tipo', 'afip_ventas', $pagina) ?>">
                                <i class="fa-solid fa-receipt ddi" style="color:#10b981;"></i>
                                Ventas AFIP
                            </a>
                        </li>
                        <li role="menuitem">
                            <a href="importar_excel.php?tipo=mp_movimientos"
                               class="<?= activeParam('importar_excel.php', 'tipo', 'mp_movimientos', $pagina) ?>">
                                <i class="fa-brands fa-cc-mastercard ddi" style="color:#a78bfa;"></i>
                                MercadoPago
                            </a>
                        </li>
                        <li><div class="dd-sep"></div></li>
                        <li role="menuitem">
                            <a href="importar_excel.php"
                               class="<?= ($pagina==='importar_excel.php' && empty($_GET['tipo'])) ? 'active' : '' ?>">
                                <i class="fa-solid fa-clock-rotate-left ddi" style="color:#94a3b8;"></i>
                                Ver Historial
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Conciliación (admin + contador) -->
                <?php if ($isContador): ?>
                <li class="has-dd" id="dd-conc">
                    <a href="#" class="nav-link <?= active('conciliacion.php', $pagina) ?>"
                       data-dd="dd-conc" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-scale-balanced ni"></i>
                        Conciliación
                        <i class="fa-solid fa-chevron-down caret"></i>
                    </a>
                    <ul class="dd-panel" role="menu">
                        <li role="menuitem">
                            <a href="conciliacion.php"
                               class="<?= ($pagina==='conciliacion.php' && empty($_GET['tipo']) && empty($_GET['estado'])) ? 'active' : '' ?>">
                                <i class="fa-solid fa-table-columns ddi" style="color:#60a5fa;"></i>
                                Panel General
                            </a>
                        </li>
                        <li><div class="dd-sep"></div></li>
                        <li role="menuitem">
                            <a href="conciliacion.php?tipo=compra"
                               class="<?= activeParam('conciliacion.php', 'tipo', 'compra', $pagina) ?>">
                                <i class="fa-solid fa-cart-shopping ddi" style="color:#f59e0b;"></i>
                                Ver Compras
                            </a>
                        </li>
                        <li role="menuitem">
                            <a href="conciliacion.php?tipo=venta"
                               class="<?= activeParam('conciliacion.php', 'tipo', 'venta', $pagina) ?>">
                                <i class="fa-solid fa-receipt ddi" style="color:#10b981;"></i>
                                Ver Ventas
                            </a>
                        </li>
                        <li><div class="dd-sep"></div></li>
                        <li role="menuitem">
                            <a href="conciliacion.php?estado=pendiente"
                               class="<?= activeParam('conciliacion.php', 'estado', 'pendiente', $pagina) ?>">
                                <i class="fa-solid fa-circle-xmark ddi" style="color:#ef4444;"></i>
                                Pendientes
                            </a>
                        </li>
                        <li role="menuitem">
                            <a href="conciliacion.php?estado=parcial"
                               class="<?= activeParam('conciliacion.php', 'estado', 'parcial', $pagina) ?>">
                                <i class="fa-solid fa-circle-half-stroke ddi" style="color:#f59e0b;"></i>
                                Parciales
                            </a>
                        </li>
                        <li><div class="dd-sep"></div></li>
                        <li role="menuitem">
                            <a href="conciliacion.php?action=export">
                                <i class="fa-solid fa-file-excel ddi" style="color:#10b981;"></i>
                                Exportar Excel
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Informes (solo admin) -->
                <?php if ($isAdmin): ?>
                <li>
                    <a href="informes.php" class="nav-link is-admin <?= active('informes.php', $pagina) ?>">
                        <i class="fa-solid fa-chart-pie ni"></i> Informes
                    </a>
                </li>

                <!-- Usuarios (solo admin) -->
                <li>
                    <a href="usuarios.php" class="nav-link is-admin <?= active('usuarios.php', $pagina) ?>">
                        <i class="fa-solid fa-users ni"></i> Usuarios
                    </a>
                </li>
                <?php endif; ?>

            </ul><!-- /nav-list -->

            <!-- Usuario en mobile -->
            <div class="mobile-user" style="display:none;">
                <a href="perfil.php" class="mu-profile">
                    <i class="fa-solid fa-circle-user"></i>
                    <div>
                        <?= htmlspecialchars($user) ?>
                        <small><?= $isAdmin ? 'Administrador' : ($rol === 'contador' ? 'Contador' : 'Usuario') ?></small>
                    </div>
                </a>
                <a href="logout.php" class="btn-logout" title="Cerrar sesión">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>

        </div><!-- /nav-drawer -->

        <!-- Usuario en desktop -->
        <div class="user-area">
            <?php if ($isAdmin): ?>
                <span class="role-badge admin">Admin</span>
            <?php elseif ($rol === 'contador'): ?>
                <span class="role-badge contador">Contador</span>
            <?php endif; ?>
            <a href="perfil.php" class="user-chip" title="Mi perfil">
                <i class="fa-solid fa-circle-user"></i>
                <?= htmlspecialchars($user) ?>
            </a>
            <a href="logout.php" class="btn-logout" title="Cerrar sesión">
                <i class="fa-solid fa-power-off"></i>
            </a>
        </div>

    </div><!-- /nav-inner -->
</nav>

<div class="nav-spacer"></div>

<script>
(function () {
    'use strict';

    const ham    = document.getElementById('nav-ham');
    const drawer = document.getElementById('nav-drawer');
    const hamIco = document.getElementById('ham-icon');
    const BP     = 900;

    function closeDrawer() {
        drawer.classList.remove('open');
        if (ham) {
            ham.setAttribute('aria-expanded', 'false');
            hamIco.className = 'fa-solid fa-bars';
        }
        document.body.style.overflow = '';
    }

    // Hamburger
    if (ham) {
        ham.addEventListener('click', () => {
            const open = drawer.classList.toggle('open');
            ham.setAttribute('aria-expanded', open);
            hamIco.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
            document.body.style.overflow = open ? 'hidden' : '';
        });
    }

    // Dropdowns — hover en desktop, click en mobile
    document.querySelectorAll('[data-dd]').forEach(trigger => {
        trigger.addEventListener('click', e => {
            if (window.innerWidth > BP) return;
            e.preventDefault();

            const id     = trigger.dataset.dd;
            const parent = document.getElementById(id);

            // Cierra los demás
            document.querySelectorAll('.has-dd.open').forEach(el => {
                if (el.id !== id) {
                    el.classList.remove('open');
                    el.querySelector('[aria-expanded]')?.setAttribute('aria-expanded', 'false');
                }
            });

            const nowOpen = parent.classList.toggle('open');
            trigger.setAttribute('aria-expanded', nowOpen);
        });
    });

    // Clic fuera cierra el drawer
    document.addEventListener('click', e => {
        if (drawer.classList.contains('open')
            && !drawer.contains(e.target)
            && !ham.contains(e.target)) {
            closeDrawer();
        }
    });

    // Al pasar a desktop, cerrar drawer si estaba abierto
    window.addEventListener('resize', () => {
        if (window.innerWidth > BP) closeDrawer();
    });

    // Escape cierra todo
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        closeDrawer();
        document.querySelectorAll('.has-dd.open').forEach(el => el.classList.remove('open'));
    });
})();
</script>
