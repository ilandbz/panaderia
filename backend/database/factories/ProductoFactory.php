<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'codigo' => 'P' . $this->faker->unique()->numberBetween(1000, 9999),
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->sentence(),
            'categoria_id' => 1,
            'tipo' => 'reventa',
            'precio_venta' => $this->faker->randomFloat(2, 0.5, 50),
            'costo' => $this->faker->randomFloat(2, 0.1, 30),
            'stock' => $this->faker->randomFloat(3, 10, 100),
            'stock_minimo' => 5,
            'unidad_medida' => 'UND',
            'activo' => true,
        ];
    }
}
