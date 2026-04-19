<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductoService
{
    public function listar(array $filtros = [])
    {
        $sucursal_id = $filtros['sucursal_id'] ?? config('app.sucursal_id') ?? auth()->user()->sucursal_id;

        $query = Producto::with(['categoria', 'sucursales' => function($q) use ($sucursal_id) {
            $q->where('sucursales.id', $sucursal_id);
        }]);

        if (isset($filtros['categoria_id']) && $filtros['categoria_id']) {
            $query->where('categoria_id', $filtros['categoria_id']);
        }

        if (isset($filtros['search'])) {
            $searchTerm = $filtros['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%' . $searchTerm . '%')
                  ->orWhere('codigo', 'like', '%' . $searchTerm . '%');
            });
        }

        $productos = $query->paginate($filtros['per_page'] ?? 15);

        // Mapear el stock de la sucursal al objeto base para compatibilidad con el frontend
        $productos->getCollection()->transform(function($producto) {
            $sucursal = $producto->sucursales->first();
            $producto->stock = $sucursal ? $sucursal->pivot->stock : 0;
            $producto->stock_minimo = $sucursal ? $sucursal->pivot->stock_minimo : 0;
            return $producto;
        });

        return $productos;
    }

    public function crear(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sucursal_id = $data['sucursal_id'] ?? config('app.sucursal_id') ?? auth()->user()->sucursal_id;
            
            $producto = Producto::create($data);
            
            // Si inicia con stock, registrar en la sucursal activa
            $stockInicial = $data['stock'] ?? 0;
            $stockMinimo = $data['stock_minimo'] ?? 0;

            $producto->sucursales()->attach($sucursal_id, [
                'stock' => $stockInicial,
                'stock_minimo' => $stockMinimo
            ]);

            if ($stockInicial > 0) {
                MovimientoInventario::create([
                    'producto_id'    => $producto->id,
                    'usuario_id'     => Auth::id(),
                    'sucursal_id'    => $sucursal_id,
                    'tipo'           => 'ingreso',
                    'cantidad'       => $stockInicial,
                    'stock_anterior' => 0,
                    'stock_nuevo'    => $stockInicial,
                    'motivo'         => 'inventario_inicial',
                ]);
            }
            
            return $producto;
        });
    }

    public function ajustarStock(Producto $producto, array $data)
    {
        return DB::transaction(function () use ($producto, $data) {
            $sucursal_id = $data['sucursal_id'] ?? config('app.sucursal_id') ?? auth()->user()->sucursal_id;
            
            // Obtener stock actual en la sucursal
            $pivot = $producto->sucursales()->where('sucursal_id', $sucursal_id)->first();
            $stockAnterior = $pivot ? $pivot->pivot->stock : 0;
            
            $cantidad = $data['cantidad'];
            $tipo = $data['tipo']; // ingreso o egreso
            $nuevoStock = ($tipo === 'ingreso') ? ($stockAnterior + $cantidad) : ($stockAnterior - $cantidad);

            // Actualizar tabla pivot
            $producto->sucursales()->syncWithoutDetaching([
                $sucursal_id => ['stock' => $nuevoStock]
            ]);

            return MovimientoInventario::create([
                'producto_id'    => $producto->id,
                'usuario_id'     => Auth::id(),
                'sucursal_id'    => $sucursal_id,
                'tipo'           => $tipo,
                'cantidad'       => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo'    => $nuevoStock,
                'motivo'         => $data['motivo'] ?? 'ajuste',
                'observacion'    => $data['observacion'] ?? null,
            ]);
        });
    }

    public function actualizar(Producto $producto, array $data)
    {
        return DB::transaction(function () use ($producto, $data) {
            $producto->update($data);
            return $producto;
        });
    }

    public function eliminar(Producto $producto)
    {
        return $producto->delete();
    }
}
