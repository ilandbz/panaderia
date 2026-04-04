<script setup>
import { onMounted, ref, computed } from 'vue';
import { useProductStore } from '@/stores/product.store';
import Swal from 'sweetalert2';
import MovimientosModal from '@/components/inventario/MovimientosModal.vue';
import AjusteStockModal from '@/components/inventario/AjusteStockModal.vue';
import { useModal } from '@/composables/useModal';

const productStore = useProductStore();
const search = ref('');
const categoryFilter = ref('');
const loading = ref(false);
const isEditing = ref(false);
const currentId = ref(null);
const selectedProduct = ref(null);

const { show: showModal, hide: hideModal } = useModal('productModal');
const { show: showKardex, hide: hideKardex } = useModal('kardexModal', {
  onClose: () => { selectedProduct.value = null; }
});
const { show: showAjuste, hide: hideAjuste } = useModal('ajusteModal', {
  onClose: () => { selectedProduct.value = null; }
});

const form = ref({
  nombre: '',
  categoria_id: '',
  tipo: 'reventa',
  precio_venta: 0,
  costo: 0,
  stock_minimo: 0,
  unidad_medida: 'UND',
  codigo: '',
  activo: true
});

onMounted(async () => {
  await productStore.fetchProducts();
  await productStore.fetchCategories();
});

const productosFiltrados = computed(() => {
  let lista = Array.isArray(productStore.products)
    ? productStore.products.filter(p => p && p.id)
    : [];

  if (categoryFilter.value) {
    lista = lista.filter(p => p.categoria_id == categoryFilter.value);
  }

  if (!search.value) return lista;

  const searchTerm = search.value.toLowerCase();
  return lista.filter(p =>
    (p.nombre || '').toLowerCase().includes(searchTerm) ||
    (p.codigo || '').toLowerCase().includes(searchTerm)
  );
});

const openModal = (producto = null) => {
  if (producto) {
    isEditing.value = true;
    currentId.value = producto.id;
    form.value = {
      nombre: producto.nombre,
      categoria_id: producto.categoria_id,
      tipo: producto.tipo,
      precio_venta: producto.precio_venta,
      costo: producto.costo,
      stock_minimo: producto.stock_minimo,
      unidad_medida: producto.unidad_medida,
      codigo: producto.codigo,
      activo: producto.activo
    };
  } else {
    isEditing.value = false;
    currentId.value = null;
    form.value = {
      nombre: '',
      categoria_id: '',
      tipo: 'reventa',
      precio_venta: 0,
      costo: 0,
      stock_minimo: 0,
      unidad_medida: 'UND',
      codigo: '',
      activo: true
    };
  }
  showModal();
};

const openKardex = (producto) => {
  selectedProduct.value = { ...producto };
  showKardex();
};

const openAjuste = (producto) => {
  selectedProduct.value = { ...producto };
  showAjuste();
};

const cerrarModal = () => {
  hideModal();
};

const cerrarKardex = () => {
  hideKardex();
};

const cerrarAjuste = () => {
  hideAjuste();
};

const saveProduct = async () => {
  try {
    loading.value = true;
    if (isEditing.value) {
      await productStore.updateProduct(currentId.value, form.value);
      Swal.fire('¡Actualizado!', 'El producto se actualizó correctamente.', 'success');
    } else {
      await productStore.addProduct(form.value);
      Swal.fire('¡Creado!', 'El producto se creó correctamente.', 'success');
    }
    cerrarModal();
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Error al procesar la solicitud';
    Swal.fire('Error', errorMsg, 'error');
  } finally {
    loading.value = false;
  }
};

const handleAjusteSave = async () => {
  cerrarAjuste();
  await productStore.fetchProducts();
};

const handleDelete = async (producto) => {
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: `Vas a eliminar el producto "${producto.nombre}". Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (result.isConfirmed) {
    try {
      await productStore.deleteProduct(producto.id);
      Swal.fire('Eliminado', 'El producto ha sido eliminado.', 'success');
    } catch (error) {
      Swal.fire('Error', 'No se pudo eliminar el producto.', 'error');
    }
  }
};
</script>

<template>
  <div class="productos-view animate__animated animate__fadeIn">
    <div class="card border-0 rounded-4 shadow-sm p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0 text-brown">
          <i class="fas fa-boxes text-primary me-2"></i>Gestión de Productos ({{ productosFiltrados.length }})
        </h3>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openModal()">
          <i class="fas fa-plus me-2"></i> Nuevo Producto
        </button>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-8">
           <div class="input-group search-box">
             <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
             <input
               v-model="search"
               type="text"
               class="form-control border-0 bg-light"
               placeholder="Buscar por nombre o código..."
             >
           </div>
         </div>
         <div class="col-md-4">
            <select v-model="categoryFilter" class="form-select border-0 bg-light">
              <option value="">Todas las Categorías</option>
              <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">
                {{ cat.nombre }}
              </option>
            </select>
         </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Tipo</th>
              <th>Stock</th>
              <th>Precio</th>
              <th>Estado</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="producto in productosFiltrados" :key="producto?.id">
              <td><span class="badge bg-light text-dark font-monospace border">{{ producto.codigo || 'S/N' }}</span></td>
              <td class="fw-bold text-dark">{{ producto.nombre }}</td>
              <td>{{ producto.categoria?.nombre || 'Sin categoría' }}</td>
              <td>
                <span class="badge rounded-pill bg-info-subtle text-info px-3 small border border-info-subtle">
                  {{ producto.tipo?.toUpperCase() || '—' }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <span :class="Number(producto.stock) <= Number(producto.stock_minimo) ? 'text-danger fw-bold' : 'text-dark fw-bold'">
                    {{ producto.stock ?? 0 }} {{ producto.unidad_medida || '' }}
                  </span>
                  <button @click="openAjuste(producto)" class="btn btn-sm text-primary p-1 ms-2" title="Ajustar Stock Manualmente">
                    <i class="fas fa-plus-minus small"></i>
                  </button>
                </div>
              </td>
              <td class="fw-bold text-primary">S/ {{ producto.precio_venta ?? '0.00' }}</td>
              <td>
                <span class="badge rounded-pill" :class="producto.activo ? 'bg-success' : 'bg-danger'">
                  {{ producto.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm rounded-pill bg-light p-1">
                  <button class="btn btn-outline-primary border-0 rounded-pill" @click="openModal(producto)" title="Editar">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-outline-info border-0 rounded-pill" @click="openKardex(producto)" title="Ver Kardex">
                    <i class="fas fa-history"></i>
                  </button>
                  <button class="btn btn-outline-danger border-0 rounded-pill" @click="handleDelete(producto)" title="Eliminar">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="productosFiltrados.length === 0">
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                <p>No se encontraron productos.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal de Producto (Create/Edit) -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 bg-brown text-white rounded-top-4 p-4">
            <h5 class="modal-title fw-bold">
              <i class="fas" :class="isEditing ? 'fa-edit' : 'fa-plus'"></i>
              {{ isEditing ? ' Editar Producto' : ' Nuevo Producto' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="cerrarModal"></button>
          </div>
          <form @submit.prevent="saveProduct">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label fw-bold small">Nombre del Producto</label>
                  <input v-model="form.nombre" type="text" class="form-control rounded-3" required placeholder="Ej. Pan de Molde">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Código / SKU</label>
                  <input v-model="form.codigo" type="text" class="form-control rounded-3" placeholder="Opcional">
                </div>
                
                <div class="col-md-6">
                  <label class="form-label fw-bold small">Categoría</label>
                  <select v-model="form.categoria_id" class="form-select rounded-3" required>
                    <option value="" disabled>Seleccione una categoría</option>
                    <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">
                      {{ cat.nombre }}
                    </option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small">Tipo de Producto</label>
                  <select v-model="form.tipo" class="form-select rounded-3" required>
                    <option value="reventa">Producto de Reventa (Abarrotes)</option>
                    <option value="elaborado">Producto Elaborado (Panadería/Pastelería)</option>
                    <option value="insumo">Insumo (Uso interno)</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold small">Precio de Venta</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">S/</span>
                    <input v-model="form.precio_venta" type="number" step="0.01" class="form-control rounded-3 border-start-0" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Costo (Opcional)</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">S/</span>
                    <input v-model="form.costo" type="number" step="0.01" class="form-control rounded-3 border-start-0">
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Unidad de Medida</label>
                  <input v-model="form.unidad_medida" type="text" class="form-control rounded-3" required placeholder="Ej. UND, KG, PQ">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold small">Stock Mínimo</label>
                  <input v-model="form.stock_minimo" type="number" step="0.001" class="form-control rounded-3" required>
                </div>

                <!-- Stock Inicial (Solo en nuevo) -->
                <div v-if="!isEditing" class="col-md-4">
                  <label class="form-label fw-bold small">Stock Inicial</label>
                  <input v-model="form.stock" type="number" step="0.001" class="form-control rounded-3">
                </div>

                <div class="col-md-4 d-flex align-items-end mb-1">
                   <div class="form-check form-switch p-0 ms-4 d-flex align-items-center">
                    <input class="form-check-input me-2" type="checkbox" role="switch" id="productActive" v-model="form.activo" style="width: 2.5em; height: 1.25em;">
                    <label class="form-check-label fw-bold small m-0" for="productActive">Producto Activo</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
              <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar Producto' : 'Guardar Producto' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Historial (Kardex) -->
    <div class="modal fade" id="kardexModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 bg-info text-white rounded-top-4 p-4">
            <h5 class="modal-title fw-bold">
              <i class="fas fa-history me-2"></i> Kardex de Producto
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="cerrarKardex"></button>
          </div>
          <div class="modal-body p-4 pt-2">
            <MovimientosModal v-if="selectedProduct" :producto="selectedProduct" />
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ajuste de Stock -->
    <div class="modal fade" id="ajusteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 bg-primary text-white rounded-top-4 p-4">
            <h5 class="modal-title fw-bold">
              <i class="fas fa-plus-minus me-2"></i> Ajustar Stock
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="cerrarAjuste"></button>
          </div>
          <div class="modal-body p-0">
            <AjusteStockModal v-if="selectedProduct" :producto="selectedProduct" @saved="handleAjusteSave" />
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.text-brown { color: #4b2c20; }
.bg-brown { background-color: #4b2c20; }
.btn-primary { background-color: #d97706; border-color: #d97706; }
.btn-primary:hover { background-color: #b45309; border-color: #b45309; }
.text-primary { color: #d97706 !important; }
.bg-primary { background-color: #d97706 !important; }

.search-box .input-group-text { border-radius: 10px 0 0 10px; }
.search-box .form-control { border-radius: 0 10px 10px 0; }

.table thead th {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  color: #6b7280;
}

.card { border-radius: 1.25rem; }
.form-control:focus, .form-select:focus {
  border-color: #d97706;
  box-shadow: 0 0 0 0.25rem rgba(217, 119, 6, 0.1);
}
</style>
