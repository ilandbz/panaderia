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
        $query = Producto::with('categoria');

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

        return $query->paginate($filtros['per_page'] ?? 100);
    }

    public function crear(array $data)
    {
        return DB::transaction(function () use ($data) {
            $producto = Producto::create($data);
            
            // Si inicia con stock, registrar movimiento inicial
            if (isset($data['stock']) && $data['stock'] > 0) {
                MovimientoInventario::create([
                    'producto_id'    => $producto->id,
                    'usuario_id'     => Auth::id(),
                    'tipo'           => 'ingreso',
                    'cantidad'       => $data['stock'],
                    'stock_anterior' => 0,
                    'stock_nuevo'    => $data['stock'],
                    'motivo'         => 'inventario_inicial',
                ]);
            }
            
            return $producto;
        });
    }

    public function ajustarStock(Producto $producto, array $data)
    {
        return DB::transaction(function () use ($producto, $data) {
            $stockAnterior = $producto->stock;
            $cantidad = $data['cantidad'];
            $tipo = $data['tipo']; // ingreso o egreso

            if ($tipo === 'ingreso') {
                $producto->increment('stock', $cantidad);
            } else {
                $producto->decrement('stock', $cantidad);
            }

            return MovimientoInventario::create([
                'producto_id'    => $producto->id,
                'usuario_id'     => Auth::id(),
                'tipo'           => $tipo,
                'cantidad'       => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo'    => $producto->stock,
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
