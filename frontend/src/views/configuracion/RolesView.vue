<template>
  <div class="roles-view animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-brown">Roles y Permisos</h2>
        <p class="text-muted">Administra los roles del sistema y sus permisos específicos.</p>
      </div>
      <button class="btn btn-primary" @click="openModal()" :disabled="loading">
        <i class="fas fa-shield-alt me-2"></i> Nuevo Rol
      </button>
    </div>

    <!-- Filtros / Estadísticas rápidas -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card bg-brown text-white border-0 shadow-sm">
          <div class="card-body">
            <h6 class="text-white-50 small fw-bold mb-1">Total de Roles</h6>
            <h3 class="m-0 fw-bold">{{ roles.length }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Spinner de carga -->
    <div v-if="loading && roles.length === 0" class="text-center py-5">
      <div class="spinner-border text-brown" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <p class="mt-2 text-muted">Cargando roles...</p>
    </div>

    <!-- Grid de Roles -->
    <div v-else class="row g-4">
      <div v-for="rol in roles" :key="rol.id" class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-brown border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="card-title fw-bold m-0 text-brown">{{ rol.nombre }}</h5>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" @click="openModal(rol)" title="Editar">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-outline-danger" @click="handleDelete(rol)" title="Eliminar">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <div class="small text-muted mb-3">ID Interno: <code>{{ rol.name }}</code></div>
            
            <div class="permissions-list mt-3">
              <h6 class="small fw-bold text-uppercase text-muted border-bottom pb-2">Permisos asginados ({{ rol.permissions?.length || 0 }}):</h6>
              <div class="d-flex flex-wrap gap-1 mt-2 overflow-auto" style="max-height: 150px;">
                <span v-for="perm in rol.permissions" :key="perm.id" class="badge bg-light text-dark border">
                  {{ perm.name }}
                </span>
                <span v-if="!rol.permissions?.length" class="text-muted italic small">Sin permisos asignados</span>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-0 text-center pb-3">
            <small class="text-muted">Guard: {{ rol.guard_name }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-if="!loading && roles.length === 0" class="card shadow-sm border-0 text-center py-5 mt-4">
      <div class="card-body">
        <i class="fas fa-shield-halved fa-3x mb-3 d-block opacity-25"></i>
        <h5 class="text-muted">No se encontraron roles registrados.</h5>
        <p class="small text-muted mb-3">Puedes crear un nuevo rol para empezar a gestionar permisos.</p>
        <button class="btn btn-sm btn-outline-brown" @click="fetchRoles">
          <i class="fas fa-sync me-1"></i> Reintentar
        </button>
      </div>
    </div>

    <!-- Modal Rol -->
    <div class="modal fade" id="roleModal" tabindex="-1">
      <div class="modal-dialog modal-lg border-0 shadow">
        <div class="modal-content border-0">
          <div class="modal-header bg-brown text-white">
            <h5 class="modal-title fw-bold">
              {{ isEditing ? 'Editar Rol' : 'Nuevo Rol' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="saveRole">
            <div class="modal-body p-4">
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Nombre para mostrar</label>
                  <input v-model="form.nombre" type="text" class="form-control" required placeholder="Ej. Supervisor de Ventas">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Nombre interno (slug)</label>
                  <input v-model="form.name" type="text" class="form-control" required placeholder="Ej. supervisor_ventas" :disabled="isEditing">
                </div>
                <div class="col-md-6" v-if="!isEditing">
                  <label class="form-label small fw-bold">Guard de seguridad</label>
                  <select v-model="form.guard_name" class="form-select" required>
                    <option value="sanctum">Sanctum (API)</option>
                    <option value="web">Web (Standard)</option>
                  </select>
                </div>
              </div>

              <div class="permissions-selector">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Asignación de Permisos:</h6>
                <div class="row g-2 overflow-auto" style="max-height: 300px;">
                  <div v-for="permission in availablePermissions" :key="permission.id" class="col-md-4">
                    <div class="form-check p-2 border rounded">
                      <input class="form-check-input ms-0 me-2" type="checkbox" 
                             :value="permission.name" v-model="form.permisos" 
                             :id="'perm-' + permission.id">
                      <label class="form-check-label small" :for="'perm-' + permission.id">
                        {{ permission.name }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary px-4" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Guardar Cambios' : 'Crear Rol' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import RoleService from '@/services/role.service';
import Swal from 'sweetalert2';
import { useModal } from '@/composables/useModal';

const roles = ref([]);
const availablePermissions = ref([]);
const loading = ref(false);
const isEditing = ref(false);
const currentRoleId = ref(null);

const { show: showModal, hide: hideModal } = useModal('roleModal');

const form = ref({
  id: null,
  nombre: '',
  name: '',
  guard_name: 'sanctum',
  permisos: []
});

onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([fetchRoles(), fetchPermissions()]);
  } finally {
    loading.value = false;
  }
});

const fetchRoles = async () => {
  try {
    const response = await RoleService.getRoles();
    // Ajuste para manejar la respuesta del interceptor de api.service (que devuelve response.data)
    const list = response?.data ?? response ?? [];
    roles.value = Array.isArray(list) ? list : [];
  } catch (error) {
    console.error('Error al cargar roles:', error);
    roles.value = [];
  }
};

const fetchPermissions = async () => {
  try {
    const response = await RoleService.getPermissions();
    const list = response?.data ?? response ?? [];
    availablePermissions.value = Array.isArray(list) ? list : [];
  } catch (error) {
    console.error('Error al cargar permisos:', error);
  }
};

const openModal = (rol = null) => {
  if (rol) {
    isEditing.value = true;
    currentRoleId.value = rol.id;
    form.value = {
      id: rol.id,
      nombre: rol.nombre || rol.name,
      name: rol.name,
      guard_name: rol.guard_name,
      permisos: rol.permissions?.map(p => p.name) || []
    };
  } else {
    isEditing.value = false;
    currentRoleId.value = null;
    form.value = {
      id: null,
      nombre: '',
      name: '',
      guard_name: 'sanctum',
      permisos: []
    };
  }
  showModal();
};

const saveRole = async () => {
  try {
    loading.value = true;
    if (isEditing.value) {
      await RoleService.updateRole(currentRoleId.value, form.value);
      Swal.fire('¡Éxito!', 'Rol actualizado correctamente', 'success');
    } else {
      await RoleService.createRole(form.value);
      Swal.fire('¡Éxito!', 'Rol creado correctamente', 'success');
    }
    
    hideModal();
    await fetchRoles();
  } catch (error) {
    const message = error.response?.data?.message || error.message || 'Error al guardar el rol';
    Swal.fire('Error', message, 'error');
  } finally {
    loading.value = false;
  }
};

const handleDelete = async (rol) => {
  const result = await Swal.fire({
    title: '¿Eliminar Rol?',
    text: "No podrás eliminarlo si tiene usuarios asignados.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    confirmButtonText: 'Sí, eliminar'
  });

  if (result.isConfirmed) {
    try {
      await RoleService.deleteRole(rol.id);
      await fetchRoles();
      Swal.fire('Eliminado', 'El rol ha sido borrado.', 'success');
    } catch (error) {
      const msg = error.response?.data?.message || 'No se pudo eliminar el rol';
      Swal.fire('Error', msg, 'error');
    }
  }
};
</script>

<style scoped>
.text-brown { color: #451a03; }
.bg-brown { background-color: #451a03; }
.btn-outline-brown { color: #451a03; border-color: #451a03; }
.btn-outline-brown:hover { background-color: #451a03; color: #fff; }
.border-brown { border-color: #451a03 !important; }
.card { border-radius: 12px; transition: transform 0.2s; }
.card:hover { transform: translateY(-5px); }
.btn-primary { background-color: #d97706; border-color: #d97706; }
.btn-primary:hover { background-color: #b45309; border-color: #b45309; }
.permissions-list .badge { font-weight: normal; font-size: 0.7rem; }
.form-check:hover { background-color: #f9fafb; cursor: pointer; }
.form-check-input:checked { background-color: #d97706; border-color: #d97706; }
</style>
