---
name: database-modeling
description: Modelo de datos completo, relaciones, migrations y estructura de base de datos para Panadería Jara. Usa este skill SIEMPRE antes de crear o modificar cualquier migration, al definir relaciones entre modelos, al diseñar nuevas tablas, o cuando necesites entender cómo están relacionadas las entidades del sistema (productos, ventas, caja, inventario, usuarios, comprobantes, etc.).
---

# Modelo de Datos — Panadería Jara

## Diagrama de Entidades Principales

```
usuarios ──── roles (Spatie)
    │
    ├─── ventas ──── detalle_ventas ──── productos
    │        │
    │        └───── comprobantes
    │
    ├─── aperturas_caja
    │        └───── movimientos_caja
    │
    └─── movimientos_inventario ──── productos

productos ──── categorias
    │
    ├─── lotes
    ├─── recetas ──── receta_insumos ──── productos (insumos)
    └─── movimientos_inventario

proveedores ──── compras ──── detalle_compras ──── productos
clientes    ──── ventas
```

---

## Tablas y Migrations

### 1. usuarios

```php
Schema::create('usuarios', function (Blueprint $table) {
    $table->id();
    $table->string('nombre');
    $table->string('apellido');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('dni', 15)->nullable();
    $table->string('telefono', 20)->nullable();
    $table->boolean('activo')->default(true);
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```

### 2. categorias

```php
Schema::create('categorias', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 100);
    $table->string('descripcion')->nullable();
    $table->string('icono', 50)->nullable();    // clase FontAwesome
    $table->string('color', 20)->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### 3. productos ⭐ (tabla central)

```php
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
```

### 4. lotes (control de vencimientos por lote)

```php
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
```

### 5. proveedores

```php
Schema::create('proveedores', function (Blueprint $table) {
    $table->id();
    $table->string('razon_social', 200);
    $table->string('ruc', 11)->unique()->nullable();
    $table->string('telefono', 20)->nullable();
    $table->string('email')->nullable();
    $table->string('direccion')->nullable();
    $table->string('contacto_nombre', 150)->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

### 6. clientes

```php
Schema::create('clientes', function (Blueprint $table) {
    $table->id();
    $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'PASAPORTE'])->default('DNI');
    $table->string('numero_documento', 20)->nullable();
    $table->string('nombre_completo', 200);
    $table->string('razon_social', 200)->nullable();
    $table->string('direccion')->nullable();
    $table->string('telefono', 20)->nullable();
    $table->string('email')->nullable();
    $table->decimal('descuento_especial', 5, 2)->default(0);
    $table->boolean('activo')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

### 7. aperturas_caja

```php
Schema::create('aperturas_caja', function (Blueprint $table) {
    $table->id();
    $table->foreignId('usuario_id')->constrained('users');
    $table->foreignId('cerrado_por')->nullable()->constrained('users');
    $table->decimal('monto_apertura', 10, 2)->default(0);
    $table->decimal('monto_cierre', 10, 2)->nullable();
    $table->decimal('monto_sistema', 10, 2)->nullable();
    $table->decimal('diferencia', 10, 2)->nullable();
    $table->text('observaciones')->nullable();
    $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
    $table->timestamp('fecha_apertura')->useCurrent();
    $table->timestamp('fecha_cierre')->nullable();
    $table->timestamps();
});
```

### 8. movimientos_caja

```php
Schema::create('movimientos_caja', function (Blueprint $table) {
    $table->id();
    $table->foreignId('apertura_caja_id')->constrained('aperturas_caja');
    $table->foreignId('usuario_id')->constrained('users');
    $table->foreignId('venta_id')->nullable()->constrained('ventas');
    $table->enum('tipo', ['ingreso', 'egreso']);
    $table->string('concepto', 200);
    $table->decimal('monto', 10, 2);
    $table->enum('forma_pago', ['efectivo', 'yape', 'plin', 'tarjeta', 'transferencia'])->default('efectivo');
    $table->text('observacion')->nullable();
    $table->timestamps();
});
```

### 9. ventas

```php
Schema::create('ventas', function (Blueprint $table) {
    $table->id();
    $table->string('numero_venta', 20)->unique();
    $table->foreignId('usuario_id')->constrained('users');
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
```

### 10. detalle_ventas

```php
Schema::create('detalle_ventas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('venta_id')->constrained('ventas');
    $table->foreignId('producto_id')->constrained('productos');
    $table->decimal('cantidad', 12, 3);
    $table->decimal('precio_unitario', 10, 2);
    $table->decimal('descuento', 10, 2)->default(0);
    $table->decimal('subtotal', 10, 2);
    $table->timestamps();
});
```

### 11. comprobantes

```php
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
```

### 12. movimientos_inventario

```php
Schema::create('movimientos_inventario', function (Blueprint $table) {
    $table->id();
    $table->foreignId('producto_id')->constrained('productos');
    $table->foreignId('usuario_id')->constrained('users');
    $table->foreignId('venta_id')->nullable()->constrained('ventas');
    $table->foreignId('compra_id')->nullable()->constrained('compras');
    $table->enum('tipo', ['ingreso', 'egreso', 'ajuste', 'merma', 'produccion']);
    $table->decimal('cantidad', 12, 3);
    $table->decimal('stock_anterior', 12, 3);
    $table->decimal('stock_nuevo', 12, 3);
    $table->string('motivo', 200)->nullable();
    $table->text('observacion')->nullable();
    $table->timestamps();
});
```

### 13. recetas (producción)

```php
Schema::create('recetas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('producto_id')->constrained('productos'); // producto elaborado
    $table->string('nombre', 150);
    $table->decimal('rendimiento', 12, 3)->default(1); // cuántas unidades produce
    $table->text('instrucciones')->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
});

Schema::create('receta_insumos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('receta_id')->constrained('recetas');
    $table->foreignId('insumo_id')->constrained('productos'); // producto tipo insumo
    $table->decimal('cantidad', 12, 4);
    $table->string('unidad_medida', 10);
    $table->timestamps();
});
```

### 14. compras

```php
Schema::create('compras', function (Blueprint $table) {
    $table->id();
    $table->string('numero_compra', 20)->unique();
    $table->foreignId('proveedor_id')->constrained('proveedores');
    $table->foreignId('usuario_id')->constrained('users');
    $table->string('numero_documento', 50)->nullable(); // factura proveedor
    $table->decimal('subtotal', 10, 2);
    $table->decimal('igv', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->enum('estado', ['pendiente', 'recibida', 'anulada'])->default('recibida');
    $table->date('fecha_compra');
    $table->text('observacion')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('detalle_compras', function (Blueprint $table) {
    $table->id();
    $table->foreignId('compra_id')->constrained('compras');
    $table->foreignId('producto_id')->constrained('productos');
    $table->decimal('cantidad', 12, 3);
    $table->decimal('costo_unitario', 10, 2);
    $table->decimal('subtotal', 10, 2);
    $table->date('fecha_vencimiento')->nullable();
    $table->timestamps();
});
```

---

## Relaciones clave entre Modelos

| Desde | Relación | Hacia |
|---|---|---|
| Venta | hasMany | DetalleVenta |
| Venta | hasOne | Comprobante |
| Venta | belongsTo | Cliente |
| Venta | belongsTo | AperturaCaja |
| DetalleVenta | belongsTo | Producto |
| Producto | belongsTo | Categoria |
| Producto | hasMany | MovimientoInventario |
| Producto | hasMany | Lote |
| Producto | hasOne | Receta (si es elaborado) |
| AperturaCaja | hasMany | MovimientoCaja |
| AperturaCaja | hasMany | Venta |
| Compra | hasMany | DetalleCompra |
| Compra | belongsTo | Proveedor |

---

## Seeders Recomendados

```php
// Orden de ejecución en DatabaseSeeder
$this->call([
    RolesPermisosSeeder::class,
    UsuariosSeeder::class,
    CategoriasSeeder::class,
    ProductosSeeder::class,
]);
```

### Categorías iniciales sugeridas:
- Panes (elaborado)
- Pasteles y Tortas (elaborado)
- Kekes y Bizcochos (elaborado)
- Postres y Dulces (elaborado)
- Abarrotes (reventa)
- Bebidas (reventa)
- Insumos de Panadería (insumo)
- Insumos de Pastelería (insumo)
