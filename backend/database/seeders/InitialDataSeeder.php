<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Cliente;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // =========================
        // CATEGORÍAS
        // =========================
        $panaderia = Categoria::create([
            'nombre' => 'Panadería',
            'icono' => 'fa-bread-slice',
            'color' => '#d97706',
            'activo' => true
        ]);
        $pasteleria = Categoria::create([
            'nombre' => 'Pastelería',
            'icono' => 'fa-birthday-cake',
            'color' => '#db2777',
            'activo' => true
        ]);
        $abarrotes = Categoria::create([
            'nombre' => 'Abarrotes',
            'icono' => 'fa-shopping-basket',
            'color' => '#059669',
            'activo' => true
        ]);

        // =========================
        // PANADERÍA
        // =========================
        $panes = [
            ['Pan Francés', 1.00],
            ['Pan Caracol', 1.00],
            ['Pan Mestizo', 1.00],
            ['Pan Cachito', 1.00],
            ['Pan Bayo', 1.00],
            ['Pan con Manteca', 1.00],
        ];

        foreach ($panes as $index => $p) {
            $producto = Producto::create([
                'nombre' => $p[0],
                'codigo' => 'PAN' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'categoria_id' => $panaderia->id,
                'tipo' => 'elaborado',
                'precio_venta' => $p[1],
                'costo' => $p[1] * 0.5,
                // stock ya no va aquí
                'unidad_medida' => 'UND',
                'activo' => true,
            ]);

            // Asignar stock a la sede central (ID 1)
            $producto->sucursales()->attach(1, [
                'stock' => 100,
                'stock_minimo' => 10
            ]);
        }

        // =========================
        // PASTELERÍA
        // =========================
        // ... (省略)
        $pasteles = [
            // ... (省略)
        ];

        foreach ($pasteles as $index => $p) {
            $producto = Producto::create([
                'nombre' => $p[0],
                'codigo' => 'PAS' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'categoria_id' => $pasteleria->id,
                'tipo' => 'elaborado',
                'precio_venta' => $p[1],
                'costo' => $p[1] * 0.5,
                'unidad_medida' => 'UND',
                'activo' => true,
            ]);

            $producto->sucursales()->attach(1, [
                'stock' => 50,
                'stock_minimo' => 5
            ]);
        }

        // =========================
        // ABARROTES / BEBIDAS
        // =========================
        // ... (省略)
        foreach ($abarrotesList as $index => $p) {
            $producto = Producto::create([
                'nombre' => $p[0],
                'codigo' => 'ABA' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'categoria_id' => $abarrotes->id,
                'tipo' => 'reventa',
                'precio_venta' => $p[1],
                'costo' => $p[1] * 0.7,
                'unidad_medida' => 'UND',
                'activo' => true,
            ]);

            $producto->sucursales()->attach(1, [
                'stock' => 100,
                'stock_minimo' => 10
            ]);
        }

        // =========================
        // CLIENTE GENERAL
        // =========================
        Cliente::create([
            'tipo_documento' => 'DNI',
            'numero_documento' => '00000000',
            'nombre_completo' => 'CLIENTE VARIOS',
            'activo' => true,
        ]);
    }
}
