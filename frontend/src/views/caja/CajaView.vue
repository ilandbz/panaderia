<template>
  <div class="caja-view">
    <div class="row g-4">
      <!-- Left: Manual Movements -->
      <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm p-4 h-100">
          <h5 class="fw-bold mb-4">Operaciones de Caja</h5>
          
          <div v-if="!isCajaAbierta" class="text-center py-4">
            <div class="p-4 bg-light rounded-4 mb-4">
               <i class="fas fa-lock fa-3x text-muted opacity-25 mb-3"></i>
               <p class="text-muted">La caja se encuentra CERRADA</p>
            </div>
             <button class="btn btn-success w-100 py-3 rounded-3 fw-bold shadow-sm" @click="handleAbrirCaja">
                APERTURAR CAJA
             </button>
          </div>

          <div v-else>
            <div class="mb-4 p-3 bg-success-subtle rounded-3 d-flex justify-content-between align-items-center">
               <div>
                  <div class="small text-success fw-bold">ESTADO</div>
                  <div class="fw-bold">Caja Abierta</div>
               </div>
               <i class="fas fa-unlock text-success fs-3"></i>
            </div>

            <div class="d-grid gap-3">
               <button class="btn btn-outline-primary py-3 fw-bold"><i class="fas fa-minus-circle me-2"></i> Registrar Gasto / Salida</button>
               <button class="btn btn-outline-success py-3 fw-bold"><i class="fas fa-plus-circle me-2"></i> Ingreso Manual</button>
               <button class="btn btn-danger py-3 fw-bold mt-4 shadow" @click="handleCerrarCaja">CERRAR CAJA DE HOY</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Summary & Movements -->
      <div class="col-lg-8">
         <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-primary text-white">
            <div class="row align-items-center">
              <div class="col-md-6 border-end border-white border-opacity-25">
                 <div class="small opacity-75">EFECTIVO EN CAJA</div>
                 <div class="display-5 fw-bold">S/ 1,450.25</div>
              </div>
              <div class="col-md-6 ps-md-4">
                 <div class="row">
                   <div class="col-6 mb-2">
                      <div class="small opacity-75">Ingresos</div>
                      <div class="fw-bold">S/ 1,200.00</div>
                   </div>
                   <div class="col-6 mb-2">
                       <div class="small opacity-75">Salidas</div>
                       <div class="fw-bold">S/ 45.00</div>
                    </div>
                 </div>
              </div>
            </div>
         </div>

         <div class="card border-0 rounded-4 shadow-sm p-4">
            <h5 class="fw-bold mb-4">Movimientos del Día</h5>
            <div class="table-responsive">
               <table class="table table-hover align-middle">
                 <thead>
                   <tr>
                     <th>Hora</th>
                     <th>Concepto</th>
                     <th>Tipo</th>
                     <th>Monto</th>
                     <th>Medio Pago</th>
                   </tr>
                 </thead>
                 <tbody>
                    <tr v-for="i in 5" :key="i">
                      <td class="text-muted small">09:15 AM</td>
                      <td>Venta V-982{{ i }}</td>
                      <td><span class="text-success fw-bold">Ingreso</span></td>
                      <td class="fw-bold">S/ 45.00</td>
                      <td><span class="badge bg-light text-dark">Efectivo</span></td>
                    </tr>
                    <tr>
                      <td class="text-muted small">10:30 AM</td>
                      <td>Pago de Luz</td>
                      <td><span class="text-danger fw-bold">Egreso</span></td>
                      <td class="fw-bold text-danger">S/ 150.00</td>
                      <td><span class="badge bg-light text-dark">Efectivo</span></td>
                    </tr>
                 </tbody>
               </table>
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

const cajaStore = useCajaStore();
const loading = ref(false);

onMounted(async () => {
  await cajaStore.fetchEstadoCaja();
});

const isCajaAbierta = computed(() => cajaStore.isCajaAbierta);
const apertura = computed(() => cajaStore.aperturaActual);

const handleAbrirCaja = async () => {
  const { value: monto } = await Swal.fire({
    title: 'Apertura de Caja',
    input: 'number',
    inputLabel: 'Monto inicial en soles',
    inputValue: 0,
    showCancelButton: true,
    inputValidator: (value) => {
      if (!value || value < 0) return 'Ingrese un monto válido';
    }
  });

  if (monto !== undefined) {
    try {
      await cajaStore.abrirCaja({ monto_apertura: monto });
      Swal.fire('Éxito', 'Caja aperturada', 'success');
    } catch (e) {
      Swal.fire('Error', e.message, 'error');
    }
  }
};

const handleCerrarCaja = async () => {
  const { value: monto } = await Swal.fire({
    title: 'Cierre de Caja',
    text: 'Ingrese el monto físico contado en caja',
    input: 'number',
    showCancelButton: true,
  });

  if (monto !== undefined) {
    try {
      await cajaStore.cerrarCaja({ monto_cierre: monto });
      Swal.fire('Éxito', 'Caja cerrada correctamente', 'success');
    } catch (e) {
      Swal.fire('Error', e.message, 'error');
    }
  }
};
</script>
