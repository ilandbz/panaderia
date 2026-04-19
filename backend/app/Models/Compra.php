<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_compra',
        'proveedor_id',
        'usuario_id',
        'sucursal_id',
        'tipo_comprobante',
        'numero_comprobante',
        'subtotal',
        'igv',
        'total',
        'estado',
        'fecha_compra',
        'observacion',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    protected $casts = [
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_compra' => 'date',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }
}
