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
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->string('numero_lote', 50)->nullable();
            $table->decimal('cantidad', 12, 3);
            $table->decimal('cantidad_disponible', 12, 3);
            $table->date('fecha_produccion')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
