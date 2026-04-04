<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecetaInsumo extends Model
{
    use HasFactory;

    protected $table = 'receta_insumos';

    protected $fillable = [
        'receta_id',
        'insumo_id',
        'cantidad',
        'unidad_medida',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
    ];

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'insumo_id');
    }
}
