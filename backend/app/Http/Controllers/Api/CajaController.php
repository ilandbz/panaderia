<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AperturaCaja;
use App\Services\CajaService;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    protected $service;

    public function __construct(CajaService $service)
    {
        $this->service = $service;
    }

    public function abrir(Request $request)
    {
        $data = $request->validate([
            'monto_apertura' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $data['usuario_id'] = $request->user()->id;

        try {
            $apertura = $this->service->abrir($data);
            return $this->successResponse($apertura, 'Caja abierta correctamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function cerrar(Request $request)
    {
        $apertura = AperturaCaja::where('usuario_id', $request->user()->id)
                                ->where('estado', 'abierta')
                                ->first();

        if (!$apertura) {
            return $this->errorResponse('No tienes una caja abierta.', 404);
        }

        $data = $request->validate([
            'monto_cierre'  => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $data['cerrado_por'] = $request->user()->id;

        try {
            $apertura = $this->service->cerrar($apertura, $data);
            return $this->successResponse($apertura, 'Caja cerrada correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function registrarGasto(Request $request)
    {
        $data = $request->validate([
            'concepto' => 'required|string|max:200',
            'monto'    => 'required|numeric|min:0.1',
            'observacion' => 'nullable|string',
        ]);

        $data['usuario_id'] = $request->user()->id;

        try {
            $gasto = $this->service->registrarGasto($data);
            return $this->successResponse($gasto, 'Gasto registrado correctamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function registrarIngreso(Request $request)
    {
        $data = $request->validate([
            'concepto' => 'required|string|max:200',
            'monto'    => 'required|numeric|min:0.1',
            'observacion' => 'nullable|string',
        ]);

        $data['usuario_id'] = $request->user()->id;

        try {
            $ingreso = $this->service->registrarIngreso($data);
            return $this->successResponse($ingreso, 'Ingreso registrado correctamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function estado(Request $request)
    {
        $apertura = AperturaCaja::where('usuario_id', $request->user()->id)
                                ->where('estado', 'abierta')
                                ->with([
                                    'usuario' => fn($q) => $q->select('id', 'nombre', 'apellido'),
                                    'movimientos' => fn($q) => $q->orderBy('created_at', 'desc')
                                ])
                                ->first();

        return $this->successResponse($apertura);
    }

    public function historial(Request $request)
    {
        $historial = AperturaCaja::where('usuario_id', $request->user()->id)
                                ->where('estado', 'cerrada')
                                ->with(['usuario', 'cerrador'])
                                ->orderBy('fecha_cierre', 'desc')
                                ->paginate(10);

        return $this->successResponse($historial);
    }

    public function show(Request $request, $id)
    {
        $apertura = AperturaCaja::where('usuario_id', $request->user()->id)
                                ->with([
                                    'usuario',
                                    'cerrador',
                                    'movimientos' => fn($q) => $q->orderBy('created_at', 'asc')
                                ])
                                ->findOrFail($id);

        return $this->successResponse($apertura);
    }
}
