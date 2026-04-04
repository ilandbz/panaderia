---
name: pos-ventas
description: Módulo de Punto de Venta (POS) y ventas para Panadería Jara. Usa este skill cuando trabajes en la pantalla de venta rápida en mostrador, registro de ventas, cálculo de subtotales/descuentos/IGV/total/vuelto, formas de pago, búsqueda de productos en el POS, emisión de comprobantes, anulación de ventas, gestión de clientes en venta, o cualquier flujo relacionado con la venta de productos al público.
---

# Módulo POS y Ventas — Panadería Jara

## Flujo de una Venta

```
1. Cajero abre el POS
2. Sistema verifica que haya una caja abierta
3. Buscar/escanear productos → agregar al carrito
4. Seleccionar cliente (opcional) / tipo comprobante
5. Aplicar descuentos (si corresponde)
6. Registrar forma de pago → calcular vuelto
7. Confirmar venta
8. Sistema: descuenta stock, registra movimiento de caja, genera comprobante
9. Imprimir ticket / enviar a SUNAT (si boleta/factura)
```

---

## Service de Ventas (Backend)

```php
<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\AperturaCaja;
use App\Enums\EstadoVenta;
use Illuminate\Support\Facades\DB;

class VentaService
{
    public function crear(array $datos, int $usuarioId): Venta
    {
        return DB::transaction(function () use ($datos, $usuarioId) {

            // 1. Validar caja abierta
            $caja = AperturaCaja::where('estado', 'abierta')
                ->where('usuario_id', $usuarioId)
                ->firstOrFail();

            // 2. Calcular totales
            $totales = $this->calcularTotales($datos['items'], $datos['descuento_general'] ?? 0);

            // 3. Crear venta
            $venta = Venta::create([
                'numero_venta'    => $this->generarNumero(),
                'usuario_id'      => $usuarioId,
                'cliente_id'      => $datos['cliente_id'] ?? null,
                'apertura_caja_id'=> $caja->id,
                'subtotal'        => $totales['subtotal'],
                'igv'             => $totales['igv'],
                'descuento'       => $totales['descuento'],
                'total'           => $totales['total'],
                'monto_pagado'    => $datos['monto_pagado'],
                'vuelto'          => max(0, $datos['monto_pagado'] - $totales['total']),
                'forma_pago'      => $datos['forma_pago'],
                'tipo_comprobante'=> $datos['tipo_comprobante'] ?? 'ticket',
                'estado'          => EstadoVenta::COMPLETADA,
            ]);

            // 4. Registrar detalles y descontar stock
            foreach ($datos['items'] as $item) {
                $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);

                // Validar stock suficiente
                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para: {$producto->nombre}");
                }

                $subtotalItem = ($item['precio_unitario'] * $item['cantidad']) - ($item['descuento'] ?? 0);

                $venta->detalles()->create([
                    'producto_id'    => $item['producto_id'],
                    'cantidad'       => $item['cantidad'],
                    'precio_unitario'=> $item['precio_unitario'],
                    'descuento'      => $item['descuento'] ?? 0,
                    'subtotal'       => $subtotalItem,
                ]);

                // Descontar stock
                $stockAnterior = $producto->stock;
                $producto->decrement('stock', $item['cantidad']);

                // Registrar movimiento de inventario
                $producto->movimientos()->create([
                    'usuario_id'    => $usuarioId,
                    'venta_id'      => $venta->id,
                    'tipo'          => 'egreso',
                    'cantidad'      => $item['cantidad'],
                    'stock_anterior'=> $stockAnterior,
                    'stock_nuevo'   => $producto->fresh()->stock,
                    'motivo'        => "Venta #{$venta->numero_venta}",
                ]);
            }

            // 5. Registrar movimiento de caja
            $caja->movimientos()->create([
                'usuario_id' => $usuarioId,
                'venta_id'   => $venta->id,
                'tipo'       => 'ingreso',
                'concepto'   => "Venta #{$venta->numero_venta}",
                'monto'      => $totales['total'],
                'forma_pago' => $datos['forma_pago'],
            ]);

            return $venta->load(['detalles.producto', 'cliente', 'comprobante']);
        });
    }

    private function calcularTotales(array $items, float $descuentoGeneral = 0): array
    {
        $subtotalBruto = collect($items)->sum(
            fn($i) => ($i['precio_unitario'] * $i['cantidad']) - ($i['descuento'] ?? 0)
        );

        $descuento = $descuentoGeneral;
        $subtotal  = $subtotalBruto - $descuento;

        // IGV solo sobre productos afectos
        $baseIgv = collect($items)->filter(fn($i) => $i['afecto_igv'] ?? true)
            ->sum(fn($i) => $i['precio_unitario'] * $i['cantidad']);

        $igv   = round($baseIgv * 0.18 / 1.18, 2); // precio incluye IGV
        $total = round($subtotal, 2);

        return compact('subtotal', 'igv', 'descuento', 'total');
    }

    private function generarNumero(): string
    {
        $ultimo = Venta::max('id') ?? 0;
        return 'V' . str_pad($ultimo + 1, 8, '0', STR_PAD_LEFT);
    }

    public function anular(int $id, string $motivo, int $usuarioId): Venta
    {
        return DB::transaction(function () use ($id, $motivo, $usuarioId) {
            $venta = Venta::with('detalles')->findOrFail($id);

            if ($venta->estado === 'anulada') {
                throw new \Exception('La venta ya está anulada');
            }

            // Revertir stock
            foreach ($venta->detalles as $detalle) {
                $producto = Producto::lockForUpdate()->find($detalle->producto_id);
                $stockAnterior = $producto->stock;
                $producto->increment('stock', $detalle->cantidad);

                $producto->movimientos()->create([
                    'usuario_id'    => $usuarioId,
                    'venta_id'      => $venta->id,
                    'tipo'          => 'ingreso',
                    'cantidad'      => $detalle->cantidad,
                    'stock_anterior'=> $stockAnterior,
                    'stock_nuevo'   => $producto->fresh()->stock,
                    'motivo'        => "Anulación venta #{$venta->numero_venta}",
                ]);
            }

            $venta->update(['estado' => 'anulada', 'observacion' => $motivo]);

            return $venta;
        });
    }
}
```

---

## POS Store (Pinia)

```javascript
// src/stores/pos.store.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { productoService } from '@/services/producto.service'
import { ventaService } from '@/services/venta.service'

export const usePosStore = defineStore('pos', () => {
  const carrito       = ref([])
  const cliente       = ref(null)
  const tipoComprobante = ref('ticket')
  const formaPago     = ref('efectivo')
  const montoPagado   = ref(0)
  const descuentoGeneral = ref(0)
  const loading       = ref(false)

  // Getters
  const subtotal = computed(() =>
    carrito.value.reduce((acc, item) =>
      acc + (item.precio_unitario * item.cantidad) - item.descuento, 0)
  )

  const total = computed(() =>
    Math.max(0, subtotal.value - descuentoGeneral.value)
  )

  const vuelto = computed(() =>
    Math.max(0, montoPagado.value - total.value)
  )

  const cantidadItems = computed(() =>
    carrito.value.reduce((acc, i) => acc + i.cantidad, 0)
  )

  // Actions
  function agregarProducto(producto) {
    const existente = carrito.value.find(i => i.producto_id === producto.id)
    if (existente) {
      existente.cantidad++
    } else {
      carrito.value.push({
        producto_id:    producto.id,
        nombre:         producto.nombre,
        precio_unitario: producto.precio_venta,
        cantidad:       1,
        descuento:      0,
        stock:          producto.stock,
        afecto_igv:     producto.afecto_igv,
      })
    }
  }

  function quitarProducto(productoId) {
    carrito.value = carrito.value.filter(i => i.producto_id !== productoId)
  }

  function actualizarCantidad(productoId, cantidad) {
    const item = carrito.value.find(i => i.producto_id === productoId)
    if (item) {
      if (cantidad <= 0) return quitarProducto(productoId)
      if (cantidad > item.stock) throw new Error(`Stock máximo: ${item.stock}`)
      item.cantidad = cantidad
    }
  }

  function limpiarCarrito() {
    carrito.value      = []
    cliente.value      = null
    tipoComprobante.value = 'ticket'
    formaPago.value    = 'efectivo'
    montoPagado.value  = 0
    descuentoGeneral.value = 0
  }

  async function procesarVenta() {
    if (carrito.value.length === 0) throw new Error('El carrito está vacío')
    if (montoPagado.value < total.value && formaPago.value === 'efectivo') {
      throw new Error('Monto pagado insuficiente')
    }

    loading.value = true
    try {
      const payload = {
        items:             carrito.value,
        cliente_id:        cliente.value?.id ?? null,
        tipo_comprobante:  tipoComprobante.value,
        forma_pago:        formaPago.value,
        monto_pagado:      montoPagado.value,
        descuento_general: descuentoGeneral.value,
      }

      const { data } = await ventaService.crear(payload)
      limpiarCarrito()
      return data.data
    } finally {
      loading.value = false
    }
  }

  return {
    carrito, cliente, tipoComprobante, formaPago, montoPagado,
    descuentoGeneral, loading, subtotal, total, vuelto, cantidadItems,
    agregarProducto, quitarProducto, actualizarCantidad, limpiarCarrito, procesarVenta,
  }
})
```

---

## Plantilla Ticket (HTML para impresión)

```html
<!-- Se genera en el navegador con window.print() -->
<div class="ticket" style="width:80mm; font-family:monospace; font-size:12px;">
  <div style="text-align:center;">
    <strong>PANADERÍA / PASTELERÍA JARA</strong><br>
    RUC: 20XXXXXXXXX<br>
    Jr. Ejemplo 123 - Huánuco<br>
    Tel: 062-XXXXXX<br>
    <hr>
    <strong>{{ tipo_comprobante.toUpperCase() }}</strong><br>
    {{ numero_comprobante }}
  </div>
  <hr>
  <div>Fecha: {{ fecha }}</div>
  <div>Cajero: {{ usuario }}</div>
  <div v-if="cliente">Cliente: {{ cliente }}</div>
  <hr>
  <table width="100%">
    <tr v-for="item in detalles">
      <td>{{ item.nombre }}</td>
      <td align="right">{{ item.cantidad }} x {{ item.precio }}</td>
      <td align="right">{{ item.subtotal }}</td>
    </tr>
  </table>
  <hr>
  <div style="text-align:right;">
    <div>Subtotal: S/. {{ subtotal }}</div>
    <div v-if="descuento > 0">Descuento: -S/. {{ descuento }}</div>
    <strong>TOTAL: S/. {{ total }}</strong><br>
    <div>Pagado: S/. {{ pagado }}</div>
    <div>Vuelto: S/. {{ vuelto }}</div>
  </div>
  <hr>
  <div style="text-align:center; font-size:10px;">
    ¡Gracias por su preferencia!<br>
    www.panaderiacjara.com
  </div>
</div>
```

---

## Endpoints del Módulo

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/ventas` | Listar ventas con filtros |
| POST | `/ventas` | Crear nueva venta |
| GET | `/ventas/{id}` | Detalle de venta |
| POST | `/ventas/{id}/anular` | Anular venta |
| GET | `/ventas/{id}/ticket` | HTML del ticket |
| GET | `/productos/buscar` | Búsqueda rápida POS |
