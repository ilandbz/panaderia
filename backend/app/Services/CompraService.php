<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompraService
{
    protected $cajaService;

    public function __construct(CajaService $cajaService)
    {
        $this->cajaService = $cajaService;
    }

    public function registrar(array $data)
    {
        return DB::transaction(function () use ($data) {
            $usuario_id = Auth::id();
            $sucursal_id = $data['sucursal_id'] ?? Auth::user()->sucursal_id;

            $registrarEnCaja = !isset($data['registrar_en_caja']) || $data['registrar_en_caja'];

            if ($registrarEnCaja) {
                // Validar Caja
                $saldoActual = $this->cajaService->obtenerSaldoActual($usuario_id, $sucursal_id);
                if ($saldoActual < $data['total']) {
                    throw new \Exception("Saldo de caja insuficiente. Saldo actual: S/ " . number_format($saldoActual, 2) . ". Monto compra: S/ " . number_format($data['total'], 2));
                }
            }

            $ultimoId = Compra::withTrashed()->max('id') ?? 0;
            $numeroCompra = 'COMP-' . str_pad($ultimoId + 1, 6, '0', STR_PAD_LEFT);

            $compra = Compra::create([
                'numero_compra'       => $numeroCompra,
                'proveedor_id'        => $data['proveedor_id'],
                'usuario_id'          => Auth::id(),
                'sucursal_id'         => $sucursal_id,
                'tipo_comprobante'    => $data['tipo_comprobante'],
                'numero_comprobante'  => $data['numero_comprobante'],
                'fecha_compra'        => $data['fecha_compra'],
                'subtotal'            => $data['subtotal'],
                'igv'                 => $data['igv'],
                'total'               => $data['total'],
                'estado'              => 'registrado',
            ]);

            foreach ($data['items'] as $item) {
                $compra->detalles()->create([
                    'producto_id'    => $item['producto_id'],
                    'cantidad'       => $item['cantidad'],
                    'precio_compra'  => $item['precio_compra'],
                    'subtotal'       => $item['subtotal'],
                ]);

                $producto = \App\Models\Producto::find($item['producto_id']);
                
                // Obtener o crear registro de stock para esta sucursal
                $pivot = $producto->sucursales()->where('sucursal_id', $sucursal_id)->first();
                $stockAnterior = $pivot ? $pivot->pivot->stock : 0;

                // Actualizar o insertar en producto_sucursal
                $producto->sucursales()->syncWithoutDetaching([
                    $sucursal_id => ['stock' => $stockAnterior + $item['cantidad']]
                ]);

                MovimientoInventario::create([
                    'producto_id'     => $producto->id,
                    'usuario_id'      => Auth::id(),
                    'sucursal_id'     => $sucursal_id,
                    'compra_id'       => $compra->id,
                    'tipo'            => 'ingreso',
                    'cantidad'        => $item['cantidad'],
                    'stock_anterior'  => $stockAnterior,
                    'stock_nuevo'     => $stockAnterior + $item['cantidad'],
                    'motivo'          => 'compra',
                    'observacion'     => "Compra registrada: {$compra->numero_compra}",
                ]);
            }

            // Registrar Egreso en Caja
            if ($registrarEnCaja) {
                $this->cajaService->registrarEgresoCompra($compra);
            }

            return $compra->load('detalles.producto', 'proveedor');
        });
    }

    public function anular(Compra $compra)
    {
        if ($compra->estado === 'anulado') {
            throw new \Exception('La compra ya se encuentra anulada.');
        }

        return DB::transaction(function () use ($compra) {
            $compra->load('detalles');

            foreach ($compra->detalles as $detalle) {
                $producto = $detalle->producto;
                $sucursal_id = $compra->sucursal_id;

                // Obtener stock actual de la sucursal
                $pivot = $producto->sucursales()->where('sucursal_id', $sucursal_id)->first();
                if ($pivot) {
                    $producto->sucursales()->updateExistingPivot($sucursal_id, [
                        'stock' => $pivot->pivot->stock - $detalle->cantidad
                    ]);
                }
            }

            // Según requerimiento del usuario: "elimine ese movimiento del kardex"
            MovimientoInventario::where('compra_id', $compra->id)->delete();

            $compra->update(['estado' => 'anulado']);

            return $compra;
        });
    }
}
