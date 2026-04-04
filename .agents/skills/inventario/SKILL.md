---
name: inventario
description: Control de inventario, stock, movimientos, mermas, producción con insumos y control de vencimientos para Panadería Jara. Usa este skill cuando trabajes en registro de ingresos de mercadería, egresos manuales de stock, ajustes de inventario, registro de mermas o pérdidas, alertas de stock bajo, alertas de productos por vencer, módulo de producción (descuento automático de insumos por recetas), lotes, y cualquier movimiento del inventario que no sea una venta directa.
---

# Módulo de Inventario — Panadería Jara

## Tipos de Movimiento

| Tipo | Descripción | Afecta Stock |
|---|---|---|
| `ingreso` | Compra o ingreso manual | + suma |
| `egreso` | Salida manual o venta | - resta |
| `ajuste` | Corrección de inventario | + o - |
| `merma` | Pérdida, vencimiento, daño | - resta |
| `produccion` | Consumo de insumos en producción | - resta (insumo) / + suma (elaborado) |

---

## Service de Inventario

```php
<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    public function registrarIngreso(array $datos, int $usuarioId): MovimientoInventario
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            $producto = Producto::lockForUpdate()->findOrFail($datos['producto_id']);
            $stockAnterior = $producto->stock;

            $producto->increment('stock', $datos['cantidad']);

            // Actualizar costo promedio si se provee
            if (isset($datos['costo_unitario'])) {
                $this->actualizarCostoPromedio($producto, $datos['cantidad'], $datos['costo_unitario']);
            }

            return MovimientoInventario::create([
                'producto_id'   => $producto->id,
                'usuario_id'    => $usuarioId,
                'compra_id'     => $datos['compra_id'] ?? null,
                'tipo'          => 'ingreso',
                'cantidad'      => $datos['cantidad'],
                'stock_anterior'=> $stockAnterior,
                'stock_nuevo'   => $producto->fresh()->stock,
                'motivo'        => $datos['motivo'] ?? 'Ingreso manual',
                'observacion'   => $datos['observacion'] ?? null,
            ]);
        });
    }

    public function registrarMerma(array $datos, int $usuarioId): MovimientoInventario
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            $producto = Producto::lockForUpdate()->findOrFail($datos['producto_id']);

            if ($producto->stock < $datos['cantidad']) {
                throw new \Exception('No hay stock suficiente para registrar la merma');
            }

            $stockAnterior = $producto->stock;
            $producto->decrement('stock', $datos['cantidad']);

            return MovimientoInventario::create([
                'producto_id'   => $producto->id,
                'usuario_id'    => $usuarioId,
                'tipo'          => 'merma',
                'cantidad'      => $datos['cantidad'],
                'stock_anterior'=> $stockAnterior,
                'stock_nuevo'   => $producto->fresh()->stock,
                'motivo'        => $datos['motivo'], // vencido, dañado, derramado, etc.
                'observacion'   => $datos['observacion'] ?? null,
            ]);
        });
    }

    public function ajustarStock(array $datos, int $usuarioId): MovimientoInventario
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            $producto      = Producto::lockForUpdate()->findOrFail($datos['producto_id']);
            $stockAnterior = $producto->stock;
            $diferencia    = $datos['stock_real'] - $producto->stock;

            $producto->update(['stock' => $datos['stock_real']]);

            return MovimientoInventario::create([
                'producto_id'   => $producto->id,
                'usuario_id'    => $usuarioId,
                'tipo'          => 'ajuste',
                'cantidad'      => abs($diferencia),
                'stock_anterior'=> $stockAnterior,
                'stock_nuevo'   => $datos['stock_real'],
                'motivo'        => 'Ajuste de inventario: ' . ($datos['motivo'] ?? 'Conteo físico'),
                'observacion'   => $datos['observacion'] ?? null,
            ]);
        });
    }

    /**
     * Producción: descuenta insumos y suma al producto elaborado
     */
    public function registrarProduccion(int $recetaId, float $cantidad, int $usuarioId): array
    {
        return DB::transaction(function () use ($recetaId, $cantidad, $usuarioId) {
            $receta = \App\Models\Receta::with(['insumos.insumo', 'producto'])->findOrFail($recetaId);

            // Verificar stock de todos los insumos primero
            foreach ($receta->insumos as $recetaInsumo) {
                $insumo = Producto::lockForUpdate()->find($recetaInsumo->insumo_id);
                $requerido = $recetaInsumo->cantidad * $cantidad;
                if ($insumo->stock < $requerido) {
                    throw new \Exception("Stock insuficiente de {$insumo->nombre}. Disponible: {$insumo->stock}, requerido: {$requerido}");
                }
            }

            $movimientos = [];

            // Descontar insumos
            foreach ($receta->insumos as $recetaInsumo) {
                $insumo = Producto::lockForUpdate()->find($recetaInsumo->insumo_id);
                $cantidadUsada = $recetaInsumo->cantidad * $cantidad;
                $stockAnterior = $insumo->stock;
                $insumo->decrement('stock', $cantidadUsada);

                $movimientos[] = MovimientoInventario::create([
                    'producto_id'   => $insumo->id,
                    'usuario_id'    => $usuarioId,
                    'tipo'          => 'produccion',
                    'cantidad'      => $cantidadUsada,
                    'stock_anterior'=> $stockAnterior,
                    'stock_nuevo'   => $insumo->fresh()->stock,
                    'motivo'        => "Producción: {$receta->nombre} x{$cantidad}",
                ]);
            }

            // Sumar al producto elaborado
            $productoElaborado = $receta->producto;
            $stockAnterior     = $productoElaborado->stock;
            $unidadesProducidas = $receta->rendimiento * $cantidad;
            $productoElaborado->increment('stock', $unidadesProducidas);

            $movimientos[] = MovimientoInventario::create([
                'producto_id'   => $productoElaborado->id,
                'usuario_id'    => $usuarioId,
                'tipo'          => 'produccion',
                'cantidad'      => $unidadesProducidas,
                'stock_anterior'=> $stockAnterior,
                'stock_nuevo'   => $productoElaborado->fresh()->stock,
                'motivo'        => "Producción: {$receta->nombre} x{$cantidad}",
            ]);

            return $movimientos;
        });
    }

    private function actualizarCostoPromedio(Producto $producto, float $cantidad, float $costoNuevo): void
    {
        $stockActual  = $producto->stock; // antes del incremento
        $costoActual  = $producto->costo ?? 0;
        $totalAnterior = $stockActual * $costoActual;
        $totalNuevo    = $cantidad * $costoNuevo;

        if (($stockActual + $cantidad) > 0) {
            $costoPromedio = ($totalAnterior + $totalNuevo) / ($stockActual + $cantidad);
            $producto->update(['costo' => round($costoPromedio, 4)]);
        }
    }

    /**
     * Alertas del sistema
     */
    public function productosStockBajo(): \Illuminate\Database\Eloquent\Collection
    {
        return Producto::activos()
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->with('categoria')
            ->get();
    }

    public function productosPorVencer(int $dias = 7): \Illuminate\Database\Eloquent\Collection
    {
        return Producto::activos()
            ->porVencer($dias)
            ->with('categoria')
            ->orderBy('fecha_vencimiento')
            ->get();
    }
}
```

---

## Endpoints del Módulo

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/inventario/movimientos` | Historial de movimientos |
| POST | `/inventario/ingreso` | Registrar ingreso de stock |
| POST | `/inventario/merma` | Registrar merma/pérdida |
| POST | `/inventario/ajuste` | Ajustar stock |
| POST | `/inventario/produccion` | Registrar producción |
| GET | `/inventario/alertas/stock-bajo` | Productos con stock bajo |
| GET | `/inventario/alertas/por-vencer` | Productos próximos a vencer |
| GET | `/inventario/kardex/{productoId}` | Kardex de un producto |

---

## Motivos de Merma (Enum)

```php
enum MotivoMerma: string
{
    case VENCIDO    = 'Producto vencido';
    case DANADO     = 'Producto dañado';
    case DERRAMADO  = 'Derramado/caído';
    case DEVOLUCION = 'Devolución de cliente';
    case MUESTREO   = 'Muestreo o degustación';
    case ERROR      = 'Error de conteo';
    case OTRO       = 'Otro motivo';
}
```

---

## Alertas en Dashboard (Vue)

```javascript
// src/composables/useAlertas.js
import { ref, onMounted } from 'vue'
import api from '@/services/api'

export function useAlertas() {
  const stockBajo   = ref([])
  const porVencer   = ref([])
  const cargando    = ref(false)

  async function cargarAlertas() {
    cargando.value = true
    try {
      const [s, v] = await Promise.all([
        api.get('/inventario/alertas/stock-bajo'),
        api.get('/inventario/alertas/por-vencer'),
      ])
      stockBajo.value = s.data.data
      porVencer.value = v.data.data
    } finally {
      cargando.value = false
    }
  }

  onMounted(cargarAlertas)

  return { stockBajo, porVencer, cargando, cargarAlertas }
}
```
