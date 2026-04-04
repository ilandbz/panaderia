<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { useVentaStore } from '@/stores/venta.store';
import { useModal } from '@/composables/useModal';
import Swal from 'sweetalert2';
import VentaResultadoModal from '@/components/pos/VentaResultadoModal.vue';
import ClienteQuickModal from '@/components/pos/ClienteQuickModal.vue';

const ventaStore = useVentaStore();
const ventas = ref([]);
const pagination = ref({});
const loading = ref(false);

const filters = ref({
  fecha_inicio: new Date().toLocaleDateString('en-CA'),
  fecha_fin: new Date().toLocaleDateString('en-CA'),
  search: '',
  page: 1
});
const selectedVenta = ref(null);
const { show: showTicket, hide: hideTicket } = useModal('ventaResultadoModal');
const { show: showClienteModal, hide: hideClienteModal } = useModal('clienteQuickModal');

const fetchVentas = async (page = 1) => {
  loading.value = true;
  filters.value.page = page;
  try {
    const response = await ventaStore.fetchVentas(filters.value);
    ventas.value = response.data;
    pagination.value = response;
    delete pagination.value.data;
  } catch (error) {
    Swal.fire('Error', 'No se pudieron cargar las ventas', 'error');
  } finally {
    loading.value = false;
  }
};

const verTicket = async (venta) => {
  selectedVenta.value = { ...venta };
  await nextTick();
  showTicket();
};

const onComprobanteGenerado = () => {
  fetchVentas(filters.value.page);
};

const onNuevoCliente = () => {
    showClienteModal();
};

const onClienteSaved = async (cliente) => {
    hideClienteModal();
    if (selectedVenta.value) {
        //try {
            Swal.showLoading();
            await ventaStore.actualizarVenta(selectedVenta.value.id, { cliente_id: cliente.id });
            selectedVenta.value.cliente = cliente;
            selectedVenta.value.cliente_id = cliente.id;
            Swal.fire('Éxito', 'Cliente asignado a la venta', 'success');
            fetchVentas(filters.value.page);
        //} catch (error) {
            //Swal.fire('Error', 'No se pudo asignar el cliente a la venta', 'error');
        //}
    }
};

const anularVenta = async (venta) => {
    const { value: motivo } = await Swal.fire({
        title: '¿Anular esta venta?',
        text: 'Se revertirá el stock y se generará una nota de crédito si corresponde.',
        input: 'text',
        inputLabel: 'Motivo de anulación',
        inputPlaceholder: 'Ej: Error en datos, Devolución total...',
        inputAttributes: {
            minlength: 5
        },
        showCancelButton: true,
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        inputValidator: (value) => {
            if (!value) return 'El motivo es obligatorio';
            if (value.length < 5) return 'El motivo debe tener al menos 5 caracteres';
        }
    });

    if (motivo) {
        try {
            Swal.fire({ title: 'Anulando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            await ventaStore.anularVenta(venta.id, motivo);
            Swal.fire('Anulada', 'La venta ha sido anulada con éxito', 'success');
            fetchVentas(filters.value.page);
        } catch (error) {
            Swal.fire('Error', error.response?.data?.message || 'No se pudo anular la venta', 'error');
        }
    }
};

onMounted(() => {
  fetchVentas();
});

watch(() => filters.value.search, () => {
  fetchVentas(1);
});
</script>

<template>
  <div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
      <div class="col">
        <h2 class="fw-bold text-dark mb-0">
          <i class="fas fa-history text-primary me-2"></i> Historial de Ventas
        </h2>
        <p class="text-muted mb-0">Consulta y reimprime tickets de ventas anteriores</p>
      </div>
      <div class="col-auto">
        <router-link to="/pos" class="btn btn-primary rounded-pill px-4 shadow-sm">
          <i class="fas fa-plus me-2"></i> Nueva Venta (POS)
        </router-link>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small fw-bold text-muted text-uppercase tracking-wider">Fecha Inicio</label>
            <input type="date" v-model="filters.fecha_inicio" class="form-control rounded-3 border-light bg-light" @change="fetchVentas(1)">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold text-muted text-uppercase tracking-wider">Fecha Fin</label>
            <input type="date" v-model="filters.fecha_fin" class="form-control rounded-3 border-light bg-light" @change="fetchVentas(1)">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold text-muted text-uppercase tracking-wider">Buscar por Número o Cliente</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-light text-muted"><i class="fas fa-search"></i></span>
              <input type="text" v-model="filters.search" class="form-control rounded-end-3 border-light bg-light" placeholder="Ej: V001 o Juan Perez...">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sales Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4 py-3 text-uppercase small fw-bold text-muted">Venta #</th>
              <th class="py-3 text-uppercase small fw-bold text-muted">Fecha / Hora</th>
              <th class="py-3 text-uppercase small fw-bold text-muted">Nro Documento</th>
              <th class="py-3 text-uppercase small fw-bold text-muted">Cliente</th>
              <th class="py-3 text-uppercase small fw-bold text-muted">Total</th>
              <th class="py-3 text-uppercase small fw-bold text-muted">Comprobante</th>
              <th class="pe-4 py-3 text-end text-uppercase small fw-bold text-muted">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </td>
            </tr>
            <tr v-else-if="ventas.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="fas fa-search fa-2x mb-3 opacity-25"></i>
                <p>No se encontraron ventas con los filtros aplicados</p>
              </td>
            </tr>
            <tr v-for="venta in ventas" :key="venta.id" class="transition-all" :class="{ 'opacity-50 grayscale bg-light': venta.estado === 'anulada' }">
              <td class="ps-4 fw-bold text-dark">
                {{ venta.numero_venta }}
                <div v-if="venta.estado === 'anulada'" class="extrasmall text-danger fw-bold text-uppercase"><i class="fas fa-ban me-1"></i>Anulada</div>
              </td>
              <td>
                <div class="small text-dark">{{ new Date(venta.created_at).toLocaleDateString() }}</div>
                <div class="extrasmall text-muted">{{ new Date(venta.created_at).toLocaleTimeString() }}</div>
              </td>
              <td>
                <span v-if="venta.cliente">{{ venta.cliente.numero_documento }}</span>
                <span v-else class="text-muted small">00000000</span>
              </td>
              <td>
                <span v-if="venta.cliente">{{ venta.cliente.nombre_completo }}</span>
                <span v-else class="text-muted small">Público General</span>
              </td>
              <td>
                <span class="fw-bold text-primary">S/ {{ venta.total }}</span>
              </td>
              <td>
                <span v-if="venta.comprobante" class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3">
                  {{ venta.comprobante.tipo.toUpperCase() }}: {{ venta.comprobante.serie }}-{{ venta.comprobante.correlativo }}
                </span>
                <span v-else class="badge rounded-pill bg-light text-muted border px-3">
                  Pendiente
                </span>
              </td>
              <td class="pe-4 text-end">
                <div class="d-flex justify-content-end gap-2">
                  <button class="btn btn-sm btn-outline-primary rounded-pill px-3 border-2 fw-bold" @click="verTicket(venta)">
                    <i class="fas fa-receipt me-1"></i> TICKET
                  </button>
                  <button 
                    v-if="venta.estado !== 'anulada'"
                    class="btn btn-sm btn-outline-danger rounded-pill px-3 border-2 fw-bold" 
                    @click="anularVenta(venta)"
                  >
                    <i class="fas fa-ban me-1"></i> ANULAR
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="card-footer bg-white border-0 p-4">
        <nav aria-label="Page navigation">
          <ul class="pagination pagination-sm justify-content-center mb-0 gap-2">
            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
              <a class="page-link rounded-circle border-0 bg-light text-dark shadow-sm" href="#" @click.prevent="fetchVentas(pagination.current_page - 1)">
                <i class="fas fa-chevron-left"></i>
              </a>
            </li>
            <li v-for="p in pagination.last_page" :key="p" class="page-item" :class="{ active: p === pagination.current_page }">
              <a class="page-link rounded-circle border-0 shadow-sm mx-1" 
                 :class="p === pagination.current_page ? 'btn-primary' : 'bg-light text-dark'" 
                 href="#" @click.prevent="fetchVentas(p)">{{ p }}</a>
            </li>
            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
              <a class="page-link rounded-circle border-0 bg-light text-dark shadow-sm" href="#" @click.prevent="fetchVentas(pagination.current_page + 1)">
                <i class="fas fa-chevron-right"></i>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <VentaResultadoModal 
      :venta="selectedVenta || {}" 
      :is-success="false" 
      @close="hideTicket"
      @comprobante-generado="onComprobanteGenerado"
      @nuevo-cliente="onNuevoCliente"
    />

    <ClienteQuickModal @saved="onClienteSaved" />
  </div>
</template>

<style scoped>
.extrasmall { font-size: 0.7rem; }
.tracking-wider { letter-spacing: 0.05em; }
.tracking-widest { letter-spacing: 0.1em; }
.transition-all { transition: all 0.2s ease-in-out; }

.table tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.02);
}

.page-link:hover {
  background-color: var(--bs-primary) !important;
  color: white !important;
}

.preview-container {
  background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
  background-size: 20px 20px;
}
</style>
