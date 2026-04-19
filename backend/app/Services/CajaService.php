<?php

namespace App\Services;

use App\Models\AperturaCaja;
use App\Models\MovimientoCaja;
use Illuminate\Support\Facades\DB;

class CajaService
{
    public function abrir(array $data)
    {
        $sucursal_id = $data['sucursal_id'] ?? config('app.sucursal_id') ?? auth()->user()->sucursal_id;

        // Verificar si ya tiene una caja abierta en ESTA sucursal
        $abierta = AperturaCaja::where('usuario_id', $data['usuario_id'])
                                ->where('sucursal_id', $sucursal_id)
                                ->where('estado', 'abierta')
                                ->exists();
        
        if ($abierta) {
            throw new \Exception('Ya tienes una caja abierta en esta sucursal.');
        }

        return AperturaCaja::create([
            'usuario_id'     => $data['usuario_id'],
            'sucursal_id'    => $sucursal_id,
            'monto_apertura' => $data['monto_apertura'],
            'estado'         => 'abierta',
            'fecha_apertura' => now(),
            'observaciones'  => $data['observaciones'] ?? null,
        ]);
    }

    public function cerrar(AperturaCaja $apertura, array $data)
    {
        return DB::transaction(function () use ($apertura, $data) {
            // Calcular monto sistema (apertura + ingresos - egresos)
            $ingresos = $apertura->movimientos()->where('tipo', 'ingreso')->sum('monto');
            $egresos  = $apertura->movimientos()->where('tipo', 'egreso')->sum('monto');
            $monto_sistema = $apertura->monto_apertura + $ingresos - $egresos;

            $apertura->update([
                'monto_cierre'  => $data['monto_cierre'],
                'monto_sistema' => $monto_sistema,
                'diferencia'    => $data['monto_cierre'] - $monto_sistema,
                'estado'        => 'cerrada',
                'fecha_cierre'  => now(),
                'cerrado_por'   => $data['cerrado_por'], // ID del supervisor/admin que cierra
                'observaciones' => $data['observaciones'] ?? $apertura->observaciones,
            ]);

            return $apertura;
        });
    }

    public function registrarGasto(array $data)
    {
        $sucursal_id = $data['sucursal_id'] ?? config('app.sucursal_id') ?? auth()->user()->sucursal_id;

        $apertura = AperturaCaja::where('estado', 'abierta')
                                ->where('usuario_id', $data['usuario_id'])
                                ->where('sucursal_id', $sucursal_id)
                                ->first();

        if (!$apertura) {
            throw new \Exception('No hay una caja abierta.');
        }

        return MovimientoCaja::create([
            'apertura_caja_id' => $apertura->id,
            'usuario_id'        => $data['usuario_id'],
            'tipo'              => 'egreso',
            'concepto'          => $data['concepto'],
            'monto'             => $data['monto'],
            'forma_pago'        => 'efectivo',
            'observacion'       => $data['observacion'] ?? null,
        ]);
    }

    public function registrarIngreso(array $data)
    {
        $sucursal_id = $data['sucursal_id'] ?? config('app.sucursal_id') ?? auth()->user()->sucursal_id;

        $apertura = AperturaCaja::where('estado', 'abierta')
                                ->where('usuario_id', $data['usuario_id'])
                                ->where('sucursal_id', $sucursal_id)
                                ->first();

        if (!$apertura) {
            throw new \Exception('No hay una caja abierta.');
        }

        return MovimientoCaja::create([
            'apertura_caja_id' => $apertura->id,
            'usuario_id'        => $data['usuario_id'],
            'tipo'              => 'ingreso',
            'concepto'          => $data['concepto'],
            'monto'             => $data['monto'],
            'forma_pago'        => 'efectivo',
            'observacion'       => $data['observacion'] ?? null,
        ]);
    }
}
