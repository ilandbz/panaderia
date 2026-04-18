<script setup>
import { onMounted, ref } from 'vue';
import { useProduccionStore } from '@/stores/produccion.store';
import { useProductStore } from '@/stores/product.store';
import RecetaModal from './components/RecetaModal.vue';
import Swal from 'sweetalert2';

const produccionStore = useProduccionStore();
const productStore = useProductStore();

const showRecipeModal = ref(false);
const recetaParaEditar = ref(null);

onMounted(async () => {
  await produccionStore.fetchRecetas();
  if (productStore.products.length === 0) {
    await productStore.fetchProducts();
  }
});

const abrirNuevaReceta = () => {
  recetaParaEditar.value = null;
  showRecipeModal.value = true;
};

const editarReceta = (receta) => {
  recetaParaEditar.value = receta;
  showRecipeModal.value = true;
};

const eliminarReceta = async (receta) => {
  const result = await Swal.fire({
    title: '¿Eliminar receta?',
    text: `Esta acción desactivará la receta: ${receta.nombre}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (result.isConfirmed) {
    try {
      await produccionStore.deleteReceta(receta.id);
      Swal.fire('Eliminado', 'La receta ha sido eliminada.', 'success');
    } catch (e) {
      Swal.fire('Error', 'No se pudo eliminar la receta.', 'error');
    }
  }
};

const openProduccionModal = async (receta) => {
  // Construir resumen de insumos
  const insumosHtml = receta.insumos.map(i => 
    `<li>${i.insumo?.nombre}: <b>${i.cantidad} ${i.unidad_medida}</b></li>`
  ).join('');

  const { value: cantidad } = await Swal.fire({
    title: `Ejecutar: ${receta.producto.nombre}`,
    html: `
      <div class="text-start small mb-3">
        <p class="mb-1">Esta receta produce <b>${receta.rendimiento} ${receta.producto.unidad_medida}</b> base por batch.</p>
        <p class="mb-1">Insumos por batch:</p>
        <ul class="mb-0">${insumosHtml}</ul>
      </div>
      <label class="mb-2">Indique la cantidad total a producir:</label>
    `,
    input: 'number',
    inputValue: receta.rendimiento,
    showCancelButton: true,
    confirmButtonText: 'Confirmar Producción',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
      if (!value || value <= 0) return 'Ingrese una cantidad válida';
    }
  });

  if (cantidad) {
    try {
      await produccionStore.ejecutarProduccion(receta.id, cantidad);
      Swal.fire('Éxito', 'Producción ejecutada. El stock se ha actualizado correctamente.', 'success');
      // Recargar stock de productos para reflejar cambios en la interfaz
      productStore.fetchProducts();
    } catch (e) {
      Swal.fire('Error', e.message || 'Error al procesar producción. Verifique stock de insumos.', 'error');
    }
  }
};
</script>

<template>
  <div class="produccion-view pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold m-0 text-brown"><i class="fas fa-mortar-pestle me-2"></i> Gestión de Producción</h3>
        <p class="text-muted small m-0">Administre sus recetas y ejecute órdenes para actualizar stock.</p>
      </div>
      <button class="btn btn-primary rounded-pill px-4" @click="abrirNuevaReceta">
        <i class="fas fa-plus me-2"></i> Nueva Receta
      </button>
    </div>

    <div class="row g-4 mb-4">
      <!-- Listado de Recetas -->
      <div class="col-md-8">
        <div class="card border-0 rounded-4 shadow-sm p-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0 text-brown">Recetas Disponibles</h5>
            <span class="badge bg-light text-brown rounded-pill px-3">{{ produccionStore.recetas?.length }} recetas</span>
          </div>

          <div v-if="produccionStore.loading && produccionStore.recetas.length === 0" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Cargando recetas...</p>
          </div>

          <div v-else-if="produccionStore.recetas.length === 0" class="text-center py-5 bg-light rounded-4">
            <i class="fas fa-receipt fa-3x text-muted mb-3 opacity-20"></i>
            <p class="text-muted">No hay recetas configuradas aún.</p>
            <button class="btn btn-link text-primary text-decoration-none" @click="abrirNuevaReceta">Crea tu primera receta</button>
          </div>

          <div v-else class="row g-3">
            <div v-for="receta in produccionStore.recetas" :key="receta.id" class="col-12 col-xl-6">
              <div class="card border border-light-subtle rounded-4 p-3 h-100 hover-shadow transition">
                <div class="d-flex justify-content-between">
                  <div class="flex-grow-1">
                    <div class="badge bg-cream text-brown mb-2 small">{{ receta.producto?.categoria?.nombre || 'General' }}</div>
                    <h6 class="fw-bold mb-1">{{ receta.producto?.nombre }}</h6>
                    <p class="extrasmall text-muted mb-2">{{ receta.nombre }}</p>
                  </div>
                  <div class="d-flex gap-1 align-items-start">
                    <button class="btn btn-icon-sm btn-light rounded-circle" @click="editarReceta(receta)" title="Editar">
                      <i class="fas fa-edit text-primary small"></i>
                    </button>
                    <button class="btn btn-icon-sm btn-light rounded-circle" @click="eliminarReceta(receta)" title="Eliminar">
                      <i class="fas fa-trash text-danger small"></i>
                    </button>
                  </div>
                </div>

                <div class="bg-light rounded-3 p-2 mb-3">
                   <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="extrasmall fw-bold text-muted">RENDIMIENTO BASE</span>
                      <span class="small fw-bold">{{ receta.rendimiento }} {{ receta.producto?.unidad_medida }}</span>
                   </div>
                   <div class="extrasmall text-muted">
                    <i class="fas fa-list me-1"></i> {{ receta.insumos?.length || 0 }} ingredientes configurados
                   </div>
                </div>

                <!-- Lista rápida de ingredientes (Opcional: solo si quieres mostrarla) -->
                <div class="mb-3 flex-grow-1">
                  <div class="extrasmall text-muted fw-bold mb-1">INGREDIENTES:</div>
                  <div class="d-flex flex-wrap gap-1">
                    <span v-for="i in receta.insumos" :key="i.id" class="badge rounded-pill bg-white border text-dark extrasmall fw-normal">
                      {{ i.insumo?.nombre }}: {{ i.cantidad }}{{ i.unidad_medida }}
                    </span>
                  </div>
                </div>

                <button class="btn btn-primary w-100 rounded-pill py-2 shadow-sm" @click="openProduccionModal(receta)">
                  <i class="fas fa-play me-2"></i> Ejecutar Producción
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ayuda / Guía -->
      <div class="col-md-4">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-brown text-white h-100">
           <div class="mb-4">
             <i class="fas fa-info-circle fa-2x mb-3 text-warning"></i>
             <h5 class="fw-bold mb-2">¿Cómo funciona?</h5>
             <p class="small opacity-75">Siga estos pasos para un control de inventario preciso:</p>
           </div>
           
           <div class="d-flex mb-4">
             <div class="me-3"><span class="badge bg-white text-brown rounded-circle">1</span></div>
             <div>
                <h6 class="fw-bold mb-1">Define la Receta</h6>
                <p class="extrasmall opacity-75 mb-0">Especifique qué insumos consume un producto elaborado y su rendimiento base.</p>
             </div>
           </div>

           <div class="d-flex mb-4">
             <div class="me-3"><span class="badge bg-white text-brown rounded-circle">2</span></div>
             <div>
                <h6 class="fw-bold mb-1">Ejecuta la Orden</h6>
                <p class="extrasmall opacity-75 mb-0">Al producir, el sistema calcula la proporción de ingredientes necesaria.</p>
             </div>
           </div>

           <div class="d-flex mb-4">
             <div class="me-3"><span class="badge bg-white text-brown rounded-circle">3</span></div>
             <div>
                <h6 class="fw-bold mb-1">Sincronización de Stock</h6>
                <p class="extrasmall opacity-75 mb-0">Se restan los insumos y se suma el producto terminado automáticamente.</p>
             </div>
           </div>

           <div class="mt-auto pt-4 border-top border-white border-opacity-10 text-center">
              <p class="extrasmall m-0">Gestione mermas e ingredientes para mantener la utilidad real de su negocio.</p>
           </div>
        </div>
      </div>
    </div>

    <!-- Modal de Recetas -->
    <RecetaModal 
      v-model="showRecipeModal" 
      :receta="recetaParaEditar"
      @saved="produccionStore.fetchRecetas" 
    />
  </div>
</template>

<style scoped>
.produccion-view {
  min-height: 80vh;
}
.hover-shadow:hover {
  box-shadow: 0 0.75rem 1.5rem rgba(69, 26, 3, 0.1) !important;
  transform: translateY(-3px);
  border-color: #fce7cf !important;
}
.transition {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.text-brown { color: #451a03; }
.bg-brown { background-color: #451a03 !important; }
.bg-cream { background-color: #fffaf5; }
.btn-icon-sm {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}
.opacity-20 { opacity: 0.2; }
.bg-white.border { border-color: #eee !important; }

/* Dashboard adjustments if needed */
</style>



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
