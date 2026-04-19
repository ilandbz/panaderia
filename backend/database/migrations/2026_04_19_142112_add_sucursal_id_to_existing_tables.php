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
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id')->constrained('sucursales');
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id')->constrained('sucursales');
        });
        Schema::table('aperturas_caja', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id')->constrained('sucursales');
        });
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id')->constrained('sucursales');
        });
        Schema::table('compras', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id')->constrained('sucursales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) { $table->dropConstrainedForeignId('sucursal_id'); });
        Schema::table('ventas', function (Blueprint $table) { $table->dropConstrainedForeignId('sucursal_id'); });
        Schema::table('aperturas_caja', function (Blueprint $table) { $table->dropConstrainedForeignId('sucursal_id'); });
        Schema::table('movimientos_inventario', function (Blueprint $table) { $table->dropConstrainedForeignId('sucursal_id'); });
        Schema::table('compras', function (Blueprint $table) { $table->dropConstrainedForeignId('sucursal_id'); });
    }
};
