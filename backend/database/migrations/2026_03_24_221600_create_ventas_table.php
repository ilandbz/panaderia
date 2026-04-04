<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_venta', 20)->unique();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->foreignId('apertura_caja_id')->constrained('aperturas_caja');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->decimal('vuelto', 10, 2)->default(0);
            $table->enum('forma_pago', ['efectivo', 'yape', 'plin', 'tarjeta', 'transferencia', 'mixto'])->default('efectivo');
            $table->enum('estado', ['pendiente', 'completada', 'anulada'])->default('completada');
            $table->enum('tipo_comprobante', ['ticket', 'boleta', 'factura'])->default('ticket');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
