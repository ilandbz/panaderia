---
name: facturacion
description: Integración con SUNAT para facturación electrónica peruana (boletas y facturas electrónicas) en Panadería Jara. Usa este skill cuando trabajes en emisión de comprobantes electrónicos, integración con la API de SUNAT o proveedores OSE/PSE, generación de XML UBL 2.1, códigos QR de comprobantes, series y correlativos, notas de crédito, o cualquier aspecto relacionado con la facturación electrónica peruana.
---

# Facturación Electrónica SUNAT — Panadería Jara

## Marco Legal Perú

- **Boleta electrónica:** B001-XXXXXXXX (para personas naturales / consumidor final)
- **Factura electrónica:** F001-XXXXXXXX (para empresas con RUC)
- **Nota de crédito:** BC01 / FC01 (anulación parcial o total)
- **Formato:** XML UBL 2.1
- **Entidad:** SUNAT (vía OSE o directamente)

---

## Estrategia de Integración Recomendada

Para una panadería pequeña/mediana, se recomienda usar un **proveedor de servicios OSE/PSE** en lugar de integración directa con SUNAT:

### Proveedores recomendados (Perú)
- **Nubefact** — API REST simple, buen soporte
- **SUNAT Beta/Producción** — Gratis pero complejo
- **ApiFactura**
- **FacturaPeru**

### Flujo con proveedor OSE

```
Sistema → genera XML UBL → envía a OSE API → OSE valida con SUNAT
    ← recibe CDR (comprobante de recepción) ← estado aceptado/rechazado
```

---

## Service de Facturación

```php
<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Comprobante;
use App\Enums\TipoComprobante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacturacionService
{
    private string $apiUrl;
    private string $token;

    public function __construct()
    {
        $this->apiUrl = config('facturacion.api_url');
        $this->token  = config('facturacion.token');
    }

    public function emitir(Venta $venta): Comprobante
    {
        return DB::transaction(function () use ($venta) {

            // Generar número de comprobante
            $tipo   = $this->getTipoComprobante($venta->tipo_comprobante);
            $serie  = $this->getSerie($venta->tipo_comprobante);
            $correlativo = $this->siguienteCorrelativo($tipo, $serie);
            $numero = "{$serie}-" . str_pad($correlativo, 8, '0', STR_PAD_LEFT);

            // Crear comprobante local primero
            $comprobante = Comprobante::create([
                'venta_id'           => $venta->id,
                'tipo'               => $tipo,
                'serie'              => $serie,
                'correlativo'        => $correlativo,
                'numero_comprobante' => $numero,
                'estado_sunat'       => 'pendiente',
            ]);

            // Solo enviar a SUNAT si es boleta o factura
            if (in_array($venta->tipo_comprobante, ['boleta', 'factura'])) {
                try {
                    $payload  = $this->construirPayload($venta, $comprobante);
                    $response = $this->enviarASunat($payload);

                    $comprobante->update([
                        'estado_sunat'   => $response['aceptado'] ? 'aceptado' : 'rechazado',
                        'codigo_hash'    => $response['hash'] ?? null,
                        'codigo_qr'      => $response['qr'] ?? null,
                        'respuesta_sunat'=> $response,
                    ]);

                } catch (\Exception $e) {
                    Log::error('Error SUNAT: ' . $e->getMessage(), ['venta_id' => $venta->id]);
                    $comprobante->update(['estado_sunat' => 'pendiente']); // reintentar después
                }
            } else {
                $comprobante->update(['estado_sunat' => 'no_aplica']);
            }

            return $comprobante->fresh();
        });
    }

    private function construirPayload(Venta $venta, Comprobante $comprobante): array
    {
        $venta->load(['detalles.producto', 'cliente']);

        return [
            'serie'       => $comprobante->serie,
            'numero'      => $comprobante->correlativo,
            'tipo_doc'    => $comprobante->tipo === 'boleta' ? '03' : '01',
            'fecha_emision' => now()->format('Y-m-d'),
            'hora_emision'  => now()->format('H:i:s'),
            'tipo_moneda'   => 'PEN',
            'empresa' => [
                'ruc'         => config('facturacion.ruc'),
                'razon_social'=> config('facturacion.razon_social'),
                'direccion'   => config('facturacion.direccion'),
            ],
            'cliente' => [
                'tipo_doc'    => $venta->cliente?->tipo_documento ?? '0', // 0 = sin documento
                'num_doc'     => $venta->cliente?->numero_documento ?? '-',
                'razon_social'=> $venta->cliente?->nombre_completo ?? 'VARIOS',
                'direccion'   => $venta->cliente?->direccion ?? '',
            ],
            'totales' => [
                'total_gravadas'   => $venta->subtotal - ($venta->subtotal * 0.18 / 1.18),
                'total_igv'        => $venta->igv,
                'total_venta'      => $venta->total,
                'descuento_global' => $venta->descuento,
            ],
            'items' => $venta->detalles->map(fn($d) => [
                'codigo'       => $d->producto->codigo ?? $d->producto_id,
                'descripcion'  => $d->producto->nombre,
                'unidad'       => $d->producto->unidad_medida,
                'cantidad'     => $d->cantidad,
                'precio_unit'  => $d->precio_unitario,
                'descuento'    => $d->descuento,
                'total'        => $d->subtotal,
                'tipo_afectacion' => $d->producto->afecto_igv ? '10' : '20',
            ])->toArray(),
        ];
    }

    private function enviarASunat(array $payload): array
    {
        $response = Http::withToken($this->token)
            ->timeout(30)
            ->post($this->apiUrl . '/comprobantes', $payload);

        if ($response->failed()) {
            throw new \Exception('Error al comunicarse con el servicio de facturación: ' . $response->body());
        }

        return $response->json();
    }

    private function getSerie(string $tipo): string
    {
        return match($tipo) {
            'boleta'  => 'B001',
            'factura' => 'F001',
            'ticket'  => 'T001',
            default   => 'T001',
        };
    }

    private function getTipoComprobante(string $tipo): string
    {
        return match($tipo) {
            'boleta'  => 'boleta',
            'factura' => 'factura',
            default   => 'ticket',
        };
    }

    private function siguienteCorrelativo(string $tipo, string $serie): int
    {
        return (Comprobante::where('tipo', $tipo)->where('serie', $serie)->max('correlativo') ?? 0) + 1;
    }

    /**
     * Reintentar envío de comprobantes pendientes
     */
    public function reintentarPendientes(): void
    {
        $pendientes = Comprobante::where('estado_sunat', 'pendiente')
            ->whereIn('tipo', ['boleta', 'factura'])
            ->with('venta')
            ->limit(50)
            ->get();

        foreach ($pendientes as $comprobante) {
            try {
                $payload  = $this->construirPayload($comprobante->venta, $comprobante);
                $response = $this->enviarASunat($payload);

                $comprobante->update([
                    'estado_sunat'   => $response['aceptado'] ? 'aceptado' : 'rechazado',
                    'codigo_hash'    => $response['hash'] ?? null,
                    'codigo_qr'      => $response['qr'] ?? null,
                    'respuesta_sunat'=> $response,
                ]);
            } catch (\Exception $e) {
                Log::warning("No se pudo reenviar comprobante {$comprobante->id}: " . $e->getMessage());
            }
        }
    }
}
```

---

## Configuración (config/facturacion.php)

```php
<?php

return [
    'ruc'          => env('SUNAT_RUC'),
    'razon_social' => env('SUNAT_RAZON_SOCIAL', 'Panadería Pastelería Jara'),
    'direccion'    => env('SUNAT_DIRECCION'),
    'api_url'      => env('SUNAT_API_URL'),
    'token'        => env('SUNAT_TOKEN'),
    'modo'         => env('SUNAT_MODO', 'beta'), // beta | produccion
];
```

---

## Endpoints

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/comprobantes/emitir/{ventaId}` | Emitir comprobante de una venta |
| GET | `/comprobantes/{id}` | Detalle del comprobante |
| GET | `/comprobantes/{id}/pdf` | Descargar PDF |
| GET | `/comprobantes/{id}/xml` | Descargar XML |
| POST | `/comprobantes/{id}/reenviar` | Reenviar a SUNAT |
| POST | `/comprobantes/nota-credito` | Emitir nota de crédito |

---

## Importantes para Perú

1. **Modo beta:** Usar `https://e-beta.sunat.gob.pe` para pruebas
2. **Modo producción:** `https://e-factura.sunat.gob.pe`
3. **Ticket (sin comprobante electrónico):** No se envía a SUNAT, solo se imprime
4. **Boleta sin RUC cliente:** `tipo_doc = '0'`, `num_doc = '-'`, `razon_social = 'VARIOS'`
5. **IGV 18%:** Precio de venta incluye IGV. Base = precio / 1.18
6. **Si la venta es menor a S/. 700:** No es obligatorio datos del cliente en boleta
