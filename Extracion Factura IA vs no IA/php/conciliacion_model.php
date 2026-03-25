<?php
// ============================================================
//  CargaFacturas/php/conciliacion_model.php
//  Motor de Conciliación — todo en la misma carpeta php/
// ============================================================
declare(strict_types=1);

class ConciliacionModel
{
    private PDO $pdo;

    private const MONTO_TOLERANCIA = 0.10;
    private const DIAS_BANCO_MAX   = 30;

    public function __construct()
    {
        global $pdo;
        if (!($pdo instanceof PDO)) {
            throw new RuntimeException('$pdo no disponible. db.php debe incluirse antes.');
        }
        $this->pdo = $pdo;
    }

    // ── Filtros ───────────────────────────────────────────────

    public function sanitizarFiltros(array $raw): array
    {
        return [
            'tipo'        => in_array($raw['tipo']   ?? '', ['compra','venta',''])
                                ? ($raw['tipo'] ?? '') : '',
            'estado'      => in_array($raw['estado'] ?? '', ['conciliado','parcial','pendiente',''])
                                ? ($raw['estado'] ?? '') : '',
            'fecha_desde' => $this->validarFecha($raw['fecha_desde'] ?? ''),
            'fecha_hasta' => $this->validarFecha($raw['fecha_hasta'] ?? ''),
            'busqueda'    => mb_substr(trim($raw['busqueda'] ?? ''), 0, 100),
        ];
    }

    private function validarFecha(string $v): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
    }

    // ── Consulta principal ────────────────────────────────────

    public function obtenerConciliacion(array $filtros, int $pagina = 1, int $porPagina = 50): array
    {
        $offset = ($pagina - 1) * $porPagina;
        $params = [];

        $sqlC = $this->buildQuery('compra', $filtros, $params, $porPagina, $offset);
        $sqlV = $this->buildQuery('venta',  $filtros, $params, $porPagina, $offset);

        if ($filtros['tipo'] === 'compra') {
            $sql = $sqlC;
            return $this->ejecutarConsulta($sql, $params, null, null);
        }
        if ($filtros['tipo'] === 'venta') {
            $sql = $sqlV;
            return $this->ejecutarConsulta($sql, $params, null, null);
        }

        $sql = "($sqlC) UNION ALL ($sqlV)
                ORDER BY fecha_emision DESC, tipo, importe_total DESC
                LIMIT :lim_union OFFSET :off_union";

        return $this->ejecutarConsulta($sql, $params, $porPagina, $offset);
    }

    private function buildQuery(string $tipo, array $filtros, array &$params, int $lim, int $off): string
    {
        $s = $tipo === 'compra' ? '_c' : '_v';

        if ($tipo === 'compra') {
            $tabla   = 'afip_compras';
            $cCuit   = 'a.nro_doc_vendedor';
            $cNombre = 'a.denominacion_vendedor';
            $cNumero = 'a.numero_comprobante';
        } else {
            $tabla   = 'afip_ventas';
            $cCuit   = 'a.nro_doc_comprador';
            $cNombre = 'a.denominacion_comprador';
            $cNumero = 'a.numero_desde';
        }

        $tol  = self::MONTO_TOLERANCIA;
        $dias = self::DIAS_BANCO_MAX;

        $where = ["a.importe_total != 0", "a.fecha_emision IS NOT NULL"];

        if ($filtros['fecha_desde']) {
            $where[] = "a.fecha_emision >= :fd{$s}";
            $params[":fd{$s}"] = $filtros['fecha_desde'];
        }
        if ($filtros['fecha_hasta']) {
            $where[] = "a.fecha_emision <= :fh{$s}";
            $params[":fh{$s}"] = $filtros['fecha_hasta'];
        }
        if ($filtros['busqueda']) {
            $where[] = "({$cNombre} LIKE :bus{$s} OR {$cCuit} LIKE :bus2{$s})";
            $params[":bus{$s}"]  = '%' . $filtros['busqueda'] . '%';
            $params[":bus2{$s}"] = '%' . $filtros['busqueda'] . '%';
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $subComp = "SELECT c.id, c.archivo_ruta, c.tipo_comprobante, c.razon_social_emisor
                    FROM comprobantes c
                    WHERE ABS(c.total - a.importe_total) <= {$tol}
                      AND c.fecha_emision = a.fecha_emision LIMIT 1";

        $subMov = "SELECT m.id, m.fecha, m.descripcion,
                          COALESCE(m.debito, m.credito) AS importe_mov
                   FROM movimientos m
                   WHERE ABS(COALESCE(m.debito,m.credito,0) - ABS(a.importe_total)) <= {$tol}
                     AND m.fecha BETWEEN a.fecha_emision
                                    AND DATE_ADD(a.fecha_emision, INTERVAL {$dias} DAY) LIMIT 1";

        $estadoExpr = "CASE
            WHEN comp_id IS NOT NULL AND mov_id IS NOT NULL THEN 'conciliado'
            WHEN comp_id IS NOT NULL OR  mov_id IS NOT NULL THEN 'parcial'
            ELSE 'pendiente' END";

        $sql = "SELECT
                    a.id                          AS afip_id,
                    '{$tipo}'                     AS tipo,
                    a.fecha_emision,
                    a.desc_tipo_comprobante       AS tipo_comprobante,
                    a.punto_venta,
                    {$cNumero}                    AS numero_comprobante,
                    {$cCuit}                      AS cuit_contraparte,
                    {$cNombre}                    AS nombre_contraparte,
                    a.importe_total,
                    a.total_neto_gravado          AS neto_gravado,
                    a.total_iva,
                    a.credito_fiscal_computable   AS credito_fiscal,
                    a.moneda,
                    (SELECT id           FROM ({$subComp}) _sc1) AS comp_id,
                    (SELECT archivo_ruta FROM ({$subComp}) _sc2) AS comp_ruta,
                    (SELECT id           FROM ({$subMov})  _sm1) AS mov_id,
                    (SELECT fecha        FROM ({$subMov})  _sm2) AS mov_fecha,
                    (SELECT descripcion  FROM ({$subMov})  _sm3) AS mov_desc,
                    ({$estadoExpr})               AS estado_conciliacion
                FROM {$tabla} a
                {$whereSQL}";

        if ($filtros['estado']) {
            $params[":est{$s}"] = $filtros['estado'];
            $sql = "SELECT * FROM ({$sql}) _base{$s}
                    WHERE estado_conciliacion = :est{$s}
                    ORDER BY fecha_emision DESC, importe_total DESC
                    LIMIT :lim{$s} OFFSET :off{$s}";
        } else {
            $sql .= " ORDER BY a.fecha_emision DESC, a.importe_total DESC
                      LIMIT :lim{$s} OFFSET :off{$s}";
        }

        $params[":lim{$s}"] = $lim;
        $params[":off{$s}"] = $off;

        return $sql;
    }

    private function ejecutarConsulta(string $sql, array $params, ?int $limUnion, ?int $offUnion): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            if ($limUnion !== null) {
                $stmt->bindValue(':lim_union', $limUnion, PDO::PARAM_INT);
                $stmt->bindValue(':off_union', $offUnion, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('ConciliacionModel::ejecutarConsulta: ' . $e->getMessage());
            return [];
        }
    }

    // ── Resumen KPI ───────────────────────────────────────────

    public function obtenerResumen(array $filtros): array
    {
        $tol  = self::MONTO_TOLERANCIA;
        $dias = self::DIAS_BANCO_MAX;

        $resumen = [
            'compra' => ['conciliado'=>0,'parcial'=>0,'pendiente'=>0,'total_importe'=>0,'total_iva'=>0],
            'venta'  => ['conciliado'=>0,'parcial'=>0,'pendiente'=>0,'total_importe'=>0,'total_iva'=>0],
        ];

        foreach (['compra'=>'afip_compras','venta'=>'afip_ventas'] as $tipo => $tabla) {
            $where  = ["a.importe_total != 0","a.fecha_emision IS NOT NULL"];
            $params = [];
            if ($filtros['fecha_desde']) { $where[] = 'a.fecha_emision >= :fd'; $params[':fd'] = $filtros['fecha_desde']; }
            if ($filtros['fecha_hasta']) { $where[] = 'a.fecha_emision <= :fh'; $params[':fh'] = $filtros['fecha_hasta']; }
            $whereSQL = 'WHERE ' . implode(' AND ', $where);

            $sql = "SELECT
                        SUM(a.importe_total) AS total_importe,
                        SUM(a.total_iva)     AS total_iva,
                        SUM(CASE WHEN EXISTS(SELECT 1 FROM comprobantes c WHERE ABS(c.total-a.importe_total)<={$tol} AND c.fecha_emision=a.fecha_emision)
                                  AND EXISTS(SELECT 1 FROM movimientos m WHERE ABS(COALESCE(m.debito,m.credito,0)-ABS(a.importe_total))<={$tol} AND m.fecha BETWEEN a.fecha_emision AND DATE_ADD(a.fecha_emision,INTERVAL {$dias} DAY))
                             THEN 1 ELSE 0 END) AS conciliado,
                        SUM(CASE WHEN (EXISTS(SELECT 1 FROM comprobantes c WHERE ABS(c.total-a.importe_total)<={$tol} AND c.fecha_emision=a.fecha_emision)
                                       XOR EXISTS(SELECT 1 FROM movimientos m WHERE ABS(COALESCE(m.debito,m.credito,0)-ABS(a.importe_total))<={$tol} AND m.fecha BETWEEN a.fecha_emision AND DATE_ADD(a.fecha_emision,INTERVAL {$dias} DAY)))
                             THEN 1 ELSE 0 END) AS parcial,
                        SUM(CASE WHEN NOT EXISTS(SELECT 1 FROM comprobantes c WHERE ABS(c.total-a.importe_total)<={$tol} AND c.fecha_emision=a.fecha_emision)
                                  AND NOT EXISTS(SELECT 1 FROM movimientos m WHERE ABS(COALESCE(m.debito,m.credito,0)-ABS(a.importe_total))<={$tol} AND m.fecha BETWEEN a.fecha_emision AND DATE_ADD(a.fecha_emision,INTERVAL {$dias} DAY))
                             THEN 1 ELSE 0 END) AS pendiente
                    FROM {$tabla} a {$whereSQL}";

            try {
                $stmt = $this->pdo->prepare($sql);
                foreach ($params as $k => $v) $stmt->bindValue($k, $v);
                $stmt->execute();
                $row = $stmt->fetch();
                $resumen[$tipo] = [
                    'conciliado'    => (int)  ($row['conciliado']    ?? 0),
                    'parcial'       => (int)  ($row['parcial']       ?? 0),
                    'pendiente'     => (int)  ($row['pendiente']     ?? 0),
                    'total_importe' => (float)($row['total_importe'] ?? 0),
                    'total_iva'     => (float)($row['total_iva']     ?? 0),
                ];
            } catch (PDOException $e) {
                error_log('obtenerResumen: ' . $e->getMessage());
            }
        }

        return $resumen;
    }

    // ── Conteo para paginación ────────────────────────────────

    public function contarFilas(array $filtros): int
    {
        $tol  = self::MONTO_TOLERANCIA;
        $dias = self::DIAS_BANCO_MAX;
        $total = 0;

        foreach (['afip_compras','afip_ventas'] as $tabla) {
            $tipo = $tabla === 'afip_compras' ? 'compra' : 'venta';
            if ($filtros['tipo'] && $filtros['tipo'] !== $tipo) continue;

            $where  = ["a.importe_total != 0","a.fecha_emision IS NOT NULL"];
            $params = [];

            if ($filtros['fecha_desde']) { $where[] = 'a.fecha_emision >= :fd'; $params[':fd'] = $filtros['fecha_desde']; }
            if ($filtros['fecha_hasta']) { $where[] = 'a.fecha_emision <= :fh'; $params[':fh'] = $filtros['fecha_hasta']; }
            if ($filtros['busqueda']) {
                $cN = $tabla === 'afip_compras' ? 'denominacion_vendedor' : 'denominacion_comprador';
                $cC = $tabla === 'afip_compras' ? 'nro_doc_vendedor'      : 'nro_doc_comprador';
                $where[] = "(a.{$cN} LIKE :bus OR a.{$cC} LIKE :bus2)";
                $params[':bus']  = '%' . $filtros['busqueda'] . '%';
                $params[':bus2'] = '%' . $filtros['busqueda'] . '%';
            }

            $whereSQL     = 'WHERE ' . implode(' AND ', $where);
            $estadoFiltro = '';
            if ($filtros['estado']) {
                $estadoFiltro   = "AND ({$this->estadoExprInline($tol, $dias)}) = :est";
                $params[':est'] = $filtros['estado'];
            }

            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$tabla} a {$whereSQL} {$estadoFiltro}");
                foreach ($params as $k => $v) $stmt->bindValue($k, $v);
                $stmt->execute();
                $total += (int)$stmt->fetchColumn();
            } catch (PDOException $e) {
                error_log("contarFilas {$tabla}: " . $e->getMessage());
            }
        }
        return $total;
    }

    private function estadoExprInline(float $tol, int $dias): string
    {
        return "CASE
            WHEN EXISTS(SELECT 1 FROM comprobantes c WHERE ABS(c.total-a.importe_total)<={$tol} AND c.fecha_emision=a.fecha_emision)
             AND EXISTS(SELECT 1 FROM movimientos m WHERE ABS(COALESCE(m.debito,m.credito,0)-ABS(a.importe_total))<={$tol} AND m.fecha BETWEEN a.fecha_emision AND DATE_ADD(a.fecha_emision,INTERVAL {$dias} DAY))
            THEN 'conciliado'
            WHEN EXISTS(SELECT 1 FROM comprobantes c WHERE ABS(c.total-a.importe_total)<={$tol} AND c.fecha_emision=a.fecha_emision)
              OR EXISTS(SELECT 1 FROM movimientos m WHERE ABS(COALESCE(m.debito,m.credito,0)-ABS(a.importe_total))<={$tol} AND m.fecha BETWEEN a.fecha_emision AND DATE_ADD(a.fecha_emision,INTERVAL {$dias} DAY))
            THEN 'parcial'
            ELSE 'pendiente' END";
    }

    // ── Export ────────────────────────────────────────────────

    public function obtenerParaExport(array $filtros): array
    {
        $tol  = self::MONTO_TOLERANCIA;
        $dias = self::DIAS_BANCO_MAX;
        $rows = [];

        foreach (['compra'=>'afip_compras','venta'=>'afip_ventas'] as $tipo => $tabla) {
            if ($filtros['tipo'] && $filtros['tipo'] !== $tipo) continue;

            $cCuit   = $tipo === 'compra' ? 'a.nro_doc_vendedor'     : 'a.nro_doc_comprador';
            $cNombre = $tipo === 'compra' ? 'a.denominacion_vendedor' : 'a.denominacion_comprador';
            $cNumero = $tipo === 'compra' ? 'a.numero_comprobante'    : 'a.numero_desde';

            $where  = ["a.importe_total != 0","a.fecha_emision IS NOT NULL"];
            $params = [':tipo' => $tipo];

            if ($filtros['fecha_desde']) { $where[] = 'a.fecha_emision >= :fd'; $params[':fd'] = $filtros['fecha_desde']; }
            if ($filtros['fecha_hasta']) { $where[] = 'a.fecha_emision <= :fh'; $params[':fh'] = $filtros['fecha_hasta']; }
            if ($filtros['busqueda']) {
                $where[] = "({$cNombre} LIKE :bus OR {$cCuit} LIKE :bus2)";
                $params[':bus']  = '%' . $filtros['busqueda'] . '%';
                $params[':bus2'] = '%' . $filtros['busqueda'] . '%';
            }

            $whereSQL   = 'WHERE ' . implode(' AND ', $where);
            $estadoExpr = $this->estadoExprInline($tol, $dias);
            $estadoHav  = $filtros['estado'] ? "HAVING estado_conciliacion = :est" : '';
            if ($filtros['estado']) $params[':est'] = $filtros['estado'];

            $sql = "SELECT :tipo AS tipo, a.fecha_emision,
                           a.desc_tipo_comprobante AS tipo_comprobante,
                           a.punto_venta, {$cNumero} AS numero_comprobante,
                           {$cCuit} AS cuit_contraparte, {$cNombre} AS nombre_contraparte,
                           a.importe_total, a.total_neto_gravado AS neto_gravado,
                           a.total_iva, a.credito_fiscal_computable AS credito_fiscal, a.moneda,
                           (SELECT c.archivo_ruta FROM comprobantes c WHERE ABS(c.total-a.importe_total)<={$tol} AND c.fecha_emision=a.fecha_emision LIMIT 1) AS comp_ruta,
                           (SELECT m.fecha FROM movimientos m WHERE ABS(COALESCE(m.debito,m.credito,0)-ABS(a.importe_total))<={$tol} AND m.fecha BETWEEN a.fecha_emision AND DATE_ADD(a.fecha_emision,INTERVAL {$dias} DAY) LIMIT 1) AS mov_fecha,
                           ({$estadoExpr}) AS estado_conciliacion
                    FROM {$tabla} a {$whereSQL} {$estadoHav}
                    ORDER BY a.fecha_emision DESC, a.importe_total DESC";

            try {
                $stmt = $this->pdo->prepare($sql);
                foreach ($params as $k => $v) $stmt->bindValue($k, $v);
                $stmt->execute();
                $rows = array_merge($rows, $stmt->fetchAll());
            } catch (PDOException $e) {
                error_log("obtenerParaExport {$tabla}: " . $e->getMessage());
            }
        }

        return $rows;
    }
}
