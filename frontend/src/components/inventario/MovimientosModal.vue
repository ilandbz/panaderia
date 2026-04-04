<script setup>
import { ref, onMounted, watch } from 'vue';
import { useProductStore } from '@/stores/product.store';

const props = defineProps({
  producto: {
    type: Object,
    required: true
  }
});

const productStore = useProductStore();
const movimientos = ref([]);
const loading = ref(false);
const pagination = ref({
  current_page: 1,
  last_page: 1
});

const fetchMovimientos = async (page = 1) => {
  if (!props.producto?.id) return;
  loading.value = true;
  try {
    const response = await productStore.fetchKardex(props.producto.id, page);
    // Ajustar según la estructura de respuesta (success:true, data: {...})
    const dataNode = response;
    movimientos.value = dataNode.data || [];
    pagination.value = {
      current_page: dataNode.current_page,
      last_page: dataNode.last_page
    };
  } catch (error) {
    console.error('Error al cargar movimientos:', error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getBadgeClass = (tipo) => {
  switch (tipo) {
    case 'ingreso': return 'bg-success-subtle text-success border-success';
    case 'egreso': return 'bg-danger-subtle text-danger border-danger';
    case 'ajuste': return 'bg-warning-subtle text-warning border-warning';
    case 'produccion': return 'bg-primary-subtle text-primary border-primary';
    case 'merma': return 'bg-secondary-subtle text-secondary border-secondary';
    default: return 'bg-light text-dark';
  }
};

onMounted(() => {
  fetchMovimientos();
});

watch(() => props.producto?.id, () => {
  fetchMovimientos();
});
</script>

<template>
  <div class="kardex-container">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">Cargando historial...</p>
    </div>

    <div v-else>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0">Historial de: {{ producto.nombre }}</h6>
        <span class="badge bg-light text-dark border">
          Stock Actual: <strong>{{ producto.stock }} {{ producto.unidad_medida }}</strong>
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light small">
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th class="text-end">Cant.</th>
              <th class="text-end">Anterior</th>
              <th class="text-end">Nuevo</th>
              <th>Motivo / Obs.</th>
            </tr>
          </thead>
          <tbody class="small">
            <tr v-for="mov in movimientos" :key="mov.id">
              <td class="text-nowrap">{{ formatDate(mov.created_at) }}</td>
              <td>
                <span class="badge rounded-pill border px-2 py-1" :class="getBadgeClass(mov.tipo)">
                  {{ mov.tipo?.toUpperCase() }}
                </span>
              </td>
              <td class="text-end fw-bold" :class="mov.tipo === 'ingreso' || (mov.tipo === 'produccion' && mov.cantidad > 0) ? 'text-success' : 'text-danger'">
                {{ mov.tipo === 'ingreso' || (mov.tipo === 'produccion' && mov.cantidad > 0) ? '+' : '-' }}{{ mov.cantidad }}
              </td>
              <td class="text-end text-muted">{{ mov.stock_anterior }}</td>
              <td class="text-end fw-bold">{{ mov.stock_nuevo }}</td>
              <td>
                <div class="fw-bold text-dark">{{ mov.motivo?.replace('_', ' ') }}</div>
                <div class="text-muted extra-small">{{ mov.observacion || '—' }}</div>
              </td>
            </tr>
            <tr v-if="movimientos.length === 0">
              <td colspan="6" class="text-center py-4 text-muted">
                No hay movimientos registrados para este producto.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Simple Pagination -->
      <nav v-if="pagination.last_page > 1" class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
          <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
            <button class="page-link" @click="fetchMovimientos(pagination.current_page - 1)">Anterior</button>
          </li>
          <li class="page-item disabled">
            <span class="page-link">{{ pagination.current_page }} de {{ pagination.last_page }}</span>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
            <button class="page-link" @click="fetchMovimientos(pagination.current_page + 1)">Siguiente</button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.extra-small {
  font-size: 0.7rem;
}
.kardex-container {
  min-height: 300px;
}
.badge {
  font-weight: 500;
  letter-spacing: 0.02em;
}
</style>
