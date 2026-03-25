<?php
// ============================================================
//  CargaFacturas/php/conciliacion_controller.php
//  Todo en la misma carpeta php/
// ============================================================
declare(strict_types=1);

// conciliacion_model.php está en la misma carpeta
require_once __DIR__ . '/conciliacion_model.php';

class DashboardController
{
    private ConciliacionModel $model;
    private const POR_PAGINA = 50;

    public function __construct()
    {
        $this->model = new ConciliacionModel();
    }

    public function manejarRequest(): void
    {
        $action = $_GET['action'] ?? 'dashboard';
        match ($action) {
            'export' => $this->exportar(),
            default  => $this->mostrarDashboard(),
        };
    }

    // ── Dashboard ─────────────────────────────────────────────

    private function mostrarDashboard(): void
    {
        $filtros = $this->model->sanitizarFiltros([
            'tipo'        => $_GET['tipo']        ?? '',
            'estado'      => $_GET['estado']      ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'busqueda'    => $_GET['busqueda']    ?? '',
        ]);

        $pagina     = max(1, (int)($_GET['pagina'] ?? 1));
        $totalFilas = $this->model->contarFilas($filtros);
        $totalPags  = max(1, (int)ceil($totalFilas / self::POR_PAGINA));
        $pagina     = min($pagina, $totalPags);
        $registros  = $this->model->obtenerConciliacion($filtros, $pagina, self::POR_PAGINA);
        $resumen    = $this->model->obtenerResumen($filtros);

        // La vista está en la misma carpeta
        require __DIR__ . '/conciliacion_view.php';
    }

    // ── Exportar ──────────────────────────────────────────────

    private function exportar(): void
    {
        $filtros = $this->model->sanitizarFiltros([
            'tipo'        => $_GET['tipo']        ?? '',
            'estado'      => $_GET['estado']      ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'busqueda'    => $_GET['busqueda']    ?? '',
        ]);
        $datos = $this->model->obtenerParaExport($filtros);

        // excel está en la misma carpeta
        require __DIR__ . '/conciliacion_excel.php';
        generarExcel($datos, $filtros);
    }

    // ── Helpers de vista ──────────────────────────────────────

    public static function formatMonto(float $v): string
    {
        return '$ ' . number_format(abs($v), 2, ',', '.');
    }

    public static function estadoBadge(string $estado): string
    {
        return match ($estado) {
            'conciliado' => '<span class="badge-estado badge-conciliado"><i class="fa fa-circle-check"></i> Conciliado</span>',
            'parcial'    => '<span class="badge-estado badge-parcial"><i class="fa fa-circle-half-stroke"></i> Parcial</span>',
            default      => '<span class="badge-estado badge-pendiente"><i class="fa fa-circle-xmark"></i> Pendiente</span>',
        };
    }
}
