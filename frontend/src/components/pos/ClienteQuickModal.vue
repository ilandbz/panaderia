<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Modal } from 'bootstrap'
import { useClienteStore } from '@/stores/cliente.store'
import Swal from 'sweetalert2'

const emit = defineEmits(['saved', 'closed'])
const clienteStore = useClienteStore()
const loading = ref(false)

const modalEl = ref(null)
let modalInstance = null

onMounted(() => {
  if (modalEl.value) {
    modalInstance = Modal.getOrCreateInstance(modalEl.value)
  }
})

const cerrarModal = () => {
  document.activeElement?.blur()
  modalInstance?.hide()
  emit('closed')
}

const form = reactive({
  tipo_documento: 'DNI',
  numero_documento: '',
  nombre_completo: '',
  razon_social: '',
  direccion: '',
})

const resetForm = () => {
  form.tipo_documento = 'DNI'
  form.numero_documento = ''
  form.nombre_completo = ''
  form.razon_social = ''
  form.direccion = ''
}

const buscarEntidad = async () => {
    const response = await clienteStore.buscarEntidad({
        numero_documento: form.numero_documento,
        tipo_documento: form.tipo_documento
    });
    form.nombre_completo = response.nombre_completo;
    form.razon_social = response.razon_social;
}

const save = async () => {
  if (!form.numero_documento || !form.nombre_completo) {
    Swal.fire('Error', 'Documento y Nombre son obligatorios', 'error')
    return
  }

  if (form.tipo_documento === 'RUC' && !form.razon_social) {
    Swal.fire('Error', 'La Razón Social es obligatoria para RUC', 'error')
    return
  }

  loading.value = true
  try {
    const result = await clienteStore.createCliente(form)
    Swal.fire('Éxito', 'Cliente registrado', 'success')
    emit('saved', result.data || result)
    resetForm()
    cerrarModal()
  } catch (error) {
    Swal.fire(
      'Error',
      error.response?.data?.message || 'Error al guardar cliente',
      'error'
    )
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div ref="modalEl" class="modal fade" id="clienteQuickModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">
        <div class="modal-header bg-primary text-white border-0 py-3">
          <h5 class="modal-title fw-bold">
            <i class="fas fa-user-plus me-2"></i> Nuevo Cliente
          </h5>

          <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="modal"
            @click="cerrarModal"
          ></button>
        </div>

        <div class="modal-body p-4">
          <form @submit.prevent="save">
            <div class="row g-3">
              <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Tipo Doc.</label>
                <select
                  v-model="form.tipo_documento"
                  class="form-select border-light bg-light"
                  @change="form.razon_social = (form.tipo_documento === 'RUC' ? form.razon_social : '')"
                >
                  <option value="DNI">DNI (Persona)</option>
                  <option value="RUC">RUC (Empresa)</option>
                  <option value="CE">CE (Extranjero)</option>
                </select>
              </div>

              <div class="col-md-7">
                <label class="form-label small fw-bold text-muted">Nro. Documento</label>
                <input
                  v-model="form.numero_documento"
                  type="text"
                  maxlength="11"
                  class="form-control border-light bg-light"
                  placeholder="00000000"
                  required
                  @blur="buscarEntidad"
                >
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold text-muted">Nombre Completo / Contacto</label>
                <input
                  v-model="form.nombre_completo"
                  type="text"
                  class="form-control border-light bg-light"
                  placeholder="Nombres y Apellidos"
                  required
                >
              </div>

              <div v-if="form.tipo_documento === 'RUC'" class="col-12">
                <label class="form-label small fw-bold text-muted">Razón Social</label>
                <input
                  v-model="form.razon_social"
                  type="text"
                  class="form-control border-light bg-light"
                  placeholder="Nombre legal de la empresa"
                  required
                >
              </div>

              <div class="col-12">
                <label class="form-label small fw-bold text-muted">Dirección (Opcional)</label>
                <input
                  v-model="form.direccion"
                  type="text"
                  class="form-control border-light bg-light"
                  placeholder="Av. Siempre Viva 123"
                >
              </div>
            </div>

            <div class="d-grid mt-4">
              <button type="submit" class="btn btn-primary py-2 fw-bold" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                GUARDAR Y SELECCIONAR
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>