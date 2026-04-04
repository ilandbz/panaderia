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
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apertura_caja_id')->constrained('aperturas_caja');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('venta_id')->nullable()->constrained('ventas');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('concepto', 200);
            $table->decimal('monto', 10, 2);
            $table->enum('forma_pago', ['efectivo', 'yape', 'plin', 'tarjeta', 'transferencia'])->default('efectivo');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
