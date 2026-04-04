<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Comprobante;
use Illuminate\Support\Facades\Http;

class FacturacionService
{
    public function generar(Venta $venta)
    {
        // ... (logic to generate Boleta/Factura)
        $serie = ($venta->tipo_comprobante === 'factura') ? 'F001' : 'B001';
        $ultimo = Comprobante::where('serie', $serie)->max('correlativo') ?? 0;
        $correlativo = $ultimo + 1;

        $comprobante = Comprobante::create([
            'venta_id'           => $venta->id,
            'tipo'               => $venta->tipo_comprobante,
            'serie'               => $serie,
            'correlativo'         => $correlativo,
            'numero_comprobante' => "{$serie}-" . str_pad($correlativo, 8, '0', STR_PAD_LEFT),
            'estado_sunat'       => 'pendiente',
        ]);

        $response = $this->enviarASunatMock($venta, $comprobante);

        if ($response['success']) {
            $comprobante->update([
                'estado_sunat'    => 'aceptado',
                'codigo_hash'     => $response['hash'],
                'codigo_qr'       => $response['qr'],
                'respuesta_sunat' => $response['sunat_response'],
            ]);
        }

        return $comprobante;
    }

    public function generarNotaCredito(Venta $venta, string $motivo)
    {
        // 1. Determinar Serie (BC01 para boletas, FC01 para facturas)
        $serieOrig = $venta->comprobante->serie;
        $serieNC = (str_starts_with($serieOrig, 'F')) ? 'FC01' : 'BC01';

        $ultimo = Comprobante::where('serie', $serieNC)->max('correlativo') ?? 0;
        $correlativo = $ultimo + 1;

        // 2. Crear Registro de Nota de Crédito
        $comprobanteNC = Comprobante::create([
            'venta_id'           => $venta->id,
            'tipo'               => 'nota_credito',
            'serie'               => $serieNC,
            'correlativo'         => $correlativo,
            'numero_comprobante' => "{$serieNC}-" . str_pad($correlativo, 8, '0', STR_PAD_LEFT),
            'estado_sunat'       => 'pendiente',
            'respuesta_sunat'    => ['motivo' => $motivo, 'documento_referencia' => $venta->comprobante->numero_comprobante]
        ]);

        // 3. Mock envío a SUNAT
        $response = [
            'success' => true,
            'hash' => bin2hex(random_bytes(16)),
            'qr' => 'https://jara.com/qr/nc/' . $comprobanteNC->numero_comprobante,
            'sunat_response' => [
                'code' => '0',
                'description' => 'La Nota de Crédito ' . $comprobanteNC->numero_comprobante . ' ha sido aceptada'
            ]
        ];

        if ($response['success']) {
            $comprobanteNC->update([
                'estado_sunat'    => 'aceptado',
                'codigo_hash'     => $response['hash'],
                'codigo_qr'       => $response['qr'],
                'respuesta_sunat' => array_merge($comprobanteNC->respuesta_sunat, $response['sunat_response']),
            ]);
        }

        return $comprobanteNC;
    }

    private function enviarASunatMock(Venta $venta, Comprobante $comprobante)
    {
        // Simulando respuesta de SUNAT
        return [
            'success' => true,
            'hash' => bin2hex(random_bytes(16)),
            'qr' => 'https://jara.com/qr/' . $comprobante->numero_comprobante,
            'sunat_response' => [
                'code' => '0',
                'description' => 'La Boleta número ' . $comprobante->numero_comprobante . ' ha sido aceptada',
                'notes' => []
            ]
        ];
    }
}
