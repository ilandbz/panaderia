<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receta extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'nombre',
        'rendimiento',
        'instrucciones',
        'activo',
    ];

    protected $casts = [
        'rendimiento' => 'decimal:3',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(RecetaInsumo::class);
    }
}
