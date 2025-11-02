<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");
$conn = conectar();
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

$usuario = $conn->real_escape_string($_SESSION['username']);
$res = $conn->query("SELECT id_usuario FROM usuarios WHERE usuario = '$usuario' LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $id_usuario = $row['id_usuario'];
} else {
    die("No se encontró el usuario.");
}

// Procesar fechaddjj
if (!empty($_POST['fechaddjj'])) {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['fechaddjj']);
    $fechaddjj = $dt ? $dt->format('Y-m-d H:i:s') : null;  // formato DATETIME MySQL
} else {
    $fechaddjj = null;
}

$lp = isset($_POST['lp']) ? $conn->real_escape_string($_POST['lp']) : null;
$detalle = isset($_POST['detalle']) ? $conn->real_escape_string($_POST['detalle']) : null;
$respuesta = isset($_POST['respuesta']) ? $conn->real_escape_string($_POST['respuesta']) : null;
$fecharespuesta = !empty($_POST['fecharespuesta']) ? $_POST['fecharespuesta'] : null;
$id_observaciones = !empty($_POST['id_observaciones']) ? (int)$_POST['id_observaciones'] : null;
$listado_detalle_problematica = isset($_POST['listado_detalle_problematica']) ? $conn->real_escape_string($_POST['listado_detalle_problematica']) : null;

$id_clasificacion = isset($_POST['id_clasificacion']) ? (int)$_POST['id_clasificacion'] : 0;
$id_estado = isset($_POST['id_estado']) ? (int)$_POST['id_estado'] : 0;
$id_anioestado = isset($_POST['id_anioestado']) ? (int)$_POST['id_anioestado'] : 0;
$id_origen = !empty($_POST['id_origen']) ? (int)$_POST['id_origen'] : null;
$id_clasificacionconsulta = !empty($_POST['id_clasificacionconsulta']) ? (int)$_POST['id_clasificacionconsulta'] : null;
$id_accion = !empty($_POST['id_accion']) ? (int)$_POST['id_accion'] : null;

// Validación simple
if (!$fechaddjj || !$lp || !$detalle || $id_clasificacion <= 0 || $id_estado <= 0 || $id_anioestado <= 0) {
    die("Faltan datos obligatorios o están mal enviados.");
}

$stmt = $conn->prepare("INSERT INTO ddjj 
    (fechaddjj, lp, detalle, respuesta, fecharespuesta, listado_detalle_problematica,
    id_clasificacion, id_estado, id_anioestado, id_origen, id_clasificacionconsulta, id_accion, id_observaciones, id_usuario)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    die("Error preparando la consulta: " . $conn->error);
}

$stmt->bind_param(
    "ssssssiiiiiiii",
    $fechaddjj,
    $lp,
    $detalle,
    $respuesta,
    $fecharespuesta,
    $listado_detalle_problematica,
    $id_clasificacion,
    $id_estado,
    $id_anioestado,
    $id_origen,
    $id_clasificacionconsulta,
    $id_accion,
    $id_observaciones,
    $id_usuario
);

if ($stmt->execute()) {
    header("Location: ddjjconsulta.php?success=1");
    exit();
} else {
    die("Error al guardar DDJJ: " . $stmt->error);
}
