---
name: laravel-backend
description: Patrones, convenciones y código base para el backend Laravel 13 de Panadería Jara. Usa este skill siempre que vayas a crear controladores, modelos, servicios, migrations, seeders, middlewares, Form Requests o cualquier clase PHP del backend. Actívalo también cuando se discutan relaciones entre modelos, scopes, observers, eventos o cualquier lógica del lado del servidor.
---

# Laravel 13 Backend — Panadería Jara

## Stack de Backend

- Laravel 13 / PHP 8.3+
- Laravel Sanctum (autenticación SPA)
- Spatie Laravel Permission (roles y permisos)
- MySQL 8+
- Laravel Pint (code style)

---

## Estructura de Directorios

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/           ← TODOS los controllers aquí
│   ├── Middleware/
│   └── Requests/          ← Un FormRequest por operación
├── Models/
├── Services/              ← Lógica de negocio (NO en controllers)
├── Repositories/          ← Consultas complejas a BD
├── Enums/                 ← Estados, tipos, categorías
├── Observers/
└── Exceptions/
```

---

## Patrón de Controller

Los controllers solo orquestan: validan → llaman Service → retornan respuesta.

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Producto\StoreProductoRequest;
use App\Http\Requests\Producto\UpdateProductoRequest;
use App\Services\ProductoService;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    public function __construct(
        private readonly ProductoService $productoService
    ) {}

    public function index(): JsonResponse
    {
        $productos = $this->productoService->listar(request()->all());
        return $this->successResponse($productos);
    }

    public function store(StoreProductoRequest $request): JsonResponse
    {
        $producto = $this->productoService->crear($request->validated());
        return $this->successResponse($producto, 'Producto creado', 201);
    }

    public function update(UpdateProductoRequest $request, int $id): JsonResponse
    {
        $producto = $this->productoService->actualizar($id, $request->validated());
        return $this->successResponse($producto, 'Producto actualizado');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productoService->eliminar($id);
        return $this->successResponse(null, 'Producto eliminado');
    }
}
```

---

## Trait ApiResponse (BaseController)

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use AuthorizesRequests;

    protected function successResponse(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(
        string $message = 'Error',
        int $status = 400,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}
```

---

## Patrón de Service

```php
<?php

namespace App\Services;

use App\Models\Producto;
use App\Enums\TipoProducto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductoService
{
    public function listar(array $filtros = [])
    {
        return Producto::query()
            ->when($filtros['categoria_id'] ?? null, fn($q, $v) => $q->where('categoria_id', $v))
            ->when($filtros['tipo'] ?? null, fn($q, $v) => $q->where('tipo', $v))
            ->when($filtros['search'] ?? null, fn($q, $v) => $q->where('nombre', 'like', "%{$v}%"))
            ->with(['categoria'])
            ->orderBy('nombre')
            ->paginate($filtros['per_page'] ?? 15);
    }

    public function crear(array $datos): Producto
    {
        return DB::transaction(function () use ($datos) {
            if (isset($datos['imagen'])) {
                $datos['imagen_path'] = Storage::disk('public')
                    ->store($datos['imagen'], 'productos');
                unset($datos['imagen']);
            }

            $producto = Producto::create($datos);

            // Si tiene stock inicial, registrar movimiento
            if (($datos['stock_inicial'] ?? 0) > 0) {
                $producto->movimientos()->create([
                    'tipo'      => 'ingreso',
                    'cantidad'  => $datos['stock_inicial'],
                    'motivo'    => 'Stock inicial',
                    'usuario_id' => auth()->id(),
                ]);
            }

            return $producto->fresh(['categoria']);
        });
    }

    public function actualizar(int $id, array $datos): Producto
    {
        $producto = Producto::findOrFail($id);

        return DB::transaction(function () use ($producto, $datos) {
            $producto->update($datos);
            return $producto->fresh(['categoria']);
        });
    }

    public function eliminar(int $id): void
    {
        $producto = Producto::findOrFail($id);
        $producto->delete(); // soft delete
    }
}
```

---

## Modelo Base con Traits

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'categoria_id',
        'tipo', 'precio_venta', 'costo', 'stock', 'stock_minimo',
        'unidad_medida', 'fecha_vencimiento', 'imagen_path',
        'activo', 'afecto_igv',
    ];

    protected $casts = [
        'precio_venta'      => 'decimal:2',
        'costo'             => 'decimal:2',
        'stock'             => 'decimal:3',
        'stock_minimo'      => 'decimal:3',
        'fecha_vencimiento' => 'date',
        'activo'            => 'boolean',
        'afecto_igv'        => 'boolean',
    ];

    protected $appends = ['stock_bajo', 'por_vencer'];

    // Relationships
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    // Accessors
    public function getStockBajoAttribute(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    public function getPorVencerAttribute(): bool
    {
        if (!$this->fecha_vencimiento) return false;
        return $this->fecha_vencimiento->lte(now()->addDays(7));
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    public function scopePorVencer($query, int $dias = 7)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<=', now()->addDays($dias));
    }
}
```

---

## Enums (PHP 8.1+)

```php
<?php

namespace App\Enums;

enum TipoProducto: string
{
    case REVENTA   = 'reventa';
    case ELABORADO = 'elaborado';
    case INSUMO    = 'insumo';

    public function label(): string
    {
        return match($this) {
            self::REVENTA   => 'Producto de Reventa',
            self::ELABORADO => 'Producto Elaborado',
            self::INSUMO    => 'Insumo',
        };
    }
}

enum EstadoVenta: string
{
    case PENDIENTE  = 'pendiente';
    case COMPLETADA = 'completada';
    case ANULADA    = 'anulada';
}

enum TipoComprobante: string
{
    case BOLETA  = 'boleta';
    case FACTURA = 'factura';
    case TICKET  = 'ticket';
    case NOTA_CREDITO = 'nota_credito';
}

enum TipoMovimientoCaja: string
{
    case INGRESO = 'ingreso';
    case EGRESO  = 'egreso';
}
```

---

## FormRequest estándar

```php
<?php

namespace App\Http\Requests\Producto;

use App\Enums\TipoProducto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear productos');
    }

    public function rules(): array
    {
        return [
            'nombre'           => ['required', 'string', 'max:150'],
            'codigo'           => ['nullable', 'string', 'max:50', 'unique:productos'],
            'categoria_id'     => ['required', 'exists:categorias,id'],
            'tipo'             => ['required', new Enum(TipoProducto::class)],
            'precio_venta'     => ['required', 'numeric', 'min:0'],
            'costo'            => ['nullable', 'numeric', 'min:0'],
            'stock'            => ['nullable', 'numeric', 'min:0'],
            'stock_minimo'     => ['nullable', 'numeric', 'min:0'],
            'unidad_medida'    => ['required', 'string', 'in:UND,KG,LT,PQT,CAJ,POR'],
            'fecha_vencimiento' => ['nullable', 'date', 'after:today'],
            'imagen'           => ['nullable', 'image', 'max:2048'],
            'afecto_igv'       => ['boolean'],
        ];
    }
}
```

---

## Transacciones — Regla de Oro

Cualquier operación que toque múltiples tablas DEBE estar en `DB::transaction()`:

```php
DB::transaction(function () {
    // operaciones que deben ser atómicas
});
```

---

## Manejo de Errores Global

En `bootstrap/app.php` o `Exceptions/Handler.php`:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (ModelNotFoundException $e) {
        return response()->json(['success' => false, 'message' => 'Registro no encontrado'], 404);
    });

    $exceptions->render(function (AuthorizationException $e) {
        return response()->json(['success' => false, 'message' => 'Sin permiso'], 403);
    });

    $exceptions->render(function (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Datos inválidos',
            'errors'  => $e->errors(),
        ], 422);
    });
})
```

---

## Rutas API (routes/api.php)

```php
Route::prefix('v1')->group(function () {

    // Públicas
    Route::post('auth/login', [AuthController::class, 'login']);

    // Protegidas
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Recursos estándar
        Route::apiResource('productos', ProductoController::class);
        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('ventas', VentaController::class)->only(['index','store','show']);
        Route::apiResource('caja', CajaController::class);
    });
});
```
