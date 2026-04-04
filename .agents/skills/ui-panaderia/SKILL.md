---
name: ui-panaderia
description: Guía de diseño, paleta de colores, componentes UI y estilo visual para Panadería Jara. Usa este skill cuando trabajes en el diseño de interfaces, estilos CSS, layout del sistema, componentes Bootstrap personalizados, temas de color, sidebar, navbar, cards, botones, badges, o cualquier aspecto visual del frontend. El objetivo es una UI cálida, moderna y alineada a la identidad de una panadería/pastelería peruana.
---

# UI & Diseño Visual — Panadería Jara

## Identidad Visual

**Concepto:** Calidez artesanal + modernidad digital
**Palabra clave:** "Confianza, limpieza, calidez"

---

## Paleta de Colores

```css
:root {
  /* Primarios - Identidad Panadería */
  --jara-primary:       #C8971A;  /* Dorado trigo (botones principales) */
  --jara-primary-dark:  #A67C14;  /* Dorado oscuro (hover) */
  --jara-primary-light: #F5D878;  /* Dorado claro (fondos suaves) */

  /* Secundarios */
  --jara-secondary:     #6B3F1E;  /* Marrón cacao */
  --jara-cream:         #FDF6E3;  /* Crema fondo general */
  --jara-warm-white:    #FFFBF5;  /* Blanco cálido */

  /* Semánticos */
  --jara-success:       #2D8A4E;
  --jara-danger:        #C0392B;
  --jara-warning:       #E67E22;
  --jara-info:          #2980B9;

  /* Texto */
  --jara-text-dark:     #2C2C2C;
  --jara-text-muted:    #7A7A7A;

  /* Sidebar */
  --sidebar-bg:         #2C1A0E;  /* Marrón oscuro chocolate */
  --sidebar-text:       #F5E6C8;
  --sidebar-active:     #C8971A;
  --sidebar-hover:      rgba(200, 151, 26, 0.15);
  --sidebar-width:      260px;
}
```

---

## CSS Base (main.css)

```css
/* ============================================
   PANADERÍA JARA — Estilos Globales
   ============================================ */

body {
  background-color: var(--jara-cream);
  color: var(--jara-text-dark);
  font-family: 'Inter', -apple-system, sans-serif;
}

/* Bootstrap overrides */
.btn-primary {
  background-color: var(--jara-primary);
  border-color: var(--jara-primary);
  color: #fff;
}
.btn-primary:hover {
  background-color: var(--jara-primary-dark);
  border-color: var(--jara-primary-dark);
}
.btn-warning {
  background-color: var(--jara-primary);
  border-color: var(--jara-primary);
  color: #fff;
}
.btn-warning:hover {
  background-color: var(--jara-primary-dark);
  color: #fff;
}

/* Cards */
.card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  background: #fff;
}
.card-header {
  background: #fff;
  border-bottom: 1px solid #f0e8d5;
  border-radius: 12px 12px 0 0 !important;
  padding: 1rem 1.25rem;
}

/* Tables */
.table thead th {
  background-color: var(--jara-primary-light);
  color: var(--jara-secondary);
  font-weight: 600;
  border-bottom: 2px solid var(--jara-primary);
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.table-hover tbody tr:hover {
  background-color: #FEF9EE;
}

/* Badges */
.badge-jara {
  background-color: var(--jara-primary);
  color: #fff;
}

/* Form controls */
.form-control:focus, .form-select:focus {
  border-color: var(--jara-primary);
  box-shadow: 0 0 0 0.2rem rgba(200, 151, 26, 0.25);
}
.form-label {
  font-weight: 500;
  color: var(--jara-secondary);
  font-size: 0.9rem;
}

/* KPI Cards */
.kpi-card {
  border-radius: 12px;
  padding: 1.25rem;
  border-left: 4px solid var(--jara-primary);
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.kpi-card .kpi-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--jara-secondary);
}
.kpi-card .kpi-label {
  font-size: 0.8rem;
  color: var(--jara-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Alertas de stock */
.alert-stock-bajo { border-left: 4px solid #C0392B; }
.alert-por-vencer { border-left: 4px solid #E67E22; }

/* Loading spinner */
.spinner-jara {
  color: var(--jara-primary);
}
```

---

## Sidebar Component

```vue
<!-- src/components/layout/AppSidebar.vue -->
<script setup>
import { useAuthStore } from '@/stores/auth.store'
import { useRouter } from 'vue-router'

const auth   = useAuthStore()
const router = useRouter()

const menu = [
  { label: 'Dashboard',   icon: 'fas fa-chart-line',    to: '/dashboard',  permiso: null },
  { label: 'Punto de Venta', icon: 'fas fa-cash-register', to: '/pos',     permiso: 'crear ventas' },
  { label: 'Ventas',      icon: 'fas fa-receipt',        to: '/ventas',     permiso: 'ver ventas' },
  { label: 'Inventario',  icon: 'fas fa-boxes',          to: '/inventario', permiso: 'ver inventario' },
  { label: 'Productos',   icon: 'fas fa-bread-slice',    to: '/productos',  permiso: 'ver productos' },
  { label: 'Caja',        icon: 'fas fa-cash-register',  to: '/caja',       permiso: 'abrir caja' },
  { label: 'Compras',     icon: 'fas fa-truck',          to: '/compras',    permiso: 'ver compras' },
  { label: 'Clientes',    icon: 'fas fa-users',          to: '/clientes',   permiso: 'ver clientes' },
  { label: 'Reportes',    icon: 'fas fa-chart-bar',      to: '/reportes',   permiso: 'ver reportes' },
  { label: 'Usuarios',    icon: 'fas fa-user-cog',       to: '/usuarios',   permiso: 'ver usuarios' },
  { label: 'Configuración', icon: 'fas fa-cog',          to: '/config',     permiso: 'ver configuracion' },
]

const menuFiltrado = menu.filter(item =>
  !item.permiso || auth.hasPermission(item.permiso)
)
</script>

<template>
  <nav class="sidebar">
    <!-- Logo / Brand -->
    <div class="sidebar-brand">
      <div class="brand-icon">🥐</div>
      <div class="brand-text">
        <div class="brand-name">Panadería Jara</div>
        <div class="brand-sub">Sistema de Gestión</div>
      </div>
    </div>

    <!-- User info -->
    <div class="sidebar-user">
      <div class="user-avatar">
        <i class="fas fa-user-circle fa-2x"></i>
      </div>
      <div class="user-info">
        <div class="user-name">{{ auth.user?.nombre }}</div>
        <div class="user-role">{{ auth.roles?.[0] }}</div>
      </div>
    </div>

    <!-- Nav items -->
    <ul class="sidebar-nav">
      <li v-for="item in menuFiltrado" :key="item.to">
        <RouterLink :to="item.to" class="nav-link" active-class="active">
          <i :class="item.icon"></i>
          <span>{{ item.label }}</span>
        </RouterLink>
      </li>
    </ul>

    <!-- Logout -->
    <div class="sidebar-footer">
      <button @click="auth.logout()" class="btn-logout">
        <i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión
      </button>
    </div>
  </nav>
</template>

<style scoped>
.sidebar {
  width: var(--sidebar-width);
  background: var(--sidebar-bg);
  height: 100vh;
  position: fixed;
  left: 0; top: 0;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  z-index: 1000;
}
.sidebar-brand {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 1.5rem 1.25rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.brand-icon  { font-size: 2rem; }
.brand-name  { color: var(--jara-primary); font-weight: 700; font-size: 1rem; }
.brand-sub   { color: var(--sidebar-text); font-size: 0.7rem; opacity: 0.6; }
.sidebar-user {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.user-name { color: var(--sidebar-text); font-weight: 600; font-size: 0.85rem; }
.user-role { color: var(--jara-primary); font-size: 0.75rem; text-transform: capitalize; }
.sidebar-nav { list-style: none; padding: 0.75rem 0; flex: 1; margin: 0; }
.sidebar-nav .nav-link {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.65rem 1.25rem;
  color: var(--sidebar-text);
  text-decoration: none;
  font-size: 0.875rem;
  border-radius: 0;
  transition: all 0.2s;
  opacity: 0.8;
}
.sidebar-nav .nav-link i { width: 18px; text-align: center; }
.sidebar-nav .nav-link:hover { background: var(--sidebar-hover); opacity: 1; }
.sidebar-nav .nav-link.active {
  background: var(--sidebar-hover);
  color: var(--jara-primary);
  border-right: 3px solid var(--jara-primary);
  opacity: 1;
  font-weight: 600;
}
.sidebar-footer { padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); }
.btn-logout {
  width: 100%; background: none; border: 1px solid rgba(255,255,255,0.2);
  color: var(--sidebar-text); padding: 0.5rem; border-radius: 8px;
  font-size: 0.85rem; cursor: pointer; transition: all 0.2s;
}
.btn-logout:hover { background: rgba(192,57,43,0.3); border-color: #C0392B; }
</style>
```

---

## Componente KpiCard

```vue
<!-- src/components/common/KpiCard.vue -->
<script setup>
defineProps({
  titulo: String,
  valor:  [String, Number],
  icono:  String,
  color:  { type: String, default: 'warning' },
  subtexto: String,
})
</script>

<template>
  <div class="col-sm-6 col-xl-3">
    <div class="card kpi-card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div :class="`kpi-icon text-${color}`">
          <i :class="[icono, 'fa-2x']"></i>
        </div>
        <div>
          <div class="kpi-value">{{ valor ?? '—' }}</div>
          <div class="kpi-label">{{ titulo }}</div>
          <small v-if="subtexto" class="text-muted">{{ subtexto }}</small>
        </div>
      </div>
    </div>
  </div>
</template>
```

---

## AppLayout

```vue
<!-- src/views/layout/AppLayout.vue -->
<template>
  <div class="app-wrapper">
    <AppSidebar />
    <div class="app-main" :style="{ marginLeft: 'var(--sidebar-width)' }">
      <AppNavbar />
      <main class="app-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>
```

---

## SweetAlert2 — Tema personalizado

```javascript
// src/plugins/swal.js
import Swal from 'sweetalert2'

export const MySwal = Swal.mixin({
  confirmButtonColor: '#C8971A',
  cancelButtonColor:  '#6c757d',
  customClass: {
    confirmButton: 'btn btn-warning px-4',
    cancelButton:  'btn btn-secondary px-4 ms-2',
  },
  buttonsStyling: false,
})
```

---

## Iconos recomendados (FontAwesome)

| Módulo | Ícono |
|---|---|
| Panadería | `fa-bread-slice`, `fa-croissant` |
| POS / Caja | `fa-cash-register` |
| Inventario | `fa-boxes`, `fa-box` |
| Ventas | `fa-receipt`, `fa-file-invoice` |
| Productos | `fa-tags`, `fa-list` |
| Usuarios | `fa-user-cog`, `fa-users` |
| Reportes | `fa-chart-bar`, `fa-chart-line` |
| Alertas stock | `fa-exclamation-triangle` |
| Vencimientos | `fa-calendar-times` |
| Mermas | `fa-trash-alt` |
| Proveedor | `fa-truck` |
| Cliente | `fa-user` |
