<?php
/**
 * guardar_extracto.php
 * Recibe JSON del extracto analizado y lo persiste en MySQL
 * Usa db.php para la conexión (sin duplicar credenciales)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require '../db.php'; // ← conexión centralizada, $pdo ya disponible

try {
    $raw = file_get_contents('php://input');
    if (!$raw) throw new RuntimeException('Sin datos recibidos.');

    $d = json_decode($raw, true);
    if (!$d || !isset($d['movimientos'])) throw new RuntimeException('JSON inválido.');

    $c  = $d['cabecera']     ?? [];
    $e  = $d['estadisticas'] ?? [];
    $im = $d['impuestos']    ?? [];

    $razon    = $c['titular']       ?? null;
    $cuit     = $c['cuit']          ?? null;
    $condIva  = $c['condicion_iva'] ?? null;
    $banco    = $d['banco']         ?? 'Banco desconocido';
    $tipoCta  = $c['tipo_cuenta']   ?? 'Cuenta Corriente';
    $nroCta   = $c['numero_cuenta'] ?? null;
    $cbu      = $c['cbu']           ?? null;
    $moneda   = $c['moneda']        ?? 'ARS';
    $sucursal = $c['sucursal']      ?? null;
    $archivo  = $d['archivo']       ?? 'desconocido.pdf';

    // ── 1. Empresa ───────────────────────────────────────
    $empresaId = null;
    if ($cuit) {
        $st = $pdo->prepare("SELECT id FROM empresas WHERE cuit = ? LIMIT 1");
        $st->execute([$cuit]);
        $empresaId = $st->fetchColumn() ?: null;
    }
    if (!$empresaId && $razon) {
        $st = $pdo->prepare("SELECT id FROM empresas WHERE razon_social = ? LIMIT 1");
        $st->execute([$razon]);
        $empresaId = $st->fetchColumn() ?: null;
    }
    if (!$empresaId) {
        $pdo->prepare("INSERT INTO empresas (razon_social, cuit, condicion_iva) VALUES (?, ?, ?)")
            ->execute([$razon ?: 'Empresa sin nombre', $cuit ?: null, $condIva ?: null]);
        $empresaId = (int) $pdo->lastInsertId();
    }

    // ── 2. Cuenta bancaria ───────────────────────────────
    $cuentaId = null;
    if ($cbu) {
        $st = $pdo->prepare("SELECT id FROM cuentas WHERE cbu = ? LIMIT 1");
        $st->execute([$cbu]);
        $cuentaId = $st->fetchColumn() ?: null;
    }
    if (!$cuentaId && $nroCta) {
        $st = $pdo->prepare("SELECT id FROM cuentas WHERE numero_cuenta = ? AND banco = ? LIMIT 1");
        $st->execute([$nroCta, $banco]);
        $cuentaId = $st->fetchColumn() ?: null;
    }
    if (!$cuentaId) {
        $pdo->prepare("INSERT INTO cuentas (empresa_id, banco, tipo_cuenta, numero_cuenta, cbu, moneda, sucursal) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$empresaId, $banco, $tipoCta, $nroCta ?: null, $cbu ?: null, $moneda, $sucursal ?: null]);
        $cuentaId = (int) $pdo->lastInsertId();
    }

    // ── 3. Extracto ──────────────────────────────────────
    $pdo->prepare(
        "INSERT INTO extractos
           (cuenta_id, empresa_id, archivo,
            periodo_desde, periodo_hasta,
            saldo_inicial, saldo_final,
            total_creditos, total_debitos, neto, total_movimientos)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $cuentaId, $empresaId, $archivo,
        $c['periodo_desde']     ?? null,
        $c['periodo_hasta']     ?? null,
        $c['saldo_inicial']     ?? null,
        $c['saldo_final']       ?? null,
        $e['total_creditos']    ?? 0,
        $e['total_debitos']     ?? 0,
        $e['neto']              ?? 0,
        $e['total_movimientos'] ?? 0,
    ]);
    $extractoId = (int) $pdo->lastInsertId();
    if (!$extractoId) throw new RuntimeException("No se pudo crear el extracto.");

    $pdo->beginTransaction();

    // ── 4. Bulk insert movimientos (chunks de 100) ───────
    $total = 0;
    foreach (array_chunk($d['movimientos'], 100) as $chunk) {
        if (empty($chunk)) continue;
        $ph = []; $p = [];
        foreach ($chunk as $k => $m) {
            $ph[] = "(:e{$k},:c{$k},:f{$k},:fv{$k},:ds{$k},:cp{$k},:db{$k},:cr{$k},:sl{$k},:tp{$k})";
            $p[":e{$k}"]  = $extractoId;
            $p[":c{$k}"]  = $cuentaId;
            $p[":f{$k}"]  = $m['fecha']       ?? null;
            $p[":fv{$k}"] = $m['fecha_valor'] ?? null;
            $p[":ds{$k}"] = mb_substr($m['descripcion'] ?? 'Movimiento', 0, 499);
            $p[":cp{$k}"] = mb_substr($m['comprobante'] ?? '', 0, 49) ?: null;
            $p[":db{$k}"] = $m['debito']      ?? null;
            $p[":cr{$k}"] = $m['credito']     ?? null;
            $p[":sl{$k}"] = $m['saldo']       ?? null;
            $p[":tp{$k}"] = in_array($m['tipo'] ?? '', ['D','C','I']) ? $m['tipo'] : 'I';
        }
        $ins = $pdo->prepare(
            "INSERT IGNORE INTO movimientos
               (extracto_id,cuenta_id,fecha,fecha_valor,descripcion,comprobante,debito,credito,saldo,tipo)
             VALUES " . implode(',', $ph)
        );
        $ins->execute($p);
        $total += $ins->rowCount();
    }

    // ── 5. Consolidado impositivo ────────────────────────
    if (!empty($im)) {
        $pdo->prepare(
            "INSERT IGNORE INTO impuestos_extracto
               (extracto_id,iva_debitos,iva_percepcion,imp_deb_cred_banc,
                ret_ley25413_creditos,ret_ley25413_debitos,credito_computable,
                iibb_tucuman,iibb_sircreb,retencion_sircreb,comision_cuenta)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $extractoId,
            $im['iva_debitos']           ?? null,
            $im['iva_percepcion']        ?? null,
            $im['imp_deb_cred_banc']     ?? null,
            $im['ret_ley25413_creditos'] ?? null,
            $im['ret_ley25413_debitos']  ?? null,
            $im['credito_computable']    ?? null,
            $im['iibb_tucuman']          ?? null,
            $im['iibb_sircreb']          ?? null,
            $im['retencion_sircreb']     ?? null,
            $im['comision_cuenta']       ?? null,
        ]);
    }

    // ── 6. Log de carga ──────────────────────────────────
    $pdo->prepare(
        "INSERT INTO log_carga
           (extracto_id, nombre_archivo, extension, movimientos_ext, resultado, ip)
         VALUES (?, ?, ?, ?, 'ok', ?)"
    )->execute([
        $extractoId,
        $archivo,
        strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) ?: 'pdf',
        $total,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $pdo->commit();

    echo json_encode([
        'success'               => true,
        'extracto_id'           => $extractoId,
        'cuenta_id'             => $cuentaId,
        'movimientos_guardados' => $total,
        'message'               => "Guardado correctamente: {$total} movimientos.",
    ]);

} catch (PDOException $ex) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $ex->getMessage()]);
} catch (Exception $ex) {
    if (isset($pdo) && $pdo->inTransaction()) @$pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
}