<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProveedoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Proveedor::create([
            'razon_social' => 'General',
            'ruc' => '123456789',
            'telefono' => '123456789',
            'email' => 'me@gmail.com',
            'direccion' => 'Calle 123',
            'contacto_nombre' => 'Juan Perez',
            'activo' => true,
        ]);
    }
}
