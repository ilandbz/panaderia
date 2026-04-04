<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Permisos (según SKILL.md)
        $permisos = [
            // Productos
            'ver productos', 'crear productos', 'editar productos', 'eliminar productos',
            // Inventario
            'ver inventario', 'registrar ingresos', 'registrar egresos', 'ajustar stock', 'registrar mermas',
            // Ventas
            'ver ventas', 'crear ventas', 'anular ventas',
            // Caja
            'abrir caja', 'cerrar caja', 'ver movimientos caja', 'registrar gastos caja',
            // Comprobantes
            'emitir boleta', 'emitir factura', 'anular comprobante',
            // Compras
            'ver compras', 'crear compras', 'editar compras',
            // Clientes
            'ver clientes', 'crear clientes', 'editar clientes',
            // Reportes
            'ver reportes', 'ver reportes gerenciales',
            // Usuarios
            'ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios',
            // Configuración
            'ver configuracion', 'editar configuracion',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'sanctum');
        }

        // 2. Crear Roles
        $roles = [
            [
                'nombre' => 'Administrador', 
                'name' => 'administrador', 
                'guard_name' => 'sanctum',
                'permisos' => $permisos // todos
            ],
            [
                'nombre' => 'Supervisor',  
                'name' => 'supervisor',    
                'guard_name' => 'sanctum',
                'permisos' => array_filter($permisos, fn($p) => !str_contains($p, 'configuracion'))
            ],
            [
                'nombre' => 'Cajero',      
                'name' => 'cajero',        
                'guard_name' => 'sanctum',
                'permisos' => ['ver productos', 'crear ventas', 'ver ventas', 'abrir caja', 'cerrar caja',
                               'ver movimientos caja', 'registrar gastos caja', 'emitir boleta', 'emitir factura',
                               'ver clientes', 'crear clientes']
            ],
            [
                'nombre' => 'Vendedor',    
                'name' => 'vendedor',      
                'guard_name' => 'sanctum',
                'permisos' => ['ver productos', 'crear ventas', 'ver ventas', 'ver clientes']
            ],
            [
                'nombre' => 'Almacenero',  
                'name' => 'almacenero',    
                'guard_name' => 'sanctum',
                'permisos' => ['ver productos', 'crear productos', 'editar productos', 'ver inventario',
                               'registrar ingresos', 'registrar egresos', 'ajustar stock', 'registrar mermas',
                               'ver compras', 'crear compras']
            ],
            [
                'nombre' => 'Cliente',     
                'name' => 'cliente',       
                'guard_name' => 'sanctum',
                'permisos' => []
            ],
        ];

        foreach ($roles as $roleData) {
            $permisosRole = $roleData['permisos'];
            unset($roleData['permisos']);
            
            $role = Role::updateOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                ['nombre' => $roleData['nombre']]
            );
            
            $role->syncPermissions($permisosRole);
        }
    }
}
