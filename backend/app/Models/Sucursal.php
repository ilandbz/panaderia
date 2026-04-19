<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'ubigueo',
        'cod_establecimiento',
        'serie_boleta',
        'serie_factura',
        'serie_nota_credito',
        'telefono',
        'principal',
        'activo',
    ];

    protected $casts = [
        'principal' => 'boolean',
        'activo' => 'boolean',
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_sucursal')
                    ->withPivot('stock', 'stock_minimo')
                    ->withTimestamps();
    }
}
