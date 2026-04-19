<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'usuario_id',
        'sucursal_id',
        'venta_id',
        'compra_id',
        'tipo',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'motivo',
        'observacion',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    protected $casts = [
        'cantidad' => 'decimal:3',
        'stock_anterior' => 'decimal:3',
        'stock_nuevo' => 'decimal:3',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }
}
