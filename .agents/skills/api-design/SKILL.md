---
name: api-design
description: Estándares de diseño de API REST para Panadería Jara. Usa este skill cuando definas nuevos endpoints, necesites saber la estructura de respuesta estándar, pagination, filtros, ordenamiento, manejo de errores HTTP, o quieras verificar la convención de nomenclatura de rutas y parámetros del sistema.
---

# Diseño de API REST — Panadería Jara

## Convenciones Generales

- Base URL: `/api/v1/`
- Formato: JSON
- Autenticación: `Authorization: Bearer {token}`
- Verbos: GET (leer), POST (crear), PUT (reemplazar), PATCH (actualizar parcial), DELETE (eliminar)

---

## Estructura de Respuesta Estándar

### Éxito con datos
```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": { ... }
}
```

### Éxito con colección paginada
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "data": [ ... ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 73,
      "from": 1,
      "to": 15
    }
  }
}
```

### Error de validación (422)
```json
{
  "success": false,
  "message": "Datos inválidos",
  "errors": {
    "campo": ["El campo es requerido."]
  }
}
```

### Error general
```json
{
  "success": false,
  "message": "Descripción del error"
}
```

---

## Parámetros de Query estándar

| Parámetro | Tipo | Descripción |
|---|---|---|
| `search` | string | Búsqueda por nombre/texto |
| `page` | int | Página actual |
| `per_page` | int | Registros por página (max 100) |
| `order_by` | string | Campo para ordenar |
| `order_dir` | string | `asc` o `desc` |
| `desde` | date | Filtro fecha inicio (Y-m-d) |
| `hasta` | date | Filtro fecha fin (Y-m-d) |
| `activo` | bool | Filtrar por estado activo |

---

## Mapa Completo de Endpoints

### Auth
```
POST   /auth/login
POST   /auth/logout
GET    /auth/me
```

### Productos
```
GET    /productos             → listar (filtros: search, categoria_id, tipo, activo)
POST   /productos             → crear
GET    /productos/{id}        → detalle
PUT    /productos/{id}        → actualizar
DELETE /productos/{id}        → eliminar (soft)
GET    /productos/buscar      → búsqueda rápida para POS (?q=pan)
GET    /productos/{id}/kardex → historial de movimientos
```

### Categorías
```
GET    /categorias
POST   /categorias
PUT    /categorias/{id}
DELETE /categorias/{id}
```

### Inventario
```
GET    /inventario/movimientos              → historial
POST   /inventario/ingreso                 → registrar ingreso
POST   /inventario/egreso                  → registrar egreso manual
POST   /inventario/ajuste                  → ajustar stock
POST   /inventario/merma                   → registrar merma
POST   /inventario/produccion              → registrar producción
GET    /inventario/alertas/stock-bajo
GET    /inventario/alertas/por-vencer
```

### Ventas
```
GET    /ventas                → listar (filtros: desde, hasta, estado, usuario_id)
POST   /ventas                → crear venta
GET    /ventas/{id}           → detalle
POST   /ventas/{id}/anular    → anular venta
GET    /ventas/{id}/ticket    → HTML ticket para imprimir
```

### Caja
```
GET    /caja/activa           → caja abierta del usuario actual
POST   /caja/abrir            → abrir caja
POST   /caja/{id}/cerrar      → cerrar caja
GET    /caja/{id}/resumen     → resumen y movimientos
POST   /caja/movimiento       → gasto o ingreso manual
GET    /caja/historial        → historial de aperturas
```

### Comprobantes
```
POST   /comprobantes/emitir/{ventaId}  → emitir comprobante
GET    /comprobantes/{id}              → detalle
GET    /comprobantes/{id}/pdf          → PDF
POST   /comprobantes/{id}/reenviar     → reenviar a SUNAT
```

### Clientes
```
GET    /clientes
POST   /clientes
GET    /clientes/{id}
PUT    /clientes/{id}
GET    /clientes/buscar       → búsqueda rápida (?q=nombre o dni)
```

### Proveedores
```
GET    /proveedores
POST   /proveedores
GET    /proveedores/{id}
PUT    /proveedores/{id}
```

### Compras
```
GET    /compras
POST   /compras
GET    /compras/{id}
POST   /compras/{id}/recibir  → marcar como recibida (actualiza stock)
```

### Recetas / Producción
```
GET    /recetas
POST   /recetas
GET    /recetas/{id}
PUT    /recetas/{id}
```

### Reportes
```
GET    /dashboard
GET    /reportes/ventas        → ?desde&hasta&agrupar=dia|semana|mes
GET    /reportes/productos-top → ?desde&hasta&limit
GET    /reportes/utilidad      → ?desde&hasta
GET    /reportes/ventas-usuario
GET    /reportes/caja          → ?desde&hasta
GET    /reportes/mermas        → ?desde&hasta
```

### Usuarios
```
GET    /usuarios
POST   /usuarios
GET    /usuarios/{id}
PUT    /usuarios/{id}
DELETE /usuarios/{id}
GET    /roles
```

---

## Códigos HTTP usados

| Código | Uso |
|---|---|
| 200 | Éxito general |
| 201 | Recurso creado |
| 204 | Éxito sin contenido (DELETE) |
| 400 | Petición incorrecta |
| 401 | No autenticado |
| 403 | Sin permiso |
| 404 | No encontrado |
| 422 | Error de validación |
| 500 | Error del servidor |
