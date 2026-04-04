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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique()->nullable();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->enum('tipo', ['reventa', 'elaborado', 'insumo'])->default('reventa');
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->decimal('costo', 10, 2)->nullable();
            $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('stock_minimo', 12, 3)->default(0);
            $table->string('unidad_medida', 10)->default('UND'); // UND, KG, LT, PQT, CAJ, POR
            $table->date('fecha_vencimiento')->nullable();
            $table->string('imagen_path')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('afecto_igv')->default(true);
            $table->decimal('igv_porcentaje', 5, 2)->default(18.00);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
