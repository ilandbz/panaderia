<script setup>
import { onMounted, ref } from 'vue';
import { useCompraStore } from '@/stores/compra.store';
import Swal from 'sweetalert2';
import { useModal } from '@/composables/useModal';

const compraStore = useCompraStore();
const selectedCompra = ref(null);
const loadingDetail = ref(false);

const { show: showDetail, hide: hideDetail } = useModal('detailModal', {
  onClose: () => {
    selectedCompra.value = null;
  }
});

onMounted(async () => {
  await compraStore.fetchCompras();
});

const verDetalle = async (id) => {
  try {
    loadingDetail.value = true;
    const response = await compraStore.fetchCompra(id);
    selectedCompra.value = response;
    showDetail();
  } catch (error) {
    Swal.fire('Error', 'No se pudo cargar el detalle de la compra', 'error');
  } finally {
    loadingDetail.value = false;
  }
};

const anularCompra = async (id) => {
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: "Esta acción anulará la compra y descontará el stock de los productos. No se puede deshacer.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, anular compra',
    cancelButtonText: 'Cancelar'
  });

  if (result.isConfirmed) {
    try {
      await compraStore.anularCompra(id);
      Swal.fire('Anulada', 'La compra ha sido anulada correctamente.', 'success');
      hideDetail();
    } catch (error) {
      const msg = error.response?.data?.message || 'Error al anular la compra';
      Swal.fire('Error', msg, 'error');
    }
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};
</script>

<template>
  <div class="compras-view animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold m-0 text-brown"><i class="fas fa-shopping-bag me-2"></i> Registro de Compras</h3>
      <router-link to="/compras/nueva" class="btn btn-primary rounded-pill px-4 shadow-sm">
        <i class="fas fa-plus me-2"></i> Nueva Compra
      </router-link>
    </div>

    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">Fecha</th>
              <th>Nº Compra</th>
              <th>Comprobante</th>
              <th>Proveedor</th>
              <th>Total</th>
              <th class="text-center">Estado</th>
              <th class="pe-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="compra in compraStore.compras" :key="compra.id" :class="{'opacity-50': compra.estado === 'anulado'}">
              <td class="ps-4">{{ formatDate(compra.fecha_compra) }}</td>
              <td class="fw-bold text-muted">{{ compra.numero_compra }}</td>
              <td>
                <span class="text-uppercase small fw-bold">{{ compra.tipo_comprobante }}</span>: 
                <span class="font-monospace text-muted small">{{ compra.numero_comprobante }}</span>
              </td>
              <td class="fw-bold">{{ compra.proveedor?.razon_social }}</td>
              <td class="fw-bold text-primary">{{ formatCurrency(compra.total) }}</td>
              <td class="text-center">
                <span class="badge rounded-pill" :class="compra.estado === 'anulado' ? 'bg-danger' : 'bg-success'">
                  {{ compra.estado?.toUpperCase() }}
                </span>
              </td>
              <td class="pe-4 text-center">
                <button 
                  class="btn btn-sm btn-outline-primary border-0 rounded-circle" 
                  title="Ver Detalle"
                  @click="verDetalle(compra.id)"
                  :disabled="loadingDetail"
                >
                  <i class="fas" :class="loadingDetail ? 'fa-spinner fa-spin' : 'fa-eye'"></i>
                </button>
              </td>
            </tr>
            <tr v-if="compraStore.compras.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                <p>No se encontraron registros de compras</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Detalle -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 bg-primary text-white p-4">
            <h5 class="modal-title fw-bold">
              <i class="fas fa-file-invoice me-2"></i> Detalle de Compra: {{ selectedCompra?.numero_compra }}
            </h5>
            <button type="button" @click="hideDetail" class="btn-close btn-close-white"></button>
          </div>
          <div class="modal-body p-4" v-if="selectedCompra">
            <div class="row mb-4">
              <div class="col-md-6">
                <p class="mb-1 text-muted small text-uppercase fw-bold">Proveedor</p>
                <p class="fw-bold mb-0 text-dark">{{ selectedCompra.proveedor?.razon_social }}</p>
                <p class="text-muted small mb-0">RUC: {{ selectedCompra.proveedor?.ruc }}</p>
              </div>
              <div class="col-md-3">
                <p class="mb-1 text-muted small text-uppercase fw-bold">Fecha</p>
                <p class="fw-bold mb-0">{{ formatDate(selectedCompra.fecha_compra) }}</p>
              </div>
              <div class="col-md-3">
                <p class="mb-1 text-muted small text-uppercase fw-bold">Estado</p>
                <span class="badge rounded-pill" :class="selectedCompra.estado === 'anulado' ? 'bg-danger' : 'bg-success'">
                  {{ selectedCompra.estado?.toUpperCase() }}
                </span>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-borderless align-middle">
                <thead class="bg-light small text-uppercase">
                  <tr>
                    <th>Producto</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-end">P. Compra</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in selectedCompra.detalles" :key="item.id" class="border-bottom">
                    <td>{{ item.producto?.nombre }}</td>
                    <td class="text-center fw-bold">{{ item.cantidad }}</td>
                    <td class="text-end">{{ formatCurrency(item.precio_compra) }}</td>
                    <td class="text-end fw-bold">{{ formatCurrency(item.subtotal) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="3" class="text-end py-2">Subtotal:</td>
                    <td class="text-end py-2 fw-bold">{{ formatCurrency(selectedCompra.subtotal) }}</td>
                  </tr>
                  <tr>
                    <td colspan="3" class="text-end py-2">IGV (18%):</td>
                    <td class="text-end py-2 fw-bold">{{ formatCurrency(selectedCompra.igv) }}</td>
                  </tr>
                  <tr class="fs-5">
                    <td colspan="3" class="text-end py-2 fw-bold text-dark">TOTAL:</td>
                    <td class="text-end py-2 fw-bold text-primary">{{ formatCurrency(selectedCompra.total) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between">
            <button 
              v-if="selectedCompra?.estado !== 'anulado'"
              class="btn btn-outline-danger rounded-pill px-4" 
              @click="anularCompra(selectedCompra.id)"
            >
              <i class="fas fa-ban me-2"></i> Anular Compra
            </button>
            <div v-else class="text-danger fw-bold italic small">
              * Esta compra ya fue anulada
            </div>
            <button type="button" class="btn btn-light rounded-pill px-4" @click="hideDetail">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.text-brown { color: #451a03; }
.btn-primary { background-color: #d97706; border-color: #d97706; }
.btn-primary:hover { background-color: #b45309; border-color: #b45309; }
.text-primary { color: #d97706 !important; }
.bg-primary { background-color: #d97706 !important; }
</style>
