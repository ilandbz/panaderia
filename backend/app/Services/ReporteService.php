<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\MovimientoCaja;
use App\Models\AperturaCaja;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    public function obtenerStatsDashboard()
    {
        $hoy = Carbon::today();

        // 1. Ventas de Hoy
        $ventasHoy = Venta::whereDate('created_at', $hoy)
                          ->where('estado', 'completada')
                          ->sum('total');

        // 2. Caja del Día (Monto neto en aperturas de hoy)
        $cajaHoy = MovimientoCaja::whereDate('created_at', $hoy)
                                  ->where('tipo', 'ingreso')
                                  ->sum('monto') 
                   - MovimientoCaja::whereDate('created_at', $hoy)
                                  ->where('tipo', 'egreso')
                                  ->sum('monto');

        // 3. Productos Bajos en Stock
        $productosBajos = Producto::where('stock', '<=', DB::raw('stock_minimo'))
                                  ->count();

        // 4. Ventas del Mes
        $ventasMes = Venta::whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year)
                          ->where('estado', 'completada')
                          ->sum('total');

        return [
            'ventas_hoy'      => (float)$ventasHoy,
            'caja_dia'        => (float)$cajaHoy,
            'productos_bajos' => $productosBajos,
            'ventas_mes'      => (float)$ventasMes,
        ];
    }

    public function obtenerVentasRecientes($limit = 5)
    {
        return Venta::with('cliente')
                    ->latest()
                    ->limit($limit)
                    ->get();
    }
}
