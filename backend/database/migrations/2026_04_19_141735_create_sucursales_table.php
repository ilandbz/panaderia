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
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('direccion')->nullable();
            $table->string('ubigueo', 6)->nullable();
            $table->string('cod_establecimiento', 4)->default('0000'); // Código SUNAT (0000, 0001, etc)
            $table->string('serie_boleta', 4)->nullable(); // Ej: B001
            $table->string('serie_factura', 4)->nullable(); // Ej: F001
            $table->string('serie_nota_credito', 4)->nullable(); // BC01, FC01
            $table->string('telefono', 20)->nullable();
            $table->boolean('principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
