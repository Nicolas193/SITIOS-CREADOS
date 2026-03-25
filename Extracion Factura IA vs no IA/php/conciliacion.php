<?php
// ============================================================
//  CargaFacturas/php/conciliacion.php  –  Punto de entrada
//  Todo en la misma carpeta php/. Sin subcarpetas.
// ============================================================
declare(strict_types=1);

// db.php está en la misma carpeta → crea $pdo global
require_once __DIR__ . '/../db.php';

// Cargar modelo y controlador (misma carpeta)
require_once __DIR__ . '/conciliacion_model.php';
require_once __DIR__ . '/conciliacion_controller.php';

$ctrl = new DashboardController();
$ctrl->manejarRequest();
