<template>
  <div class="caja-view animate__animated animate__fadeIn px-3 py-4">
    <!-- Tabs Navigation -->
    <div class="card border-0 rounded-4 shadow-sm mb-4">
      <div class="card-body p-2">
        <ul class="nav nav-pills nav-fill gap-2">
          <li class="nav-item">
            <button class="nav-link py-3 rounded-3" :class="{ active: activeTab === 'actual' }" @click="activeTab = 'actual'">
              <i class="fas fa-cash-register me-2"></i>Caja Actual
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link py-3 rounded-3" :class="{ active: activeTab === 'historial' }" @click="handleTabHistorial">
              <i class="fas fa-history me-2"></i>Historial de Cierres
            </button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Tab: Caja Actual -->
    <div v-if="activeTab === 'actual'" class="animate__animated animate__fadeIn">
      <div class="row g-4">
        <!-- Left: Manual Movements -->
        <div class="col-lg-4">
          <div class="card border-0 rounded-4 shadow-sm p-4 h-100">
            <h5 class="fw-bold mb-4 text-brown"><i class="fas fa-wallet me-2"></i>Operaciones de Caja</h5>
            
            <div v-if="!isCajaAbierta" class="text-center py-5">
              <div class="p-4 bg-light rounded-4 mb-4 border border-dashed">
                 <i class="fas fa-lock fa-3x text-muted opacity-25 mb-3"></i>
                 <p class="text-muted fw-bold">La caja se encuentra CERRADA</p>
                 <small class="text-muted">Debe abrir una caja para iniciar ventas y registrar movimientos.</small>
              </div>
               <button class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow" @click="handleAbrirCaja">
                  <i class="fas fa-plus-circle me-2"></i> APERTURAR CAJA
               </button>
            </div>

            <div v-else>
              <div class="mb-4 p-3 bg-success-subtle border border-success-subtle rounded-4 d-flex justify-content-between align-items-center">
                 <div>
                    <div class="small text-success fw-bold text-uppercase tracking-wider">ESTADO</div>
                    <div class="h5 fw-bold mb-0 text-success">Caja Abierta</div>
                 </div>
                 <div class="bg-success text-white rounded-circle p-2 shadow-sm">
                   <i class="fas fa-unlock"></i>
                 </div>
              </div>

              <div class="d-grid gap-3">
                 <button class="btn btn-outline-primary py-3 fw-bold rounded-3 transition-hover" @click="handleManualMovement('egreso')">
                   <i class="fas fa-minus-circle me-2"></i> Registrar Gasto / Salida
                 </button>
                 <button class="btn btn-outline-success py-3 fw-bold rounded-3 transition-hover" @click="handleManualMovement('ingreso')">
                   <i class="fas fa-plus-circle me-2"></i> Ingreso Manual
                 </button>
                 <button class="btn btn-danger py-3 fw-bold mt-4 shadow rounded-pill text-uppercase tracking-widest" @click="handleCerrarCaja">
                   CERRAR CAJA DE HOY
                 </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Summary & Movements -->
        <div class="col-lg-8">
           <div class="card border-0 rounded-4 shadow-lg p-4 mb-4 bg-primary text-white overflow-hidden position-relative shadow-primary">
              <div class="position-absolute top-0 end-0 p-5 opacity-10" style="transform: translate(30%, -30%)">
                 <i class="fas fa-cash-register fa-10x"></i>
              </div>

              <div class="row align-items-center position-relative">
                <div class="col-md-6 border-end border-white border-opacity-25 py-2">
                   <div class="small opacity-75 fw-bold text-uppercase tracking-wider">TOTAL EN CAJA (SISTEMA)</div>
                   <div class="display-4 fw-bold">S/ {{ formatMoney(totalSistema) }}</div>
                   <div class="small opacity-50">Inició con S/ {{ formatMoney(apertura?.monto_apertura || 0) }}</div>
                </div>
                <div class="col-md-6 ps-md-4 py-2">
                   <div class="row">
                     <div class="col-6 mb-2">
                        <div class="small opacity-75 fw-bold text-uppercase">Ingresos</div>
                        <div class="h4 fw-bold mb-0 text-white-50">+ S/ {{ formatMoney(totalIngresos) }}</div>
                     </div>
                     <div class="col-6 mb-2">
                          <div class="small opacity-75 fw-bold text-uppercase">Salidas</div>
                          <div class="h4 fw-bold mb-0 text-danger-subtle">- S/ {{ formatMoney(totalSalidas) }}</div>
                       </div>
                   </div>
                </div>
              </div>
           </div>

           <div class="card border-0 rounded-4 shadow-sm p-4 h-100">
              <h5 class="fw-bold mb-4 text-brown">
                <i class="fas fa-list-ul text-primary me-2"></i>Movimientos de Hoy ({{ movimientos.length }})
              </h5>
              <div class="table-responsive custom-scrollbar" style="max-height: 500px">
                 <table class="table table-hover align-middle">
                   <thead class="table-light text-uppercase small opacity-75 fw-bold">
                     <tr>
                       <th class="border-0">Hora</th>
                       <th class="border-0">Concepto</th>
                       <th class="border-0">Tipo</th>
                       <th class="border-0">Monto</th>
                       <th class="border-0">Medio Pago</th>
                     </tr>
                   </thead>
                   <tbody>
                      <tr v-for="m in movimientos" :key="m.id" class="transition-hover">
                        <td class="text-muted small border-light">{{ formatTime(m.created_at) }}</td>
                        <td class="border-light">
                          <div class="fw-bold text-dark">{{ m.concepto }}</div>
                          <div v-if="m.observacion" class="extrasmall text-muted fst-italic">{{ m.observacion }}</div>
                        </td>
                        <td class="border-light">
                          <span :class="m.tipo === 'ingreso' ? 'badge bg-success-subtle text-success' : 'badge bg-danger-subtle text-danger'" class="rounded-pill px-3">
                            {{ m.tipo === 'ingreso' ? 'Ingreso' : 'Egreso' }}
                          </span>
                        </td>
                        <td :class="m.tipo === 'ingreso' ? 'text-success fw-bold' : 'text-danger fw-bold'" class="border-light">
                          {{ m.tipo === 'ingreso' ? '+' : '-' }} S/ {{ formatMoney(m.monto) }}
                        </td>
                        <td class="border-light">
                          <span class="badge bg-light text-dark rounded-pill border px-2">
                             <i :class="getFormaPagoIcon(m.forma_pago)" class="me-1 small"></i>
                             {{ m.forma_pago?.toUpperCase() || 'EFECTIVO' }}
                          </span>
                        </td>
                      </tr>
                      <tr v-if="movimientos.length === 0">
                        <td colspan="5" class="text-center py-5 text-muted">
                          <i class="fas fa-info-circle fa-2x mb-2 opacity-25"></i>
                          <p class="mb-0">No hay movimientos registrados para esta caja.</p>
                        </td>
                      </tr>
                   </tbody>
                 </table>
              </div>
           </div>
        </div>
      </div>
    </div>

    <!-- Tab: Historial -->
    <div v-else class="animate__animated animate__fadeIn">
      <div class="card border-0 rounded-4 shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="fw-bold text-brown mb-0"><i class="fas fa-history me-2 text-primary"></i>Historial de Cierres</h5>
          <button class="btn btn-light btn-sm rounded-pill px-3" @click="handleTabHistorial" :disabled="cajaStore.loading">
            <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': cajaStore.loading }"></i> Actualizar
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="bg-light small fw-bold text-uppercase opacity-75">
              <tr>
                <th>Fecha Cierre</th>
                <th>Apertura</th>
                <th>Sistema</th>
                <th>Cierre Reales</th>
                <th>Diferencia</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in cajaStore.historial?.data" :key="c.id">
                <td>
                  <div class="fw-bold">{{ formatDate(c.fecha_cierre) }}</div>
                  <div class="small text-muted">{{ formatTime(c.fecha_cierre) }}</div>
                </td>
                <td>S/ {{ formatMoney(c.monto_apertura) }}</td>
                <td>S/ {{ formatMoney(c.monto_sistema) }}</td>
                <td class="fw-bold">S/ {{ formatMoney(c.monto_cierre) }}</td>
                <td>
                  <span :class="parseFloat(c.diferencia) < 0 ? 'text-danger' : (parseFloat(c.diferencia) > 0 ? 'text-success' : 'text-muted')" class="fw-bold">
                    {{ parseFloat(c.diferencia) === 0 ? '-' : (parseFloat(c.diferencia) > 0 ? '+' : '') + ' S/ ' + formatMoney(c.diferencia) }}
                  </span>
                  <div v-if="parseFloat(c.diferencia) < 0" class="extrasmall text-danger fw-bold">FALTANTE</div>
                  <div v-if="parseFloat(c.diferencia) > 0" class="extrasmall text-success fw-bold">SOBRANTE</div>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" @click="handleAuditCaja(c.id)">
                    <i class="fas fa-search me-1"></i> Auditoría
                  </button>
                </td>
              </tr>
              <tr v-if="!cajaStore.historial?.data?.length">
                <td colspan="6" class="text-center py-5 text-muted">No hay cierres registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal de Auditoría -->
    <div class="modal fade" id="auditModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered shadow-lg">
        <div class="modal-content border-0 rounded-5">
          <div class="modal-header border-0 bg-dark text-white p-4 px-5">
             <div>
               <h5 class="modal-title fw-bold mb-0">Auditoría de Movimientos</h5>
               <p class="small opacity-50 mb-0">Cierre del {{ formatDate(cajaStore.detalleApertura?.fecha_cierre) }}</p>
             </div>
             <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-0">
             <div class="p-4 px-5 bg-light d-flex justify-content-between align-items-center mb-0">
               <div class="text-center">
                 <div class="small text-muted text-uppercase fw-bold">Sistema</div>
                 <div class="h4 fw-bold mb-0">S/ {{ formatMoney(cajaStore.detalleApertura?.monto_sistema || 0) }}</div>
               </div>
               <div class="text-center">
                  <div class="small text-muted text-uppercase fw-bold">Físico</div>
                  <div class="h4 fw-bold mb-0">S/ {{ formatMoney(cajaStore.detalleApertura?.monto_cierre || 0) }}</div>
                </div>
                <div class="text-center">
                   <div class="small text-muted text-uppercase fw-bold">Diferencia</div>
                   <div class="h4 fw-bold mb-0" :class="parseFloat(cajaStore.detalleApertura?.diferencia) < 0 ? 'text-danger' : 'text-success'">
                     S/ {{ formatMoney(cajaStore.detalleApertura?.diferencia || 0) }}
                   </div>
                 </div>
             </div>

             <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
               <table class="table table-hover table-sm mb-0">
                 <thead class="table-dark small text-uppercase">
                   <tr>
                     <th class="ps-5">Hora</th>
                     <th>Concepto</th>
                     <th>Tipo</th>
                     <th class="pe-5 text-end">Monto</th>
                   </tr>
                 </thead>
                 <tbody>
                    <tr v-for="m in cajaStore.detalleApertura?.movimientos" :key="m.id">
                      <td class="ps-5 border-light py-2">{{ formatTime(m.created_at) }}</td>
                      <td class="border-light py-2">
                        <div class="fw-bold small">{{ m.concepto }}</div>
                        <div v-if="m.observacion" class="extrasmall text-muted">{{ m.observacion }}</div>
                      </td>
                      <td class="border-light py-2">
                        <span class="extrasmall fw-bold" :class="m.tipo === 'ingreso' ? 'text-success' : 'text-danger'">{{ m.tipo.toUpperCase() }}</span>
                      </td>
                      <td class="pe-5 text-end border-light py-2 fw-bold" :class="m.tipo === 'ingreso' ? 'text-success' : 'text-danger'">
                        {{ m.tipo === 'ingreso' ? '+' : '-' }} S/ {{ formatMoney(m.monto) }}
                      </td>
                    </tr>
                 </tbody>
               </table>
             </div>
          </div>
          <div class="modal-footer border-0 p-4 px-5 justify-content-start bg-light rounded-bottom-5">
             <div class="fw-bold text-dark mb-0">Observaciones:</div>
             <p class="mb-0 text-muted small w-100 italic">{{ cajaStore.detalleApertura?.observaciones || 'Sin observaciones' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useCajaStore } from '@/stores/caja.store';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';

const cajaStore = useCajaStore();
const activeTab = ref('actual');
let auditModal = null;

onMounted(async () => {
  await cajaStore.fetchEstadoCaja();
  auditModal = new Modal(document.getElementById('auditModal'));
});

const isCajaAbierta = computed(() => cajaStore.isCajaAbierta);
const apertura = computed(() => cajaStore.aperturaActual);
const movimientos = computed(() => apertura.value?.movimientos || []);

// Cálculos Dinámicos
const totalIngresos = computed(() => {
  return movimientos.value
    .filter(m => m.tipo === 'ingreso')
    .reduce((sum, m) => sum + parseFloat(m.monto), 0);
});

const totalSalidas = computed(() => {
  return movimientos.value
    .filter(m => m.tipo === 'egreso')
    .reduce((sum, m) => sum + parseFloat(m.monto), 0);
});

const totalSistema = computed(() => {
  const base = parseFloat(apertura.value?.monto_apertura || 0);
  return base + totalIngresos.value - totalSalidas.value;
});

// Formateadores
const formatMoney = (val) => parseFloat(val).toFixed(2);

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getFormaPagoIcon = (forma) => {
  switch (forma?.toLowerCase()) {
    case 'efectivo': return 'fas fa-money-bill';
    case 'tarjeta': return 'fas fa-credit-card';
    case 'yape':
    case 'plin': return 'fas fa-mobile-alt';
    default: return 'fas fa-money-check';
  }
};

// Handlers
const handleTabHistorial = async () => {
  activeTab.value = 'historial';
  await cajaStore.fetchHistorial();
};

const handleAuditCaja = async (id) => {
  Swal.showLoading();
  await cajaStore.fetchDetalleApertura(id);
  Swal.close();
  auditModal.show();
};

const handleAbrirCaja = async () => {
  const { value: monto } = await Swal.fire({
    title: 'Apertura de Caja',
    text: 'Ingrese el monto inicial en efectivo (fondo de caja)',
    input: 'number',
    inputAttributes: { step: '0.10', min: '0' },
    inputValue: 0,
    showCancelButton: true,
    confirmButtonText: 'Abrir Caja',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
      if (value === '' || parseFloat(value) < 0) return 'Ingrese un monto válido';
    }
  });

  if (monto !== undefined) {
    try {
      Swal.showLoading();
      await cajaStore.abrirCaja({ monto_apertura: parseFloat(monto) });
      Swal.fire('Éxito', 'Caja aperturada correctamente', 'success');
    } catch (e) {
      Swal.fire('Error', e.response?.data?.message || e.message, 'error');
    }
  }
};

const handleCerrarCaja = async () => {
  const { value: monto } = await Swal.fire({
    title: 'Cierre de Caja',
    html: `Monto sistema esperado: <b>S/ ${formatMoney(totalSistema.value)}</b><br><br>Ingrese el efectivo final contado:`,
    input: 'number',
    inputAttributes: { step: '0.10', min: '0' },
    showCancelButton: true,
    confirmButtonText: 'Cerrar Caja',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
      if (value === '') return 'Debe ingresar el monto contado';
    }
  });

  if (monto !== undefined) {
    try {
      Swal.showLoading();
      await cajaStore.cerrarCaja({ monto_cierre: parseFloat(monto) });
      Swal.fire('Éxito', 'Caja cerrada y cuadre guardado', 'success');
    } catch (e) {
      Swal.fire('Error', e.response?.data?.message || e.message, 'error');
    }
  }
};

const handleManualMovement = async (tipo) => {
  const { value: formValues } = await Swal.fire({
    title: tipo === 'ingreso' ? 'Ingreso Manual' : 'Registrar Gasto / Salida',
    html:
      '<input id="swal-input1" class="swal2-input" placeholder="Concepto (Ej. Pago de luz, Venta rápida...)">' +
      '<input id="swal-input2" type="number" step="0.10" class="swal2-input" placeholder="Monto S/">' +
      '<textarea id="swal-input3" class="swal2-textarea" placeholder="Observación (opcional)"></textarea>',
    focusConfirm: false,
    showCancelButton: true,
    preConfirm: () => {
      return {
        concepto: document.getElementById('swal-input1').value,
        monto: document.getElementById('swal-input2').value,
        observacion: document.getElementById('swal-input3').value
      }
    }
  });

  if (formValues) {
    if (!formValues.concepto || !formValues.monto) {
      Swal.fire('Atención', 'Concepto y monto son obligatorios', 'warning');
      return;
    }
    try {
      Swal.showLoading();
      await cajaStore.registrarMovimiento(tipo, {
        concepto: formValues.concepto,
        monto: parseFloat(formValues.monto),
        observacion: formValues.observacion
      });
      Swal.fire('Éxito', 'Movimiento registrado', 'success');
    } catch (e) {
      Swal.fire('Error', e.response?.data?.message || e.message, 'error');
    }
  }
};
</script>

<style scoped>
.text-brown { color: #4b2c20; }
.bg-primary { background-color: #d97706 !important; border-color: #d97706 !important; }
.shadow-primary { box-shadow: 0 15px 30px rgba(217, 119, 6, 0.2) !important; }
.nav-pills .nav-link { 
  background-color: #f8f9fa; 
  color: #6c757d; 
  font-weight: 600;
  border: 1px solid transparent;
}
.nav-pills .nav-link.active { 
  background-color: #d97706; 
  color: white; 
  box-shadow: 0 5px 15px rgba(217, 119, 6, 0.2);
}
.tracking-wider { letter-spacing: 0.05em; }
.tracking-widest { letter-spacing: 0.15em; }
.transition-hover:hover { background-color: #fff9f0; transform: scale(1.002); }
.extrasmall { font-size: 0.7rem; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e9ecef; border-radius: 10px; }
.shadow-lg { box-shadow: 0 1rem 3rem rgba(0,0,0,.075)!important; }
.italic { font-style: italic; }
</style>

<style scoped>
.text-brown { color: #4b2c20; }
.bg-primary { background-color: #d97706 !important; border-color: #d97706 !important; }
.shadow-primary { box-shadow: 0 15px 30px rgba(217, 119, 6, 0.2) !important; }
.tracking-wider { letter-spacing: 0.05em; }
.tracking-widest { letter-spacing: 0.15em; }
.transition-hover:hover { background-color: #fff9f0; transform: scale(1.002); }
.extrasmall { font-size: 0.7rem; }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e9ecef; border-radius: 10px; }

.shadow-lg { box-shadow: 0 1rem 3rem rgba(0,0,0,.075)!important; }
</style>ript>
