<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'tipo',
        'precio_venta',
        'costo',
        'stock',
        'stock_minimo',
        'unidad_medida',
        'fecha_vencimiento',
        'imagen_path',
        'activo',
        'afecto_igv',
        'igv_porcentaje',
    ];

    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'producto_sucursal')
                    ->withPivot('stock', 'stock_minimo')
                    ->withTimestamps();
    }

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'costo' => 'decimal:2',
        'stock' => 'decimal:3',
        'stock_minimo' => 'decimal:3',
        'fecha_vencimiento' => 'date',
        'activo' => 'boolean',
        'afecto_igv' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorVencer($query, $dias = 30)
    {
        return $query->whereNotNull('fecha_vencimiento')
                     ->where('fecha_vencimiento', '<=', now()->addDays($dias));
    }
}
