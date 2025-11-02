<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");
$conexion = conectar();

if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

$usuario = mysqli_real_escape_string($conexion, $_SESSION['username']);
$registros_por_pagina = 1000;
$offset = 0;

$sql = "
SELECT 
    MAX(rt.fecha_solicitud) AS fecha_solicitud,
    MAX(rt.plazo_entrega) AS plazo_entrega,
    MAX(u_responsable.usuario) AS responsable,
    MAX(u_responsable.cargo) AS cargo_responsable,
    MAX(u_responsable.sector) AS sector_responsable,
    MAX(t.nombre_tarea) AS tipo_tarea,
    MAX(rt.asunto) AS descripcion,
    rt.id_registro,
    GROUP_CONCAT(DISTINCT CASE 
        WHEN uv.id_usuario = (SELECT id_usuario FROM usuarios WHERE usuario = '$usuario' LIMIT 1) 
        THEN uv.id_persona_asignada 
        ELSE NULL 
    END SEPARATOR '') AS id_persona_asignada,
    GROUP_CONCAT(DISTINCT CONCAT(u_encargado.usuario, ' (', u_encargado.cargo, ', ', u_encargado.sector, ')') SEPARATOR ', ') AS encargados_concatenados
FROM registro_de_tareas rt
JOIN tareas t ON rt.id_tarea = t.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
JOIN estado_tarea et ON et.id_registro = rt.id_registro
WHERE et.id_estado = 1
  AND rt.id_registro IN (
    SELECT uv2.id_registro
    FROM usuarios_vinculados uv2
    JOIN usuarios u2 ON u2.id_usuario = uv2.id_usuario
    WHERE u2.usuario = '$usuario'
  )
GROUP BY rt.id_registro
ORDER BY fecha_solicitud DESC
";


$result = mysqli_query($conexion, $sql);
if (!$result) {
    die("Error en la consulta: " . mysqli_error($conexion));
}

// Encabezados para CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tareas_exportadas.csv');

// Salida CSV
$output = fopen('php://output', 'w');

// Agregar BOM para que Excel reconozca UTF-8 correctamente
fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Cabeceras
fputcsv($output, [
    'Fecha Solicitud', 'Plazo Entrega', 'Responsable', 'Cargo Responsable', 'Sector Responsable', 
    'Tipo de Tarea', 'Descripción', 'Encargados'
]);

// Filas
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['fecha_solicitud'],
        $row['plazo_entrega'],
        $row['responsable'],
        $row['cargo_responsable'],
        $row['sector_responsable'],
        $row['tipo_tarea'],
        $row['descripcion'],
        $row['encargados_concatenados']
    ]);
}

fclose($output);
exit;
?>
