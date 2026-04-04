<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        return $this->successResponse($roles, 'Roles recuperados');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'     => 'required|string|max:50|unique:roles,nombre',
            'name'       => 'required|string|max:50|unique:roles,name',
            'guard_name' => 'required|string|in:sanctum,web',
            'permisos'   => 'array',
            'permisos.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'nombre'     => $validated['nombre'],
            'name'       => $validated['name'],
            'guard_name' => $validated['guard_name'],
        ]);

        if (!empty($validated['permisos'])) {
            $role->syncPermissions($validated['permisos']);
        }

        return $this->successResponse($role->load('permissions'), 'Rol creado', 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'nombre'     => 'required|string|max:50|unique:roles,nombre,' . $role->id,
            'name'       => 'required|string|max:50|unique:roles,name,' . $role->id,
            'permisos'   => 'array',
            'permisos.*' => 'string|exists:permissions,name',
        ]);

        $role->update([
            'nombre' => $validated['nombre'],
            'name'   => $validated['name'],
        ]);

        if (isset($validated['permisos'])) {
            $role->syncPermissions($validated['permisos']);
        }

        return $this->successResponse($role->load('permissions'), 'Rol actualizado');
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'sanctum')->get();
        return $this->successResponse($permissions, 'Permisos recuperados');
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->count() > 0) {
            return $this->errorResponse('No se puede eliminar un rol que tiene usuarios asignados', 422);
        }

        $role->delete();
        return $this->successResponse(null, 'Rol eliminado');
    }
}
