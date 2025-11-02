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

$sql = "
SELECT 
    rt.fecha_solicitud,
    rt.plazo_entrega,
    u_responsable.usuario AS responsable,
    u_responsable.cargo AS cargo_responsable,
    u_responsable.sector AS sector_responsable,
    t.nombre_tarea AS tipo_tarea,
    rt.asunto AS descripcion,
    GROUP_CONCAT(DISTINCT CONCAT(u_encargado.usuario, ' (', u_encargado.cargo, ', ', u_encargado.sector, ')') SEPARATOR ', ') AS encargados_concatenados,
    es.nombre_estado AS ultimo_estado,
    rt.plazo_entrega AS fecha_limite,
    u_registrador.usuario AS registrado_por
FROM registro_de_tareas rt
JOIN tareas t ON rt.id_tarea = t.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario
JOIN (
    -- Traigo solo el último estado por id_registro
    SELECT et1.*
    FROM estado_tarea et1
    INNER JOIN (
        SELECT id_registro, MAX(fecha_actualizacion) AS max_fecha
        FROM estado_tarea
        GROUP BY id_registro
    ) ult ON et1.id_registro = ult.id_registro AND et1.fecha_actualizacion = ult.max_fecha
) et ON et.id_registro = rt.id_registro
JOIN estados es ON es.id_estado = et.id_estado
LEFT JOIN (
    -- Traigo el primer usuario que registró el registro (fecha mínima)
    SELECT et2.id_registro, et2.id_usuario
    FROM estado_tarea et2
    INNER JOIN (
        SELECT id_registro, MIN(fecha_actualizacion) AS min_fecha
        FROM estado_tarea
        GROUP BY id_registro
    ) primero ON et2.id_registro = primero.id_registro AND et2.fecha_actualizacion = primero.min_fecha
) primer_estado ON primer_estado.id_registro = rt.id_registro
LEFT JOIN usuarios u_registrador ON u_registrador.id_usuario = primer_estado.id_usuario
WHERE u_responsable.usuario = '$usuario'
GROUP BY 
    rt.id_registro,
    rt.fecha_solicitud,
    rt.plazo_entrega,
    u_responsable.usuario,
    u_responsable.cargo,
    u_responsable.sector,
    t.nombre_tarea,
    rt.asunto,
    es.nombre_estado,
    u_registrador.usuario
ORDER BY rt.fecha_solicitud DESC

";

$resultado = mysqli_query($conexion, $sql);
if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conexion));
}

// Configurar headers para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tareas_asignadas_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

// Agregar BOM para que Excel reconozca UTF-8 correctamente
fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Encabezados CSV
fputcsv($output, [
    'Fecha Solicitud',
    'Plazo de Entrega',
    'Responsable',
    'Cargo Responsable',
    'Sector Responsable',
    'Tipo de Tarea',
    'Descripción',
    'Encargados',
    'Último Estado',
    'Tiempo Restante',
    'Registrado por'
]);

// Filas de datos
while ($row = mysqli_fetch_assoc($resultado)) {
    $fecha_plazo = new DateTime($row['fecha_limite']);
    $hoy = new DateTime();
    $dias_restantes = (int)$hoy->diff($fecha_plazo)->format('%r%a');
    $dias_texto = ($dias_restantes < 0 ? 0 : $dias_restantes) . ' días';

    fputcsv($output, [
        $row['fecha_solicitud'],
        $row['plazo_entrega'],
        $row['responsable'],
        $row['cargo_responsable'],
        $row['sector_responsable'],
        $row['tipo_tarea'],
        $row['descripcion'],
        $row['encargados_concatenados'],
        $row['ultimo_estado'],
        $dias_texto,
        $row['registrado_por']
    ]);
}

fclose($output);
exit;
?>
