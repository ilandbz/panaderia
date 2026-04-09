<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los productos de panadería/pastelería en Perú están exonerados del IGV
     * según el Apéndice I del TUO de la Ley del IGV.
     * Esta migración corrige el valor por defecto a false para evitar que
     * nuevos productos sean creados con afecto_igv = true por error.
     */
    public function up(): void
    {
        // Cambiar el default de la columna a false (exonerado)
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('afecto_igv')->default(false)->change();
        });

        // Asegurar que todos los productos existentes estén como exonerados
        // (corrección de datos insertados incorrectamente)
        DB::table('productos')->update([
            'afecto_igv'     => false,
            'igv_porcentaje' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('afecto_igv')->default(true)->change();
        });
    }
};
