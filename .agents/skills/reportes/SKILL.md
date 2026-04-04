---
name: reportes
description: Dashboard principal y reportes gerenciales para Panadería Jara. Usa este skill cuando trabajes en el dashboard de inicio del sistema, reportes de ventas por período, productos más/menos vendidos, utilidad estimada, movimientos de caja, stock bajo, productos por vencer, ventas por usuario, o cualquier consulta analítica o de inteligencia de negocio del sistema.
---

# Reportes y Dashboard — Panadería Jara

## Dashboard Principal (KPIs del día)

```php
<?php

namespace App\Services;

use App\Models\{Venta, Producto, AperturaCaja, MovimientoInventario};
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function resumenDia(): array
    {
        $hoy = today();

        return [
            // Ventas del día
            'ventas_hoy' => [
                'total'    => Venta::whereDate('created_at', $hoy)->where('estado', 'completada')->sum('total'),
                'cantidad' => Venta::whereDate('created_at', $hoy)->where('estado', 'completada')->count(),
                'promedio' => Venta::whereDate('created_at', $hoy)->where('estado', 'completada')->avg('total'),
            ],

            // Caja actual
            'caja_activa' => AperturaCaja::where('estado', 'abierta')
                ->withSum(['movimientos as total_ingresos' => fn($q) => $q->where('tipo', 'ingreso')], 'monto')
                ->withSum(['movimientos as total_egresos'  => fn($q) => $q->where('tipo', 'egreso')],  'monto')
                ->first(),

            // Alertas
            'alertas' => [
                'stock_bajo'    => Producto::activos()->stockBajo()->count(),
                'por_vencer'    => Producto::activos()->porVencer(7)->count(),
                'vencidos'      => Producto::activos()->whereDate('fecha_vencimiento', '<', $hoy)->count(),
            ],

            // Top productos del día
            'top_productos' => DB::table('detalle_ventas')
                ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
                ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
                ->whereDate('ventas.created_at', $hoy)
                ->where('ventas.estado', 'completada')
                ->select('productos.nombre', DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'), DB::raw('SUM(detalle_ventas.subtotal) as total_monto'))
                ->groupBy('productos.id', 'productos.nombre')
                ->orderByDesc('total_monto')
                ->limit(5)
                ->get(),

            // Últimas ventas
            'ultimas_ventas' => Venta::with(['usuario', 'cliente'])
                ->whereDate('created_at', $hoy)
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    public function ventasPorPeriodo(string $desde, string $hasta, string $agrupar = 'dia'): array
    {
        $formato = match($agrupar) {
            'dia'  => '%Y-%m-%d',
            'semana' => '%Y-%u',
            'mes'  => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return DB::table('ventas')
            ->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('estado', 'completada')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$formato}') as periodo"),
                DB::raw('SUM(total) as total_ventas'),
                DB::raw('COUNT(*) as cantidad_ventas'),
                DB::raw('AVG(total) as ticket_promedio'),
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->toArray();
    }

    public function productosMasVendidos(string $desde, string $hasta, int $limit = 10): array
    {
        return DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->whereBetween('ventas.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('ventas.estado', 'completada')
            ->select(
                'productos.nombre',
                'categorias.nombre as categoria',
                DB::raw('SUM(detalle_ventas.cantidad) as cantidad_vendida'),
                DB::raw('SUM(detalle_ventas.subtotal) as total_facturado'),
            )
            ->groupBy('productos.id', 'productos.nombre', 'categorias.nombre')
            ->orderByDesc('total_facturado')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function utilidadEstimada(string $desde, string $hasta): array
    {
        $datos = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->whereBetween('ventas.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('ventas.estado', 'completada')
            ->whereNotNull('productos.costo')
            ->select(
                DB::raw('SUM(detalle_ventas.subtotal) as total_venta'),
                DB::raw('SUM(detalle_ventas.cantidad * productos.costo) as total_costo'),
            )
            ->first();

        $utilidad = ($datos->total_venta ?? 0) - ($datos->total_costo ?? 0);
        $margen   = $datos->total_venta > 0
            ? ($utilidad / $datos->total_venta) * 100
            : 0;

        return [
            'total_venta'  => round($datos->total_venta ?? 0, 2),
            'total_costo'  => round($datos->total_costo ?? 0, 2),
            'utilidad'     => round($utilidad, 2),
            'margen_pct'   => round($margen, 2),
        ];
    }

    public function ventasPorUsuario(string $desde, string $hasta): array
    {
        return DB::table('ventas')
            ->join('users', 'users.id', '=', 'ventas.usuario_id')
            ->whereBetween('ventas.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('ventas.estado', 'completada')
            ->select(
                DB::raw("CONCAT(users.nombre, ' ', users.apellido) as vendedor"),
                DB::raw('COUNT(*) as cantidad_ventas'),
                DB::raw('SUM(ventas.total) as total_vendido'),
            )
            ->groupBy('users.id', 'users.nombre', 'users.apellido')
            ->orderByDesc('total_vendido')
            ->get()
            ->toArray();
    }

    public function movimientosCaja(string $desde, string $hasta): array
    {
        return DB::table('movimientos_caja')
            ->whereBetween('created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->select(
                'tipo',
                DB::raw('SUM(monto) as total'),
                DB::raw('COUNT(*) as cantidad'),
            )
            ->groupBy('tipo')
            ->get()
            ->toArray();
    }

    public function mermasPorPeriodo(string $desde, string $hasta): array
    {
        return DB::table('movimientos_inventario')
            ->join('productos', 'productos.id', '=', 'movimientos_inventario.producto_id')
            ->whereBetween('movimientos_inventario.created_at', [$desde . ' 00:00:00', $hasta . ' 23:59:59'])
            ->where('movimientos_inventario.tipo', 'merma')
            ->select(
                'productos.nombre',
                'movimientos_inventario.motivo',
                DB::raw('SUM(movimientos_inventario.cantidad) as cantidad_total'),
                DB::raw('SUM(movimientos_inventario.cantidad * productos.costo) as costo_perdida'),
            )
            ->groupBy('productos.id', 'productos.nombre', 'movimientos_inventario.motivo')
            ->orderByDesc('costo_perdida')
            ->get()
            ->toArray();
    }
}
```

---

## Endpoints de Reportes

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/dashboard` | KPIs del día |
| GET | `/reportes/ventas` | Ventas por período |
| GET | `/reportes/productos-top` | Productos más vendidos |
| GET | `/reportes/utilidad` | Utilidad estimada |
| GET | `/reportes/ventas-usuario` | Ventas por vendedor |
| GET | `/reportes/caja` | Movimientos de caja |
| GET | `/reportes/mermas` | Mermas y pérdidas |
| GET | `/reportes/stock-bajo` | Productos con stock bajo |
| GET | `/reportes/por-vencer` | Productos próximos a vencer |

---

## Parámetros estándar de reportes

```
?desde=2025-01-01&hasta=2025-01-31&agrupar=dia&limit=10
```

---

## Componente Dashboard (Vue — estructura)

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard.store'
import { useAlertas } from '@/composables/useAlertas'

const dash    = useDashboardStore()
const alertas = useAlertas()

onMounted(() => {
  dash.cargar()
})
</script>

<template>
  <!-- Fila de KPIs -->
  <div class="row g-3 mb-4">
    <KpiCard titulo="Ventas hoy" :valor="`S/. ${dash.data.ventas_hoy?.total}`" icono="fas fa-cash-register" color="warning" />
    <KpiCard titulo="Transacciones" :valor="dash.data.ventas_hoy?.cantidad" icono="fas fa-receipt" color="info" />
    <KpiCard titulo="Ticket promedio" :valor="`S/. ${dash.data.ventas_hoy?.promedio}`" icono="fas fa-tag" color="success" />
    <KpiCard titulo="Stock bajo" :valor="dash.data.alertas?.stock_bajo" icono="fas fa-exclamation-triangle" color="danger" />
  </div>

  <!-- Alertas -->
  <AlertasPanel :stock-bajo="alertas.stockBajo" :por-vencer="alertas.porVencer" />

  <!-- Gráfico de ventas + Top productos -->
  <div class="row g-3">
    <div class="col-lg-8"><GraficoVentasSemana /></div>
    <div class="col-lg-4"><TopProductos :items="dash.data.top_productos" /></div>
  </div>

  <!-- Últimas ventas -->
  <UltimasVentas :ventas="dash.data.ultimas_ventas" />
</template>
```
