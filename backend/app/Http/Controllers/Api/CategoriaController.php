<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        return $this->successResponse(Categoria::where('activo', true)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'icono'       => 'nullable|string',
            'color'       => 'nullable|string',
        ]);

        $categoria = Categoria::create($data);
        return $this->successResponse($categoria, 'Categoría creada', 201);
    }
}
