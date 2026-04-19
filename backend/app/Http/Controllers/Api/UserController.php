<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with(['roles', 'sucursal'])->get();
        return $this->successResponse($users, 'Usuarios recuperados');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email'    => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:6',
            'dni'      => 'nullable|string|max:15',
            'telefono' => 'nullable|string|max:20',
            'rol'      => 'required|string|exists:roles,name',
            'role_id'  => 'nullable|integer|exists:roles,id',
            'sucursal_id' => 'required|integer|exists:sucursales,id',
        ]);

        $user = User::create([
            'nombre'      => $validated['nombre'],
            'apellido'    => $validated['apellido'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'dni'         => $validated['dni'],
            'telefono'    => $validated['telefono'],
            'role_id'     => $validated['role_id'] ?? null,
            'sucursal_id' => $validated['sucursal_id'],
            'activo'      => true,
        ]);

        $user->assignRole($validated['rol']);

        return $this->successResponse($user->load(['roles', 'sucursal']), 'Usuario creado', 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'nombre'   => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('usuarios', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'dni'      => 'nullable|string|max:15',
            'telefono' => 'nullable|string|max:20',
            'rol'         => 'required|string|exists:roles,name',
            'role_id'     => 'nullable|integer|exists:roles,id',
            'sucursal_id' => 'required|integer|exists:sucursales,id',
        ]);

        $data = [
            'nombre'      => $validated['nombre'],
            'apellido'    => $validated['apellido'],
            'email'       => $validated['email'],
            'dni'         => $validated['dni'],
            'telefono'    => $validated['telefono'],
            'role_id'     => $validated['role_id'] ?? $user->role_id,
            'sucursal_id' => $validated['sucursal_id'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);
        $user->syncRoles([$validated['rol']]);

        return $this->successResponse($user->load(['roles', 'sucursal']), 'Usuario actualizado');
    }

    public function toggleStatus(User $usuario): JsonResponse
    {
        $usuario->update(['activo' => !$usuario->activo]);
        $status = $usuario->activo ? 'activado' : 'desactivado';
        return $this->successResponse($usuario, "Usuario $status correctamente");
    }

    public function destroy(User $usuario): JsonResponse
    {
        $usuario->delete();
        return $this->successResponse(null, 'Usuario eliminado (Soft Delete)');
    }
}
