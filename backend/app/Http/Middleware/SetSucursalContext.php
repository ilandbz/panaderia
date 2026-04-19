<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSucursalContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sucursalId = $request->header('X-Sucursal-Id');

        // Si no viene en el header, intentar obtenerlo del usuario autenticado
        if (!$sucursalId) {
            try {
                if (auth()->check()) {
                    $sucursalId = auth()->user()->sucursal_id;
                }
            } catch (\Exception $e) {
                // Silenciar errores de auth si el sistema no está totalmente listo
            }
        }

        if ($sucursalId) {
            config(['app.sucursal_id' => (int)$sucursalId]);
        }

        return $next($request);
    }
}
