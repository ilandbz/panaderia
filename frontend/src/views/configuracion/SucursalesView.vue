<script setup>
import { ref, onMounted } from 'vue';
import { useSucursalStore } from '@/stores/sucursal.store';
import { useModal } from '@/composables/useModal';
import Swal from 'sweetalert2';

const sucursalStore = useSucursalStore();
const { show: showModal, hide: hideModal } = useModal('sucursalModal');

const loading = ref(false);
const editing = ref(false);
const currentId = ref(null);

const form = ref({
    nombre: '',
    direccion: '',
    cod_establecimiento: '',
    serie_boleta: '',
    serie_factura: '',
    serie_nota_credito: '',
    activo: true
});

onMounted(async () => {
    await sucursalStore.fetchSucursales();
});

const resetForm = () => {
    form.value = {
        nombre: '',
        direccion: '',
        cod_establecimiento: '',
        serie_boleta: '',
        serie_factura: '',
        serie_nota_credito: '',
        activo: true
    };
    editing.value = false;
    currentId.value = null;
};

const calcularSiguientesValores = () => {
    const sucursales = sucursalStore.sucursales;
    if (sucursales.length === 0) return;

    // Sugerir Código SUNAT (Establecimiento)
    const maxCod = Math.max(...sucursales.map(s => parseInt(s.cod_establecimiento || 0)));
    form.value.cod_establecimiento = String(maxCod + 1).padStart(4, '0');

    // Sugerir Series (asumiendo formato Letra + 000)
    const incrementarSerie = (serie, prefijo) => {
        if (!serie) return prefijo + '001';
        const num = parseInt(serie.substring(1)) || 0;
        return prefijo + String(num + 1).padStart(3, '0');
    };

    // Buscamos las series más altas registradas
    const maxBoleta = sucursales.reduce((max, s) => s.serie_boleta > max ? s.serie_boleta : max, 'B000');
    const maxFactura = sucursales.reduce((max, s) => s.serie_factura > max ? s.serie_factura : max, 'F000');
    const maxNC = sucursales.reduce((max, s) => s.serie_nota_credito > max ? s.serie_nota_credito : max, 'BC00');

    form.value.serie_boleta = incrementarSerie(maxBoleta, 'B');
    form.value.serie_factura = incrementarSerie(maxFactura, 'F');
    
    // Para Nota de Crédito calculamos según el patrón (puede ser B o F)
    const numNC = parseInt(maxNC.substring(2)) || 0;
    form.value.serie_nota_credito = 'BC' + String(numNC + 1).padStart(2, '0');
};

const openCreateModal = () => {
    resetForm();
    calcularSiguientesValores();
    showModal();
};

const openEditModal = (sucursal) => {
    form.value = { ...sucursal };
    editing.value = true;
    currentId.value = sucursal.id;
    showModal();
};

const handleSubmit = async () => {
    loading.value = true;
    try {
        if (editing.value) {
            await sucursalStore.updateSucursal(currentId.value, form.value);
            Swal.fire('Éxito', 'Sucursal actualizada correctamente', 'success');
        } else {
            await sucursalStore.saveSucursal(form.value);
            Swal.fire('Éxito', 'Sucursal creada correctamente', 'success');
        }
        hideModal();
        await sucursalStore.fetchSucursales();
    } catch (error) {
        const message = error.response?.data?.message || 'Error al procesar la solicitud';
        Swal.fire('Error', message, 'error');
    } finally {
        loading.value = false;
    }
};

</script>

<template>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1 fw-bold text-dark">Gestión de Sucursales</h2>
                <p class="text-muted small mb-0">Administra los locales de venta y series de facturación</p>
            </div>
            <button @click="openCreateModal" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-plus me-2"></i>Nueva Sede
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light border-bottom">
                            <tr>
                                <th class="ps-4 py-3 text-uppercase small fw-bold text-muted">Nombre/Sede</th>
                                <th class="py-3 text-uppercase small fw-bold text-muted">Ubicación</th>
                                <th class="py-3 text-uppercase small fw-bold text-muted text-center">Cód. SUNAT</th>
                                <th class="py-3 text-uppercase small fw-bold text-muted">Series</th>
                                <th class="py-3 text-uppercase small fw-bold text-muted text-center">Estado</th>
                                <th class="pe-4 py-3 text-uppercase small fw-bold text-muted text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sucursal in sucursalStore.sucursales" :key="sucursal.id">
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle text-primary rounded-3 p-2 me-3">
                                            <i class="fas fa-store"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold d-block">{{ sucursal.nombre }}</span>
                                            <span v-if="sucursal.principal" class="badge bg-warning-subtle text-warning extrasmall rounded-pill px-2">PRINCIPAL</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <small class="text-muted text-wrap d-block" style="max-width: 200px;">{{ sucursal.direccion }}</small>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="badge bg-light text-dark fw-bold border">{{ sucursal.cod_establecimiento }}</span>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <span class="badge bg-info-subtle text-info extrasmall">B: {{ sucursal.serie_boleta }}</span>
                                        <span class="badge bg-primary-subtle text-primary extrasmall">F: {{ sucursal.serie_factura }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary extrasmall">N: {{ sucursal.serie_nota_credito }}</span>
                                    </div>
                                </td>
                                <td class="py-3 text-center">
                                    <span v-if="sucursal.activo" class="badge rounded-pill bg-success-subtle text-success px-3">Activo</span>
                                    <span v-else class="badge rounded-pill bg-danger-subtle text-danger px-3">Inactivo</span>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <button @click="openEditModal(sucursal)" class="btn btn-sm btn-outline-primary rounded-pill px-3 border-0">
                                        <i class="fas fa-edit me-1"></i>Editar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Sucursal -->
        <div class="modal fade" id="sucursalModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <form @submit.prevent="handleSubmit">
                        <div class="modal-header bg-primary text-white border-bottom-0 p-4">
                            <h5 class="modal-title fw-bold">
                                <i class="fas" :class="editing ? 'fa-edit' : 'fa-plus'"></i> 
                                {{ editing ? 'Editar Sede' : 'Nueva Sede' }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" @click="hideModal"></button>
                        </div>
                        <div class="modal-body p-4 bg-light-subtle">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Nombre de la Sede</label>
                                    <input v-model="form.nombre" type="text" class="form-control rounded-3" placeholder="Ej: Sucursal Centro" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Dirección</label>
                                    <input v-model="form.direccion" type="text" class="form-control rounded-3" placeholder="Calle, Av, Jr..." required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Cód. Establecimiento (SUNAT)</label>
                                    <input v-model="form.cod_establecimiento" type="text" maxlength="4" class="form-control rounded-3" placeholder="Ej: 0000" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4 pt-2">
                                        <input v-model="form.activo" class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
                                        <label class="form-check-label small fw-bold" for="flexSwitchCheckDefault">Sede Activa</label>
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Configuración de Series</h6>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Serie Boleta</label>
                                    <input v-model="form.serie_boleta" type="text" maxlength="4" class="form-control rounded-3" placeholder="Ej: B001" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Serie Factura</label>
                                    <input v-model="form.serie_factura" type="text" maxlength="4" class="form-control rounded-3" placeholder="Ej: F001" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Serie N. Crédito</label>
                                    <input v-model="form.serie_nota_credito" type="text" maxlength="4" class="form-control rounded-3" placeholder="Ej: BC01" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top-0 p-4">
                            <button type="button" class="btn btn-light rounded-pill px-4" @click="hideModal">Cancelar</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="fas fa-save me-2"></i>Guardar Sede
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.extrasmall {
    font-size: 0.65rem;
    font-weight: 700;
}

.table-hover tbody tr:hover {
    background-color: #fffbeb;
}

.bg-primary-subtle { background-color: #e0f2fe; }
.text-primary { color: #0369a1; }

.form-control:focus {
    border-color: #d97706;
    box-shadow: 0 0 0 0.25rem rgba(217, 119, 6, 0.1);
}
</style>
