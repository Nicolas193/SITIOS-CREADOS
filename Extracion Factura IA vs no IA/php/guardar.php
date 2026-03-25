<?php
// guardar.php — mapeado a la estructura real de la tabla comprobantes
session_start();
header('Content-Type: application/json; charset=utf-8');
require '../db.php';

try {
    if (!isset($_SESSION['user_id'])) throw new Exception('Sesión expirada.');
    $uid = (int) $_SESSION['user_id'];

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) throw new Exception('Sin datos.');

    // Mover archivo al destino final
    $rutaFinal = null;
    if (!empty($data['temp_path']) && file_exists($data['temp_path'])) {
        $carpeta = 'uploads/' . date('Y/m/');
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
        $rutaFinal = $carpeta . basename($data['temp_path']);
        rename($data['temp_path'], $rutaFinal);
    }

    $n = static function ($v) {
        if ($v === null || $v === '') return null;
        $f = (float) $v;
        return ($f !== 0.0) ? $f : null;
    };
    $s = static function ($v) {
        if ($v === null || $v === '') return null;
        $t = trim((string) $v);
        return ($t !== '' && strtolower($t) !== 'null') ? $t : null;
    };

    // Columnas reales de la tabla:
    // id, archivo_nombre, archivo_ruta, fecha_carga (auto),
    // tipo_comprobante, punto_venta, numero_comprobante,
    // fecha_emision, fecha_vencimiento,
    // razon_social_emisor, cuit_emisor,
    // razon_social_cliente, cuit_cliente,
    // iva, otros_impuestos, total,
    // texto_ocr, raw_text, usuario_id

    $sql = "INSERT INTO comprobantes (
        usuario_id,
        archivo_nombre, archivo_ruta,
        tipo_comprobante, punto_venta, numero_comprobante,
        fecha_emision, fecha_vencimiento,
        razon_social_emisor, cuit_emisor,
        razon_social_cliente, cuit_cliente,
        iva, otros_impuestos, total,
        texto_ocr, raw_text
    ) VALUES (
        :uid,
        :arch_nom, :arch_ruta,
        :tipo, :pv, :num,
        :f_emi, :f_venc,
        :emi_nom, :emi_cuit,
        :cli_nom, :cli_cuit,
        :iva, :otros, :total,
        :texto_ocr, :raw_text
    )";

    $pdo->prepare($sql)->execute([
        ':uid'       => $uid,
        ':arch_nom'  => $s($data['original_name']         ?? $data['archivo_nombre']         ?? null),
        ':arch_ruta' => $rutaFinal,

        ':tipo'      => $s($data['tipo_comprobante']      ?? null),
        ':pv'        => $s($data['punto_venta']           ?? null),
        ':num'       => $s($data['numero_comprobante']    ?? null),
        ':f_emi'     => $s($data['fecha_emision']         ?? null),
        ':f_venc'    => $s($data['fecha_vencimiento']     ?? null),

        ':emi_nom'   => $s($data['emisor_nombre']         ?? $data['razon_social_emisor']    ?? null),
        ':emi_cuit'  => $s($data['emisor_cuit']           ?? $data['cuit_emisor']             ?? null),

        ':cli_nom'   => $s($data['cliente_nombre']        ?? $data['razon_social_cliente']   ?? null),
        ':cli_cuit'  => $s($data['cliente_cuit']          ?? $data['cuit_cliente']            ?? null),

        // La tabla tiene un solo campo "iva" — usa iva_importe si viene desglosado
        ':iva'       => $n($data['iva_importe']           ?? $data['iva']                    ?? null),
        ':otros'     => $n($data['otros_impuestos']       ?? null),
        ':total'     => $n($data['total']                 ?? null),

        ':texto_ocr' => $s($data['texto_ocr']             ?? null),
        ':raw_text'  => $s($data['raw_text']              ?? null),
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'BD: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}