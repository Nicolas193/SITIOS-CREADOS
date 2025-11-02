<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");
$mysqli = conectar();

if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

$usuario = $mysqli->real_escape_string($_SESSION['username']);

$sql = "
SELECT 
    rt.fecha_solicitud,
    rt.plazo_entrega,
    u_responsable.usuario AS responsable,
    u_responsable.cargo AS cargo_responsable,
    u_responsable.sector AS sector_responsable,
    t.nombre_tarea AS tipo_tarea,
    rt.asunto AS descripcion,
    rt.id_registro,
    GROUP_CONCAT(DISTINCT CONCAT(uv.id_persona_asignada) ORDER BY uv.id_persona_asignada) AS id_persona_asignada,
    GROUP_CONCAT(DISTINCT CONCAT(u_encargado.usuario, ' (', u_encargado.cargo, ', ', u_encargado.sector, ')') SEPARATOR ', ') AS encargados_concatenados,

    (
      SELECT CONCAT(
          es2.nombre_estado, 
          ' (', DATE_FORMAT(et2.fecha_actualizacion, '%d/%m/%Y %H:%i'), ')'
      )
      FROM estado_tarea et2
      JOIN estados es2 ON es2.id_estado = et2.id_estado
      WHERE et2.id_registro = rt.id_registro
      ORDER BY et2.fecha_actualizacion DESC
      LIMIT 1
    ) AS ultimo_estado,

    (
      SELECT CONCAT(
          u2.usuario, ': ', 
          c2.comentario, 
          ' (', DATE_FORMAT(c2.fecha_comentario, '%d/%m/%Y %H:%i'), ')'
      )
      FROM comentarios c2
      JOIN usuarios u2 ON u2.id_usuario = c2.id_usuario
      WHERE c2.id_registro = rt.id_registro
      ORDER BY c2.fecha_comentario DESC
      LIMIT 1
    ) AS ultimo_comentario

FROM registro_de_tareas rt
JOIN tareas t ON rt.id_tarea = t.id_tarea
JOIN usuarios u_responsable ON u_responsable.id_usuario = rt.id_usuario_rest
JOIN usuarios_vinculados uv ON uv.id_registro = rt.id_registro
JOIN usuarios u_encargado ON u_encargado.id_usuario = uv.id_usuario AND u_encargado.usuario = ?
GROUP BY 
  rt.id_registro,
  rt.fecha_solicitud,
  rt.plazo_entrega,
  u_responsable.usuario,
  u_responsable.cargo,
  u_responsable.sector,
  t.nombre_tarea,
  rt.asunto
ORDER BY rt.fecha_solicitud DESC

";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Error en prepare: " . $mysqli->error);
}
$stmt->bind_param('s', $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

// Headers para descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tareas_asignadas_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

// Agregar BOM para que Excel reconozca UTF-8 correctamente
fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Encabezados CSV
fputcsv($output, [
    'ID Asignados',
    'Fecha Solicitud',
    'Plazo de Entrega',
    'Responsable',
    'Cargo Responsable',
    'Sector Responsable',
    'Tipo de Tarea',
    'Descripción',
    'Encargados',
    'Último Comentario',
    'Último Estado',
    'Días Restantes'
]);

while ($row = $resultado->fetch_assoc()) {
    $plazo = new DateTime($row['plazo_entrega']);
    $hoy = new DateTime();
    $dias_restantes = (int)$hoy->diff($plazo)->format('%r%a');
    $dias_restantes = $dias_restantes < 0 ? 0 : $dias_restantes;

    fputcsv($output, [
        $row['id_persona_asignada'],
        $row['fecha_solicitud'],
        $row['plazo_entrega'],
        $row['responsable'],
        $row['cargo_responsable'],
        $row['sector_responsable'],
        $row['tipo_tarea'],
        $row['descripcion'],
        $row['encargados_concatenados'],
        $row['ultimo_comentario'] ?? '',
        $row['ultimo_estado'] ?? '',
        $dias_restantes . ' días'
    ]);
}

fclose($output);
exit;
