---
name: vue-frontend
description: Patrones, estructura y código base para el frontend Vue 3 con Composition API de Panadería Jara. Usa este skill siempre que vayas a crear componentes Vue, composables, stores de Pinia, vistas, servicios Axios, router o cualquier código del lado del cliente. También actívalo cuando se hable de manejo de estado, navegación, formularios reactivos, llamadas a la API o estructura de carpetas del frontend.
---

# Vue 3 Frontend — Panadería Jara

## Stack Frontend

- Vue 3 + Composition API (`<script setup>`)
- Pinia (estado global)
- Vue Router 4
- Axios
- Bootstrap 5
- FontAwesome 6
- SweetAlert2
- Vite

---

## Estructura de Directorios

```
src/
├── assets/
│   ├── css/
│   │   └── main.css
│   └── images/
├── components/
│   ├── common/        ← Componentes reutilizables globales
│   ├── layout/        ← Sidebar, Navbar, Footer
│   └── [modulo]/      ← Componentes de cada módulo
├── composables/       ← Lógica reutilizable (useX)
├── router/
│   └── index.js
├── services/          ← Llamadas Axios a la API
│   ├── api.js         ← Instancia base Axios
│   └── [modulo].service.js
├── stores/            ← Pinia stores
│   ├── auth.store.js
│   └── [modulo].store.js
├── views/             ← Páginas/rutas
│   ├── auth/
│   └── [modulo]/
└── main.js
```

---

## Instancia Axios Base

```javascript
// src/services/api.js
import axios from 'axios'
import { useAuthStore } from '@/stores/auth.store'
import router from '@/router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
  withCredentials: true,
})

api.interceptors.request.use((config) => {
  const auth = useAuthStore()
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

api.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore().logout()
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

export default api
```

---

## Servicio Genérico por Módulo

```javascript
// src/services/producto.service.js
import api from './api'

export const productoService = {
  listar:    (params = {}) => api.get('/productos', { params }),
  obtener:   (id)          => api.get(`/productos/${id}`),
  crear:     (data)        => api.post('/productos', data),
  actualizar:(id, data)    => api.put(`/productos/${id}`, data),
  eliminar:  (id)          => api.delete(`/productos/${id}`),
}
```

---

## Store Pinia (Patrón)

```javascript
// src/stores/producto.store.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { productoService } from '@/services/producto.service'

export const useProductoStore = defineStore('producto', () => {
  // State
  const productos  = ref([])
  const producto   = ref(null)
  const loading    = ref(false)
  const pagination = ref({})
  const filtros    = ref({ search: '', categoria_id: null, page: 1, per_page: 15 })

  // Getters
  const stockBajos = computed(() =>
    productos.value.filter(p => p.stock_bajo)
  )

  // Actions
  async function listar() {
    loading.value = true
    try {
      const { data } = await productoService.listar(filtros.value)
      productos.value  = data.data.data
      pagination.value = data.data.meta
    } finally {
      loading.value = false
    }
  }

  async function crear(payload) {
    const { data } = await productoService.crear(payload)
    productos.value.unshift(data.data)
    return data.data
  }

  async function actualizar(id, payload) {
    const { data } = await productoService.actualizar(id, payload)
    const idx = productos.value.findIndex(p => p.id === id)
    if (idx !== -1) productos.value[idx] = data.data
    return data.data
  }

  async function eliminar(id) {
    await productoService.eliminar(id)
    productos.value = productos.value.filter(p => p.id !== id)
  }

  return { productos, producto, loading, pagination, filtros, stockBajos, listar, crear, actualizar, eliminar }
})
```

---

## Componente Vue (Patrón Lista + CRUD)

```vue
<script setup>
import { onMounted, ref } from 'vue'
import { useProductoStore } from '@/stores/producto.store'
import Swal from 'sweetalert2'

const store = useProductoStore()
const showModal = ref(false)
const editId = ref(null)

onMounted(() => store.listar())

function abrirCrear() {
  editId.value = null
  showModal.value = true
}

function abrirEditar(id) {
  editId.value = id
  showModal.value = true
}

async function confirmarEliminar(id, nombre) {
  const result = await Swal.fire({
    title: `¿Eliminar "${nombre}"?`,
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#d33',
  })

  if (result.isConfirmed) {
    try {
      await store.eliminar(id)
      Swal.fire('Eliminado', 'El producto fue eliminado', 'success')
    } catch (e) {
      Swal.fire('Error', 'No se pudo eliminar', 'error')
    }
  }
}
</script>

<template>
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-box me-2 text-warning"></i>Productos</h5>
      <button class="btn btn-warning btn-sm" @click="abrirCrear">
        <i class="fas fa-plus me-1"></i>Nuevo
      </button>
    </div>

    <div class="card-body">
      <!-- Búsqueda -->
      <div class="row mb-3">
        <div class="col-md-4">
          <input
            v-model="store.filtros.search"
            @input="store.listar()"
            class="form-control"
            placeholder="Buscar producto..."
          />
        </div>
      </div>

      <!-- Tabla -->
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Stock</th>
              <th>Precio</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.loading">
              <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-warning"></div>
              </td>
            </tr>
            <tr v-for="p in store.productos" :key="p.id">
              <td><code>{{ p.codigo }}</code></td>
              <td>{{ p.nombre }}</td>
              <td>{{ p.categoria?.nombre }}</td>
              <td>
                <span :class="p.stock_bajo ? 'badge bg-danger' : 'badge bg-success'">
                  {{ p.stock }} {{ p.unidad_medida }}
                </span>
              </td>
              <td>S/. {{ p.precio_venta }}</td>
              <td>
                <span :class="p.activo ? 'badge bg-success' : 'badge bg-secondary'">
                  {{ p.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-outline-primary me-1" @click="abrirEditar(p.id)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="confirmarEliminar(p.id, p.nombre)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
```

---

## Gestión de Modales (Bootstrap 5)

Para asegurar una limpieza correcta de eventos y accesibilidad, siempre define una función `cerrarModal` y úsala para cerrar el modal programáticamente o mediante clics.

```javascript
// Patrón recomendado en componentes
let modalInstance = null;

onMounted(() => {
  const modalEl = document.getElementById('myModal');
  if (modalEl) modalInstance = new bootstrap.Modal(modalEl);
});

const cerrarModal = () => {
  document.activeElement?.blur(); // Quitar foco para evitar doble clic accidental
  modalInstance?.hide();
};

// Uso en el template:
// <button type="button" class="btn-close" @click="cerrarModal"></button>
// <button @click="cerrarModal">Cancelar</button>
```

---

## Composable Reutilizable (useForm)

```javascript
// src/composables/useForm.js
import { ref, reactive } from 'vue'
import Swal from 'sweetalert2'

export function useForm(initialData = {}) {
  const form    = reactive({ ...initialData })
  const errors  = ref({})
  const loading = ref(false)

  function resetForm() {
    Object.assign(form, initialData)
    errors.value = {}
  }

  function setErrors(errorsObj) {
    errors.value = errorsObj
  }

  async function submitForm(action, successMessage = 'Operación exitosa') {
    loading.value = true
    errors.value  = {}
    try {
      const result = await action()
      await Swal.fire({ icon: 'success', title: successMessage, timer: 1500, showConfirmButton: false })
      return result
    } catch (e) {
      if (e.response?.status === 422) {
        errors.value = e.response.data.errors || {}
      } else {
        Swal.fire('Error', e.response?.data?.message || 'Error inesperado', 'error')
      }
      throw e
    } finally {
      loading.value = false
    }
  }

  return { form, errors, loading, resetForm, setErrors, submitForm }
}
```

---

## Router (estructura)

```javascript
// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

const routes = [
  { path: '/login', component: () => import('@/views/auth/Login.vue'), meta: { guest: true } },
  {
    path: '/',
    component: () => import('@/views/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard',  component: () => import('@/views/Dashboard.vue') },
      { path: 'productos',  component: () => import('@/views/productos/Index.vue') },
      { path: 'inventario', component: () => import('@/views/inventario/Index.vue') },
      { path: 'ventas',     component: () => import('@/views/ventas/Index.vue') },
      { path: 'pos',        component: () => import('@/views/ventas/POS.vue') },
      { path: 'caja',       component: () => import('@/views/caja/Index.vue') },
      { path: 'reportes',   component: () => import('@/views/reportes/Index.vue'), meta: { role: 'administrador' } },
    ],
  },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) return next('/login')
  if (to.meta.guest && auth.isAuthenticated) return next('/')
  next()
})

export default router
```

---

## SweetAlert2 — Helpers

```javascript
// src/composables/useSwal.js
import Swal from 'sweetalert2'

export function useSwal() {
  const confirmar = (titulo, texto) => Swal.fire({
    title: titulo, text: texto, icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Confirmar', cancelButtonText: 'Cancelar',
    confirmButtonColor: '#c8971a',
  })

  const exito    = (msg) => Swal.fire({ icon: 'success', title: msg, timer: 1500, showConfirmButton: false })
  const error    = (msg) => Swal.fire({ icon: 'error',   title: 'Error', text: msg })
  const cargando = ()    => Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() })

  return { confirmar, exito, error, cargando }
}
```
