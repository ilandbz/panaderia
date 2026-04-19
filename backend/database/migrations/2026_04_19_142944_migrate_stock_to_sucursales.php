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
        // 1. Crear sede central inicial
        $sucursalId = DB::table('sucursales')->insertGetId([
            'nombre'              => 'CASA CENTRAL',
            'direccion'           => 'Jr. Principal s/n',
            'cod_establecimiento' => '0000',
            'serie_boleta'        => 'B001',
            'serie_factura'       => 'F001',
            'serie_nota_credito'  => 'BC01',
            'principal'           => true,
            'activo'              => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // 2. Asociar registros huérfanos a la sede central
        DB::table('usuarios')->whereNull('sucursal_id')->update(['sucursal_id' => $sucursalId]);
        DB::table('ventas')->whereNull('sucursal_id')->update(['sucursal_id' => $sucursalId]);
        DB::table('aperturas_caja')->whereNull('sucursal_id')->update(['sucursal_id' => $sucursalId]);
        DB::table('movimientos_inventario')->whereNull('sucursal_id')->update(['sucursal_id' => $sucursalId]);
        DB::table('compras')->whereNull('sucursal_id')->update(['sucursal_id' => $sucursalId]);

        // 3. Migrar stock actual a la tabla pivot
        $productos = DB::table('productos')->get();
        foreach ($productos as $producto) {
            DB::table('producto_sucursal')->insert([
                'producto_id'  => $producto->id,
                'sucursal_id'  => $sucursalId,
                'stock'        => $producto->stock,
                'stock_minimo' => $producto->stock_minimo,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // 4. Eliminar columnas obsoletas en productos
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['stock', 'stock_minimo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nota: El rollback es complejo debido a la pérdida de las columnas en 'productos'
        // pero se puede intentar recrear si es necesario.
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('stock', 12, 3)->default(0)->after('costo');
            $table->decimal('stock_minimo', 12, 3)->default(0)->after('stock');
        });

        // Devolver stocks (solo de la sede principal para simplificar el revert)
        $stocks = DB::table('producto_sucursal')->where('sucursal_id', 1)->get();
        foreach ($stocks as $s) {
            DB::table('productos')->where('id', $s->producto_id)->update([
                'stock' => $s->stock,
                'stock_minimo' => $s->stock_minimo
            ]);
        }
    }
};
