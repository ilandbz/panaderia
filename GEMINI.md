# GEMINI.md — Panadería/Pastelería Jara · Sistema Web Integral

> Este archivo es el punto de entrada principal para cualquier tarea de desarrollo en este proyecto.
> Lee este archivo antes de escribir cualquier línea de código o tomar decisiones de arquitectura.

---

## 🏪 Descripción del Proyecto

Sistema web integral para **Panadería/Pastelería Jara**, un negocio peruano dedicado a la venta de:
- Productos de panadería y pastelería (elaboración propia)
- Productos de abarrotes (reventa)
- Productos preparados y listos para la venta

**Objetivo:** Centralizar ventas, inventario, caja, facturación electrónica, producción y gestión del negocio en una sola plataforma moderna, rápida e intuitiva.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Vue 3 · Composition API · `<script setup>` |
| UI Components | Bootstrap 5 · FontAwesome 6 · SweetAlert2 |
| Base de Datos | MySQL 8+ |
| Autenticación | Laravel Sanctum (SPA) |
| API | RESTful JSON API |
| Facturación | Integración SUNAT (Perú) vía API externa |
| Estado global | Pinia |
| HTTP client | Axios |
| Build | Vite |

---

## 📁 Estructura del Proyecto

```
panaderia-jara/
├── backend/                  # Laravel 13
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   ├── Services/          # Lógica de negocio
│   │   ├── Repositories/
│   │   └── Enums/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/api.php
│
└── frontend/                  # Vue 3
    ├── src/
    │   ├── views/
    │   ├── components/
    │   ├── composables/
    │   ├── stores/            # Pinia
    │   ├── services/          # Axios API calls
    │   └── router/
    └── public/
```

---

## 🗂️ Módulos del Sistema

### Módulos Principales (MVP)
| # | Módulo | Skill asociado |
|---|---|---|
| 1 | **Autenticación & Roles** | `auth-roles/SKILL.md` |
| 2 | **Productos & Inventario** | `inventario/SKILL.md` |
| 3 | **Punto de Venta (POS)** | `pos-ventas/SKILL.md` |
| 4 | **Caja** | `caja/SKILL.md` |
| 5 | **Facturación Electrónica** | `facturacion/SKILL.md` |
| 6 | **Reportes & Dashboard** | `reportes/SKILL.md` |

### Módulos Secundarios (Fase 2)
| # | Módulo | Skill asociado |
|---|---|---|
| 7 | **Compras & Proveedores** | Ver `database-modeling/SKILL.md` |
| 8 | **Producción & Insumos** | Ver `inventario/SKILL.md` |
| 9 | **Clientes** | Ver `pos-ventas/SKILL.md` |
| 10 | **Promociones** | Ver `pos-ventas/SKILL.md` |

---

## 🎨 Identidad Visual

- **Paleta principal:** Tonos cálidos (mostaza, crema, marrón, naranja suave)
- **Tipografía:** Limpia y legible, sin serifa para UI
- **Estilo general:** Moderno + calidez de panadería artesanal
- **Recursos visuales:** Iconos de pan, pasteles, trigo en zonas decorativas
- **Referencia completa:** Ver `ui-panaderia/SKILL.md`

---

## 🧱 Tipos de Productos (Fundamental)

El sistema distingue **3 categorías base** de productos:

```
A. PRODUCTOS DE REVENTA
   └─ Se compran y venden directamente (abarrotes, insumos envasados)

B. PRODUCTOS ELABORADOS
   └─ Producidos internamente (pan, tortas, kekes, pasteles)
   └─ Consumen insumos mediante recetas

C. INSUMOS
   └─ Ingredientes para producción (harina, azúcar, manteca, huevos)
   └─ No se venden directamente
```

---

## 🔐 Roles del Sistema

| Rol | Permisos clave |
|---|---|
| `administrador` | Acceso total |
| `supervisor` | Todo menos config del sistema |
| `cajero` | POS, caja, comprobantes |
| `vendedor` | POS, consultas de stock |
| `almacenero` | Inventario, ingresos, mermas |

---

## 📐 Convenciones de Código

### Backend (Laravel)
- **Controladores:** Solo orquestan, lógica en `Services/`
- **Naming:** `PascalCase` para clases, `snake_case` para BD, `camelCase` en JSON response
- **API Response estándar:**
```json
{
  "success": true,
  "data": {},
  "message": "Operación exitosa",
  "meta": { "pagination": {} }
}
```
- **Validaciones:** Siempre en `FormRequest`
- **Soft deletes:** En todos los modelos principales
- **Timestamps:** `created_at`, `updated_at`, `deleted_at`

### Frontend (Vue 3)
- Siempre usar `<script setup>` con Composition API
- **Naming:** componentes en `PascalCase`, composables `use` + PascalCase
- Estado global solo en Pinia stores
- Servicios API en `/services/*.service.js`
- Nunca hacer fetch directo en componentes: usar composables o stores
- **Cierre de Modales:** Siempre implementar `cerrarModal()` que incluya `document.activeElement?.blur()` e `hide()` de la instancia Bootstrap.

---

## ⚙️ Variables de Entorno clave

```env
# Backend
APP_NAME="Panaderia Jara"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173
DB_DATABASE=panaderia_jara
SANCTUM_STATEFUL_DOMAINS=localhost:5173

# Facturación SUNAT
SUNAT_API_URL=
SUNAT_RUC=
SUNAT_CLIENT_ID=
SUNAT_CLIENT_SECRET=
```

---

## 📋 Skills Disponibles

Consulta el skill específico antes de generar código para ese módulo:

```
skills/
├── GEMINI.md                   ← Este archivo (leer siempre primero)
├── laravel-backend/SKILL.md    ← Patrones base Laravel 13
├── vue-frontend/SKILL.md       ← Patrones base Vue 3
├── database-modeling/SKILL.md  ← Modelo de datos completo
├── api-design/SKILL.md         ← Diseño de endpoints REST
├── auth-roles/SKILL.md         ← Autenticación y permisos
├── pos-ventas/SKILL.md         ← Punto de venta y ventas
├── inventario/SKILL.md         ← Stock, insumos, producción
├── caja/SKILL.md               ← Caja, apertura, cierre, cuadre
├── facturacion/SKILL.md        ← SUNAT, boletas, facturas
├── reportes/SKILL.md           ← Dashboard y reportes
└── ui-panaderia/SKILL.md       ← Diseño y componentes UI
```

---

## 🚀 Orden de Desarrollo Recomendado

```
Fase 1 — Base
  1. Configuración Laravel + Vue + Sanctum
  2. Modelo de base de datos completo (migrations + seeders)
  3. Auth + Roles + Permisos (Spatie)

Fase 2 — Núcleo
  4. CRUD de Productos + Categorías
  5. Inventario básico (stock, ingresos, salidas)
  6. Módulo de Caja (apertura/cierre)
  7. POS - Punto de Venta

Fase 3 — Comercial
  8. Facturación electrónica SUNAT
  9. Comprobantes (boleta/factura/ticket)
  10. Clientes y descuentos

Fase 4 — Avanzado
  11. Producción + Recetas + Insumos
  12. Compras + Proveedores
  13. Reportes y Dashboard gerencial
  14. Mermas y control de pérdidas
```

---

## ⚠️ Reglas Críticas

1. **Nunca saltarse los Skills** — Siempre lee el skill del módulo antes de codificar
2. **Modelo de datos primero** — Antes de crear controllers, verifica `database-modeling/SKILL.md`
3. **API consistente** — Todos los endpoints siguen el estándar de `api-design/SKILL.md`
4. **Permisos en todo** — Todo endpoint debe verificar permisos según `auth-roles/SKILL.md`
5. **Validar siempre** — Usar FormRequest en cada endpoint que recibe datos
6. **Transacciones DB** — Operaciones múltiples de BD siempre dentro de `DB::transaction()`
7. **Stock nunca negativo** — Validar disponibilidad antes de cualquier descuento de inventario
