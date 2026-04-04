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
        $panaderia = Categoria::create(['nombre' => 'Panadería', 'activo' => true]);
        $pasteleria = Categoria::create(['nombre' => 'Pastelería', 'activo' => true]);
        $abarrotes = Categoria::create(['nombre' => 'Abarrotes', 'activo' => true]);

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
            Producto::create([
                'nombre' => $p[0],
                'codigo' => 'PAN' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'categoria_id' => $panaderia->id,
                'tipo' => 'elaborado',
                'precio_venta' => $p[1],
                'costo' => $p[1] * 0.5,
                'stock' => 100,
                'stock_minimo' => 10,
                'unidad_medida' => 'UND',
                'activo' => true,
            ]);
        }

        // =========================
        // PASTELERÍA
        // =========================
        $pasteles = [
            ['Mil Hojas', 2.50],
            ['Empanada', 2.50],
            ['Pie de Manzana', 3.00],
            ['Alfajor', 2.00],
            ['Pionono', 2.00],
            ['Dona', 2.50],
            ['Galleta', 1.00],
            ['Oreja', 1.50],
            ['Volteado', 1.00],
            ['Pie de Limón', 3.50],
            ['Cono', 2.00],
            ['Tajada de Keke', 2.00],
            ['Keke Entero', 12.50],

            // Tortas
            ['Torta Grande (Selva Negra)', 55.00],
            ['Torta Mediana (Selva Negra)', 40.00],
            ['Torta Redonda', 35.00],
            ['Tres Leches Grande', 40.00],
            ['Tres Leches Kilo', 35.00],
            ['Tres Leches Mediano', 25.00],

            // Porciones
            ['Tajada Selva Negra', 5.00],
            ['Tajada Tres Leches', 5.50],
            ['Torta en Vaso', 5.50],
            ['Copa de Chocolate', 4.50],
            ['Chicker', 6.00],
            ['Tajada de Torta Helada', 5.50],
            ['Tajada de Chocolate', 5.50],
            ['Tajada Chocobanano', 5.50],
        ];

        foreach ($pasteles as $index => $p) {
            Producto::create([
                'nombre' => $p[0],
                'codigo' => 'PAS' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'categoria_id' => $pasteleria->id,
                'tipo' => 'elaborado',
                'precio_venta' => $p[1],
                'costo' => $p[1] * 0.5,
                'stock' => 50,
                'stock_minimo' => 5,
                'unidad_medida' => 'UND',
                'activo' => true,
            ]);
        }

        // =========================
        // ABARROTES / BEBIDAS
        // =========================
        $abarrotesList = [
            ['Leche Gloria', 4.20],
            ['Leche Ideal', 4.00],
            ['Leche Bonlé', 3.50],
            ['Leche Pura Vida', 5.50],
            ['Nestlé', 5.50],
            ['Milo', 5.00],
            ['Café', 1.50],

            ['Inca Kola', 3.50],
            ['Fanta', 2.50],
            ['Volt', 2.50],
            ['Agua con Chupón', 2.00],
            ['Pepsi', 2.00],
            ['Concordia', 1.20],
            ['Frugos del Valle', 5.50],
            ['Pulp', 1.50],
            ['Inca Kola Grande', 13.00],
        ];

        foreach ($abarrotesList as $index => $p) {
            Producto::create([
                'nombre' => $p[0],
                'codigo' => 'ABA' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'categoria_id' => $abarrotes->id,
                'tipo' => 'reventa',
                'precio_venta' => $p[1],
                'costo' => $p[1] * 0.7,
                'stock' => 100,
                'stock_minimo' => 10,
                'unidad_medida' => 'UND',
                'activo' => true,
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
