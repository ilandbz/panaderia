<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\MovimientoInventario;
use App\Models\MovimientoCaja;
use App\Models\AperturaCaja;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VentaService
{
    protected $facturacion;

    public function __construct(FacturacionService $facturacion)
    {
        $this->facturacion = $facturacion;
    }

    public function registrar(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 0. Validar Reglas SUNAT
            $this->validarReglasSunat($data);

            // 1. Validar apertura de caja
            $apertura = AperturaCaja::where('estado', 'abierta')
                ->where('usuario_id', $data['usuario_id'])
                ->first();

            if (!$apertura) {
                throw new \Exception('No hay una caja abierta para este usuario.');
            }

            // 2. Crear Venta
            $venta = Venta::create([
                'numero_venta'     => $this->generarNumeroVenta(),
                'usuario_id'       => $data['usuario_id'],
                'cliente_id'       => $data['cliente_id'] ?? null,
                'apertura_caja_id' => $apertura->id,
                'subtotal'         => $data['subtotal'],
                'igv'              => $data['igv'] ?? 0,
                'descuento'        => $data['descuento'] ?? 0,
                'total'            => $data['total'],
                'monto_pagado'     => $data['monto_pagado'],
                'vuelto'           => $data['vuelto'] ?? 0,
                'forma_pago'       => $data['forma_pago'],
                'tipo_comprobante' => $data['tipo_comprobante'] ?? 'ticket',
                'observacion'      => $data['observacion'] ?? null,
            ]);

            // 3. Registrar Detalles, Descontar Stock y Kardex
            foreach ($data['items'] as $item) {
                $producto = Producto::find($item['producto_id']);

                if (!$producto) {
                    throw new \Exception("Producto no encontrado ID: {$item['producto_id']}");
                }

                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para: {$producto->nombre}");
                }

                $stockAnterior = $producto->stock;

                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $item['producto_id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'descuento'       => $item['descuento'] ?? 0,
                    'subtotal'        => $item['subtotal'],
                ]);

                // Descontar Stock
                $producto->decrement('stock', $item['cantidad']);

                // Registrar Movimiento de Inventario (Kardex)
                MovimientoInventario::create([
                    'producto_id'    => $producto->id,
                    'usuario_id'     => $data['usuario_id'],
                    'venta_id'       => $venta->id,
                    'tipo'           => 'egreso',
                    'cantidad'       => $item['cantidad'],
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo'    => $stockAnterior - $item['cantidad'],
                    'motivo'         => 'venta',
                    'observacion'    => "Venta {$venta->numero_venta}",
                ]);
            }

            // 4. Registrar Movimiento de Caja
            MovimientoCaja::create([
                'apertura_caja_id' => $apertura->id,
                'usuario_id'        => $data['usuario_id'],
                'venta_id'          => $venta->id,
                'tipo'              => 'ingreso',
                'concepto'          => "Venta {$venta->numero_venta}",
                'monto'             => $venta->total,
                'forma_pago'        => $venta->forma_pago,
            ]);

            // 5. Generar Comprobante Electrónico (Boleta/Factura)
            if (in_array($venta->tipo_comprobante, ['boleta', 'factura'])) {
                $this->facturacion->generar($venta);
            }

            return $venta->load('detalles.producto');
        });
    }

    public function anular(int $id, string $motivo)
    {
        return DB::transaction(function () use ($id, $motivo) {
            $venta = Venta::with(['detalles.producto', 'comprobante'])->findOrFail($id);

            if ($venta->estado === 'anulada') {
                throw new \Exception('Esta venta ya se encuentra anulada.');
            }

            // 1. Revertir Stock y Kardex
            foreach ($venta->detalles as $detalle) {
                $producto = $detalle->producto;
                $stockAnterior = $producto->stock;

                // Restituir Stock
                $producto->increment('stock', $detalle->cantidad);

                // Registrar Kardex
                MovimientoInventario::create([
                    'producto_id'    => $producto->id,
                    'usuario_id'     => auth()->id() ?? $venta->usuario_id,
                    'venta_id'       => $venta->id,
                    'tipo'           => 'ingreso',
                    'cantidad'       => $detalle->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo'    => $stockAnterior + $detalle->cantidad,
                    'motivo'         => 'ajuste',
                    'observacion'    => "Anulación de Venta {$venta->numero_venta}. Motivo: {$motivo}",
                ]);
            }

            // 2. Revertir Caja (Egreso)
            // Buscamos la caja abierta del usuario actual para realizar el reintegro
            $apertura = AperturaCaja::where('estado', 'abierta')
                ->where('usuario_id', auth()->id())
                ->first();

            if ($apertura) {
                MovimientoCaja::create([
                    'apertura_caja_id' => $apertura->id,
                    'usuario_id'        => auth()->id(),
                    'venta_id'          => $venta->id,
                    'tipo'              => 'egreso',
                    'concepto'          => "Anulación Venta {$venta->numero_venta}",
                    'monto'             => $venta->total,
                    'forma_pago'        => $venta->forma_pago,
                    'observacion'       => $motivo,
                ]);
            }

            // 3. Generar Nota de Crédito si es necesario
            if ($venta->comprobante && in_array($venta->comprobante->tipo, ['boleta', 'factura'])) {
                $this->facturacion->generarNotaCredito($venta, $motivo);
            }

            // 4. Actualizar estado de la venta
            $venta->update([
                'estado' => 'anulada',
                'observacion' => ($venta->observacion ? $venta->observacion . ' | ' : '') . "ANULADA: {$motivo}"
            ]);

            return $venta->load(['detalles.producto', 'comprobante']);
        });
    }

    private function validarReglasSunat(array $data)
    {
        $tipo = $data['tipo_comprobante'] ?? 'ticket';
        $total = $data['total'];
        $clienteId = $data['cliente_id'] ?? null;
        if ($tipo === 'factura') {
            if (!$clienteId) {
                throw new \Exception('La factura requiere un cliente seleccionado.');
            }
            $cliente = \App\Models\Cliente::find($clienteId);
            if (!$cliente || $cliente->tipo_documento !== 'RUC') {
                throw new \Exception('La factura requiere un cliente con RUC válido.');
            }
            if (empty($cliente->razon_social)) {
                throw new \Exception('La factura requiere la razón social del cliente.');
            }
        }
        if ($tipo === 'boleta' && $total > 700) {
            if (!$clienteId) {
                throw new \Exception('Boletas mayores a S/ 700 requieren identificación del cliente.');
            }
            $cliente = \App\Models\Cliente::find($clienteId);
            // Cliente Varios (DNI 00000000) no es válido para > 700
            if (!$cliente || $cliente->numero_documento === '00000000' || empty($cliente->numero_documento)) {
                throw new \Exception('Boletas mayores a S/ 700 requieren un cliente identificado (DNI/CE).');
            }
        }
    }

    private function generarNumeroVenta()
    {
        return 'V-' . strtoupper(Str::random(8));
    }
}
