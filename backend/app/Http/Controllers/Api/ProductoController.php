<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Services\ProductoService;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    protected $service;

    public function __construct(ProductoService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $productos = $this->service->listar($request->all());
        return $this->successResponse($productos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:150',
            'categoria_id'  => 'required|exists:categorias,id',
            'tipo'          => 'required|in:reventa,elaborado,insumo',
            'precio_venta'  => 'required|numeric',
            'costo'         => 'nullable|numeric',
            'stock_minimo'  => 'nullable|numeric',
            'unidad_medida' => 'required|string|max:10',
            'codigo'        => 'nullable|string|max:50|unique:productos,codigo',
            'stock'         => 'nullable|numeric', // Stock inicial opcional
        ]);

        $producto = $this->service->crear($data);
        return $this->successResponse($producto, 'Producto creado correctamente', 201);
    }

    public function show(Producto $producto)
    {
        return $this->successResponse($producto->load('categoria'));
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'nombre'        => 'string|max:150',
            'categoria_id'  => 'exists:categorias,id',
            'tipo'          => 'in:reventa,elaborado,insumo',
            'precio_venta'  => 'numeric',
            'costo'         => 'nullable|numeric',
            'stock_minimo'  => 'nullable|numeric',
            'unidad_medida' => 'string|max:10',
            'codigo'        => 'nullable|string|max:50|unique:productos,codigo,' . $producto->id,
            'activo'        => 'boolean',
        ]);

        $producto = $this->service->actualizar($producto, $data);
        return $this->successResponse($producto, 'Producto actualizado correctamente');
    }

    public function destroy(Producto $producto)
    {
        $this->service->eliminar($producto);
        return $this->successResponse(null, 'Producto eliminado correctamente');
    }

    /**
     * Obtiene el historial de movimientos de un producto (Kardex)
     */
    public function movimientos(Producto $producto)
    {
        $movimientos = MovimientoInventario::with('usuario')
            ->where('producto_id', $producto->id)
            ->latest()
            ->paginate(20);

        return $this->successResponse($movimientos);
    }

    /**
     * Realiza un ajuste manual de stock
     */
    public function ajustarStock(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'tipo'        => 'required|in:ingreso,egreso',
            'cantidad'    => 'required|numeric|min:0.001',
            'motivo'      => 'required|string|max:100',
            'observacion' => 'nullable|string|max:255',
        ]);

        $movimiento = $this->service->ajustarStock($producto, $data);
        return $this->successResponse($movimiento, 'Stock ajustado correctamente');
    }
}
