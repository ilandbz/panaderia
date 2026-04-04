<script setup>
import { ref, onMounted } from 'vue';
import UserService from '@/services/user.service';
import RoleService from '@/services/role.service';
import Swal from 'sweetalert2';
import { useModal } from '@/composables/useModal';

const usuarios = ref([]);
const roles = ref([]);
const loading = ref(false);
const isEditing = ref(false);
const currentUserId = ref(null);

const { show: showModal, hide: hideModal } = useModal('userModal');

const form = ref({
  id: '',
  nombre: '',
  apellido: '',
  email: '',
  password: '',
  dni: '',
  telefono: '',
  rol: '',
  role_id: null
});

onMounted(async () => {
  await fetchUsers();
  await fetchRoles();
});

const fetchUsers = async () => {
  try {
    const response = await UserService.getUsers();
    const list = response?.data?.data ?? response?.data ?? [];
    usuarios.value = Array.isArray(list) ? list : [];
  } catch (error) {
    console.error('Error al cargar usuarios:', error);
    usuarios.value = [];
  }
};

const fetchRoles = async () => {
  try {
    const response = await RoleService.getRoles();
    const list = response?.data?.data ?? response?.data ?? [];
    roles.value = Array.isArray(list) ? list : [];
  } catch (error) {
    console.error('Error al cargar roles:', error);
    roles.value = [];
  }
};

const openModal = (usuario = null) => {
  if (usuario) {
    isEditing.value = true;
    currentUserId.value = usuario.id;
    form.value = {
      id: usuario.id,
      nombre: usuario.nombre,
      apellido: usuario.apellido,
      email: usuario.email,
      password: '',
      dni: usuario.dni || '',
      telefono: usuario.telefono || '',
      rol: usuario.roles?.[0]?.name || '',
      role_id: usuario.roles?.[0]?.id || null
    };
  } else {
    isEditing.value = false;
    currentUserId.value = null;
    form.value = {
      nombre: '',
      apellido: '',
      email: '',
      password: '',
      dni: '',
      telefono: '',
      rol: '',
      role_id: null
    };
  }
  showModal();
};

const saveUser = async () => {
  try {
    loading.value = true;
    // Sincronizar role_id para compatibilidad
    const selectedRole = roles.value.find(r => r.name === form.value.rol);
    if (selectedRole) form.value.role_id = selectedRole.id;

    if (isEditing.value) {
      await UserService.updateUser(currentUserId.value, form.value);
      Swal.fire('¡Éxito!', 'Usuario actualizado correctamente', 'success');
    } else {
      await UserService.createUser(form.value);
      Swal.fire('¡Éxito!', 'Usuario creado correctamente', 'success');
    }
    cerrarModal();
    await fetchUsers();
  } catch (error) {
    const message = error.response?.data?.message || 'Ocurrió un error';
    Swal.fire('Error', message, 'error');
  } finally {
    loading.value = false;
  }
};

const handleToggleStatus = async (usuario) => {
  const status = usuario.activo ? 'desactivar' : 'activar';
  const result = await Swal.fire({
    title: `¿Estás seguro?`,
    text: `Vas a ${status} el acceso de este usuario.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d97706',
    confirmButtonText: 'Sí, confirmar'
  });

  if (result.isConfirmed) {
    try {
      await UserService.toggleStatus(usuario.id);
      await fetchUsers();
      Swal.fire('¡Listo!', `El usuario ha sido ${status}ado.`, 'success');
    } catch (error) {
      Swal.fire('Error', 'No se pudo cambiar el estado', 'error');
    }
  }
};

const cerrarModal = () => {
  hideModal();
};

const handleDelete = async (usuario) => {
  const result = await Swal.fire({
    title: '¿Eliminar usuario?',
    text: "Esta acción no se puede deshacer.",
    icon: 'error',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    confirmButtonText: 'Sí, eliminar'
  });

  if (result.isConfirmed) {
    try {
      await UserService.deleteUser(usuario.id);
      await fetchUsers();
      Swal.fire('Eliminado', 'El usuario ha sido borrado.', 'success');
    } catch (error) {
      Swal.fire('Error', 'No se pudo eliminar el usuario', 'error');
    }
  }
};
</script>

<template>
  <div class="usuarios-view animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-brown">Gestión de Usuarios</h2>
        <p class="text-muted">Administra los accesos y roles del personal.</p>
      </div>
      <button class="btn btn-primary" @click="openModal()">
        <i class="fas fa-user-plus me-2"></i> Nuevo Usuario
      </button>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="px-4">Usuario</th>
                <th>Email</th>
                <th>Rol</th>
                <th>DNI / Teléfono</th>
                <th>Estado</th>
                <th class="text-end px-4">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="usuario in usuarios" :key="usuario.id">
                <td class="px-4">
                  <div class="d-flex align-items-center">
                    <div class="avatar me-3 bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                      {{ usuario.nombre[0] }}{{ usuario.apellido[0] }}
                    </div>
                    <div>
                      <div class="fw-bold text-dark">{{ usuario.nombre }} {{ usuario.apellido }}</div>
                      <small class="text-muted">ID: #{{ usuario.id }}</small>
                    </div>
                  </div>
                </td>
                <td>{{ usuario.email }}</td>
                <td>
                  <span v-for="role in usuario.roles" :key="role.id" class="badge bg-info text-dark me-1">
                    {{ role.nombre || role.name }}
                  </span>
                </td>
                <td>
                  <div class="small">{{ usuario.dni || '---' }}</div>
                  <div class="small text-muted">{{ usuario.telefono || '---' }}</div>
                </td>
                <td>
                  <span :class="['badge', usuario.activo ? 'bg-success' : 'bg-danger']">
                    {{ usuario.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="text-end px-4">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" @click="openModal(usuario)" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-warning" @click="handleToggleStatus(usuario)" :title="usuario.activo ? 'Desactivar' : 'Activar'">
                      <i class="fas" :class="usuario.activo ? 'fa-user-slash' : 'fa-user-check'"></i>
                    </button>
                    <button class="btn btn-outline-danger" @click="handleDelete(usuario)" title="Eliminar">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="usuarios.length === 0">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="fas fa-users fa-3x mb-3 d-block opacity-25"></i>
                  No se encontraron usuarios registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Usuario -->
    <div class="modal fade" id="userModal" tabindex="-1" ref="modalElement">
      <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-brown text-white">
            <h5 class="modal-title fw-bold">
              {{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="cerrarModal"></button>
          </div>
          <form @submit.prevent="saveUser">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Nombre</label>
                  <input v-model="form.nombre" type="text" class="form-control" required placeholder="Ej. Juan">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Apellido</label>
                  <input v-model="form.apellido" type="text" class="form-control" required placeholder="Ej. Pérez">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Email</label>
                  <input v-model="form.email" type="email" class="form-control" required placeholder="email@ejemplo.com">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Contraseña {{ isEditing ? '(Opcional)' : '' }}</label>
                  <input v-model="form.password" type="password" class="form-control" :required="!isEditing" placeholder="********">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">DNI</label>
                  <input v-model="form.dni" type="text" class="form-control" placeholder="Opcional">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Teléfono</label>
                  <input v-model="form.telefono" type="text" class="form-control" placeholder="Opcional">
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Rol de Usuario</label>
                  <select v-model="form.rol" class="form-select" required>
                    <option value="" hidden>Seleccionar rol</option>
                    <option v-for="rol in roles" :key="rol.id" :value="rol.name">
                      {{ rol.nombre }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary px-4" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.text-brown { color: #451a03; }
.bg-brown { background-color: #451a03; }
.card { border-radius: 12px; }
.btn-primary { background-color: #d97706; border-color: #d97706; }
.btn-primary:hover { background-color: #b45309; border-color: #b45309; }
.avatar { font-weight: bold; font-size: 0.9rem; }
</style>
