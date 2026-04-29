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
    // =========================================================
    // DASHBOARD - KPIs del día
    // =========================================================
    public function obtenerStatsDashboard(): array
    {
        $hoy = Carbon::today();

        $ventasHoy = Venta::whereDate('created_at', $hoy)
                          ->where('estado', 'completada')
                          ->sum('total');

        $cajaHoy = MovimientoCaja::whereDate('created_at', $hoy)
                                  ->where('tipo', 'ingreso')
                                  ->sum('monto')
                   - MovimientoCaja::whereDate('created_at', $hoy)
                                  ->where('tipo', 'egreso')
                                  ->sum('monto');

        $productosBajos = DB::table('producto_sucursal')
                                  ->join('productos', 'productos.id', '=', 'producto_sucursal.producto_id')
                                  ->where('producto_sucursal.sucursal_id', config('app.sucursal_id'))
                                  ->whereColumn('producto_sucursal.stock', '<=', 'producto_sucursal.stock_minimo')
                                  ->whereNull('productos.deleted_at')
                                  ->count();

        $ventasMes = Venta::whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year)
                          ->where('estado', 'completada')
                          ->sum('total');

        $cantidadHoy = Venta::whereDate('created_at', $hoy)
                            ->where('estado', 'completada')
                            ->count();

        $ticketPromedio = $cantidadHoy > 0 ? ($ventasHoy / $cantidadHoy) : 0;

        return [
            'ventas_hoy'      => (float) $ventasHoy,
            'caja_dia'        => (float) $cajaHoy,
            'productos_bajos' => (int) $productosBajos,
            'ventas_mes'      => (float) $ventasMes,
            'cantidad_hoy'    => (int) $cantidadHoy,
            'ticket_promedio' => round((float) $ticketPromedio, 2),
        ];
    }

    public function obtenerVentasRecientes(int $limit = 5)
    {
        return Venta::with('cliente')
                    ->latest()
                    ->limit($limit)
                    ->get();
    }

    // =========================================================
    // REPORTE: Ventas por período (línea/barra para gráfico)
    // =========================================================
    public function ventasPorPeriodo(string $desde, string $hasta, string $agrupar = 'dia'): array
    {
        $formato = match ($agrupar) {
            'semana' => '%Y-%u',
            'mes'    => '%Y-%m',
            default  => '%Y-%m-%d',
        };

        $rows = DB::table('ventas')
            ->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('estado', 'completada')
            ->whereNull('deleted_at')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$formato}') as periodo"),
                DB::raw('SUM(total) as total_ventas'),
                DB::raw('COUNT(*) as cantidad_ventas'),
                DB::raw('AVG(total) as ticket_promedio'),
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        return $rows->map(fn($r) => [
            'periodo'         => $r->periodo,
            'total_ventas'    => round((float) $r->total_ventas, 2),
            'cantidad_ventas' => (int) $r->cantidad_ventas,
            'ticket_promedio' => round((float) $r->ticket_promedio, 2),
        ])->toArray();
    }

    // =========================================================
    // REPORTE: Productos más vendidos
    // =========================================================
    public function productosMasVendidos(string $desde, string $hasta, int $limit = 10): array
    {
        $rows = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->whereBetween('ventas.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('ventas.estado', 'completada')
            ->whereNull('ventas.deleted_at')
            ->select(
                'productos.id',
                'productos.nombre',
                'categorias.nombre as categoria',
                DB::raw('SUM(detalle_ventas.cantidad) as cantidad_vendida'),
                DB::raw('SUM(detalle_ventas.subtotal) as total_facturado'),
            )
            ->groupBy('productos.id', 'productos.nombre', 'categorias.nombre')
            ->orderByDesc('total_facturado')
            ->limit($limit)
            ->get();

        return $rows->map(fn($r) => [
            'id'               => $r->id,
            'nombre'           => $r->nombre,
            'categoria'        => $r->categoria,
            'cantidad_vendida' => (int) $r->cantidad_vendida,
            'total_facturado'  => round((float) $r->total_facturado, 2),
        ])->toArray();
    }

    // =========================================================
    // REPORTE: Utilidad estimada
    // =========================================================
    public function utilidadEstimada(string $desde, string $hasta): array
    {
        $datos = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->whereBetween('ventas.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('ventas.estado', 'completada')
            ->whereNull('ventas.deleted_at')
            ->whereNotNull('productos.costo')
            ->select(
                DB::raw('SUM(detalle_ventas.subtotal) as total_venta'),
                DB::raw('SUM(detalle_ventas.cantidad * productos.costo) as total_costo'),
            )
            ->first();

        $totalVenta = (float) ($datos->total_venta ?? 0);
        $totalCosto = (float) ($datos->total_costo ?? 0);
        $utilidad   = $totalVenta - $totalCosto;
        $margen     = $totalVenta > 0 ? ($utilidad / $totalVenta) * 100 : 0;

        return [
            'total_venta'  => round($totalVenta, 2),
            'total_costo'  => round($totalCosto, 2),
            'utilidad'     => round($utilidad, 2),
            'margen_pct'   => round($margen, 2),
        ];
    }

    // =========================================================
    // REPORTE: Ventas por vendedor/usuario
    // =========================================================
    public function ventasPorUsuario(string $desde, string $hasta): array
    {
        $rows = DB::table('ventas')
            ->join('usuarios', 'usuarios.id', '=', 'ventas.usuario_id')
            ->whereBetween('ventas.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('ventas.estado', 'completada')
            ->whereNull('ventas.deleted_at')
            ->select(
                'usuarios.id',
                DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) as vendedor"),
                DB::raw('COUNT(*) as cantidad_ventas'),
                DB::raw('SUM(ventas.total) as total_vendido'),
                DB::raw('AVG(ventas.total) as ticket_promedio'),
            )
            ->groupBy('usuarios.id', 'usuarios.nombre', 'usuarios.apellido')
            ->orderByDesc('total_vendido')
            ->get();

        return $rows->map(fn($r) => [
            'id'              => $r->id,
            'vendedor'        => $r->vendedor,
            'cantidad_ventas' => (int) $r->cantidad_ventas,
            'total_vendido'   => round((float) $r->total_vendido, 2),
            'ticket_promedio' => round((float) $r->ticket_promedio, 2),
        ])->toArray();
    }

    // =========================================================
    // REPORTE: Movimientos de Caja por período
    // =========================================================
    public function movimientosCaja(string $desde, string $hasta): array
    {
        $rows = DB::table('movimientos_caja')
            ->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->select(
                'tipo',
                'concepto',
                'monto',
                'created_at',
            )
            ->orderBy('created_at')
            ->get();

        $resumen = DB::table('movimientos_caja')
            ->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->select(
                'tipo',
                DB::raw('SUM(monto) as total'),
                DB::raw('COUNT(*) as cantidad'),
            )
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        return [
            'movimientos' => $rows->map(fn($r) => [
                'tipo'       => $r->tipo,
                'concepto'   => $r->concepto,
                'monto'      => round((float) $r->monto, 2),
                'fecha'      => $r->created_at,
            ])->toArray(),
            'resumen' => [
                'total_ingresos' => round((float) ($resumen->get('ingreso')->total ?? 0), 2),
                'total_egresos'  => round((float) ($resumen->get('egreso')->total ?? 0),  2),
                'cant_ingresos'  => (int) ($resumen->get('ingreso')->cantidad ?? 0),
                'cant_egresos'   => (int) ($resumen->get('egreso')->cantidad ?? 0),
            ],
        ];
    }

    // =========================================================
    // REPORTE: Mermas por período
    // =========================================================
    public function mermasPorPeriodo(string $desde, string $hasta): array
    {
        $rows = DB::table('movimientos_inventario')
            ->join('productos', 'productos.id', '=', 'movimientos_inventario.producto_id')
            ->whereBetween('movimientos_inventario.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('movimientos_inventario.tipo', 'merma')
            ->select(
                'productos.nombre',
                'movimientos_inventario.motivo',
                DB::raw('SUM(movimientos_inventario.cantidad) as cantidad_total'),
                DB::raw('SUM(movimientos_inventario.cantidad * COALESCE(productos.costo, 0)) as costo_perdida'),
            )
            ->groupBy('productos.id', 'productos.nombre', 'movimientos_inventario.motivo')
            ->orderByDesc('costo_perdida')
            ->get();

        return $rows->map(fn($r) => [
            'nombre'        => $r->nombre,
            'motivo'        => $r->motivo,
            'cantidad'      => (float) $r->cantidad_total,
            'costo_perdida' => round((float) $r->costo_perdida, 2),
        ])->toArray();
    }

    // =========================================================
    // REPORTE: Stock bajo
    // =========================================================
    public function stockBajo(): array
    {
        return DB::table('producto_sucursal')
            ->join('productos', 'productos.id', '=', 'producto_sucursal.producto_id')
            ->where('producto_sucursal.sucursal_id', config('app.sucursal_id'))
            ->whereColumn('producto_sucursal.stock', '<=', 'producto_sucursal.stock_minimo')
            ->whereNull('productos.deleted_at')
            ->select(
                'productos.id', 
                'productos.nombre', 
                'producto_sucursal.stock', 
                'producto_sucursal.stock_minimo', 
                'productos.unidad_medida'
            )
            ->orderBy('producto_sucursal.stock')
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'nombre'       => $p->nombre,
                'stock'        => (float) $p->stock,
                'stock_minimo' => (float) $p->stock_minimo,
                'unidad'       => $p->unidad_medida,
                'diferencia'   => round((float) $p->stock_minimo - (float) $p->stock, 2),
            ])
            ->toArray();
    }

    // =========================================================
    // REPORTE: Productos por vencer
    // =========================================================
    public function porVencer(int $dias = 7): array
    {
        return DB::table('productos')
            ->join('producto_sucursal', 'productos.id', '=', 'producto_sucursal.producto_id')
            ->where('producto_sucursal.sucursal_id', config('app.sucursal_id'))
            ->whereNotNull('productos.fecha_vencimiento')
            ->whereDate('productos.fecha_vencimiento', '>=', Carbon::today())
            ->whereDate('productos.fecha_vencimiento', '<=', Carbon::today()->addDays($dias))
            ->whereNull('productos.deleted_at')
            ->select('productos.id', 'productos.nombre', 'producto_sucursal.stock', 'productos.fecha_vencimiento')
            ->orderBy('productos.fecha_vencimiento')
            ->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'nombre'           => $p->nombre,
                'stock'            => (float) $p->stock,
                'fecha_vencimiento'=> $p->fecha_vencimiento,
                'dias_restantes'   => Carbon::today()->diffInDays($p->fecha_vencimiento),
            ])
            ->toArray();
    }

    // =========================================================
    // REPORTE: Ventas por forma de pago
    // =========================================================
    public function ventasPorFormaPago(string $desde, string $hasta): array
    {
        $rows = DB::table('ventas')
            ->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('estado', 'completada')
            ->whereNull('deleted_at')
            ->select(
                'forma_pago',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total) as total'),
            )
            ->groupBy('forma_pago')
            ->orderByDesc('total')
            ->get();

        return $rows->map(fn($r) => [
            'forma_pago' => $r->forma_pago,
            'cantidad'   => (int) $r->cantidad,
            'total'      => round((float) $r->total, 2),
        ])->toArray();
    }

    // =========================================================
    // EXPORT DATA: Ventas para Excel
    // =========================================================
    public function exportarVentas(string $desde, string $hasta): array
    {
        return DB::table('ventas')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'ventas.usuario_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'ventas.cliente_id')
            ->leftJoin('comprobantes', 'comprobantes.venta_id', '=', 'ventas.id')
            ->whereBetween('ventas.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->whereNull('ventas.deleted_at')
            ->select(
                'ventas.numero_venta',
                DB::raw("COALESCE(clientes.nombre_completo, 'Público General') as cliente"),
                'ventas.forma_pago',
                'ventas.subtotal',
                'ventas.igv',
                'ventas.total',
                'ventas.estado',
                'ventas.tipo_comprobante',
                'comprobantes.serie as serie_comprobante',
                'comprobantes.numero_comprobante as numero_comprobante',
                DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) as vendedor"),
                'ventas.created_at as fecha',
            )
            ->orderBy('ventas.created_at')
            ->get()
            ->map(fn($r) => [
                'N° Venta'       => $r->numero_venta,
                'Cliente'        => $r->cliente,
                'Forma de Pago'  => $r->forma_pago,
                'Subtotal'       => (float) $r->subtotal,
                'IGV'            => (float) $r->igv,
                'Total'          => (float) $r->total,
                'Estado'         => $r->estado,
                'Comprobante'    => $r->tipo_comprobante,
                'Serie'          => $r->serie_comprobante,
                'N° Comprobante' => $r->numero_comprobante,
                'Vendedor'       => $r->vendedor,
                'Fecha'          => $r->fecha,
            ])
            ->toArray();
    }

    // =========================================================
    // EXPORT DATA: Productos más vendidos para Excel
    // =========================================================
    public function exportarProductosMasVendidos(string $desde, string $hasta): array
    {
        return collect($this->productosMasVendidos($desde, $hasta, 100))
            ->map(fn($r) => [
                'Producto'         => $r['nombre'],
                'Categoría'        => $r['categoria'],
                'Cantidad Vendida' => $r['cantidad_vendida'],
                'Total Facturado'  => $r['total_facturado'],
            ])
            ->toArray();
    }

    // =========================================================
    // EXPORT DATA: Stock bajo para Excel
    // =========================================================
    public function exportarStockBajo(): array
    {
        return collect($this->stockBajo())
            ->map(fn($r) => [
                'Producto'      => $r['nombre'],
                'Stock Actual'  => $r['stock'],
                'Stock Mínimo'  => $r['stock_minimo'],
                'Diferencia'    => $r['diferencia'],
                'Unidad'        => $r['unidad'],
            ])
            ->toArray();
    }

    // =========================================================
    // EXPORT DATA: Inventario actual para Excel
    // =========================================================
    public function exportarInventarioActual(?string $categoria = null, ?string $buscar = null): array
    {
        return $this->inventarioActual($categoria, $buscar);
    }

    // =========================================================
    // REPORTE: Inventario actual (todos los productos con stock)
    // =========================================================
    public function inventarioActual(?string $categoria = null, ?string $buscar = null): array
    {
        $query = DB::table('producto_sucursal')
            ->join('productos', 'productos.id', '=', 'producto_sucursal.producto_id')
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->where('producto_sucursal.sucursal_id', config('app.sucursal_id'))
            ->whereNull('productos.deleted_at')
            ->select(
                'productos.id',
                'productos.codigo',
                'productos.nombre',
                'categorias.nombre as categoria',
                'producto_sucursal.stock',
                'producto_sucursal.stock_minimo',
                'productos.precio_venta',
                'productos.costo',
                'productos.unidad_medida',
                'productos.tipo',
            );

        if ($categoria) {
            $query->where('categorias.nombre', 'like', "%{$categoria}%");
        }

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('productos.nombre', 'like', "%{$buscar}%")
                  ->orWhere('productos.codigo', 'like', "%{$buscar}%");
            });
        }

        return $query
            ->orderBy('categorias.nombre')
            ->orderBy('productos.nombre')
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'codigo'       => $p->codigo ?? '—',
                'nombre'       => $p->nombre,
                'categoria'    => $p->categoria,
                'stock'        => (float) $p->stock,
                'stock_minimo' => (float) $p->stock_minimo,
                'precio_venta' => round((float) $p->precio_venta, 2),
                'costo'        => round((float) ($p->costo ?? 0), 2),
                'unidad'       => $p->unidad_medida,
                'tipo'         => $p->tipo,
                'estado_stock' => (float) $p->stock <= 0
                    ? 'agotado'
                    : ((float) $p->stock <= (float) $p->stock_minimo ? 'bajo' : 'normal'),
            ])
            ->toArray();
    }
}
