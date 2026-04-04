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
        Schema::create('receta_insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas');
            $table->foreignId('insumo_id')->constrained('productos'); // producto tipo insumo
            $table->decimal('cantidad', 12, 4);
            $table->string('unidad_medida', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receta_insumos');
    }
};
