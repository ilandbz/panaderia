<?php

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;

// Buscamos la categoría Pastelería para el producto padre
$categoria = Categoria::where('nombre', 'like', '%Pastel%')->first();

DB::transaction(function() use ($categoria) {
    // 1. Creamos el PRODUCTO PADRE (El contenedor)
    $padre = Producto::create([
        'nombre' => 'Torta Selva Negra',
        'descripcion' => 'Torta clásica de chocolate con crema y cerezas',
        'categoria_id' => $categoria->id,
        'tipo' => 'elaborado',
        'precio_venta' => 0, // El padre usualmente no se vende directo, o tiene el precio "desde"
        'activo' => true
    ]);

    // 2. Mapeamos los productos existentes como variantes
    $variantes = [
        20 => 'Grande 2KG',
        21 => 'Mediana 1.5KG',
        26 => 'Tajada',
        112 => 'Redonda 1KG',
        113 => 'Redondo 1/2 KG'
    ];

    foreach ($variantes as $id => $nombreVariante) {
        $producto = Producto::find($id);
        if ($producto) {
            $producto->update([
                'parent_id' => $padre->id,
                'nombre_variante' => $nombreVariante,
                // Opcional: Estandarizamos el nombre base para que en el ticket salga bien
                'nombre' => 'Torta Selva Negra' 
            ]);
            echo "Producto ID $id migrado como variante: $nombreVariante\n";
        }
    }
});

echo "Migración de Selva Negra completada.\n";
