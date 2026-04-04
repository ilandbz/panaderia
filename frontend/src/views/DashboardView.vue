<template>
  <div class="dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold m-0 text-brown">Resumen del Negocio</h2>
      <div class="text-muted">{{ currentDate }}</div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-4">
      <div class="col-md-3" v-for="stat in stats" :key="stat.label">
        <div class="card border-0 rounded-4 p-3 h-100 shadow-sm">
          <div class="d-flex align-items-center">
            <div class="stat-icon p-3 rounded-circle me-3" :class="stat.bgClass">
              <i class="fas fa-lg" :class="stat.icon"></i>
            </div>
            <div>
              <div class="text-muted small fw-bold">{{ stat.label }}</div>
              <div class="h4 m-0 fw-bold">{{ stat.format }}{{ dashboardData.stats[stat.key]?.toLocaleString() || 0 }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">
      <div class="col-lg-8">
         <div class="card border-0 rounded-4 p-4 shadow-sm h-100">
           <h5 class="fw-bold mb-4">Ventas Recientes</h5>
           <div class="table-responsive">
             <table class="table table-hover align-middle">
               <thead class="table-light">
                 <tr>
                    <th>Nro. Venta</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                 </tr>
               </thead>
               <tbody>
                 <tr v-for="venta in dashboardData.recientes" :key="venta.id">
                   <td class="fw-bold">{{ venta.numero_venta }}</td>
                   <td>{{ venta.cliente?.nombre_completo || 'Público General' }}</td>
                   <td class="fw-bold">S/ {{ venta.total }}</td>
                   <td><span class="badge bg-success rounded-pill">{{ venta.estado }}</span></td>
                   <td class="text-muted small">{{ new Date(venta.created_at).toLocaleTimeString() }}</td>
                 </tr>
                 <tr v-if="dashboardData.recientes.length === 0">
                    <td colspan="5" class="text-center text-muted py-3">No hay ventas recientes</td>
                 </tr>
               </tbody>
             </table>
           </div>
         </div>
      </div>
      <div class="col-lg-4">
        <div class="card border-0 rounded-4 p-4 shadow-sm bg-primary text-white h-100">
          <h5 class="fw-bold mb-3">Accesos Rápidos</h5>
          <div class="d-grid gap-2">
            <router-link to="/pos" class="btn btn-light text-primary fw-bold py-3 rounded-3">
              <i class="fas fa-cash-register me-2"></i> Nueva Venta POS
            </router-link>
            <router-link to="/productos" class="btn btn-outline-light fw-bold py-3 rounded-3">
              <i class="fas fa-bread-slice me-2"></i> Gestionar Productos
            </router-link>
             <router-link to="/caja" class="btn btn-outline-light fw-bold py-3 rounded-3">
              <i class="fas fa-box me-2"></i> Apertura de Caja
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api.service';

const currentDate = new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
const loading = ref(true);
const dashboardData = ref({
  stats: {
    ventas_hoy: 0,
    caja_dia: 0,
    productos_bajos: 0,
    ventas_mes: 0
  },
  recientes: []
});

onMounted(async () => {
    try {
        const response = await api.get('/dashboard');
        dashboardData.value = response.data;
    } finally {
        loading.value = false;
    }
});

const stats = ref([
  { label: 'Ventas de Hoy', key: 'ventas_hoy', icon: 'fa-shopping-cart', bgClass: 'bg-primary-light text-primary', format: 'S/ ' },
  { label: 'Caja del Día', key: 'caja_dia', icon: 'fa-wallet', bgClass: 'bg-success-light text-success', format: 'S/ ' },
  { label: 'Productos Bajos', key: 'productos_bajos', icon: 'fa-exclamation-triangle', bgClass: 'bg-warning-light text-warning', format: '' },
  { label: 'Ventas del Mes', key: 'ventas_mes', icon: 'fa-chart-bar', bgClass: 'bg-info-light text-info', format: 'S/ ' },
]);
</script>

<style scoped>
.bg-primary-light { background-color: rgba(217, 119, 6, 0.1); }
.bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
.bg-warning-light { background-color: rgba(255, 193, 7, 0.1); }
.bg-info-light { background-color: rgba(13, 202, 240, 0.1); }
.text-brown { color: #451a03; }
</style>
