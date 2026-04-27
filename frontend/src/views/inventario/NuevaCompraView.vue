<template>
  <div class="nueva-compra-view">
    <div class="mb-4">
      <router-link to="/compras" class="btn btn-sm btn-link text-decoration-none p-0 mb-2">
        <i class="fas fa-arrow-left me-1"></i> Volver al listado
      </router-link>
      <h3 class="fw-bold m-0 text-brown">Nueva Compra de Mercadería</h3>
    </div>

    <div class="row g-4">
      <!-- Form Column -->
      <div class="col-lg-8">
        <div class="card border-0 rounded-4 shadow-sm p-4 mb-4">
          <h5 class="fw-bold mb-4">Detalles del Comprobante</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Proveedor</label>
              <select v-model="form.proveedor_id" class="form-select border-0 bg-light rounded-3">
                <option value="">Seleccione un proveedor...</option>
                <option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.razon_social }} ({{ p.ruc }})</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold text-muted">Tipo Doc.</label>
              <select v-model="form.tipo_comprobante" class="form-select border-0 bg-light rounded-3">
                <option value="Factura">Factura</option>
                <option value="Boleta">Boleta</option>
                <option value="Guía">Guía</option>
                <option value="Nota de Venta">Nota de Venta</option>
                <option value="Sin Comprobante">Sin Comprobante</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold text-muted">Nro. Comprobante</label>
              <input v-model="form.numero_comprobante" type="text" class="form-control border-0 bg-light rounded-3" placeholder="Ej: F001-123">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-muted">Fecha de Compra</label>
              <input v-model="form.fecha_compra" type="date" class="form-control border-0 bg-light rounded-3">
            </div>
            <div class="col-md-8 d-flex align-items-end gap-4">
               <div class="form-check form-switch mb-2 ms-2">
                <input class="form-check-input custom-switch" type="checkbox" role="switch" id="switchIgv" v-model="form.incluye_igv">
                <label class="form-check-label fw-bold small text-muted ms-2" for="switchIgv">
                  {{ form.incluye_igv ? 'Calcular IGV (18%)' : 'Exonerado de IGV (0%)' }}
                </label>
              </div>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input custom-switch" type="checkbox" role="switch" id="switchCaja" v-model="form.registrar_en_caja">
                <label class="form-check-label fw-bold small text-muted ms-2" for="switchCaja" :class="{'text-primary': form.registrar_en_caja}">
                   {{ form.registrar_en_caja ? 'Pasar por caja (Egreso)' : 'No pasar por caja' }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 rounded-4 shadow-sm p-4">
          <div class="d-flex justify-content-between align-items-center mb-4">
             <h5 class="fw-bold m-0">Productos / Insumos</h5>
             <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addItem">
               <i class="fas fa-plus me-1"></i> Agregar Item
             </button>
          </div>
          
          <div class="table-responsive">
            <table class="table table-borderless align-middle">
              <thead>
                <tr class="text-muted small fw-bold border-bottom">
                   <th width="40%">Producto</th>
                   <th width="20%">Cant.</th>
                   <th width="20%">P. Unit.</th>
                   <th width="15%">Subtotal</th>
                   <th width="5%"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in form.items" :key="index">
                  <td>
                    <select v-model="item.producto_id" class="form-select form-select-sm border-0 bg-light rounded-2" @change="updatePrice(index)">
                       <option value="">Seleccione...</option>
                       <option v-for="prod in productos" :key="prod.id" :value="prod.id">{{ prod.nombre }}</option>
                    </select>
                  </td>
                  <td>
                    <input v-model.number="item.cantidad" type="number" step="0.001" class="form-control form-select-sm border-0 bg-light rounded-2 text-center" @input="calculateItemTotal(index)">
                  </td>
                  <td>
                    <input v-model.number="item.precio_compra" type="number" step="0.1" class="form-control form-select-sm border-0 bg-light rounded-2 text-center" @input="calculateItemTotal(index)">
                  </td>
                  <td class="fw-bold text-primary">S/ {{ item.subtotal.toFixed(2) }}</td>
                  <td>
                    <button class="btn btn-sm text-danger border-0" @click="removeItem(index)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right Column: Summary -->
      <div class="col-lg-4">
         <div class="card border-0 rounded-4 shadow-sm p-4 bg-light border sticky-top" style="top: 2rem;">
            <h5 class="fw-bold mb-4">Resumen de Compra</h5>
            <div class="d-flex justify-content-between mb-2">
               <span class="text-muted small fw-bold">Subtotal</span>
               <span class="fw-bold">S/ {{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2 align-items-center">
               <span class="text-muted small fw-bold">IGV (18%)</span>
               <span v-if="!form.incluye_igv" class="badge bg-secondary-subtle text-secondary small border border-secondary-subtle">Exonerado</span>
               <span v-else class="fw-bold text-dark">S/ {{ igv.toFixed(2) }}</span>
            </div>
            <hr class="my-4 opacity-10">
            <div class="d-flex justify-content-between h3 fw-bold mb-4 text-primary">
               <span>TOTAL</span>
               <span>S/ {{ total.toFixed(2) }}</span>
            </div>
            <button class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm transition-all" :disabled="!isReadyToSubmit" @click="submitCompra">
               <i class="fas fa-save me-2"></i> GUARDAR COMPRA
            </button>
         </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch  } from 'vue';
import { useRouter } from 'vue-router';
import { useProveedorStore } from '@/stores/proveedor.store';
import { useProductStore } from '@/stores/product.store';
import { useCompraStore } from '@/stores/compra.store';
import Swal from 'sweetalert2';

const router = useRouter();
const provStore = useProveedorStore();
const prodStore = useProductStore();
const compraStore = useCompraStore();

const form = ref({
  proveedor_id: '',
  tipo_comprobante: 'Factura',
  numero_comprobante: '',
  fecha_compra: new Date().toISOString().substr(0, 10),
  incluye_igv: false,
  registrar_en_caja: true,
  items: [
    { producto_id: '', cantidad: 1, precio_compra: 0, subtotal: 0 }
  ]
});

onMounted(() => {
  provStore.fetchProveedores();
  prodStore.fetchProducts();
});

const proveedores = computed(() => provStore.proveedores);
const productos = computed(() => prodStore.products);

const subtotal = computed(() => form.value.items.reduce((acc, item) => acc + item.subtotal, 0));
const igv = computed(() => form.value.incluye_igv ? subtotal.value * 0.18 : 0);
const total = computed(() => subtotal.value + igv.value);

const isReadyToSubmit = computed(() => {
  return form.value.proveedor_id && 
         form.value.numero_comprobante && 
         form.value.items.length > 0 && 
         form.value.items.every(i => i.producto_id && i.cantidad > 0);
});

const addItem = () => {
  form.value.items.push({ producto_id: '', cantidad: 1, precio_compra: 0, subtotal: 0 });
};

const removeItem = (index) => {
  form.value.items.splice(index, 1);
};

const updatePrice = (index) => {
  const item = form.value.items[index];
  const prod = productos.value.find(p => p.id === item.producto_id);
  if (prod) {
    // Si no tiene precio de compra configurado, usamos un valor base
    item.precio_compra = prod.costo || 0;
    calculateItemTotal(index);
  }
};

const calculateItemTotal = (index) => {
  const item = form.value.items[index];
  item.subtotal = item.cantidad * item.precio_compra;
};

watch(() => form.value.tipo_comprobante, (nuevoTipo) => {
  if (nuevoTipo === 'Sin Comprobante') {
    form.value.numero_comprobante = 'S/N';
  } else {
    // opcional: limpiar si venía de sin comprobante
    if (form.value.numero_comprobante === 'S/N') {
      form.value.numero_comprobante = '';
    }
  }
});

const submitCompra = async () => {
  try {
    const data = {
      ...form.value,
      subtotal: subtotal.value,
      igv: igv.value,
      total: total.value
    };
    await compraStore.registrarCompra(data);
    Swal.fire('Éxito', 'Compra registrada correctamente', 'success');
    router.push('/compras');
  } catch (error) {
    Swal.fire('Error', error.message || 'Error al registrar la compra', 'error');
  }
};
</script>

<style scoped>
.text-brown { color: #451a03; }
.transition-all { transition: all 0.3s ease; }
.custom-switch { width: 3em !important; height: 1.5em !important; cursor: pointer; }
.form-check-input:checked { background-color: #d97706; border-color: #d97706; }
.bg-secondary-subtle { background-color: rgba(108, 117, 125, 0.1) !important; }
</style>
