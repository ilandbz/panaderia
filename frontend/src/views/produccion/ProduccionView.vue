<script setup>
import { onMounted, ref } from 'vue';
import { useProduccionStore } from '@/stores/produccion.store';
import Swal from 'sweetalert2';

const produccionStore = useProduccionStore();
const showNewRecipeModal = ref(false);

onMounted(async () => {
  await produccionStore.fetchRecetas();
});

const openProduccionModal = async (receta) => {
  const { value: cantidad } = await Swal.fire({
    title: `Producir: ${receta.producto.nombre}`,
    text: `Indique la cantidad total a producir (Rendimiento base: ${receta.rendimiento})`,
    input: 'number',
    inputValue: receta.rendimiento,
    showCancelButton: true,
    confirmButtonText: 'Ejecutar Producción',
    inputValidator: (value) => {
      if (!value || value <= 0) return 'Ingrese una cantidad válida';
    }
  });

  if (cantidad) {
    try {
      await produccionStore.ejecutarProduccion(receta.id, cantidad);
      Swal.fire('Éxito', 'Producción ejecutada y stock actualizado', 'success');
    } catch (e) {
      Swal.fire('Error', e.message || 'Error al procesar producción', 'error');
    }
  }
};
</script>
<template>
  <div class="produccion-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold m-0 text-brown"><i class="fas fa-mortar-pestle me-2"></i> Órdenes de Producción</h3>
      <button class="btn btn-primary rounded-pill px-4" @click="showNewRecipeModal = true">
        <i class="fas fa-plus me-2"></i> Nueva Receta
      </button>
    </div>

    <!-- Stats summary -->
    <div class="row g-4 mb-4">
      <div class="col-md-8">
        <div class="card border-0 rounded-4 shadow-sm p-4 h-100">
          <h5 class="fw-bold mb-4">Recetas Disponibles</h5>
          <div class="row g-3">
            <div v-for="receta in produccionStore.recetas" :key="receta.id" class="col-md-6">
              <div class="card border border-light-subtle rounded-4 p-3 hover-shadow transition">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-bold">{{ receta.producto?.nombre }}</div>
                    <div class="small text-muted">Rendimiento base: {{ receta.rendimiento }} {{ receta.producto?.unidad_medida }}</div>
                  </div>
                  <button class="btn btn-sm btn-outline-primary rounded-pill" @click="openProduccionModal(receta)">
                    Producir
                  </button>
                </div>
                <div class="mt-2 text-primary small fw-bold">
                  {{ receta.insumos?.length || 0 }} insumos configurados
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-primary text-white h-100">
           <h5 class="fw-bold mb-3">¿Cómo funciona?</h5>
           <ul class="small opacity-90 p-0 m-0" style="list-style: none;">
             <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Define una receta con sus insumos.</li>
             <li class="mb-2"><i class="fas fa-check-circle me-2"></i> Ejecuta una orden indicando la cantidad.</li>
             <li class="mb-2"><i class="fas fa-check-circle me-2"></i> El sistema descontará automáticamente los insumos.</li>
             <li><i class="fas fa-check-circle me-2"></i> El stock del producto terminado aumentará.</li>
           </ul>
        </div>
      </div>
    </div>
  </div>
</template>



<style scoped>
.hover-shadow:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
  transform: translateY(-2px);
}
.transition {
  transition: all 0.2s ease-in-out;
}
.text-brown { color: #451a03; }
</style>
