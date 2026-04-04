<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\RecetaController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::get('/ventas/{venta}/impresion', [VentaController::class, 'imprimirTicket']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Productos
    Route::apiResource('productos', ProductoController::class);
    Route::get('/productos/{producto}/movimientos', [ProductoController::class, 'movimientos']);
    Route::post('/productos/{producto}/ajuste-stock', [ProductoController::class, 'ajustarStock']);

    // Compras & Proveedores
    Route::apiResource('proveedores', ProveedorController::class);
    Route::apiResource('compras', CompraController::class);

    // Producción & Recetas
    Route::get('/recetas', [RecetaController::class, 'index']);
    Route::post('/recetas/{receta}/producir', [RecetaController::class, 'producir']);

    // Reportes & Dashboard
    Route::get('/dashboard', [ReporteController::class, 'dashboard']);

    // Ventas
    Route::apiResource('ventas', VentaController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular']);
    Route::post('/ventas/{venta}/generar-comprobante', [VentaController::class, 'generarComprobante']);
    Route::get('/ventas/{venta}/pdf', [VentaController::class, 'downloadPdf']);

    // Caja
    Route::prefix('caja')->group(function () {
        Route::get('/estado', [CajaController::class, 'estado']);
        Route::post('/abrir', [CajaController::class, 'abrir']);
        Route::post('/cerrar', [CajaController::class, 'cerrar']);
        Route::post('/gasto', [CajaController::class, 'registrarGasto']);
    });

    // Categorias
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::post('/categorias', [CategoriaController::class, 'store']);

    // Clientes
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::post('/clientes', [ClienteController::class, 'store']);

    // Configuración: Usuarios & Roles
    Route::apiResource('users', UserController::class)->except(['show']);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    Route::apiResource('roles', RoleController::class)->except(['show']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
});
