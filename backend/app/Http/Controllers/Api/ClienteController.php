<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre_completo', 'like', "%{$search}%")
                  ->orWhere('numero_documento', 'like', "%{$search}%")
                  ->orWhere('razon_social', 'like', "%{$search}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === 'true' || $request->activo == 1);
        }

        $perPage = $request->get('per_page', 10);
        $clientes = $query->orderBy('nombre_completo', 'asc')->paginate($perPage);

        return $this->successResponse($clientes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_documento'     => 'required|in:DNI,RUC,CE,PASAPORTE',
            'numero_documento'   => 'required|string|max:20|unique:clientes,numero_documento',
            'nombre_completo'    => 'required|string|max:200',
            'razon_social'       => 'nullable|string|max:200',
            'direccion'          => 'nullable|string',
            'telefono'           => 'nullable|string|max:20',
            'email'              => 'nullable|email',
            'descuento_especial' => 'nullable|numeric|min:0|max:100',
        ]);

        $cliente = Cliente::create($data);
        return $this->successResponse($cliente, 'Cliente registrado con éxito', 201);
    }

    public function show($id)
    {
        $cliente = Cliente::with('ventas')->findOrFail($id);
        return $this->successResponse($cliente);
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $data = $request->validate([
            'tipo_documento'     => 'sometimes|required|in:DNI,RUC,CE,PASAPORTE',
            'numero_documento'   => 'sometimes|required|string|max:20|unique:clientes,numero_documento,' . $id,
            'nombre_completo'    => 'sometimes|required|string|max:200',
            'razon_social'       => 'nullable|string|max:200',
            'direccion'          => 'nullable|string',
            'telefono'           => 'nullable|string|max:20',
            'email'              => 'nullable|email',
            'descuento_especial' => 'nullable|numeric|min:0|max:100',
            'activo'             => 'nullable|boolean',
        ]);

        $cliente->update($data);
        return $this->successResponse($cliente, 'Cliente actualizado correctamente');
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        
        // Verificar si tiene ventas asociadas si queremos evitar el borrado físico (aunque use SoftDeletes)
        // Por ahora, usamos SoftDeletes de Eloquent.
        $cliente->delete();

        return $this->successResponse(null, 'Cliente eliminado correctamente');
    }
}
