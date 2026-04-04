# 🍞 Panadería & Pastelería Jara — Sistema Web Integral

Sistema integral de gestión para **Panadería Jara**, diseñado para centralizar ventas, inventario, facturación electrónica y gestión de caja en una plataforma moderna y eficiente.

## 🚀 Módulos Principales

### 🛒 Punto de Venta (POS)
- Venta rápida con interfaz táctil e intuitiva.
- Gestión de **Tickets** de preventa.
- Conversión dinámica de Tickets a **Boletas** o **Facturas** electrónicas.
- Buscador y registro rápido de clientes.

### 🧾 Facturación Electrónica (SUNAT)
- **Validaciones Automáticas**:
  - Boletas mayores a S/ 700 requieren identificación (DNI/CE).
  - Facturas requieren RUC y Razón Social obligatorios.
- **Notas de Crédito**: Anulación total de comprobantes con generación automática de Nota de Crédito (BC01 / FC01).
- Generación de PDF en formatos 80mm y 58mm.

### 📦 Inventario & Stock
- Control de stock en tiempo real.
- **Kardex**: Historial detallado de movimientos por cada producto (ingresos, egresos, mermas, anulaciones).
- Categorización de productos (Elaborados, Reventa, Insumos).

### 💰 Gestión de Caja
- Apertura y cierre de caja por turno/usuario.
- Registro automático de ingresos por ventas.
- Registro de egresos (compras, gastos, reintegros por anulación).
- Arqueo y cuadre de caja detallado.

## 🛠️ Stack Tecnológico

- **Backend**: Laravel 13 (PHP 8.3+)
- **Frontend**: Vue 3 (Composition API) + Pinia + Vite
- **UI**: Bootstrap 5 + FontAwesome 6 + SweetAlert2
- **Base de Datos**: MySQL 8+
- **Autenticación**: Laravel Sanctum (SPA)
- **Reportes**: ECharts para visualización de datos

## 📂 Estructura del Proyecto

```bash
panaderia-jara/
├── backend/            # API REST (Laravel)
│   ├── app/Models      # Entidades (Venta, Producto, Comprobante, etc.)
│   ├── app/Services    # Lógica de Negocio (VentaService, FacturacionService)
│   └── routes/api.php  # Endpoints del sistema
├── frontend/           # Aplicación SPA (Vue 3)
│   ├── src/views       # Pantallas (POS, Inventario, Historial)
│   ├── src/components  # Componentes reutilizables
│   └── src/stores      # Estado global (Pinia)
└── .agents/            # Instrucciones y Skills para mantenimiento IA
```

## ⚙️ Instalación

1. **Backend**:
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve
   ```

2. **Frontend**:
   ```bash
   cd frontend
   npm install
   npm run dev
   ```

---
*Desarrollado para Panadería Jara · 2026*
