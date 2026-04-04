<script setup>
import { ref } from 'vue';
import { useProductStore } from '@/stores/product.store';
import Swal from 'sweetalert2';

const props = defineProps({
  producto: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['saved']);
const productStore = useProductStore();
const loading = ref(false);

const form = ref({
  tipo: 'ingreso',
  cantidad: 1,
  motivo: 'ajuste_manual',
  observacion: ''
});

const handleSave = async () => {
  if (form.value.cantidad <= 0) {
    Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'error');
    return;
  }

  loading.value = true;
  try {
    const payload = {
      tipo: form.value.tipo,
      cantidad: form.value.cantidad,
      motivo: form.value.motivo,
      observacion: form.value.observacion
    };
    await productStore.ajustarStock(props.producto.id, payload);
    Swal.fire('Éxito', 'Stock ajustado correctamente', 'success');
    emit('saved');
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Error al ajustar stock';
    Swal.fire('Error', errorMsg, 'error');
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="p-3">
    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
      <div class="d-flex align-items-center">
        <i class="fas fa-info-circle fa-2x me-3 opacity-50"></i>
        <div>
          <h6 class="fw-bold mb-1">Ajuste de Stock: {{ producto.nombre }}</h6>
          <p class="small mb-0 opacity-90">Stock Actual: <strong>{{ producto.stock }} {{ producto.unidad_medida }}</strong></p>
        </div>
      </div>
    </div>

    <form @submit.prevent="handleSave">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-bold">Tipo de Ajuste</label>
          <div class="d-flex gap-2">
            <input type="radio" class="btn-check" name="tipoAjuste" id="btnIngreso" value="ingreso" v-model="form.tipo" autocomplete="off" checked>
            <label class="btn btn-outline-success border-2 rounded-pill flex-fill" for="btnIngreso">
              <i class="fas fa-plus me-1"></i> Ingreso
            </label>

            <input type="radio" class="btn-check" name="tipoAjuste" id="btnEgreso" value="egreso" v-model="form.tipo" autocomplete="off">
            <label class="btn btn-outline-danger border-2 rounded-pill flex-fill" for="btnEgreso">
              <i class="fas fa-minus me-1"></i> Salida
            </label>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-bold">Cantidad a ajustar</label>
          <div class="input-group">
             <input v-model.number="form.cantidad" type="number" step="0.001" class="form-control rounded-3 border-2" required>
             <span class="input-group-text bg-light text-muted border-2">{{ producto.unidad_medida }}</span>
          </div>
        </div>

        <div class="col-md-12">
          <label class="form-label small fw-bold">Motivo del ajuste</label>
          <select v-model="form.motivo" class="form-select rounded-3 border-2" required>
            <option value="ajuste_manual">Ajuste Manual / Error de sistema</option>
            <option value="inventario_inicial">Inventario Inicial</option>
            <option value="merma">Merma / Desperdicio</option>
            <option value="vencimiento">Producto Vencido</option>
            <option value="donacion">Donación</option>
            <option value="otro">Otro</option>
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label small fw-bold">Observación (Opcional)</label>
          <textarea v-model="form.observacion" class="form-control rounded-3 border-2" rows="2" placeholder="Escriba el detalle del ajuste..."></textarea>
        </div>
      </div>

      <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          Procesar Ajuste
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.btn-check:checked + .btn-outline-success {
  background-color: #198754 !important;
  color: white !important;
  border-color: #198754 !important;
}
.btn-check:checked + .btn-outline-danger {
  background-color: #dc3545 !important;
  color: white !important;
  border-color: #dc3545 !important;
}
.form-label {
  color: #555;
}
.border-2 {
  border-width: 2px !important;
}
.form-control:focus, .form-select:focus {
  border-color: #d97706;
  box-shadow: 0 0 0 0.25rem rgba(217, 119, 6, 0.1);
}
</style>
