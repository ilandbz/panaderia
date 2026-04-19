<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    /**
     * Listar sucursales activas.
     */
    public function index()
    {
        $sucursales = Sucursal::where('activo', true)->get();
        
        return response()->json([
            'success' => true,
            'data' => $sucursales,
            'message' => 'Sucursales obtenidas con éxito'
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
}
