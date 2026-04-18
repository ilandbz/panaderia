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
            // Factor de escala (si se produce más o menos del rendimiento base de la receta)
            $factor = $cantidadRendimiento / $receta->rendimiento;

            // 1. Validar y descontar insumos
            foreach ($receta->insumos as $detalle) {
                $insumo = $detalle->insumo;
                $cantidadNecesaria = $detalle->cantidad * $factor;

                if ($insumo->stock < $cantidadNecesaria) {
                    throw new \Exception("Insumo insuficiente: {$insumo->nombre}");
                }

                $stockAnterior = $insumo->stock;
                $insumo->decrement('stock', $cantidadNecesaria);

                MovimientoInventario::create([
                    'producto_id' => $insumo->id,
                    'usuario_id'  => Auth::id(),
                    'tipo'        => 'egreso',
                    'cantidad'    => $cantidadNecesaria,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo'    => $insumo->stock,
                    'motivo'      => 'produccion',
                    'observacion' => "Uso en producción de: {$receta->producto->nombre}",
                ]);
            }

            // 2. Incrementar stock del producto terminado
            $productoTerminado = $receta->producto;
            $stockAnteriorPT = $productoTerminado->stock;
            $productoTerminado->increment('stock', $cantidadRendimiento);

            MovimientoInventario::create([
                'producto_id' => $productoTerminado->id,
                'usuario_id'  => Auth::id(),
                'tipo'        => 'ingreso',
                'cantidad'    => $cantidadRendimiento,
                'stock_anterior' => $stockAnteriorPT,
                'stock_nuevo'    => $productoTerminado->stock,
                'motivo'      => 'produccion',
                'observacion' => "Nueva producción de: {$receta->producto->nombre}",
            ]);

            return $productoTerminado;
        });
    }
}
