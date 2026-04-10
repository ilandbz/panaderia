<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use App\Exports\VentasExport;
use App\Exports\ProductosVendidosExport;
use App\Exports\StockBajoExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    protected ReporteService $service;

    public function __construct(ReporteService $service)
    {
        $this->service = $service;
    }

    // --------------------------------------------------------
    // GET /api/dashboard
    // --------------------------------------------------------
    public function dashboard()
    {
        $stats    = $this->service->obtenerStatsDashboard();
        $recientes = $this->service->obtenerVentasRecientes();

        return $this->successResponse([
            'stats'     => $stats,
            'recientes' => $recientes,
        ]);
    }

    // --------------------------------------------------------
    // GET /api/reportes/ventas
    // ?desde=2025-01-01&hasta=2025-01-31&agrupar=dia
    // --------------------------------------------------------
    public function ventas(Request $request)
    {
        $request->validate([
            'desde'   => 'required|date',
            'hasta'   => 'required|date|after_or_equal:desde',
            'agrupar' => 'nullable|in:dia,semana,mes',
        ]);

        $data = $this->service->ventasPorPeriodo(
            $request->desde,
            $request->hasta,
            $request->get('agrupar', 'dia')
        );

        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/productos-top
    // ?desde=&hasta=&limit=10
    // --------------------------------------------------------
    public function productosTop(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $data = $this->service->productosMasVendidos(
            $request->desde,
            $request->hasta,
            (int) $request->get('limit', 10)
        );

        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/utilidad
    // ?desde=&hasta=
    // --------------------------------------------------------
    public function utilidad(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $data = $this->service->utilidadEstimada($request->desde, $request->hasta);

        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/ventas-usuario
    // ?desde=&hasta=
    // --------------------------------------------------------
    public function ventasPorUsuario(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $data = $this->service->ventasPorUsuario($request->desde, $request->hasta);

        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/caja
    // ?desde=&hasta=
    // --------------------------------------------------------
    public function caja(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $data = $this->service->movimientosCaja($request->desde, $request->hasta);

        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/mermas
    // ?desde=&hasta=
    // --------------------------------------------------------
    public function mermas(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $data = $this->service->mermasPorPeriodo($request->desde, $request->hasta);

        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/stock-bajo
    // --------------------------------------------------------
    public function stockBajo()
    {
        $data = $this->service->stockBajo();
        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/por-vencer
    // ?dias=7
    // --------------------------------------------------------
    public function porVencer(Request $request)
    {
        $dias = (int) $request->get('dias', 7);
        $data = $this->service->porVencer($dias);

        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/forma-pago
    // ?desde=&hasta=
    // --------------------------------------------------------
    public function formaPago(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $data = $this->service->ventasPorFormaPago($request->desde, $request->hasta);

        return $this->successResponse($data);
    }

    // --------------------------------------------------------
    // GET /api/reportes/export/ventas
    // ?desde=&hasta= → descarga .xlsx
    // --------------------------------------------------------
    public function exportVentas(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $data = $this->service->exportarVentas($request->desde, $request->hasta);

        $filename = "ventas_{$request->desde}_{$request->hasta}.xlsx";

        return Excel::download(
            new VentasExport($data, $request->desde, $request->hasta),
            $filename
        );
    }

    // --------------------------------------------------------
    // GET /api/reportes/export/productos-top
    // ?desde=&hasta= → descarga .xlsx
    // --------------------------------------------------------
    public function exportProductosTop(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $data = $this->service->exportarProductosMasVendidos($request->desde, $request->hasta);
        $filename = "productos_vendidos_{$request->desde}_{$request->hasta}.xlsx";

        return Excel::download(new ProductosVendidosExport($data), $filename);
    }

    // --------------------------------------------------------
    // GET /api/reportes/export/stock-bajo
    // → descarga .xlsx
    // --------------------------------------------------------
    public function exportStockBajo()
    {
        $data = $this->service->exportarStockBajo();

        return Excel::download(new StockBajoExport($data), 'stock_bajo.xlsx');
    }
}
