<?php
// ─────────────────────────────────────────────
//  auth.php — ROOT (mismo nivel que index.php)
//  Incluido desde archivos dentro de /php/
//
//  ⚠️  El redirect DEBE ser absoluto (/index.php)
//      porque este archivo es INCLUIDO desde /php/*.php
//      y un redirect relativo 'index.php' resolvería
//      como '/php/index.php' en el navegador → 404.
// ─────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    // ✅ CORREGIDO: ruta absoluta para que funcione
    //    sin importar desde qué subcarpeta se incluya
    header('Location: /index.php');
    exit;
}

// Evitar que el navegador cachee páginas protegidas
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Variables de sesión disponibles en todos los archivos que incluyan auth.php
$rol  = $_SESSION['rol']      ?? 'user';
$user = $_SESSION['username'] ?? 'Usuario';
