<template>
  <div class="proveedores-view animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold m-0 text-brown"><i class="fas fa-truck me-2"></i> Gestión de Proveedores</h3>
      <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openModal()">
        <i class="fas fa-plus me-2"></i> Nuevo Proveedor
      </button>
    </div>

    <!-- Filtros/Búsqueda opcional -->
    <div class="card border-0 rounded-4 shadow-sm p-3 mb-4">
       <div class="input-group">
          <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
          <input v-model="search" type="text" class="form-control border-0 bg-light" placeholder="Buscar por RUC o Razón Social...">
       </div>
    </div>

    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">RUC</th>
              <th>Razón Social</th>
              <th>Contacto</th>
              <th>Teléfono</th>
              <th>Email</th>
              <th class="text-center">Estado</th>
              <th class="pe-4 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="prov in proveedoresFiltrados" :key="prov.id">
              <td class="ps-4 font-monospace small">{{ prov.ruc }}</td>
              <td class="fw-bold">{{ prov.razon_social }}</td>
              <td>{{ prov.contacto_nombre || '-' }}</td>
              <td>{{ prov.telefono || '-' }}</td>
              <td>{{ prov.email || '-' }}</td>
              <td class="text-center">
                <span v-if="prov.activo" class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3">Activo</span>
                <span v-else class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3">Inactivo</span>
              </td>
              <td class="pe-4 text-center">
                <div class="btn-group btn-group-sm">
                  <button @click="openModal(prov)" class="btn btn-outline-primary border-0 rounded-pill mx-1" title="Editar">
                    <i class="fas fa-edit"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="proveedoresFiltrados.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="fas fa-truck-loading fa-3x mb-3 opacity-25"></i>
                <p>No se encontraron proveedores</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal de Proveedor -->
    <div class="modal fade" id="proveedorModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 bg-brown text-white rounded-top-4 p-4">
            <h5 class="modal-title fw-bold">
              <i class="fas" :class="isEditing ? 'fa-edit' : 'fa-plus'"></i>
              {{ isEditing ? ' Editar Proveedor' : ' Nuevo Proveedor' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="cerrarModal"></button>
          </div>
          <form @submit.prevent="guardarProveedor">
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-bold small text-muted">RUC (11 dígitos)</label>
                  <input v-model="form.ruc" type="text" class="form-control rounded-3 border-2" maxlength="11" required placeholder="12345678901">
                </div>
                <div class="col-md-8">
                  <label class="form-label fw-bold small text-muted">Razón Social</label>
                  <input v-model="form.razon_social" type="text" class="form-control rounded-3 border-2" required placeholder="Nombre de la empresa">
                </div>
                
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">Nombre Comercial (Opcional)</label>
                  <input v-model="form.nombre_comercial" type="text" class="form-control rounded-3 border-2" placeholder="Ej. Panificación S.A.">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">Persona de Contacto</label>
                  <input v-model="form.contacto_nombre" type="text" class="form-control rounded-3 border-2" placeholder="Nombre completo">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">Teléfono / Celular</label>
                  <input v-model="form.telefono" type="text" class="form-control rounded-3 border-2" placeholder="987 654 321">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">Email</label>
                  <input v-model="form.email" type="email" class="form-control rounded-3 border-2" placeholder="correo@proveedor.com">
                </div>

                <div class="col-md-12">
                  <label class="form-label fw-bold small text-muted">Dirección</label>
                  <input v-model="form.direccion" type="text" class="form-control rounded-3 border-2" placeholder="Dirección completa">
                </div>

                <div v-if="isEditing" class="col-md-12">
                   <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="provActive" v-model="form.activo">
                    <label class="form-check-label fw-bold small" for="provActive">Proveedor Activo</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
              <button type="button" class="btn btn-light rounded-pill px-4" @click="cerrarModal">Cancelar</button>
              <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar Proveedor' : 'Guardar Proveedor' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useProveedorStore } from '@/stores/proveedor.store';
import Swal from 'sweetalert2';
import { useModal } from '@/composables/useModal';

const proveedorStore = useProveedorStore();
const search = ref('');
const isEditing = ref(false);
const currentId = ref(null);
const loading = ref(false);

const { show: showModal, hide: hideModal } = useModal('proveedorModal');

const form = ref({
  ruc: '',
  razon_social: '',
  nombre_comercial: '',
  contacto_nombre: '',
  telefono: '',
  email: '',
  direccion: '',
  activo: true
});

const resetForm = () => {
  form.value = {
    ruc: '',
    razon_social: '',
    nombre_comercial: '',
    contacto_nombre: '',
    telefono: '',
    email: '',
    direccion: '',
    activo: true
  };
};

onMounted(async () => {
  await proveedorStore.fetchProveedores();
});

const proveedoresFiltrados = computed(() => {
  const lista = proveedorStore.proveedores || [];
  if (!search.value) return lista;
  
  const searchTerm = search.value.toLowerCase();
  return lista.filter(p => 
    p.razon_social.toLowerCase().includes(searchTerm) || 
    p.ruc.includes(searchTerm)
  );
});

const openModal = (proveedor = null) => {
  if (proveedor) {
    isEditing.value = true;
    currentId.value = proveedor.id;
    form.value = { ...proveedor };
  } else {
    isEditing.value = false;
    currentId.value = null;
    resetForm();
  }
  showModal();
};

const cerrarModal = () => {
  hideModal();
};

const guardarProveedor = async () => {
  if (form.value.ruc.length !== 11) {
    Swal.fire('Error', 'El RUC debe tener exactamente 11 dígitos', 'error');
    return;
  }

  loading.value = true;
  try {
    if (isEditing.value) {
      await proveedorStore.updateProveedor(currentId.value, form.value);
      Swal.fire('¡Éxito!', 'Proveedor actualizado correctamente', 'success');
    } else {
      await proveedorStore.crearProveedor(form.value);
      Swal.fire('¡Éxito!', 'Proveedor registrado correctamente', 'success');
    }
    cerrarModal();
  } catch (error) {
    const msg = error.response?.data?.message || 'Ocurrió un error inesperado';
    Swal.fire('Error', msg, 'error');
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.text-brown { color: #4b2c20; }
.bg-brown { background-color: #4b2c20; }
.btn-primary { background-color: #d97706; border-color: #d97706; }
.btn-primary:hover { background-color: #b45309; border-color: #b45309; }
.text-primary { color: #d97706 !important; }

.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; }
.bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1) !important; }

.table thead th {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  color: #6b7280;
}

.form-control:focus, .form-select:focus {
  border-color: #d97706;
  box-shadow: 0 0 0 0.25rem rgba(217, 119, 6, 0.1);
}

.border-2 {
  border-width: 2px !important;
}
</style>
