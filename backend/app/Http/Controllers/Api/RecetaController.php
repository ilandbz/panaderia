<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use App\Models\RecetaInsumo;
use App\Services\ProduccionService;
use App\Http\Requests\RecetaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecetaController extends Controller
{
    protected $produccionService;

    public function __construct(ProduccionService $produccionService)
    {
        $this->produccionService = $produccionService;
    }

    public function index()
    {
        $recetas = Receta::with(['producto', 'insumos.insumo'])
            ->where('activo', true)
            ->latest()
            ->get();
        return $this->successResponse($recetas);
    }

    public function store(RecetaRequest $request)
    {
        try {
            $receta = DB::transaction(function () use ($request) {
                $receta = Receta::create($request->validated());

                foreach ($request->insumos as $insumo) {
                    $receta->insumos()->create([
                        'insumo_id'      => $insumo['insumo_id'],
                        'cantidad'       => $insumo['cantidad'],
                        'unidad_medida'  => $insumo['unidad_medida'],
                    ]);
                }

                return $receta->load(['producto', 'insumos.insumo']);
            });

            return $this->successResponse($receta, 'Receta creada exitosamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear la receta: ' . $e->getMessage(), 500);
        }
    }

    public function show(Receta $receta)
    {
        return $this->successResponse($receta->load(['producto', 'insumos.insumo']));
    }

    public function update(RecetaRequest $request, Receta $receta)
    {
        try {
            DB::transaction(function () use ($request, $receta) {
                $receta->update($request->validated());

                // Sincronizar insumos (Eliminar actuales y volver a crear)
                $receta->insumos()->delete();
                foreach ($request->insumos as $insumo) {
                    $receta->insumos()->create([
                        'insumo_id'      => $insumo['insumo_id'],
                        'cantidad'       => $insumo['cantidad'],
                        'unidad_medida'  => $insumo['unidad_medida'],
                    ]);
                }
            });

            return $this->successResponse($receta->load(['producto', 'insumos.insumo']), 'Receta actualizada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar la receta: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Receta $receta)
    {
        try {
            // Soft delete o desactivar
            $receta->update(['activo' => false]);
            // $receta->delete(); // Si tuviera soft deletes, pero el modelo dice que no por ahora

            return $this->successResponse(null, 'Receta eliminada correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar la receta', 500);
        }
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
