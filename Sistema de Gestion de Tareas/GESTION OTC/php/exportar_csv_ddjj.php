<?php
session_start();
require_once("../../conexion.php");

if (!isset($_SESSION['username'])) {
    die("Usuario no autenticado.");
}

$mysqli = conectar();

// Consulta adaptada al CSV
$query = "
    SELECT 
        d.fechaddjj, d.lp, d.detalle, c.descripcion as clasificacion_desc,
        o.descripcion as origen_desc, u.usuario as usuario_nombre,
        d.respuesta, d.fecharespuesta, cc.descripcion as consulta_desc,
        ac.descripcion as accion_desc, es.descripcion as estado_desc,
        ae.anio as anioestado_val, ob.observacion as observacion_text,
        d.listado_detalle_problematica, n.grado, n.apellido, n.nombre as nombre_nomina,
        n.dni, n.correo, n.telasignado, n.dependencia
    FROM ddjj d
    LEFT JOIN clasificacionddjj c ON d.id_clasificacion = c.id_clasificacion
    LEFT JOIN origenddjj o ON d.id_origen = o.id_origen
    LEFT JOIN usuarios u ON d.id_usuario = u.id_usuario
    LEFT JOIN clasificacionesconsultaddjj cc ON d.id_clasificacionconsulta = cc.id_clasificacionconsulta
    LEFT JOIN accionddjj ac ON d.id_accion = ac.id_accion
    LEFT JOIN estadoddjj es ON d.id_estado = es.id_estado
    LEFT JOIN anioestadoddjj ae ON d.id_anioestado = ae.id_anioestado
    LEFT JOIN observacionesddjj ob ON d.id_observaciones = ob.id_observaciones
    LEFT JOIN nominaddjj n ON d.lp = n.lp
    ORDER BY d.id DESC
";

$resultado = $mysqli->query($query);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ddjj_registro_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

// Agregar BOM para que Excel reconozca UTF-8 correctamente
fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Encabezados CSV
fputcsv($output, [
    'Fecha Consulta DDJJ', // <- CAMBIADO ESTE NOMBRE
    'LP', 'Detalle', 'Clasificación', 'Origen', 'Responsable',
    'Respuesta', 'Fecha Respuesta', 'Clasificación Consulta', 'Acción', 'Estado',
    'Año Estado', 'Observaciones', 'Listado Problemática',
    'Grado', 'Apellido', 'Nombre', 'DNI', 'Correo', 'Tel Asignado', 'Dependencia'
]);


while ($row = $resultado->fetch_assoc()) {
    fputcsv($output, [
        $row['fechaddjj'],
        $row['lp'],
        $row['detalle'],
        $row['clasificacion_desc'],
        $row['origen_desc'],
        $row['usuario_nombre'],
        $row['respuesta'],
        $row['fecharespuesta'],
        $row['consulta_desc'],
        $row['accion_desc'],
        $row['estado_desc'],
        $row['anioestado_val'],
        $row['observacion_text'],
        $row['listado_detalle_problematica'],
        $row['grado'],
        $row['apellido'],
        $row['nombre_nomina'],
        $row['dni'],
        $row['correo'],
        $row['telasignado'],
        $row['dependencia']
    ]);
}

fclose($output);
exit;
