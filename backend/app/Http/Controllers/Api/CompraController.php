<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Services\CompraService;
use Illuminate\Http\Request;

class CompraController extends Controller
{
    protected $service;

    public function __construct(CompraService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->successResponse(Compra::with('proveedor', 'usuario')->latest()->get());
    }

    public function show(Compra $compra)
    {
        return $this->successResponse($compra->load('detalles.producto', 'proveedor', 'usuario'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id'      => 'required|exists:proveedores,id',
            'tipo_comprobante'  => 'required|in:Factura,Boleta,Guia,Nota de Venta,Sin Comprobante',
            'numero_comprobante' => 'required|string|max:50',
            'fecha_compra'      => 'required|date',
            'subtotal'          => 'required|numeric|min:0',
            'igv'               => 'required|numeric|min:0',
            'total'             => 'required|numeric|min:0',
            'items'             => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad'    => 'required|numeric|min:0.001',
            'items.*.precio_compra' => 'required|numeric|min:0',
            'items.*.subtotal'    => 'required|numeric|min:0',
            'registrar_en_caja'   => 'nullable|boolean',
        ]);

        try {
            $compra = $this->service->registrar($validated);
            return $this->successResponse($compra, 'Compra registrada con éxito', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy(Compra $compra)
    {
        try {
            $compra = $this->service->anular($compra);
            return $this->successResponse($compra, 'Compra anulada correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
