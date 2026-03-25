<?php
// ─────────────────────────────────────────────
//  env.php — Carga variables de entorno desde .env
//  Busca .env en el directorio raíz del proyecto
// ─────────────────────────────────────────────

function cargarEnv(): void {
    // Buscar .env en el directorio raíz (un nivel arriba de /php/)
    $envFile = dirname(__DIR__) . '/.env';
    if (!$envFile || !file_exists($envFile)) {
        $envFile = __DIR__ . '/.env'; // fallback: mismo directorio
    }
    if (!file_exists($envFile)) return;

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        if (!getenv($key)) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

cargarEnv();
