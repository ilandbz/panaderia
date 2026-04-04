<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompraService
{
    public function registrar(array $data)
    {
        return DB::transaction(function () use ($data) {
            $ultimoId = Compra::withTrashed()->max('id') ?? 0;
            $numeroCompra = 'COMP-' . str_pad($ultimoId + 1, 6, '0', STR_PAD_LEFT);

            $compra = Compra::create([
                'numero_compra'       => $numeroCompra,
                'proveedor_id'        => $data['proveedor_id'],
                'usuario_id'          => Auth::id(),
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
                $stockAnterior = $producto->stock;
                $producto->increment('stock', $item['cantidad']);
                $producto->refresh();

                MovimientoInventario::create([
                    'producto_id'     => $producto->id,
                    'usuario_id'      => Auth::id(),
                    'compra_id'       => $compra->id,
                    'tipo'            => 'ingreso',
                    'cantidad'        => $item['cantidad'],
                    'stock_anterior'  => $stockAnterior,
                    'stock_nuevo'     => $producto->stock,
                    'motivo'          => 'compra',
                    'observacion'     => "Compra registrada: {$compra->numero_compra}",
                ]);
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
                // Al anular una compra, restamos la cantidad que se ingresó
                $producto->decrement('stock', $detalle->cantidad);
            }

            // Según requerimiento del usuario: "elimine ese movimiento del kardex"
            MovimientoInventario::where('compra_id', $compra->id)->delete();

            $compra->update(['estado' => 'anulado']);

            return $compra;
        });
    }
}
