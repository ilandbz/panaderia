<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre_completo',
        'razon_social',
        'direccion',
        'telefono',
        'email',
        'descuento_especial',
        'activo',
    ];

    protected $casts = [
        'descuento_especial' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
