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
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas');
            $table->enum('tipo', ['boleta', 'factura', 'nota_credito']);
            $table->string('serie', 10);
            $table->integer('correlativo');
            $table->string('numero_comprobante', 20)->unique(); // B001-00000001
            $table->enum('estado_sunat', ['pendiente', 'enviado', 'aceptado', 'rechazado', 'no_aplica'])->default('pendiente');
            $table->string('codigo_hash', 100)->nullable();
            $table->string('codigo_qr')->nullable();
            $table->json('respuesta_sunat')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
