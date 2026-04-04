<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::where('activo', true);
        if ($request->search) {
            $query->where('nombre_completo', 'like', "%{$request->search}%")
                  ->orWhere('numero_documento', 'like', "%{$request->search}%");
        }
        return $this->successResponse($query->limit(20)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_documento'   => 'required|in:DNI,RUC,CE,PASAPORTE',
            'numero_documento' => 'required|string|max:20|unique:clientes,numero_documento',
            'nombre_completo'  => 'required|string|max:200',
            'razon_social'     => 'nullable|string|max:200',
            'direccion'        => 'nullable|string',
            'telefono'         => 'nullable|string|max:20',
            'email'            => 'nullable|email',
        ]);

        $cliente = Cliente::create($data);
        return $this->successResponse($cliente, 'Cliente registrado', 201);
    }
}
