<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lote extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'numero_lote',
        'cantidad',
        'cantidad_disponible',
        'fecha_produccion',
        'fecha_vencimiento',
        'costo_unitario',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'cantidad_disponible' => 'decimal:3',
        'fecha_produccion' => 'date',
        'fecha_vencimiento' => 'date',
        'costo_unitario' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
