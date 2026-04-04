<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(InitialDataSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(ProveedoresSeeder::class);
        $admin = User::create([
            'nombre'   => 'Admin',
            'role_id'  => 1,
            'apellido' => 'General',
            'email'    => 'admin@me.com',
            'password' => bcrypt('admin123'),
            'dni'      => '00000000',
            'telefono' => '999999999',
            'activo'   => true,
        ]);
        $admin->assignRole('administrador');
        $this->command->info('Sistema inicializado con usuario: admin@me.com / admin123');
    }
}
