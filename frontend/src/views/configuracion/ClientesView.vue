<script setup>
import { ref, onMounted, computed } from 'vue';
import { useClienteStore } from '@/stores/cliente.store';
import { useAuthStore } from '@/stores/auth.store';
import Swal from 'sweetalert2';

const clienteStore = useClienteStore();
const authStore = useAuthStore();

const search = ref('');
const showModal = ref(false);
const editMode = ref(false);
const loading = ref(false);

const form = ref({
    id: null,
    tipo_documento: 'DNI',
    numero_documento: '',
    nombre_completo: '',
    razon_social: '',
    direccion: '',
    telefono: '',
    email: '',
    descuento_especial: 0,
});

const fetchClientes = async (page = 1) => {
    loading.value = true;
    try {
        await clienteStore.fetchClientes({
            search: search.value,
            page: page
        });
    } catch (error) {
        console.error('Error al cargar clientes:', error);
    } finally {
        loading.value = false;
    }
};

const abrirModal = (cliente = null) => {
    if (cliente) {
        editMode.value = true;
        form.value = { ...cliente };
    } else {
        editMode.value = false;
        form.value = {
            id: null,
            tipo_documento: 'DNI',
            numero_documento: '',
            nombre_completo: '',
            razon_social: '',
            direccion: '',
            telefono: '',
            email: '',
            descuento_especial: 0,
        };
    }
    showModal.value = true;
};

const cerrarModal = () => {
    showModal.value = false;
    document.activeElement?.blur();
};

const guardarCliente = async () => {
    try {
        loading.value = true;
        if (editMode.value) {
            await clienteStore.updateCliente(form.value.id, form.value);
            Swal.fire('Éxito', 'Cliente actualizado correctamente', 'success');
        } else {
            await clienteStore.createCliente(form.value);
            Swal.fire('Éxito', 'Cliente registrado correctamente', 'success');
        }
        cerrarModal();
        fetchClientes();
    } catch (error) {
        const message = error.response?.data?.message || 'Error al procesar la solicitud';
        Swal.fire('Error', message, 'error');
    } finally {
        loading.value = false;
    }
};

const eliminarCliente = async (cliente) => {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `Se eliminará al cliente: ${cliente.nombre_completo || cliente.razon_social}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            await clienteStore.deleteCliente(cliente.id);
            Swal.fire('Eliminado', 'El cliente ha sido eliminado', 'success');
            fetchClientes();
        } catch (error) {
            Swal.fire('Error', 'No se pudo eliminar el cliente', 'error');
        }
    }
};

const canEdit = computed(() => authStore.hasPermission('editar clientes'));
const canCreate = computed(() => authStore.hasPermission('crear clientes'));

const buscarEntidad = async () => {
    const response = await clienteStore.buscarEntidad({
        numero_documento: form.value.numero_documento,
        tipo_documento: form.value.tipo_documento
    });
    form.value.nombre_completo = response.razon_social;
    form.value.razon_social = response.razon_social;
}

onMounted(() => {
    fetchClientes();
});
</script>

<template>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0"><i class="fas fa-users me-2 text-primary"></i>Gestión de Clientes</h2>
                <button v-if="canCreate" @click="abrirModal()" class="btn btn-primary d-flex align-items-center">
                    <i class="fas fa-plus-circle me-2"></i> Nuevo Cliente
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input 
                                v-model="search" 
                                type="text" 
                                class="form-control bg-light border-start-0" 
                                placeholder="Buscar por nombre, documento o razón social..."
                                @keyup.enter="fetchClientes(1)"
                            >
                            <button @click="fetchClientes(1)" class="btn btn-outline-primary">Buscar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Documento</th>
                                <th>Nombre / Razón Social</th>
                                <th>Contacto</th>
                                <th>Dirección</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="clienteStore.loading && clienteStore.clientes.length === 0">
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else-if="clienteStore.clientes.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    No se encontraron clientes.
                                </td>
                            </tr>
                            <tr v-for="cliente in clienteStore.clientes" :key="cliente.id">
                                <td class="ps-4">
                                    <span class="badge bg-secondary-subtle text-secondary me-1">{{ cliente.tipo_documento }}</span>
                                    <span class="fw-bold">{{ cliente.numero_documento }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ cliente.nombre_completo }}</div>
                                    <div class="small text-muted" v-if="cliente.razon_social">{{ cliente.razon_social }}</div>
                                </td>
                                <td>
                                    <div v-if="cliente.telefono"><i class="fas fa-phone small me-2 text-muted"></i>{{ cliente.telefono }}</div>
                                    <div v-if="cliente.email"><i class="fas fa-envelope small me-2 text-muted"></i>{{ cliente.email }}</div>
                                </td>
                                <td class="small">{{ cliente.direccion || '-' }}</td>
                                <td class="text-center">
                                    <span :class="cliente.activo ? 'bg-success' : 'bg-danger'" class="badge rounded-pill pt-1">
                                        {{ cliente.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button v-if="canEdit" @click="abrirModal(cliente)" class="btn btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button v-if="canEdit" @click="eliminarCliente(cliente)" class="btn btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-white border-top-0 py-3" v-if="clienteStore.pagination.last_page > 1">
                <nav aria-label="Navegación de clientes">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item" :class="{ disabled: clienteStore.pagination.current_page === 1 }">
                            <a class="page-link" href="#" @click.prevent="fetchClientes(clienteStore.pagination.current_page - 1)">Anterior</a>
                        </li>
                        <li v-for="p in clienteStore.pagination.last_page" :key="p" class="page-item" :class="{ active: p === clienteStore.pagination.current_page }">
                            <a class="page-link" href="#" @click.prevent="fetchClientes(p)">{{ p }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: clienteStore.pagination.current_page === clienteStore.pagination.last_page }">
                            <a class="page-link" href="#" @click.prevent="fetchClientes(clienteStore.pagination.current_page + 1)">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Modal Cliente -->
        <div v-if="showModal" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold">
                            {{ editMode ? 'Editar Cliente' : 'Nuevo Cliente' }}
                        </h5>
                        <button type="button" class="btn-close" @click="cerrarModal"></button>
                    </div>
                    <form @submit.prevent="guardarCliente">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Tipo Documento</label>
                                    <select v-model="form.tipo_documento" class="form-select border-light-subtle bg-light" required>
                                        <option value="DNI">DNI</option>
                                        <option value="RUC">RUC</option>
                                        <option value="CE">C.E.</option>
                                        <option value="PASAPORTE">Pasaporte</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Número Documento</label>
                                    <input v-model="form.numero_documento" type="text" class="form-control border-light-subtle bg-light" @blur="buscarEntidad" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-uppercase">Nombre Completo</label>
                                    <input v-model="form.nombre_completo" type="text" class="form-control border-light-subtle bg-light" placeholder="Nombre completo o contacto principal" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-uppercase">Razón Social</label>
                                    <input v-model="form.razon_social" type="text" class="form-control border-light-subtle bg-light" placeholder="Razón social (opcional)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Teléfono</label>
                                    <input v-model="form.telefono" type="text" class="form-control border-light-subtle bg-light">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Email</label>
                                    <input v-model="form.email" type="email" class="form-control border-light-subtle bg-light">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-uppercase">Dirección</label>
                                    <textarea v-model="form.direccion" class="form-control border-light-subtle bg-light" rows="2"></textarea>
                                </div>
                                <div class="col-md-6" v-if="editMode">
                                    <div class="form-check form-switch mt-4">
                                        <input v-model="form.activo" class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
                                        <label class="form-check-label fw-bold" for="flexSwitchCheckDefault">Cliente Activo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pb-4 px-4">
                            <button type="button" class="btn btn-light px-4" @click="cerrarModal" :disabled="loading">Cancelar</button>
                            <button type="submit" class="btn btn-primary px-4 d-flex align-items-center" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                {{ editMode ? 'Actualizar Cliente' : 'Registrar Cliente' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card {
    transition: all 0.2s ease-in-out;
}
.table thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: #6c757d;
}
.badge {
    font-weight: 600;
}
.bg-secondary-subtle {
    background-color: #f8f9fa;
}
</style>
