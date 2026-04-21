<script setup>
import { onMounted, ref, watch } from 'vue';
import { useProductStore } from '@/stores/product.store';
import Swal from 'sweetalert2';
import MovimientosModal from '@/components/inventario/MovimientosModal.vue';
import AjusteStockModal from '@/components/inventario/AjusteStockModal.vue';
import { useModal } from '@/composables/useModal';

const productStore = useProductStore();
const search = ref(productStore.filters.search);
const categoryFilter = ref(productStore.filters.categoria_id);
const loading = ref(false);
const isEditing = ref(false);
const currentId = ref(null);
const selectedProduct = ref(null);
const expandedRows = ref([]); // Almacena IDs de productos padres expandidos

const { show: showModal, hide: hideModal } = useModal('productModal');
const { show: showKardex, hide: hideKardex } = useModal('kardexModal', {
  onClose: () => { selectedProduct.value = null; }
});
const { show: showAjuste, hide: hideAjuste } = useModal('ajusteModal', {
  onClose: () => { selectedProduct.value = null; }
});

const form = ref({
  nombre: '',
  nombre_variante: '',
  parent_id: '',
  categoria_id: '',
  tipo: 'reventa',
  precio_venta: 0,
  costo: 0,
  stock_minimo: 0,
  unidad_medida: 'UND',
  codigo: '',
  activo: true
});

const changePage = (page) => {
  if (page >= 1 && page <= productStore.totalPages) {
     productStore.pagination.current_page = page;
     window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

onMounted(async () => {
    loading.value = true;
    await productStore.fetchProducts({ all: true });
    await productStore.fetchCategories();
    loading.value = false;
});

watch(search, (val) => {
    productStore.filters.search = val;
    productStore.pagination.current_page = 1;
});

watch(categoryFilter, (val) => {
    productStore.filters.categoria_id = val;
    productStore.pagination.current_page = 1;
});

const openModal = (producto = null) => {
  if (producto) {
    isEditing.value = true;
    currentId.value = producto.id;
    form.value = {
      nombre: producto.nombre,
      nombre_variante: producto.nombre_variante || '',
      parent_id: producto.parent_id || '',
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
      nombre_variante: '',
      parent_id: '',
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

const toggleExpand = (id) => {
  const index = expandedRows.value.indexOf(id);
  if (index > -1) {
    expandedRows.value.splice(index, 1);
  } else {
    expandedRows.value.push(id);
  }
};

const handleEdit = (producto) => {
  selectedProduct.value = { ...producto };
  showKardex();
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
          <i class="fas fa-boxes text-primary me-2"></i>Gestión de Productos ({{ productStore.pagination.total }})
        </h3>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openModal()">
          <i class="fas fa-plus me-2"></i> Nuevo Producto
        </button>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-5">
           <div class="input-group search-box shadow-sm">
             <span class="input-group-text bg-white border-0 text-muted"><i class="fas fa-search"></i></span>
             <input
               v-model="search"
               type="text"
               class="form-control border-0 bg-white"
               placeholder="Buscar por nombre o código..."
             >
           </div>
         </div>
         <div class="col-lg-7">
            <div class="category-filters d-flex gap-2 overflow-auto pb-2 scrollbar-hidden">
                <button 
                  class="btn category-btn" 
                  :class="categoryFilter === '' ? 'active' : ''"
                  @click="categoryFilter = ''"
                >
                  <i class="fas fa-th-large me-2"></i> Todas
                </button>
                <button 
                  v-for="cat in productStore.categories" 
                  :key="cat.id"
                  class="btn category-btn"
                  :class="categoryFilter == cat.id ? 'active' : ''"
                  @click="categoryFilter = cat.id"
                >
                  <i v-if="cat.nombre.toLowerCase().includes('pan')" class="fas fa-wheat-awn me-2"></i>
                  <i v-else-if="cat.nombre.toLowerCase().includes('pastel')" class="fas fa-cake-candles me-2"></i>
                  <i v-else-if="cat.nombre.toLowerCase().includes('abarro')" class="fas fa-box me-2"></i>
                  <i v-else :class="['fas', cat.icono || 'fa-tag', 'me-2']"></i>
                  {{ cat.nombre }}
                </button>
            </div>
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
            <template v-for="producto in productStore.paginatedProducts" :key="producto?.id">
              <!-- Fila Padre -->
              <tr :class="{ 'bg-light-subtle': producto.variantes?.length > 0 }">
                <td><span class="badge bg-light text-dark font-monospace border">{{ producto.codigo || 'S/N' }}</span></td>
                <td class="fw-bold text-dark">
                    <div class="d-flex align-items-center">
                        <button 
                            v-if="producto.variantes?.length" 
                            class="btn btn-xs btn-link text-primary p-0 me-2" 
                            @click="toggleExpand(producto.id)"
                        >
                            <i class="fas" :class="expandedRows.includes(producto.id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                        </button>
                        <span>{{ producto.nombre }}</span>
                    </div>
                    <div v-if="producto.variantes?.length" class="extrasmall text-primary mt-1 ms-3">
                        <i class="fas fa-layer-group me-1"></i>{{ producto.variantes.length }} variantes
                    </div>
                </td>
                <td>
                  <i v-if="producto.categoria?.icono" :class="['fas', producto.categoria.icono, 'me-1']" :style="{ color: producto.categoria.color || 'inherit' }"></i>
                  {{ producto.categoria?.nombre || 'Sin categoría' }}
                </td>
                <td>
                  <span class="badge rounded-pill" :class="producto.tipo === 'elaborado' ? 'bg-info-subtle text-info' : producto.tipo === 'reventa' ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-warning'">
                    {{ producto.tipo.toUpperCase() }}
                  </span>
                </td>
                <td class="text-center">
                  <span :class="['fw-bold', producto.stock <= producto.stock_minimo ? 'text-danger' : 'text-dark']">
                    {{ producto.stock }}
                  </span>
                  <span class="extrasmall text-muted ms-1">{{ producto.unidad_medida }}</span>
                </td>
                <td class="text-center">
                    <span v-if="producto.variantes?.length" class="text-muted small italic">Desde</span>
                    <span class="fw-bold text-primary">S/ {{ producto.precio_venta }}</span>
                </td>
                <td class="text-center">
                  <span class="badge rounded-pill" :class="producto.activo ? 'bg-success' : 'bg-danger'">
                    {{ producto.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group shadow-sm rounded-3 overflow-hidden">
                    <button class="btn btn-sm btn-white text-primary border" @click="openModal(producto)" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-white text-info border" @click="openKardex(producto)" title="Kardex / Movimientos">
                      <i class="fas fa-history"></i>
                    </button>
                    <button class="btn btn-sm btn-white text-danger border" @click="handleDelete(producto)" title="Eliminar">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Filas de Variantes (Solo si está expandido) -->
              <tr v-if="expandedRows.includes(producto.id)" v-for="variante in producto.variantes" :key="variante.id" class="bg-light animate__animated animate__fadeIn">
                <td class="ps-4"><span class="badge bg-white text-muted font-monospace border small">{{ variante.codigo || 'S/N' }}</span></td>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-level-up-alt fa-rotate-90 text-muted me-2"></i>
                        <span class="text-muted">{{ variante.nombre_variante }}</span>
                    </div>
                </td>
                <td><span class="text-muted small">{{ producto.categoria?.nombre }}</span></td>
                <td><span class="text-muted extrasmall">VARIANTE</span></td>
                <td class="text-center">
                  <span :class="['small', variante.stock <= variante.stock_minimo ? 'text-danger fw-bold' : 'text-muted']">
                    {{ variante.stock }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="fw-bold text-primary">S/ {{ variante.precio_venta }}</span>
                </td>
                <td class="text-center">
                  <span class="badge bg-light text-muted border rounded-pill extrasmall">
                    {{ variante.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-xs shadow-none">
                    <button class="btn btn-xs btn-outline-primary border-0 p-1" @click="openModal(variante)" title="Editar Variante">
                      <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-danger border-0 p-1" @click="handleDelete(variante)" title="Eliminar Variante">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-if="productStore.paginatedProducts.length === 0 && !loading">
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                <p>No se encontraron productos.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="productStore.filteredProducts.length > 0" class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3">
        <div class="text-muted small order-2 order-md-1">
          Mostrando {{ productStore.paginatedProducts.length }} de <strong>{{ productStore.filteredProducts.length }}</strong> productos filtrados
        </div>
        <nav v-if="productStore.totalPages > 1" class="order-1 order-md-2">
          <ul class="pagination pagination-sm m-0">
            <li class="page-item" :class="{ disabled: productStore.pagination.current_page === 1 }">
              <button class="btn btn-sm btn-outline-primary rounded-pill me-2" @click="changePage(productStore.pagination.current_page - 1)" :disabled="productStore.pagination.current_page === 1">
                <i class="fas fa-chevron-left"></i>
              </button>
            </li>
            <li v-for="page in productStore.totalPages" :key="page" class="page-item mx-1">
              <button 
                class="btn btn-sm rounded-pill px-3" 
                :class="productStore.pagination.current_page === page ? 'btn-primary' : 'btn-light'"
                @click="changePage(page)"
              >
                {{ page }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: productStore.pagination.current_page === productStore.totalPages }">
              <button class="btn btn-sm btn-outline-primary rounded-pill ms-2" @click="changePage(productStore.pagination.current_page + 1)" :disabled="productStore.pagination.current_page === productStore.totalPages">
                <i class="fas fa-chevron-right"></i>
              </button>
            </li>
          </ul>
        </nav>
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
                <div class="col-md-5">
                  <label class="form-label fw-bold small">Nombre del Producto</label>
                  <input v-model="form.nombre" type="text" class="form-control rounded-3" required placeholder="Ej. Torta Selva Negra">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Variante / Presentación</label>
                  <input v-model="form.nombre_variante" type="text" class="form-control rounded-3" placeholder="Ej. Redonda 1KG, Tajada">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold small">Código / SKU</label>
                  <input v-model="form.codigo" type="text" class="form-control rounded-3" placeholder="Opcional">
                </div>
                
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Producto Padre (Opcional)</label>
                  <select v-model="form.parent_id" class="form-select rounded-3">
                    <option value="">Ninguno (Es producto Principal)</option>
                    <option v-for="p in productStore.products.filter(pr => pr.id !== currentId && !pr.parent_id)" :key="p.id" :value="p.id">
                      {{ p.nombre }}
                    </option>
                  </select>
                </div>
                <div class="col-md-4">
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
                    <input v-model="form.precio_venta"
                    type="number"
                    step="0.01"
                    class="form-control rounded-3 border-start-0"
                    required>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Costo (Opcional)</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">S/</span>
                    <input v-model="form.costo" type="number" step="0.10" class="form-control rounded-3 border-start-0">
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Unidad de Medida</label>
                  <input v-model="form.unidad_medida" type="text" class="form-control rounded-3" required placeholder="Ej. UND, KG, PQ">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold small">Stock Mínimo</label>
                  <input v-model="form.stock_minimo"
                  type="number"
                  step="1"
                  class="form-control rounded-3"
                  required>
                </div>

                <!-- Stock Inicial (Solo en nuevo) -->
                <div v-if="!isEditing" class="col-md-4">
                  <label class="form-label fw-bold small">Stock Inicial</label>
                  <input v-model="form.stock" type="number" step="1" class="form-control rounded-3">
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

.search-box { border: 1px solid #e5e7eb; border-radius: 12px; }
.search-box .input-group-text { border-radius: 12px 0 0 12px; }
.search-box .form-control { border-radius: 0 12px 12px 0; }

.category-filters::-webkit-scrollbar { display: none; }
.scrollbar-hidden { -ms-overflow-style: none; scrollbar-width: none; }

.category-btn {
  background-color: #fff;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  border-radius: 12px;
  padding: 0.6rem 1.25rem;
  font-weight: 600;
  white-space: nowrap;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.category-btn:hover {
  background-color: #f9fafb;
  border-color: #d97706;
  color: #d97706;
}

.category-btn.active {
  background-color: #d97706;
  border-color: #d97706;
  color: #fff;
  box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.2);
}

.table thead th {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  color: #6b7280;
  background-color: #f9fafb;
  border-bottom: 1px solid #edf2f7;
}

.card { border-radius: 1.25rem; }
.form-control:focus, .form-select:focus {
  border-color: #d97706;
  box-shadow: 0 0 0 0.25rem rgba(217, 119, 6, 0.1);
}
.italic { font-style: italic; }

.btn-xs {
  padding: 0.1rem 0.4rem;
  font-size: 0.75rem;
}

.btn-group-xs > .btn {
  padding: 0.1rem 0.3rem;
  font-size: 0.7rem;
}
</style>
