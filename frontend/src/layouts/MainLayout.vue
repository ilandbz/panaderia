<template>
  <div class="main-layout d-flex">
    <!-- Sidebar -->
    <aside class="sidebar bg-sidebar text-white shadow no-print" :class="{ 'collapsed': isCollapsed }">
      <div class="sidebar-header p-3 d-flex align-items-center justify-content-between">
        <h1 v-if="!isCollapsed" class="h5 m-0 fw-bold">Panadería Jara</h1>
        <button class="btn btn-link text-white p-0" @click="isCollapsed = !isCollapsed">
          <i class="fas" :class="isCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
        </button>
      </div>

      <nav class="sidebar-nav flex-grow-1 py-3">
        <router-link to="/" class="nav-item">
          <i class="fas fa-chart-line"></i>
          <span v-if="!isCollapsed">Dashboard</span>
        </router-link>
        <router-link to="/pos" class="nav-item">
          <i class="fas fa-cash-register"></i>
          <span v-if="!isCollapsed">Venta POS</span>
        </router-link>
        <router-link to="/pos/historial" class="nav-item">
          <i class="fas fa-history"></i>
          <span v-if="!isCollapsed">Historial Ventas</span>
        </router-link>
        <router-link to="/productos" class="nav-item">
          <i class="fas fa-bread-slice"></i>
          <span v-if="!isCollapsed">Inventario</span>
        </router-link>
        <router-link to="/caja" class="nav-item">
          <i class="fas fa-box"></i>
          <span v-if="!isCollapsed">Caja</span>
        </router-link>
        <router-link to="/produccion" class="nav-item">
          <i class="fas fa-mortar-pestle"></i>
          <span v-if="!isCollapsed">Producción</span>
        </router-link>
        <router-link to="/proveedores" class="nav-item">
          <i class="fas fa-truck"></i>
          <span v-if="!isCollapsed">Proveedores</span>
        </router-link>
        <router-link to="/compras" class="nav-item">
          <i class="fas fa-shopping-bag"></i>
          <span v-if="!isCollapsed">Compras</span>
        </router-link>

        <!-- Configuración -->
        <div v-if="!isCollapsed" class="nav-section-title px-4 pt-3 pb-1 text-uppercase small text-white fw-bold">
          Configuración
        </div>
        <router-link v-can="'ver usuarios'" to="/usuarios" class="nav-item">
          <i class="fas fa-users-cog"></i>
          <span v-if="!isCollapsed">Usuarios</span>
        </router-link>
        <router-link v-can="'ver clientes'" to="/clientes" class="nav-item">
          <i class="fas fa-id-card"></i>
          <span v-if="!isCollapsed">Clientes</span>
        </router-link>
        <router-link v-can="'ver configuracion'" to="/roles" class="nav-item">
          <i class="fas fa-user-shield"></i>
          <span v-if="!isCollapsed">Roles y Permisos</span>
        </router-link>
      </nav>

      <div class="sidebar-footer p-3 border-top border-secondary">
        <button class="btn btn-outline-light w-100 btn-sm" @click="handleLogout">
          <i class="fas fa-sign-out-alt"></i>
          <span v-if="!isCollapsed" class="ms-2">Cerrar Sesión</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow-1 d-flex flex-column vh-100 overflow-hidden">
      <!-- Navbar -->
      <header class="navbar bg-white shadow-sm px-4 py-2 border-bottom no-print">
        <div class="d-flex align-items-center">
          <span class="text-muted fw-bold">Sistema de Gestión</span>
        </div>
        <div class="user-profile d-flex align-items-center">
          <div class="text-end me-3">
            <div class="fw-bold small">{{ user?.nombre }} {{ user?.apellido }}</div>
            <div class="text-muted small" style="font-size: 0.75rem;">{{ user?.roles?.[0]?.name || 'Cajero' }}</div>
          </div>
          <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
               style="width:35px; height:35px;">
            {{ user?.nombre?.[0] }}
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <section class="content-area flex-grow-1 p-4 overflow-auto bg-light">
        <router-view></router-view>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth.store';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const isCollapsed = ref(false);

const user = computed(() => authStore.user);

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<style scoped>
.sidebar {
  width: 250px;
  height: 100vh;
  background-color: #451a03;
  transition: width 0.3s;
  display: flex;
  flex-direction: column;
}

.sidebar.collapsed {
  width: 70px;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 12px 24px;
  color: #fef3c7;
  text-decoration: none;
  transition: background 0.2s;
}

.nav-item i {
  width: 20px;
  margin-right: 15px;
}

.collapsed .nav-item i {
  margin-right: 0;
}

.nav-item:hover, .nav-item.router-link-exact-active {
  background-color: #d97706;
  color: white;
}

.content-area {
  scroll-behavior: smooth;
}
</style>
