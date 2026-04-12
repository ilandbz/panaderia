<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\FacturacionService;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VentaController extends Controller
{
    protected $service;

    public function __construct(VentaService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'usuario', 'comprobante'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->fecha_inicio . ' 00:00:00'),
                Carbon::parse($request->fecha_fin . ' 23:59:59')
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_venta', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($cq) use ($search) {
                        $cq->where('nombre', 'like', "%{$search}%")
                            ->orWhere('documento', 'like', "%{$search}%");
                    });
            });
        }

        return $this->successResponse(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id'       => 'nullable|exists:clientes,id',
            'subtotal'         => 'required|numeric',
            'igv'              => 'nullable|numeric',
            'total'            => 'required|numeric',
            'monto_pagado'     => 'required|numeric',
            'forma_pago'       => 'required|string',
            'items'            => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad'    => 'required|numeric|min:0.001',
            'items.*.precio_unitario' => 'required|numeric',
            'items.*.subtotal'    => 'required|numeric',
        ]);

        $data['usuario_id'] = $request->user()->id;

        try {
            $venta = $this->service->registrar($data);
            return $this->successResponse($venta, 'Venta registrada con éxito', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function show($id)
    {
        try {
            $venta = \App\Models\Venta::with(['detalles.producto', 'comprobante', 'usuario', 'cliente'])->findOrFail($id);
            return $this->successResponse($venta);
        } catch (\Exception $e) {
            return $this->errorResponse('Venta no encontrada', 404);
        }
    }

    public function update(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);

        $data = $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        $venta->update($data);

        return $this->successResponse($venta, 'Cliente actualizado correctamente');
    }

    public function generarComprobante(Request $request, $id)
    {
        $data = $request->validate([
            'tipo' => 'required|in:boleta,factura',
        ]);
        try {
            $venta = Venta::findOrFail($id);
            $venta->update(['tipo_comprobante' => $data['tipo']]);
            $facturacionService = app(FacturacionService::class);
            $facturacionService->generar($venta);
            $venta->load('comprobante');
            return $this->successResponse($venta, 'Venta registrada con éxito', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function reenviarComprobante(Request $request, $id)
    {
        $venta = Venta::with(['comprobante', 'detalles.producto', 'cliente'])->findOrFail($id);

        if (!$venta->comprobante) {
            return $this->errorResponse('Esta venta no tiene un comprobante generado.', 422);
        }

        if ($venta->comprobante->estado_sunat !== 'pendiente') {
            return $this->errorResponse(
                'Solo se pueden reenviar comprobantes en estado pendiente. ' .
                'Los rechazados no deben reenviarse (el número ya fue usado en SUNAT).',
                422
            );
        }

        try {
            $facturacionService = app(FacturacionService::class);
            $facturacionService->reenviar($venta);
            $venta->load('comprobante');
            return $this->successResponse($venta, 'Comprobante reenviado a SUNAT correctamente.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function downloadPdf($id, Request $request)
    {
        try {
            $venta = \App\Models\Venta::with(['detalles.producto', 'usuario', 'cliente'])->findOrFail($id);
            $format = $request->get('format', '80mm');

            // Configuración de dimensiones (en puntos pt)
            // 1mm = 2.83465pt
            $width = ($format === '58mm') ? 164.4 : 226.7;

            // Altura variable (Estimada según ítems) o automática
            // DomPDF no soporta altura automática perfecta en el lienzo inicial, pero se puede usar un largo suficiente 
            // y recortar o simplemente usar un papel continuo simulado.
            $height = 600; // Altura base para tickets largos

            $qrCodeBase64 = null;
            if ($venta->comprobante && $venta->comprobante->codigo_qr) {
                // Generar QR en formato SVG para mayor compatibilidad y calidad en PDF
                $qrCodeBase64 = base64_encode((string) QrCode::format('svg')->size(100)->margin(0)->generate($venta->comprobante->codigo_qr));
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.comprobante', compact('venta', 'format', 'qrCodeBase64'));
            $pdf->setPaper([0, 0, $width, $height], 'portrait');

            return $pdf->stream("ticket-{$venta->numero_venta}.pdf");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function anular(Request $request, $id)
    {
        $venta = Venta::with('comprobante')->findOrFail($id);

        $rules = [
            'motivo' => 'required|string|min:5|max:200'
        ];

        // El motivo es obligatorio SIEMPRE para anulación, pero para Boleta/Factura es crítico por SUNAT
        $data = $request->validate($rules);

        try {
            $ventaAnulada = $this->service->anular($id, $data['motivo']);
            return $this->successResponse($ventaAnulada, 'Venta anulada correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function imprimirTicket($id, Request $request)
    {
        try {
            $venta = Venta::with(['detalles.producto', 'usuario', 'comprobante', 'cliente'])->findOrFail($id);
            $format = $request->get('format', '80mm');
            $tipo = $request->get('tipo', 'ticket');

            $paperWidth = $format === '58mm' ? 164.41 : 226.77;
            $paperHeight = 1000;

            if ($tipo === 'ticket') {
                $qrCodeBase64 = null;
                if ($venta->comprobante && $venta->comprobante->codigo_qr) {
                    $qrCodeBase64 = base64_encode((string) QrCode::format('svg')->size(100)->margin(0)->generate($venta->comprobante->codigo_qr));
                }

                $pdf = Pdf::loadView('ticket.impresion', compact('venta', 'format', 'tipo', 'qrCodeBase64'))
                    ->setPaper([0, 0, $paperWidth, $paperHeight], 'portrait');
            } else {

                $venta->loadMissing('comprobante', 'detalles.producto', 'usuario', 'cliente');

                $qrCodeBase64 = null;
                if ($venta->comprobante && $venta->comprobante->codigo_qr) {
                    $qrCodeBase64 = base64_encode((string) QrCode::format('svg')->size(120)->margin(0)->generate($venta->comprobante->codigo_qr));
                }

                $datos = [
                    'venta' => $venta,
                    'comprobante' => $venta->comprobante,
                    'detalles' => $venta->detalles,
                    'usuario' => $venta->usuario,
                    'cliente' => $venta->cliente,
                    'format' => $format,
                    'tipo' => $tipo,
                    'qrCodeBase64' => $qrCodeBase64
                ];

                $pdf = Pdf::loadView('pdfs.comprobante', $datos)
                    ->setPaper([0, 0, $paperWidth, $paperHeight], 'portrait');
            }


            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="ticket.pdf"',
            ]);
        } catch (\Exception $e) {
            return "Error al abrir ticket para impresión: " . $e->getMessage();
        }
    }

    public function sunatConfig()
    {
        return $this->successResponse([
            'ruc'  => config('facturacion.ruc'),
            'user' => config('facturacion.user'),
            'modo' => config('facturacion.modo'),
        ]);
    }
}
