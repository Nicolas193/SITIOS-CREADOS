<?php
require_once("../../conexion.php");
require_once "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$rutaExcel = __DIR__ . '/../archivo/nomina.xlsx';

header('Content-Type: text/plain');

// Verificamos que el archivo exista
if (!file_exists($rutaExcel)) {
    echo "Error: Archivo Excel no encontrado.";
    exit;
}

try {
    $spreadsheet = IOFactory::load($rutaExcel);
    $rows = $spreadsheet->getActiveSheet()->toArray();

    $mysqli = conectar();
    $mysqli->set_charset("utf8mb4");

    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

    if (!$mysqli->query("TRUNCATE TABLE nominaddjj")) {
        throw new Exception("No se pudo truncar la tabla: " . $mysqli->error);
    }

    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

    $stmt = $mysqli->prepare("
        INSERT INTO nominaddjj (lp, grado, apellido, nombre, dni, correo, telasignado, dependencia)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $mysqli->error);
    }

    $mysqli->begin_transaction();
    $batchSize = 1000;
    $contador = 0;
    $insertadas = 0;

    // Comenzar desde 1 para saltar encabezado
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        $lp = trim($row[0] ?? '');
        $grado = trim($row[1] ?? '');
        $apellido = trim($row[2] ?? '');
        $nombre = trim($row[3] ?? '');
        $dni = trim($row[10] ?? '');
        $correo = trim($row[13] ?? '');
        $telasignado = trim($row[14] ?? '');
        $dependencia = trim($row[8] ?? '');

        if ($lp === '' && $apellido === '' && $nombre === '') continue;

        $stmt->bind_param("ssssssss", $lp, $grado, $apellido, $nombre, $dni, $correo, $telasignado, $dependencia);
        if (!$stmt->execute()) {
            throw new Exception("Error en fila $i: " . $stmt->error);
        }

        $contador++;
        $insertadas++;

        if ($contador % $batchSize === 0) {
            $mysqli->commit();
            $mysqli->begin_transaction();
        }
    }

    $mysqli->commit();
    $stmt->close();
    $mysqli->close();

    echo "✅ Importación completada correctamente. Filas insertadas: {$insertadas}";

} catch (Throwable $e) {
    echo "❌ Error durante la importación: " . $e->getMessage();
}
