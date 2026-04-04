---
name: auth-roles
description: Autenticación con Laravel Sanctum, sistema de roles y permisos con Spatie Laravel Permission, y control de accesos para Panadería Jara. Usa este skill cuando trabajes en login, logout, guards, middleware de permisos, protección de rutas en Vue Router, directivas v-permission, o cualquier aspecto relacionado con seguridad, roles (administrador, supervisor, cajero, vendedor, almacenero) y auditoría del sistema.
---

# Autenticación y Roles — Panadería Jara

## Stack

- Laravel Sanctum (tokens SPA)
- Spatie Laravel Permission
- Vue Router guards
- Directiva personalizada `v-can`

---

## Roles y Permisos del Sistema

### Roles definidos

| Rol | Descripción |
|---|---|
| `administrador` | Acceso total al sistema |
| `supervisor` | Todo excepto configuración del sistema |
| `cajero` | POS, caja, comprobantes, consultas |
| `vendedor` | POS y consultas de productos/stock |
| `almacenero` | Inventario, ingresos, mermas, compras |

### Permisos por módulo

```php
// En RolesPermisosSeeder.php

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
    Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'sanctum']);
}

// Asignar permisos a roles
$roles = [
    'administrador' => $permisos, // todos
    'supervisor'    => array_filter($permisos, fn($p) => !str_contains($p, 'configuracion')),
    'cajero'        => ['ver productos', 'crear ventas', 'ver ventas', 'abrir caja', 'cerrar caja',
                        'ver movimientos caja', 'registrar gastos caja', 'emitir boleta', 'emitir factura',
                        'ver clientes', 'crear clientes'],
    'vendedor'      => ['ver productos', 'crear ventas', 'ver ventas', 'ver clientes'],
    'almacenero'    => ['ver productos', 'crear productos', 'editar productos', 'ver inventario',
                        'registrar ingresos', 'registrar egresos', 'ajustar stock', 'registrar mermas',
                        'ver compras', 'crear compras'],
];
```

---

## Backend — AuthController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son válidas.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$user->activo) {
            return $this->errorResponse('Tu cuenta está desactivada. Contacta al administrador.', 403);
        }

        $token = $user->createToken('panaderia-jara')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user'  => [
                'id'       => $user->id,
                'nombre'   => $user->nombre,
                'apellido' => $user->apellido,
                'email'    => $user->email,
                'roles'    => $user->getRoleNames(),
                'permisos' => $user->getAllPermissions()->pluck('name'),
            ],
        ], 'Sesión iniciada');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Sesión cerrada');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return $this->successResponse([
            'id'       => $user->id,
            'nombre'   => $user->nombre,
            'apellido' => $user->apellido,
            'email'    => $user->email,
            'roles'    => $user->getRoleNames(),
            'permisos' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
```

---

## Frontend — Auth Store (Pinia)

```javascript
// src/stores/auth.store.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token    = ref(localStorage.getItem('token') || null)
  const user     = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const permisos = ref(JSON.parse(localStorage.getItem('permisos') || '[]'))
  const roles    = ref(JSON.parse(localStorage.getItem('roles') || '[]'))

  const isAuthenticated = computed(() => !!token.value)

  const hasPermission = (permiso) => permisos.value.includes(permiso)
  const hasRole       = (rol)     => roles.value.includes(rol)

  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)
    const { token: tkn, user: usr } = data.data

    token.value    = tkn
    user.value     = usr
    permisos.value = usr.permisos
    roles.value    = usr.roles

    localStorage.setItem('token',    tkn)
    localStorage.setItem('user',     JSON.stringify(usr))
    localStorage.setItem('permisos', JSON.stringify(usr.permisos))
    localStorage.setItem('roles',    JSON.stringify(usr.roles))

    return usr
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    token.value = null
    user.value  = null
    permisos.value = []
    roles.value    = []
    localStorage.clear()
  }

  return { token, user, permisos, roles, isAuthenticated, hasPermission, hasRole, login, logout }
}, { persist: false }) // localStorage gestionado manualmente arriba
```

---

## Directiva v-can (Vue)

```javascript
// src/directives/can.js
import { useAuthStore } from '@/stores/auth.store'

export const vCan = {
  mounted(el, binding) {
    const auth = useAuthStore()
    if (!auth.hasPermission(binding.value)) {
      el.style.display = 'none'
    }
  }
}

// Uso en componentes:
// <button v-can="'crear productos'">Nuevo Producto</button>
```

```javascript
// Registrar globalmente en main.js
import { vCan } from '@/directives/can'
app.directive('can', vCan)
```

---

## Middleware Laravel para Permisos

```php
// En controller o route
Route::middleware(['auth:sanctum', 'can:crear productos'])->post('/productos', ...);

// Con Spatie en controller:
public function store(Request $request)
{
    $this->authorize('crear productos'); // lanza 403 si no tiene permiso
    // ...
}
```

---

## Configuración Sanctum (config/sanctum.php)

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:5173')),
'guard'    => ['sanctum'],
'expiration' => null, // tokens sin expiración (ajustar en producción)
```

En `app/Models/User.php`:
```php
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected string $guard_name = 'sanctum';
}
```
