<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index()
    {
        return $this->successResponse(Proveedor::latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ruc' => 'required|string|size:11|unique:proveedores',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            'email' => 'nullable|email',
            'contacto_nombre' => 'nullable|string|max:100',
        ]);

        $proveedor = Proveedor::create($validated);
        return $this->successResponse($proveedor, 'Proveedor registrado correctamente', 201);
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $validated = $request->validate([
            'ruc' => 'required|string|size:11|unique:proveedores,ruc,' . $proveedor->id,
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            'email' => 'nullable|email',
            'contacto_nombre' => 'nullable|string|max:100',
            'activo' => 'boolean',
        ]);

        $proveedor->update($validated);
        return $this->successResponse($proveedor, 'Proveedor actualizado correctamente');
    }

    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();
        return $this->successResponse(null, 'Proveedor eliminado');
    }
}
