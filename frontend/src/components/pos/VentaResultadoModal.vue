<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useVentaStore } from '@/stores/venta.store';
import { useClienteStore } from '@/stores/cliente.store';
import Swal from 'sweetalert2';

const props = defineProps({
  venta: {
    type: Object,
    required: true
  },
  isSuccess: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'comprobanteGenerado', 'nuevoCliente']);

const ventaStore = useVentaStore();
const clienteStore = useClienteStore();
const formatoImpresion = ref('80mm');
const pdfUrl = ref('');
const loadingPdf = ref(false);
const processingComprobante = ref(false);

const searchCliente = ref('');
const clientesEncontrados = ref([]);
const mostrarResultadosCliente = ref(false);

const tipoComprobante = computed(() => {
  return props.venta?.tipo_comprobante || 'ticket';
});

const esTicket = computed(() => tipoComprobante.value === 'ticket');
const esBoleta = computed(() => tipoComprobante.value === 'boleta');
const esFactura = computed(() => tipoComprobante.value === 'factura');
const estadoSunat = computed(() => props.venta?.comprobante?.estado_sunat || 'pendiente');

const puedeGenerarBoleta = computed(() => {
  if (processingComprobante.value) return false;
  if (esFactura.value) return false; // Ya es factura, bloquear boleta
  // Se permite si es ticket o si es boleta pero fue rechazada (reintento)
  return esTicket.value || (esBoleta.value && estadoSunat.value !== 'aceptado');
});

const puedeGenerarFactura = computed(() => {
  if (processingComprobante.value) return false;
  if (esBoleta.value) return false; // Ya es boleta, bloquear factura
  // Se permite si es ticket o si es factura pero fue rechazada (reintento)
  return esTicket.value || (esFactura.value && estadoSunat.value !== 'aceptado');
});

const cargarPdf = async () => {
  if (!props.venta?.id) return;
  loadingPdf.value = true;
  try {
    pdfUrl.value = await ventaStore.getTicketUrl(
      props.venta.id,
      formatoImpresion.value,
      props.venta.tipo_comprobante
    );
  } catch (error) {
    console.error('Error al cargar PDF:', error);
  } finally {
    loadingPdf.value = false;
  }
};

const generarComprobante = async (tipo) => {
  if (!props.venta?.id) return;
  
  // VALIDACIONES SUNAT
  const cli = props.venta.cliente;
  if (tipo === 'factura') {
    if (!cli || cli.tipo_documento !== 'RUC') {
      Swal.fire({
        icon: 'warning',
        title: 'SUNAT: Factura Requerida',
        text: 'La factura requiere un cliente con RUC y Razón Social.',
        confirmButtonText: 'Entendido'
      });
      return;
    }
  }

  if (tipo === 'boleta' && props.venta.total > 700) {
    if (!cli || cli.numero_documento === '00000000') {
        Swal.fire({
            icon: 'warning',
            title: 'SUNAT: Boleta > S/ 700',
            text: 'Las boletas superiores a S/ 700 requieren identificación del cliente (DNI/RUC/CE).',
            confirmButtonText: 'Entendido'
        });
        return;
    }
  }

  const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 5000,
    timerProgressBar: true,
  });

  try {
    processingComprobante.value = true;
    Swal.showLoading();
    const result = await ventaStore.generarComprobante(props.venta.id, tipo, formatoImpresion.value);
    
    // Sincronizar el objeto local con la respuesta del servidor para actualizar la UI (botones, estado SUNAT)
    const ventaActualizada = result.data?.data || result.data || result;
    if (ventaActualizada && ventaActualizada.id) {
        props.venta.tipo_comprobante = ventaActualizada.tipo_comprobante;
        props.venta.comprobante = ventaActualizada.comprobante;
    }
    
    toast.fire({
      icon: 'success',
      title: result.data?.message || `Se generó la ${tipo} correctamente`
    });
    
    emit('comprobanteGenerado', tipo);
    await cargarPdf();
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || `No se pudo generar la ${tipo}`, 'error');
  } finally {
    processingComprobante.value = false;
  }
};

const buscarClientes = async () => {
    if (searchCliente.value.length < 3) {
        clientesEncontrados.value = [];
        return;
    }
    clientesEncontrados.value = await clienteStore.fetchClientes(searchCliente.value);
    mostrarResultadosCliente.value = true;
};

const asignarCliente = async (cliente) => {
  try {
    await ventaStore.actualizarVenta(props.venta.id, {
      cliente_id: cliente.id
    });

    // actualizar UI local
    props.venta.cliente = cliente;
    props.venta.cliente_id = cliente.id;

    // cerrar y limpiar buscador
    mostrarResultadosCliente.value = false;
    searchCliente.value = '';
    clientesEncontrados.value = [];

    Swal.fire({
      icon: 'success',
      title: 'Cliente asignado',
      toast: true,
      position: 'top-end',
      timer: 3000,
      showConfirmButton: false
    });

    await cargarPdf();
  } catch (error) {
    console.error('ERROR REAL:', error);
    console.error('response:', error.response);

    Swal.fire('Error', 'No se pudo asignar el cliente', 'error');
  }
};

const abrirNuevoCliente = () => {
    emit('nuevoCliente');
};

watch(() => props.venta?.id, (newId) => {
  if (newId) cargarPdf();
}, { immediate: true });

watch(formatoImpresion, () => {
    cargarPdf();
});

const cerrar = () => {
  emit('close');
};
</script>

<template>
  <div class="modal fade no-print" id="ventaResultadoModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content border-0 shadow-lg rounded-5 overflow-hidden">
        <div class="modal-header border-0 py-4 text-center d-block position-relative" 
             :class="isSuccess ? 'bg-success text-white' : 'bg-primary text-white'">
          <h4 class="modal-title fw-bold text-uppercase tracking-widest">
            <i :class="isSuccess ? 'fas fa-check-circle animate__animated animate__bounceIn' : 'fas fa-receipt'" class="me-2"></i>
            {{ isSuccess ? '¡Venta Exitosa!' : 'Reimpresión de Ticket' }}
          </h4>
          <button v-if="!isSuccess" type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-4" @click="cerrar"></button>
        </div>
        
        <div class="modal-body p-4">
          <div class="row g-4">
            <!-- Columna Detalles y Botones -->
            <div class="col-md-5">
              <div class="p-3 rounded-4 border bg-light text-center mb-4">
                <div class="row g-2">
                  <div class="col-6 border-end">
                    <p class="text-muted extrasmall mb-0 text-uppercase tracking-widest">
                        {{ isSuccess ? 'Total' : 'Venta #' }}
                    </p>
                    <div class="h4 fw-bold text-dark m-0 font-monospace">
                        {{ isSuccess ? 'S/ ' + (venta?.total || '0.00') : (venta?.numero_venta || '---') }}
                    </div>
                  </div>
                  <div class="col-6 text-center">
                    <p class="text-muted extrasmall mb-0 text-uppercase tracking-widest">
                        {{ isSuccess ? 'Vuelto' : 'Total Pago' }}
                    </p>
                    <div class="h4 fw-bold m-0 font-monospace" :class="isSuccess ? 'text-primary' : 'text-dark'">
                        S/ {{ isSuccess ? (venta?.vuelto || '0.00') : (venta?.total || '0.00') }}
                    </div>
                  </div>
                </div>
                <div class="mt-3 pt-3 border-top text-start">
                    <div v-if="esTicket || estadoSunat === 'rechazado'" class="client-assign-tool mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                             <label class="extrasmall fw-bold text-primary text-uppercase tracking-wider">¿Vincular a otro cliente?</label>
                             <button class="btn btn-xs btn-link p-0 text-decoration-none fw-bold small" @click="abrirNuevoCliente">+ NUEVO</button>
                        </div>
                        <div class="position-relative">
                           <div class="input-group input-group-sm rounded-pill shadow-sm overflow-hidden border">
                             <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                             <input type="text" v-model="searchCliente" @input="buscarClientes" class="form-control border-0" placeholder="Doc o Nombre...">
                           </div>
                           <div v-if="clientesEncontrados.length > 0 && mostrarResultadosCliente" class="client-results-box position-absolute w-100 bg-white shadow-lg rounded-3 mt-1 overflow-hidden" style="z-index: 1060; max-height: 150px; overflow-y: auto;">
                             <div v-for="c in clientesEncontrados" :key="c.id" class="p-2 border-bottom cursor-pointer hover-bg-light" @click="asignarCliente(c)">
                               <div class="small fw-bold">{{ c.nombre_completo || c.razon_social }}</div>
                               <div class="extrasmall text-muted">{{ c.tipo_documento }}: {{ c.numero_documento }}</div>
                             </div>
                           </div>
                        </div>
                    </div>

                    <div class="current-client bg-white p-3 rounded-4 border border-dashed text-center">
                        <p class="text-muted extrasmall mb-2 text-uppercase tracking-widest">Cliente Actual</p>
                        <div class="small fw-bold text-dark">{{ venta?.cliente?.nombre_completo || venta?.cliente?.razon_social || 'Público General' }}</div>
                        <div class="extrasmall text-muted">{{ venta?.cliente?.tipo_documento || 'DNI' }}: {{ venta?.cliente?.numero_documento || '00000000' }}</div>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-between extrasmall">
                        <span class="text-muted text-uppercase">Fecha:</span>
                        <span class="fw-bold">{{ venta?.created_at ? new Date(venta.created_at).toLocaleString() : '---' }}</span>
                    </div>

                    <!-- SUNAT Status Section -->
                    <div v-if="venta?.comprobante" class="mt-3 p-2 rounded-3 text-center" 
                         :class="{
                             'bg-success-subtle text-success border border-success-subtle': estadoSunat === 'aceptado',
                             'bg-danger-subtle text-danger border border-danger-subtle': estadoSunat === 'rechazado',
                             'bg-warning-subtle text-warning border border-warning-subtle': estadoSunat === 'pendiente'
                         }">
                        <div class="extrasmall fw-bold text-uppercase tracking-widest mb-1">Estado SUNAT</div>
                        <div class="small fw-bold">
                            <i v-if="estadoSunat === 'aceptado'" class="fas fa-check-circle me-1"></i>
                            <i v-else-if="estadoSunat === 'rechazado'" class="fas fa-times-circle me-1"></i>
                            <i v-else class="fas fa-clock me-1"></i>
                            {{ estadoSunat.toUpperCase() }}
                        </div>
                        <div v-if="estadoSunat === 'rechazado'" class="extrasmall mt-1 text-dark text-start">
                            <strong>Motivo:</strong> {{ venta.comprobante.respuesta_sunat?.error || venta.comprobante.respuesta_sunat?.exception || 'Error desconocido' }}
                        </div>
                    </div>
                </div>
              </div>

              <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label fw-bold extrasmall text-muted text-uppercase tracking-widest mb-0">Formato Ticket</label>
                    <div class="btn-group btn-group-sm rounded-pill border overflow-hidden">
                      <input type="radio" class="btn-check" name="format-btn" :id="'f58-'+venta?.id" value="58mm" v-model="formatoImpresion">
                      <label class="btn btn-outline-primary px-3" :for="'f58-'+venta?.id">58mm</label>
                      <input type="radio" class="btn-check" name="format-btn" :id="'f80-'+venta?.id" value="80mm" v-model="formatoImpresion">
                      <label class="btn btn-outline-primary px-3" :for="'f80-'+venta?.id">80mm</label>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                  <div class="row g-2">
                    <div class="col-6">
                      <button
                        class="btn py-2 rounded-3 w-100 fw-bold border-2 small"
                        :class="esBoleta ? 'btn-primary' : 'btn-outline-secondary'"
                        :disabled="!puedeGenerarBoleta"
                        @click="generarComprobante('boleta')"
                      >
                        {{ esBoleta && estadoSunat === 'rechazado' ? 'REINTENTAR BOLETA' : 'BOLETA' }}
                      </button>
                    </div>
                    <div class="col-6">
                      <button
                        class="btn py-2 rounded-3 w-100 fw-bold border-2 small"
                        :class="esFactura ? 'btn-primary' : 'btn-outline-secondary'"
                        :disabled="!puedeGenerarFactura"
                        @click="generarComprobante('factura')"
                      >
                        {{ esFactura && estadoSunat === 'rechazado' ? 'REINTENTAR FACTURA' : 'FACTURA' }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <button class="btn btn-primary w-100 py-3 rounded-4 shadow fw-bold text-uppercase tracking-widest mt-2" @click="cerrar">
                <span v-if="isSuccess">Siguiente Venta <i class="fas fa-arrow-right ms-1"></i></span>
                <span v-else>Cerrar Ventana <i class="fas fa-times ms-1"></i></span>
              </button>
            </div>

            <!-- Columna Previsualización -->
            <div class="col-md-7 border-start ps-4 d-none d-md-block">
              <div class="preview-container bg-white rounded-4 border overflow-hidden shadow-sm position-relative" style="height: 480px;">
                <div v-if="loadingPdf" class="position-absolute top-50 start-50 translate-middle">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <iframe
                  v-if="pdfUrl"
                  :src="pdfUrl"
                  class="w-100 h-100 border-0"
                  title="Ticket Preview"
                  @load="loadingPdf = false"
                ></iframe>

                <div v-else-if="!loadingPdf" class="h-100 d-flex align-items-center justify-content-center text-muted flex-column">
                  <i class="fas fa-receipt fa-3x mb-3 opacity-25"></i>
                  <p class="small">No hay ticket seleccionado</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.extrasmall { font-size: 0.7rem; }
.tracking-widest { letter-spacing: 0.1em; }
.preview-container {
  background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
  background-size: 20px 20px;
}
</style>
