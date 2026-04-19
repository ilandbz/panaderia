<?php

namespace App\Services;

use App\Models\Receta;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProduccionService
{
    /**
     * Ejecuta una orden de producción:
     * 1. Descuenta insumos del inventario según la receta.
     * 2. Incrementa el stock del producto terminado.
     * 3. Registra movimientos de inventario.
     */
    public function ejecutar(Receta $receta, float $cantidadRendimiento)
    {
        return DB::transaction(function () use ($receta, $cantidadRendimiento) {
            $sucursal_id = config('app.sucursal_id') ?? auth()->user()->sucursal_id;
            
            // Factor de escala (si se produce más o menos del rendimiento base de la receta)
            $factor = $cantidadRendimiento / $receta->rendimiento;

            // 1. Validar y descontar insumos
            foreach ($receta->insumos as $detalle) {
                $insumo = $detalle->insumo;
                $cantidadNecesaria = $detalle->cantidad * $factor;

                // Obtener stock actual del insumo en la sucursal
                $pivotInsumo = $insumo->sucursales()->where('sucursal_id', $sucursal_id)->first();
                $stockAnteriorInsumo = $pivotInsumo ? $pivotInsumo->pivot->stock : 0;

                if ($stockAnteriorInsumo < $cantidadNecesaria) {
                    throw new \Exception("Insumo insuficiente en esta sede: {$insumo->nombre}");
                }

                $nuevoStockInsumo = $stockAnteriorInsumo - $cantidadNecesaria;

                // Descontar en la sede
                $insumo->sucursales()->updateExistingPivot($sucursal_id, [
                    'stock' => $nuevoStockInsumo
                ]);

                MovimientoInventario::create([
                    'producto_id' => $insumo->id,
                    'usuario_id'  => Auth::id(),
                    'sucursal_id' => $sucursal_id,
                    'tipo'        => 'egreso',
                    'cantidad'    => $cantidadNecesaria,
                    'stock_anterior' => $stockAnteriorInsumo,
                    'stock_nuevo'    => $nuevoStockInsumo,
                    'motivo'      => 'produccion',
                    'observacion' => "Uso en producción de: {$receta->producto->nombre}",
                ]);
            }

            // 2. Incrementar stock del producto terminado
            $productoTerminado = $receta->producto;
            
            $pivotPT = $productoTerminado->sucursales()->where('sucursal_id', $sucursal_id)->first();
            $stockAnteriorPT = $pivotPT ? $pivotPT->pivot->stock : 0;
            $nuevoStockPT = $stockAnteriorPT + $cantidadRendimiento;

            // Incrementar en la sede
            $productoTerminado->sucursales()->syncWithoutDetaching([
                $sucursal_id => ['stock' => $nuevoStockPT]
            ]);

            MovimientoInventario::create([
                'producto_id' => $productoTerminado->id,
                'usuario_id'  => Auth::id(),
                'sucursal_id' => $sucursal_id,
                'tipo'        => 'ingreso',
                'cantidad'    => $cantidadRendimiento,
                'stock_anterior' => $stockAnteriorPT,
                'stock_nuevo'    => $nuevoStockPT,
                'motivo'      => 'produccion',
                'observacion' => "Nueva producción de: {$receta->producto->nombre}",
            ]);

            return $productoTerminado;
        });
    }
}
