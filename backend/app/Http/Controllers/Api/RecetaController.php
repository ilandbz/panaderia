<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use App\Services\ProduccionService;
use Illuminate\Http\Request;

class RecetaController extends Controller
{
    protected $produccionService;

    public function __construct(ProduccionService $produccionService)
    {
        $this->produccionService = $produccionService;
    }

    public function index()
    {
        $recetas = Receta::with('producto', 'insumos.insumo')->where('activo', true)->get();
        return $this->successResponse($recetas);
    }

    public function producir(Request $request, Receta $receta)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:0.001',
        ]);

        try {
            $producto = $this->produccionService->ejecutar($receta, $request->cantidad);
            return $this->successResponse($producto, 'Producción ejecutada correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
