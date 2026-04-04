---
name: caja
description: Módulo de caja para Panadería Jara. Usa este skill cuando trabajes en apertura de caja, cierre de caja, registro de gastos o egresos manuales, cuadre diario, arqueo de caja, historial de movimientos, diferencias en caja, o cualquier operación relacionada con el control del efectivo y otros medios de pago del negocio.
---

# Módulo de Caja — Panadería Jara

## Flujo de la Caja

```
Inicio del turno
    └─► Apertura de Caja (con monto inicial)
            └─► Venta → Ingreso automático
            └─► Gasto/Egreso manual (luz, gas, insumo urgente)
            └─► Ingreso manual (cobro deuda, etc.)
    └─► Cierre de Caja
            └─► Sistema calcula total esperado
            └─► Cajero ingresa monto contado
            └─► Se registra diferencia y observaciones
```

---

## Service de Caja (Backend)

```php
<?php

namespace App\Services;

use App\Models\AperturaCaja;
use App\Models\MovimientoCaja;
use Illuminate\Support\Facades\DB;

class CajaService
{
    public function abrirCaja(array $datos, int $usuarioId): AperturaCaja
    {
        // Verificar que no haya una caja ya abierta por este usuario
        $cajaAbierta = AperturaCaja::where('usuario_id', $usuarioId)
            ->where('estado', 'abierta')
            ->first();

        if ($cajaAbierta) {
            throw new \Exception('Ya tienes una caja abierta. Debes cerrarla primero.');
        }

        return DB::transaction(function () use ($datos, $usuarioId) {
            $caja = AperturaCaja::create([
                'usuario_id'    => $usuarioId,
                'monto_apertura'=> $datos['monto_apertura'],
                'observaciones' => $datos['observaciones'] ?? null,
                'estado'        => 'abierta',
                'fecha_apertura'=> now(),
            ]);

            // Registrar apertura como movimiento
            $caja->movimientos()->create([
                'usuario_id' => $usuarioId,
                'tipo'       => 'ingreso',
                'concepto'   => 'Apertura de caja',
                'monto'      => $datos['monto_apertura'],
                'forma_pago' => 'efectivo',
            ]);

            return $caja;
        });
    }

    public function cerrarCaja(int $cajaId, array $datos, int $usuarioId): AperturaCaja
    {
        return DB::transaction(function () use ($cajaId, $datos, $usuarioId) {
            $caja = AperturaCaja::where('id', $cajaId)
                ->where('estado', 'abierta')
                ->lockForUpdate()
                ->firstOrFail();

            // Calcular total según movimientos del sistema
            $totalIngresos = $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
            $totalEgresos  = $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
            $montoSistema  = $caja->monto_apertura + $totalIngresos - $totalEgresos;

            $montoCierre  = $datos['monto_cierre'];
            $diferencia   = $montoCierre - $montoSistema;

            $caja->update([
                'cerrado_por'   => $usuarioId,
                'monto_cierre'  => $montoCierre,
                'monto_sistema' => $montoSistema,
                'diferencia'    => $diferencia,
                'observaciones' => $datos['observaciones'] ?? null,
                'estado'        => 'cerrada',
                'fecha_cierre'  => now(),
            ]);

            return $caja->fresh();
        });
    }

    public function registrarMovimiento(array $datos, int $usuarioId): MovimientoCaja
    {
        $caja = AperturaCaja::where('usuario_id', $usuarioId)
            ->where('estado', 'abierta')
            ->firstOrFail();

        return $caja->movimientos()->create([
            'usuario_id' => $usuarioId,
            'tipo'       => $datos['tipo'],
            'concepto'   => $datos['concepto'],
            'monto'      => $datos['monto'],
            'forma_pago' => $datos['forma_pago'] ?? 'efectivo',
            'observacion'=> $datos['observacion'] ?? null,
        ]);
    }

    public function resumenCaja(int $cajaId): array
    {
        $caja = AperturaCaja::with(['movimientos', 'usuario'])->findOrFail($cajaId);

        $movimientos = $caja->movimientos;

        return [
            'caja'           => $caja,
            'total_ingresos' => $movimientos->where('tipo', 'ingreso')->sum('monto'),
            'total_egresos'  => $movimientos->where('tipo', 'egreso')->sum('monto'),
            'total_efectivo' => $movimientos->where('forma_pago', 'efectivo')->sum(fn($m) =>
                $m->tipo === 'ingreso' ? $m->monto : -$m->monto
            ),
            'total_yape'     => $movimientos->where('forma_pago', 'yape')->where('tipo', 'ingreso')->sum('monto'),
            'total_plin'     => $movimientos->where('forma_pago', 'plin')->where('tipo', 'ingreso')->sum('monto'),
            'total_tarjeta'  => $movimientos->where('forma_pago', 'tarjeta')->where('tipo', 'ingreso')->sum('monto'),
            'ventas_count'   => $movimientos->whereNotNull('venta_id')->count(),
            'monto_sistema'  => $caja->monto_apertura
                + $movimientos->where('tipo', 'ingreso')->sum('monto')
                - $movimientos->where('tipo', 'egreso')->sum('monto'),
        ];
    }

    public function cajaActiva(int $usuarioId): ?AperturaCaja
    {
        return AperturaCaja::where('usuario_id', $usuarioId)
            ->where('estado', 'abierta')
            ->first();
    }
}
```

---

## Conceptos de Egreso Comunes

```php
// Sugerencias para select en el formulario de egreso
$conceptosEgreso = [
    'Pago de proveedores',
    'Compra de insumos urgentes',
    'Pago de servicios (luz, agua)',
    'Gastos de limpieza',
    'Gastos de mantenimiento',
    'Sueldo o adelanto',
    'Flete o transporte',
    'Otros gastos operativos',
];
```

---

## Caja Store (Pinia)

```javascript
// src/stores/caja.store.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { cajaService } from '@/services/caja.service'

export const useCajaStore = defineStore('caja', () => {
  const cajaActiva  = ref(null)
  const movimientos = ref([])
  const resumen     = ref(null)
  const loading     = ref(false)

  const estaAbierta = computed(() => !!cajaActiva.value)

  async function verificarCaja() {
    const { data } = await cajaService.cajaActiva()
    cajaActiva.value = data.data
    return cajaActiva.value
  }

  async function abrir(payload) {
    loading.value = true
    try {
      const { data } = await cajaService.abrir(payload)
      cajaActiva.value = data.data
      return data.data
    } finally {
      loading.value = false
    }
  }

  async function cerrar(payload) {
    loading.value = true
    try {
      const { data } = await cajaService.cerrar(cajaActiva.value.id, payload)
      cajaActiva.value = null
      return data.data
    } finally {
      loading.value = false
    }
  }

  async function cargarResumen() {
    if (!cajaActiva.value) return
    const { data } = await cajaService.resumen(cajaActiva.value.id)
    resumen.value     = data.data.resumen
    movimientos.value = data.data.movimientos
  }

  return { cajaActiva, movimientos, resumen, loading, estaAbierta, verificarCaja, abrir, cerrar, cargarResumen }
})
```

---

## Endpoints del Módulo

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/caja/activa` | Caja abierta del usuario actual |
| POST | `/caja/abrir` | Abrir nueva caja |
| POST | `/caja/{id}/cerrar` | Cerrar caja |
| GET | `/caja/{id}/resumen` | Resumen y movimientos de una caja |
| POST | `/caja/movimiento` | Registrar gasto o ingreso manual |
| GET | `/caja/historial` | Historial de aperturas |
| GET | `/caja/{id}/reporte` | PDF/HTML del arqueo |

---

## Reglas de Negocio

1. **Solo una caja activa por usuario** — no se puede abrir si ya hay una abierta
2. **No se puede vender sin caja abierta** — el POS verifica esto antes de mostrar la pantalla
3. **El monto de apertura se registra como ingreso** en los movimientos
4. **Las ventas registran automáticamente el ingreso** en los movimientos de caja
5. **El cierre de caja no elimina movimientos** — solo cambia el estado a "cerrada"
6. **Una caja cerrada no puede reabrirse** — se debe crear una nueva
