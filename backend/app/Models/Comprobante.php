<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comprobante extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'tipo',
        'serie',
        'correlativo',
        'numero_comprobante',
        'estado_sunat',
        'codigo_hash',
        'codigo_qr',
        'respuesta_sunat',
        'pdf_path',
        'xml_path',
    ];

    protected $casts = [
        'respuesta_sunat' => 'json',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }
}
