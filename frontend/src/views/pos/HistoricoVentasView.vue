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
const reenviando = ref(null); // ID de la venta que se está reenviando

const filters = ref({
  fecha_inicio: new Date().toLocaleDateString('en-CA'),
  fecha_fin: new Date().toLocaleDateString('en-CA'),
  search: '',
  page: 1
});
const selectedVenta = ref(null);
const { show: showTicket, hide: hideTicket } = useModal('ventaResultadoModal');
const { show: showClienteModal, hide: hideClienteModal } = useModal('clienteQuickModal');

const sunatConfig = ref(null);

const fetchSunatConfig = async () => {
    const response = await ventaStore.fetchSunatConfig();
    if (response && response.success) {
        sunatConfig.value = response.data;
    }
};

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
const truncate = (text, length = 60) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
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

const verMensajeSunat = (venta) => {
  const mensaje = venta.comprobante.respuesta_sunat?.description
    ?? venta.comprobante.respuesta_sunat?.error
    ?? 'Sin detalle disponible';

  const isPerfilError = mensaje.toLowerCase().includes('perfil') || mensaje.toLowerCase().includes('policy');

  let htmlConfig = '';
  if (sunatConfig.value) {
    htmlConfig = `
      <div class="mt-3 p-3 bg-light rounded-3 border">
        <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 10px;">Perfil de Envío Actual</div>
        <div class="d-flex justify-content-between mb-1">
          <span class="small text-muted">RUC:</span>
          <span class="small fw-bold">${sunatConfig.value.ruc}</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="small text-muted">Usuario:</span>
          <span class="small fw-bold">${sunatConfig.value.user}</span>
        </div>
        <div class="d-flex justify-content-between">
          <span class="small text-muted">Entorno:</span>
          <span class="badge ${sunatConfig.value.modo === 'produccion' ? 'bg-danger' : 'bg-info'} rounded-pill" style="font-size: 9px;">${sunatConfig.value.modo.toUpperCase()}</span>
        </div>
      </div>
    `;
  }

  let guide = '';
  if (isPerfilError) {
    guide = `
      <div class="mt-3 p-3 bg-warning-subtle text-dark rounded-3 border border-warning-subtle shadow-sm">
        <div class="fw-bold mb-1 small text-warning-emphasis"><i class="fas fa-lightbulb me-1"></i> Guía de Solución Sugerida:</div>
        <p class="mb-0 text-muted" style="font-size: 11px; line-height: 1.4;">
          Este error indica que el usuario SOL secundario no tiene permisos suficientes para envío electrónico. <br>
          Para solucionarlo: <br>
          1. Ingrese al portal <b>SUNAT SOL</b> con su cuenta principal. <br>
          2. Vaya a <b>Administración de Usuarios</b> y seleccione el usuario secundario. <br>
          3. En <b>Asignar Roles</b>, busque <b>Comprobantes de Pago</b> -> <b>SEE del Contribuyente</b>. <br>
          4. Marque las opciones de <b>Envío de Factura/Boleta Electrónica</b>.
        </p>
      </div>
    `;
  }

  Swal.fire({
    title: 'Detalle SUNAT',
    html: `
      <div style="text-align:left; font-size:13px;">
        <div class="text-secondary mb-1 small fw-bold">MENSAJE DE SUNAT:</div>
        <div class="p-3 bg-white border rounded-3 mb-1 text-dark">${mensaje}</div>
        ${htmlConfig}
        ${guide}
      </div>
    `,
    width: 600,
    confirmButtonColor: '#3b82f6',
    confirmButtonText: 'Entendido'
  });
};

const canReenviar = (venta) => {
  if (!venta.comprobante || venta.estado === 'anulada') return false;

  const estado = venta.comprobante.estado_sunat;
  if (estado === 'pendiente') return true;

  if (estado === 'rechazado') {
      const respuesta = venta.comprobante.respuesta_sunat;
      const mensaje = (
          (respuesta?.description || '') +
          (respuesta?.error || '') +
          (respuesta?.exception || '')
      ).toLowerCase();

      return mensaje.includes('perfil') || mensaje.includes('policy');
  }

  return false;
};

const reenviarComprobante = async (venta) => {
  const confirm = await Swal.fire({
    title: '¿Reenviar a SUNAT?',
    html: `Se intentará enviar el comprobante <strong>${venta.comprobante.numero_comprobante}</strong> a SUNAT nuevamente.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, reenviar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#f59e0b',
  });

  if (!confirm.isConfirmed) return;

  reenviando.value = venta.id;
  try {
    const result = await ventaStore.reenviarComprobante(venta.id);
    const nuevoEstado = result?.data?.comprobante?.estado_sunat;

    if (nuevoEstado === 'aceptado') {
      Swal.fire('¡Aceptado!', 'El comprobante fue aceptado por SUNAT.', 'success');
    } else {
      Swal.fire('Pendiente', 'No se pudo enviar aún. Intenta nuevamente cuando haya conexión.', 'warning');
    }
    fetchVentas(filters.value.page);
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'No se pudo reenviar el comprobante.', 'error');
  } finally {
    reenviando.value = null;
  }
};

onMounted(() => {
  fetchVentas();
  //fetchSunatConfig();
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

    <div v-if="sunatConfig" class="alert bg-white border-0 shadow-sm rounded-4 mb-4 p-0 overflow-hidden">
      <div class="bg-primary text-white px-4 py-2 d-flex align-items-center justify-content-between">
        <span class="small fw-bold"><i class="fas fa-satellite-dish me-2"></i> PANEL DE SEGUIMIENTO SUNAT (TEMPORAL)</span>
        <button type="button" class="btn-close btn-close-white" @click="sunatConfig = null" aria-label="Close"></button>
      </div>
      <div class="p-4">
        <div class="row align-items-center g-4">
          <div class="col-md-3">
            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">RUC Emisor</div>
            <div class="h5 mb-0 fw-bold text-dark">{{ sunatConfig.ruc }}</div>
          </div>
          <div class="col-md-3">
            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Usuario SOL</div>
            <div class="h5 mb-0 fw-bold text-dark">{{ sunatConfig.user }}</div>
          </div>
          <div class="col-md-3">
            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Entorno SUNAT</div>
            <div>
              <span class="badge rounded-pill px-3 py-2 fw-bold" :class="sunatConfig.modo === 'produccion' ? 'bg-danger' : 'bg-info'">
                <i class="fas" :class="sunatConfig.modo === 'produccion' ? 'fa-rocket' : 'fa-flask'"></i>
                {{ sunatConfig.modo.toUpperCase() }}
              </span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 10px;">Estado</div>
            <div class="d-flex align-items-center text-success fw-bold">
              <span class="rounded-circle bg-success me-2" style="width: 8px; height: 8px;"></span>
              Conectado a la API
            </div>
          </div>
        </div>
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
              <th class="py-3 text-uppercase small fw-bold text-muted text-center">Estado SUNAT</th>
              <th class="py-3 text-uppercase small fw-bold text-muted text-center">Mensaje SUNAT</th>
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
                <span v-if="venta.comprobante" class="badge rounded-pill bg-white text-dark border px-3 shadow-sm">
                  <span class="text-primary fw-bold">{{ venta.comprobante.tipo?.toUpperCase() }}</span>: {{ venta.comprobante.serie }}-{{ venta.comprobante.correlativo }}
                </span>
                <span v-else class="badge rounded-pill bg-light text-muted border px-3">
                  TICKET
                </span>
              </td>
              <td class="text-center">
                <template v-if="venta.comprobante">
                    <span v-if="venta.comprobante.estado_sunat === 'aceptado'"
                          class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3"
                          title="Comprobante aceptado por SUNAT">
                      <i class="fas fa-check-circle me-1"></i> ACEPTADO
                    </span>
                    <span v-else-if="venta.comprobante.estado_sunat === 'rechazado'"
                          class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 cursor-help"
                          :title="venta.comprobante.respuesta_sunat?.error || venta.comprobante.respuesta_sunat?.exception || 'Rechazado por SUNAT'">
                      <i class="fas fa-times-circle me-1"></i> RECHAZADO
                    </span>
                    <span v-else
                          class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-3"
                          title="Pendiente de envío a SUNAT">
                      <i class="fas fa-clock me-1"></i> PENDIENTE
                    </span>
                </template>
                <span v-else class="text-muted small">---</span>
              </td>
              <td class="text-center">
                <template v-if="venta.comprobante">
                  <div class="d-flex align-items-center justify-content-center gap-2">

                    <!-- Texto recortado -->
                    <span class="text-muted small text-truncate-sunat" :title="venta.comprobante.respuesta_sunat?.description ?? venta.comprobante.respuesta_sunat?.error">
                      {{ truncate(
                        venta.comprobante.respuesta_sunat?.description
                        ?? venta.comprobante.respuesta_sunat?.error
                      ) }}
                    </span>

                    <!-- Botón ver más -->
                    <button
                      v-if="(venta.comprobante.respuesta_sunat?.description ?? venta.comprobante.respuesta_sunat?.error)?.length > 60"
                      class="btn btn-sm btn-outline-secondary rounded-circle"
                      @click="verMensajeSunat(venta)"
                      title="Ver detalle completo"
                    >
                      <i class="fas fa-eye"></i>
                    </button>

                  </div>
                </template>

                <span v-else class="text-muted small">---</span>
              </td>
              <td class="pe-4 text-end">
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                  <button class="btn btn-sm btn-outline-primary rounded-pill px-3 border-2 fw-bold" @click="verTicket(venta)">
                    <i class="fas fa-receipt me-1"></i> TICKET
                  </button>

                  <!-- Botón REENVIAR: visible si está PENDIENTE o RECHAZADO por error de perfil/policy -->
                  <button
                    v-if="canReenviar(venta)"
                    class="btn btn-sm btn-outline-warning rounded-pill px-3 border-2 fw-bold"
                    @click="reenviarComprobante(venta)"
                    :disabled="reenviando === venta.id"
                    title="Reenviar comprobante a SUNAT"
                  >
                    <span v-if="reenviando === venta.id">
                      <i class="fas fa-spinner fa-spin me-1"></i> Enviando...
                    </span>
                    <span v-else>
                      <i class="fas fa-paper-plane me-1"></i> REENVIAR
                    </span>
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

.text-truncate-sunat {
  max-width: 250px;
  display: inline-block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
