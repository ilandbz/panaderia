<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { useProductStore } from '@/stores/product.store';
import { useVentaStore } from '@/stores/venta.store';
import { useCajaStore } from '@/stores/caja.store';
import { useClienteStore } from '@/stores/cliente.store';
import Swal from 'sweetalert2';
import { useModal } from '@/composables/useModal';
import ClienteQuickModal from '@/components/pos/ClienteQuickModal.vue';
import VentaResultadoModal from '@/components/pos/VentaResultadoModal.vue';

const productStore = useProductStore();
const ventaStore = useVentaStore();
const cajaStore = useCajaStore();
const clienteStore = useClienteStore();
const loadingPdf = ref(false);
const search = ref('');
const selectedCategory = ref(0);
const cart = ref([]);
const tipoComprobante = ref('ticket');
const conIgv = ref(false);
const lastVenta = ref(null);
const montoRecibido = ref(0);

const { show: showPay, hide: hidePay } = useModal('payModal');
const { show: showResult, hide: hideResult } = useModal('ventaResultadoModal');
const { show: showClienteModal, hide: hideClienteModal } = useModal('clienteQuickModal');
const { show: showVariants, hide: hideVariants } = useModal('variantModal');

const selectedParent = ref(null);

const selectedCliente = ref(null);
const searchCliente = ref('');
const clientesEncontrados = ref([]);
const mostrarResultadosCliente = ref(false);

const total = computed(() => {
  return cart.value.reduce((acc, item) => acc + (item.cantidad * item.precio_venta), 0);
});

const subtotalDetalle = computed(() => {
  return conIgv.value ? total.value / 1.18 : total.value;
});

const igvDetalle = computed(() => {
  return conIgv.value ? total.value - subtotalDetalle.value : 0;
});

const handleKeydown = (event) => {
  if (event.key === 'F8') {
    event.preventDefault();
    processPayment();
  }
  if (event.key === 'Enter' && lastVenta.value) {
    nuevaVenta();
  }
};

onMounted(async () => {
  // Configurar paginación para POS
  productStore.pagination.per_page = 12;
  productStore.pagination.current_page = 1;
  
  await productStore.fetchProducts({ all: true, activos: true });
  await productStore.fetchCategories();
  await cajaStore.fetchEstadoCaja();
  await inicializarClienteDefecto();
  window.addEventListener('keydown', handleKeydown);
});

watch(search, (val) => {
    productStore.filters.search = val;
    productStore.pagination.current_page = 1;
});

watch(selectedCategory, (val) => {
    productStore.filters.categoria_id = val === 0 ? '' : val;
    productStore.pagination.current_page = 1;
});

const inicializarClienteDefecto = async () => {
  const result = await clienteStore.fetchClientes('00000000');
  if (result.length > 0) {
    selectedCliente.value = result[0];
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

const seleccionarCliente = (cliente) => {
  selectedCliente.value = cliente;
  searchCliente.value = '';
  clientesEncontrados.value = [];
  mostrarResultadosCliente.value = false;
};

const quitCliente = () => {
  selectedCliente.value = null;
  inicializarClienteDefecto();
};

const onClienteSaved = async (cliente) => {
  hideClienteModal();
  if (lastVenta.value) {
    // Si estamos en el modal de resultado, vinculamos el cliente a la venta
    try {
        Swal.showLoading();
        await ventaStore.actualizarVenta(lastVenta.value.id, { cliente_id: cliente.id });
        lastVenta.value.cliente = cliente;
        lastVenta.value.cliente_id = cliente.id;
        Swal.fire('Éxito', 'Cliente asignado a la venta', 'success');
    } catch (error) {
        Swal.fire('Error', 'No se pudo asignar el cliente a la venta', 'error');
    }
  } else {
    // Si estamos en medio de una venta normal
    seleccionarCliente(cliente);
  }
};

const onNuevoCliente = () => {
    showClienteModal();
};

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown);
});

const categories = computed(() => [
  { id: 0, nombre: 'Todas' },
  ...productStore.categories
]);

const totalPages = computed(() => productStore.totalPages);

const paginatedProducts = computed(() => productStore.paginatedProducts);

// El reset de página ya lo manejamos en el watch de search/selectedCategory que actualizan el store

const addToCart = (product) => {
  if (!cajaStore.isCajaAbierta) {
    Swal.fire('Atención', 'Debe abrir caja para realizar ventas', 'warning');
    return;
  }

  // Si tiene variantes y no se ha seleccionado una variante específica todavía (viniendo del grid)
  if (product.variantes && product.variantes.length > 0 && !product.parent_id) {
    selectedParent.value = product;
    showVariants();
    return;
  }
  
  const existing = cart.value.find(item => item.id === product.id);
  const currentQty = existing ? existing.cantidad : 0;

  if (product.stock <= currentQty) {
    Swal.fire('Sin Stock', `No hay suficientes unidades de ${product.nombre} (Disponibles: ${product.stock})`, 'error');
    return;
  }

  const nombreMostrar = product.parent_id ? `${product.nombre} (${product.nombre_variante})` : product.nombre;

  if (existing) {
    existing.cantidad++;
  } else {
    cart.value.push({ ...product, nombre_completo: nombreMostrar, cantidad: 1 });
  }
  
  if (product.parent_id) {
    hideVariants();
  }
};

const addToCartByAmount = (product, amount) => {
  if (!cajaStore.isCajaAbierta) {
    Swal.fire('Atención', 'Debe abrir caja para realizar ventas', 'warning');
    return;
  }
  
  // Calcular cantidad basada en monto de soles redondeando hacia abajo
  const qty = Math.floor(amount / product.precio_venta);
  
  if (qty <= 0) {
    Swal.fire('Atención', `El monto S/ ${amount} es insuficiente para comprar ${product.nombre} (Precio: S/ ${product.precio_venta})`, 'info');
    return;
  }

  const existing = cart.value.find(item => item.id === product.id);
  const currentTotalQty = (existing ? existing.cantidad : 0) + qty;

  if (product.stock < currentTotalQty) {
    Swal.fire('Sin Stock', `Solo hay ${product.stock} unidades de ${product.nombre} disponibles`, 'error');
    return;
  }

  if (existing) {
    existing.cantidad += qty;
  } else {
    cart.value.push({ ...product, cantidad: qty });
  }
};

const updateQuantity = (id, newQty) => {
  const item = cart.value.find(i => i.id === id);
  if (!item) return;

  const qty = parseFloat(newQty);

  if (isNaN(qty) || qty < 1) {
    item.cantidad = 1;
    return;
  }

  if (qty > item.stock) {
    Swal.fire('Stock Insuficiente', `Solo hay ${item.stock} unidades de ${item.nombre}`, 'warning');
    item.cantidad = item.stock;
    return;
  }

  item.cantidad = qty;
};

const removeFromCart = (id) => {
  cart.value = cart.value.filter(item => item.id !== id);
};

const processPayment = () => {
  if (cart.value.length === 0) return;

  // Validaciones SUNAT previas
  if (tipoComprobante.value === 'factura') {
    if (!selectedCliente.value || selectedCliente.value.tipo_documento !== 'RUC') {
      Swal.fire('Atención', 'La Factura requiere un cliente con RUC', 'warning');
      return;
    }
  }

  if (tipoComprobante.value === 'boleta' && total.value > 700) {
    if (!selectedCliente.value || selectedCliente.value.numero_documento === '00000000') {
      Swal.fire('Atención', 'Boletas mayores a S/ 700 requieren identificación del cliente', 'warning');
      return;
    }
  }

  montoRecibido.value = total.value;
  showPay();
  nextTick(() => {
    const input = document.getElementById('montoRecibidoInput');
    if (input) {
      input.focus();
      input.select();
    }
  });
};

const confirmarVenta = async () => {
  if (montoRecibido.value < total.value) {
    Swal.fire('Error', 'Monto recibido insuficiente', 'error');
    return;
  }
  try {
    Swal.showLoading();
    const ventaData = {
      cliente_id: selectedCliente.value?.id,
      subtotal: subtotalDetalle.value,
      igv: igvDetalle.value,
      total: total.value,
      monto_pagado: parseFloat(montoRecibido.value),
      vuelto: (montoRecibido.value - total.value),
      forma_pago: 'efectivo',
      tipo_comprobante: tipoComprobante.value,
      items: cart.value.map(item => ({
        producto_id: item.id,
        cantidad: item.cantidad,
        precio_unitario: item.precio_venta,
        subtotal: item.cantidad * item.precio_venta
      }))
    };
    hidePay();
    const result = await ventaStore.registrarVenta(ventaData);
    Swal.close();
    lastVenta.value = result.data || result;
    cart.value = [];
    conIgv.value = false;

    await productStore.fetchProducts();
    await nextTick();
    showResult();
  } catch (error) {
    Swal.close(); 
    Swal.fire('Error', error.message || 'Error al guardar la venta', 'error');
  }
};

const nuevaVenta = () => {
  hideResult();
  lastVenta.value = null;
  search.value = '';
};
</script>

<template>
  <div class="pos-view h-100 d-flex flex-column bg-light">
    <div class="row g-3 flex-grow-1 no-print p-3">
      <div class="col-lg-8 h-100 d-flex flex-column">
        <div class="card border-0 rounded-4 shadow-sm mb-3">
          <div class="card-body p-3 bg-white">
            <div class="input-group mb-3 bg-light rounded-pill px-3 py-1">
              <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
              <input v-model="search" type="text" class="form-control border-0 bg-transparent" placeholder="Buscar por nombre o código...">
            </div>           
            <div class="categories-tabs d-flex gap-2 overflow-auto pb-2 scroll-hide">
              <button v-for="cat in categories" :key="cat.id" 
                      @click="selectedCategory = cat.id"
                      class="btn btn-sm rounded-pill px-4 text-nowrap transition-all"
                      :class="selectedCategory === cat.id ? 'btn-primary shadow' : 'btn-outline-secondary border-0 bg-light'">
                {{ cat.nombre }}
              </button>
            </div>
          </div>
        </div>

        <div class="products-grid flex-grow-1 overflow-auto pe-2 custom-scrollbar">
          <!-- Spinner de carga -->
          <div v-if="productStore.loading" class="d-flex flex-column align-items-center justify-content-center py-5 mt-3">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted small fw-semibold">Cargando productos...</p>
          </div>

          <!-- Grid de productos -->
          <div v-else>
            <div v-if="paginatedProducts.length === 0" class="text-center py-5 text-muted">
              <div class="display-4 mb-3 opacity-25"><i class="fas fa-box-open"></i></div>
              <p class="fw-bold">No se encontraron productos</p>
            </div>

            <div class="row g-3">
              <div v-for="product in paginatedProducts" :key="product.id" class="col-6 col-md-3">
                  <div class="card product-card border-0 rounded-4 shadow-sm h-100 position-relative transition-all" @click="addToCart(product)">
                    <div v-if="product.variantes?.length" class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-primary rounded-pill small shadow-sm">
                            <i class="fas fa-layer-group me-1"></i>{{ product.variantes.length }} opciones
                        </span>
                    </div>
                    <div class="card-img-top p-4 text-center bg-light-subtle rounded-top-4">
                     <i class="fas fa-3x opacity-25" :class="product.categoria.icono" :style="{color: product.categoria.color}"></i>
                     <span v-if="product.stock <= 0 && !product.variantes?.length"
                     class="position-absolute top-50 start-50 translate-middle badge bg-danger-subtle text-danger px-3 py-2 rounded-pill shadow-sm"
                     >SIN STOCK</span>
                  </div>
                  <div class="card-body p-3 text-center">
                    <div class="small fw-bold text-truncate text-dark mb-1">{{ product.nombre }}</div>
                    <div class="text-primary fw-bold" :class="product.variantes?.length ? 'small opacity-75' : 'fs-5'">
                        {{ product.variantes?.length ? 'Desde ' : '' }}S/ {{ product.precio_venta }}
                    </div>
                    <div class="extrasmall text-muted mt-1">Stock: <span :class="product.stock < 10 ? 'text-danger fw-bold' : ''">{{ product.stock }}</span></div>

                    <!-- Botones rápidos por monto (Soles) -->
                    <div v-if="product.categoria.nombre === 'Panadería'" 
                         class="d-flex justify-content-center gap-1 mt-2 pt-2 border-top" 
                         @click.stop>
                        <button v-for="amount in [1, 2, 5]" :key="amount" 
                                @click="addToCartByAmount(product, amount)"
                                class="btn btn-xs btn-outline-primary py-1 px-1 rounded-3 flex-grow-1" 
                                style="font-size: 0.65rem; border-style: dashed;">
                            S/ {{ amount }}
                        </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Paginación -->
            <div v-if="totalPages > 1" class="d-flex align-items-center justify-content-between mt-4 pb-2">
              <span class="small text-muted">
                Mostrando {{ (productStore.pagination.current_page - 1) * productStore.pagination.per_page + 1 }}–{{ Math.min(productStore.pagination.current_page * productStore.pagination.per_page, productStore.filteredProducts.length) }} de {{ productStore.filteredProducts.length }} productos
              </span>
              <nav aria-label="Navegación de productos">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item" :class="{ disabled: productStore.pagination.current_page === 1 }">
                    <button class="page-link rounded-start-pill" @click="productStore.pagination.current_page--" :disabled="productStore.pagination.current_page === 1">
                      <i class="fas fa-chevron-left"></i>
                    </button>
                  </li>
                  <li v-for="page in totalPages" :key="page" class="page-item" :class="{ active: productStore.pagination.current_page === page }">
                    <button class="page-link" @click="productStore.pagination.current_page = page">{{ page }}</button>
                  </li>
                  <li class="page-item" :class="{ disabled: productStore.pagination.current_page === totalPages }">
                    <button class="page-link rounded-end-pill" @click="productStore.pagination.current_page++" :disabled="productStore.pagination.current_page === totalPages">
                      <i class="fas fa-chevron-right"></i>
                    </button>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 h-100 d-flex flex-column">
        <div class="card border-0 rounded-4 shadow-sm flex-grow-1 d-flex flex-column overflow-hidden">
          <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
             <h5 class="fw-bold m-0 text-dark"><i class="fas fa-shopping-basket me-2 text-primary"></i> Carrito</h5>
             <button class="btn btn-sm btn-light rounded-pill px-3 text-danger border-0" @click="cart = []">Vaciar</button>
          </div>
          
          <div class="cart-items flex-grow-1 overflow-auto p-4 custom-scrollbar">
            <div v-if="cart.length === 0" class="text-center text-muted py-5 mt-5">
              <div class="display-3 mb-3 opacity-25"><i class="fas fa-shopping-cart"></i></div>
              <p class="fw-bold">El carrito está vacío</p>
              <p class="small">Selecciona productos para comenzar</p>
            </div>
            <div v-for="item in cart" :key="item.id" class="cart-item mb-4 d-flex align-items-center justify-content-between animate__animated animate__fadeIn">
              <div class="flex-grow-1">
                <div class="small fw-bold text-dark text-truncate mb-1" style="max-width: 180px;">{{ item.nombre_completo || item.nombre }}</div>
                <div class="extrasmall text-muted d-flex align-items-center mt-2">
                  <div class="input-group input-group-sm border rounded-pill overflow-hidden shadow-sm me-2" style="width: 130px;">
                    <button class="btn btn-light btn-sm border-0 px-2" @click="updateQuantity(item.id, item.cantidad - 1)" :disabled="item.cantidad <= 1">
                      <i class="fas fa-minus extrasmall"></i>
                    </button>
                    <input type="number" 
                           :value="item.cantidad" 
                           @change="e => updateQuantity(item.id, e.target.value)"
                           class="form-control form-control-sm border-0 text-center p-0 bg-transparent fw-bold" 
                           style="font-size: 0.75rem;">
                    <button class="btn btn-light btn-sm border-0 px-2" @click="updateQuantity(item.id, item.cantidad + 1)">
                      <i class="fas fa-plus extrasmall"></i>
                    </button>
                  </div>
                  <span class="ms-2">x S/ {{ item.precio_venta }}</span>
                </div>
              </div>
              <div class="d-flex align-items-center text-end ms-2">
                <div class="fw-bold text-dark fs-6 me-3">S/ {{ (item.cantidad * item.precio_venta).toFixed(2) }}</div>
                <button class="btn btn-sm btn-outline-danger border-0 rounded-circle p-2" @click="removeFromCart(item.id)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="cart-footer p-4 bg-light-subtle border-top">

            <!-- <div class="client-selection mb-4 p-3 bg-white rounded-4 shadow-sm border border-dashed">
               <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="small fw-bold text-dark"><i class="fas fa-user me-1 text-primary"></i> Cliente</span>
                  <button class="btn btn-xs btn-link p-0 text-primary small text-decoration-none fw-bold" @click="showClienteModal">+ Nuevo</button>
               </div>
               
               <div v-if="selectedCliente" class="selected-client bg-light p-2 rounded-3 d-flex align-items-center justify-content-between animate__animated animate__fadeIn">
                  <div class="small text-truncate" style="max-width: 140px;">
                    <strong class="text-dark">{{ selectedCliente.nombre_completo || selectedCliente.razon_social }}</strong>
                    <div class="extrasmall text-muted">{{ selectedCliente.tipo_documento }}: {{ selectedCliente.numero_documento }}</div>
                  </div>
                  <button class="btn btn-sm btn-link text-danger p-0" @click="quitCliente"><i class="fas fa-times"></i></button>
               </div>

               <div v-else class="position-relative">
                  <input type="text" v-model="searchCliente" @input="buscarClientes" class="form-control form-control-sm rounded-pill bg-light border-0 px-3" placeholder="Buscar cliente (DNI/RUC/Nombre)...">
                  <div v-if="clientesEncontrados.length > 0 && mostrarResultadosCliente" class="client-results position-absolute w-100 bg-white shadow-lg rounded-3 mt-2 overflow-hidden" style="z-index: 1050; max-height: 200px; overflow-y: auto;">
                    <div v-for="c in clientesEncontrados" :key="c.id" class="p-2 border-bottom cursor-pointer hover-bg-light" @click="seleccionarCliente(c)">
                      <div class="small fw-bold">{{ c.nombre_completo || c.razon_social }}</div>
                      <div class="extrasmall text-muted">{{ c.tipo_documento }}: {{ c.numero_documento }}</div>
                    </div>
                  </div>
               </div>
            </div> -->

            <div class="sale-options mb-4 p-3 bg-white rounded-4 shadow-sm border border-dashed">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="small fw-bold text-dark"><i class="fas fa-file-invoice me-1 text-primary"></i> Comprobante</span>
                <select v-model="tipoComprobante" class="form-select form-select-sm border-0 bg-light rounded-pill w-50">
                  <option value="ticket">Ticket</option>
                  <option value="boleta">Boleta</option>
                  <option value="factura">Factura</option>
                </select>
              </div>

              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <span class="small fw-bold text-dark"><i class="fas fa-percentage me-1 text-primary"></i> Impuestos</span>
                  <p class="extrasmall text-muted mb-0">Aplicar IGV (18%)</p>
                </div>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input custom-switch" type="checkbox" v-model="conIgv" role="switch">
                </div>
              </div>
            </div>

            <div class="summary-details mb-3">
              <div class="d-flex justify-content-between mb-2 small text-muted">
                <span>Subtotal Neto</span>
                <span>S/ {{ subtotalDetalle.toFixed(2) }}</span>
              </div>
              <div v-if="conIgv" class="d-flex justify-content-between mb-2 small text-muted animate__animated animate__fadeIn">
                <span>IGV Desglosado</span>
                <span>S/ {{ igvDetalle.toFixed(2) }}</span>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-end mb-4 pt-2 border-top border-2">
               <span class="h6 text-uppercase tracking-widest text-muted fw-bold">Total Final</span>
               <span class="h1 fw-bold text-primary m-0">S/ {{ total.toFixed(2) }}</span>
            </div>
            
            <button class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-lg border-0 btn-lg text-uppercase tracking-widest" 
                    :disabled="cart.length === 0" @click="processPayment">
                PROCESAR PAGO (F8)
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para Cobrar (PayModal) -->
    <div class="modal fade no-print" id="payModal" tabindex="-1" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5 overflow-hidden">
          <div class="modal-header border-0 bg-primary text-white p-5 text-center d-block position-relative">
            <h4 class="modal-title fw-bold text-uppercase tracking-widest"><i class="fas fa-cash-register me-2"></i> Cobro</h4>
            <p class="opacity-75 mb-0 small">Panadería & Pastelería Jara</p>
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-4" @click="hidePay"></button>
          </div>
          <div class="modal-body p-5">
            <div class="text-center mb-5 bg-primary-subtle p-4 rounded-5 border border-primary-subtle">
              <h1 class="display-3 fw-bold text-primary mb-0">S/ {{ total.toFixed(2) }}</h1>
              <p class="text-primary text-uppercase small fw-bold tracking-widest mb-0 opacity-75">Importe Total</p>
            </div>
            <div class="mb-5 position-relative">
              <label class="form-label fw-bold small text-muted text-uppercase tracking-widest mb-3">Cantidad Recibida</label>
              <div class="input-group input-group-lg shadow-sm border rounded-4 overflow-hidden focus-ring">
                <span class="input-group-text bg-white border-0 fs-2 text-muted fw-light">S/</span>
                <input type="number" id="montoRecibidoInput" v-model="montoRecibido" 
                       class="form-control border-0 fs-1 fw-bold text-dark text-center" 
                       @keyup.enter="confirmarVenta" step="0.10" autofocus>
              </div>
            </div>
            <div class="p-4 rounded-5 border-2 text-center" 
                 :class="montoRecibido < total ? 'bg-danger-subtle border-danger-subtle text-danger' : 'bg-success-subtle border-success-subtle text-success'">
              <div class="small fw-bold text-uppercase tracking-widest mb-2">
                {{ montoRecibido < total ? 'Faltante a pagar' : 'Cambio para entregar' }}
              </div>
              <div class="display-4 fw-bold mb-0">
                S/ {{ Math.abs(montoRecibido - total).toFixed(2) }}
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 p-5 pt-0">
            <button class="btn btn-primary w-100 py-4 rounded-4 fw-bold shadow-lg text-uppercase tracking-widest btn-lg" 
                    :disabled="montoRecibido < total" @click="confirmarVenta">
              <i class="fas fa-check-circle me-2"></i> FINALIZAR VENTA
            </button>
          </div>
        </div>
      </div>
    </div>

    <VentaResultadoModal 
      :venta="lastVenta || {}" 
      :is-success="true" 
      @close="nuevaVenta" 
      @nuevo-cliente="onNuevoCliente"
    />

    <ClienteQuickModal @saved="onClienteSaved" />

    <!-- Modal de Selección de Variantes -->
    <div class="modal fade no-print" id="variantModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-5 overflow-hidden">
          <div class="modal-header border-0 bg-primary text-white p-4">
             <h5 class="modal-title fw-bold"><i class="fas fa-layer-group me-2"></i> {{ selectedParent?.nombre }}</h5>
             <button type="button" class="btn-close btn-close-white" @click="hideVariants"></button>
          </div>
          <div class="modal-body p-4 bg-light">
             <p class="text-muted small mb-4 fw-bold text-uppercase tracking-widest">Seleccione una presentación:</p>
             <div class="row g-3">
                <div v-for="v in selectedParent?.variantes" :key="v.id" class="col-6 col-md-4">
                    <div class="card variant-card border-0 rounded-4 shadow-sm h-100 cursor-pointer transition-all" 
                         :class="v.stock <= 0 ? 'opacity-50 grayscale' : 'hover-lift'"
                         @click="v.stock > 0 && addToCart(v)">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small">{{ v.nombre_variante }}</span>
                                <span class="badge rounded-pill small" :class="v.stock > 10 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                                    Stock: {{ v.stock }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted extrasmall">{{ v.codigo || 'S/N' }}</span>
                                <span class="fw-bold text-primary mb-0 small font-monospace">
                                  S/ {{ v.precio_venta }}
                                </span>
                            </div>
                        </div>
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
.pos-view { background-color: #f8f9fa; }
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 10px; }
.scroll-hide::-webkit-scrollbar { display: none; }

.product-card {
  cursor: pointer;
  border: 1px solid rgba(0,0,0,0.05) !important;
}
.product-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important;
}

.variant-card {
    border: 1px solid #edf2f7 !important;
}
.hover-lift:hover {
    transform: translateY(-5px);
    border-color: var(--bs-primary) !important;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
    background-color: #fff;
}
.grayscale { filter: grayscale(1); }

.custom-switch { width: 3.5em; height: 1.7em; cursor: pointer; }
.extrasmall { font-size: 0.7rem; }
.tracking-widest { letter-spacing: 0.1em; }
.transform-hover:hover { transform: scale(1.02); }



@media screen {
  .printable-ticket { display: none; }
}

/* Animations Helper */
.transition-all { transition: all 0.3s ease-in-out; }
.focus-ring:focus-within { 
  border-color: var(--bs-primary) !important; 
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), .25) !important; 
}

.qr-mock {
  width: 60px;
  height: 60px;
  border: 4px solid #000;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 10px;
}
</style>