<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SucursalController extends Controller
{
    /**
     * Listar sucursales activas.
     */
    public function index()
    {
        // Si el usuario es admin/supervisor, puede ver todas
        // Por ahora, devolvemos todas para el gestor
        $sucursales = Sucursal::orderBy('principal', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $sucursales,
            'message' => 'Sucursales obtenidas con éxito'
        ]);
    }

    /**
     * Guardar una nueva sucursal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'              => 'required|string|max:255',
            'direccion'           => 'required|string|max:255',
            'cod_establecimiento' => 'required|string|size:4',
            'serie_boleta'        => 'required|string|size:4|starts_with:B',
            'serie_factura'       => 'required|string|size:4|starts_with:F',
            'serie_nota_credito'  => 'required|string|size:4',
            'activo'              => 'boolean',
        ]);

        $sucursal = Sucursal::create($validated);

        return response()->json([
            'success' => true,
            'data' => $sucursal,
            'message' => 'Sucursal creada con éxito'
        ]);
    }

    /**
     * Mostrar una sucursal específica.
     */
    public function show(Sucursal $sucursal)
    {
        return response()->json([
            'success' => true,
            'data' => $sucursal,
        ]);
    }

    /**
     * Actualizar una sucursal.
     */
    public function update(Request $request, Sucursal $sucursal)
    {
        $validated = $request->validate([
            'nombre'              => 'required|string|max:255',
            'direccion'           => 'required|string|max:255',
            'cod_establecimiento' => 'required|string|size:4',
            'serie_boleta'        => 'required|string|size:4|starts_with:B',
            'serie_factura'       => 'required|string|size:4|starts_with:F',
            'serie_nota_credito'  => 'required|string|size:4',
            'activo'              => 'boolean',
        ]);

        $sucursal->update($validated);

        return response()->json([
            'success' => true,
            'data' => $sucursal,
            'message' => 'Sucursal actualizada con éxito'
        ]);
    }
}
