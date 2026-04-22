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

        $query = Producto::with(['categoria', 'variantes', 'sucursales' => function ($q) use ($sucursal_id) {
            $q->where('sucursales.id', $sucursal_id);
        }]);

        // Filtrar solo destacados/padres por defecto si no se pide uno específico
        if (!isset($filtros['include_children']) || $filtros['include_children'] !== 'true') {
            $query->whereNull('parent_id');
        }

        if (isset($filtros['categoria_id']) && $filtros['categoria_id']) {
            $query->where('categoria_id', $filtros['categoria_id']);
        }

        // Filtro para traer solo activos (Ideal para el POS)
        if (isset($filtros['activos']) && ($filtros['activos'] === 'true' || $filtros['activos'] === true || $filtros['activos'] == 1)) {
            $query->where('activo', 1);
        }

        if (isset($filtros['search'])) {
            $searchTerm = $filtros['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%' . $searchTerm . '%')
                    ->orWhere('codigo', 'like', '%' . $searchTerm . '%');
            });
        }

        // Si se pide 'all', devolvemos todo el listado sin paginar
        if (isset($filtros['all']) && ($filtros['all'] === 'true' || $filtros['all'] === true)) {
            $productos = $query->get();
            
            $productos->transform(function ($producto) use ($sucursal_id) {
                return $this->mapearStockYVariantes($producto, $sucursal_id);
            });
            
            return $productos;
        }

        $productos = $query->paginate($filtros['per_page'] ?? 15);

        // Mapear el stock de la sucursal al objeto base para compatibilidad con el frontend
        $productos->getCollection()->transform(function($producto) use ($sucursal_id) {
            return $this->mapearStockYVariantes($producto, $sucursal_id);
        });

        return $productos;
    }

    /**
     * Mapea el stock de la sucursal y procesa las variantes recursivamente
     */
    private function mapearStockYVariantes($producto, $sucursal_id)
    {
        $sucursal = $producto->sucursales->where('id', $sucursal_id)->first();
        $producto->stock = $sucursal ? $sucursal->pivot->stock : 0;
        $producto->stock_minimo = $sucursal ? $sucursal->pivot->stock_minimo : 0;

        if ($producto->variantes && $producto->variantes->count() > 0) {
            $producto->variantes->transform(function ($variante) use ($sucursal_id) {
                // Cargar sucursales para la variante si no están cargadas
                if (!$variante->relationLoaded('sucursales')) {
                    $variante->load(['sucursales' => function($q) use ($sucursal_id) {
                        $q->where('sucursales.id', $sucursal_id);
                    }]);
                }
                $vSucursal = $variante->sucursales->where('id', $sucursal_id)->first();
                $variante->stock = $vSucursal ? $vSucursal->pivot->stock : 0;
                $variante->stock_minimo = $vSucursal ? $vSucursal->pivot->stock_minimo : 0;
                return $variante;
            });
        }

        return $producto;
    }

    public function crear(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sucursal_id = $data['sucursal_id'] ?? config('app.sucursal_id') ?? auth()->user()->sucursal_id;

            // Extraer datos de stock para la tabla pivot
            $stockInicial = $data['stock'] ?? 0;
            $stockMinimo = $data['stock_minimo'] ?? 0;

            // Limpiar data para la tabla base 'productos'
            unset($data['stock'], $data['stock_minimo'], $data['sucursal_id']);

            $producto = Producto::create($data);

            // Registrar en la sucursal activa
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
            $sucursal_id = config('app.sucursal_id') ?? auth()->user()->sucursal_id;

            // Si viene stock_minimo, actualizarlo en la tabla pivot
            if (isset($data['stock_minimo'])) {
                $stockMinimo = $data['stock_minimo'];
                $producto->sucursales()->syncWithoutDetaching([
                    $sucursal_id => ['stock_minimo' => $stockMinimo]
                ]);
                unset($data['stock_minimo']);
            }

            // Eliminar otros campos que no pertenecen a la tabla productos
            unset($data['stock'], $data['sucursal_id']);

            $producto->update($data);
            return $producto;
        });
    }

    public function eliminar(Producto $producto)
    {
        return $producto->delete();
    }
}
