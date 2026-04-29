<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useProductStore } from '@/stores/product.store';
import { useProduccionStore } from '@/stores/produccion.store';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';

// Formatear moneda PEN
const formatCurrency = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'S/ —';
    return 'S/ ' + Number(val).toFixed(2);
};

const props = defineProps({
    modelValue: Boolean,
    receta: {
        type: Object,
        default: null
    }
});

const errors = ref({});

const emit = defineEmits(['update:modelValue', 'saved']);

const productStore = useProductStore();
const produccionStore = useProduccionStore();

const modalRef = ref(null);
let modalInstance = null;

const form = ref({
    producto_id: '',
    nombre: '',
    rendimiento: 1,
    instrucciones: '',
    insumos: []
});

const isEditing = computed(() => !!props.receta);
const productosElaborados = computed(() => productStore.products.filter(p => p.tipo === 'elaborado'));
const insumosDisponibles = computed(() => productStore.products.filter(p => p.tipo === 'insumo'));

// Producto elaborado seleccionado actualmente
const productoSeleccionado = computed(() =>
    productosElaborados.value.find(p => p.id == form.value.producto_id) || null
);

// Precio de venta del producto elaborado
const precioVenta = computed(() => {
    const p = productoSeleccionado.value;
    if (!p) return null;
    return parseFloat(p.precio) || parseFloat(p.precio_venta) || null;
});

// Costo por cada línea de insumo (precio_compra * cantidad)
const costosPorInsumo = computed(() =>
    form.value.insumos.map(item => {
        const insumo = insumosDisponibles.value.find(i => i.id == item.insumo_id);
        if (!insumo || !item.cantidad) return null;
        const costo = parseFloat(insumo.precio_compra || insumo.costo || 0);
        return costo * parseFloat(item.cantidad);
    })
);

// Costo total de la receta (un batch)
const costoTotalReceta = computed(() => {
    const costos = costosPorInsumo.value;
    if (costos.every(c => c === null)) return null;
    return costos.reduce((sum, c) => sum + (c || 0), 0);
});

// Utilidad = precio de venta * rendimiento - costo total
const utilidadEstimada = computed(() => {
    if (precioVenta.value === null || costoTotalReceta.value === null) return null;
    const ingresos = precioVenta.value * parseFloat(form.value.rendimiento || 1);
    return ingresos - costoTotalReceta.value;
});

// Margen de utilidad en %
const margenPorcentaje = computed(() => {
    if (precioVenta.value === null || costoTotalReceta.value === null || costoTotalReceta.value === 0) return null;
    const ingresos = precioVenta.value * parseFloat(form.value.rendimiento || 1);
    return ((ingresos - costoTotalReceta.value) / ingresos) * 100;
});

watch(() => props.modelValue, (val) => {
    if (val) {
        initForm();
        modalInstance?.show();
    } else {
        modalInstance?.hide();
    }
});

onMounted(async () => {
    modalInstance = new Modal(modalRef.value);
    modalRef.value.addEventListener('hidden.bs.modal', () => {
        emit('update:modelValue', false);
    });

    if (productStore.products.length === 0) {
        await productStore.fetchProducts();
    }
});

const initForm = () => {
    if (props.receta) {
        form.value = {
            producto_id: props.receta.producto_id,
            nombre: props.receta.nombre,
            rendimiento: props.receta.rendimiento,
            instrucciones: props.receta.instrucciones || '',
            insumos: props.receta.insumos?.map(i => ({
                insumo_id: i.insumo_id,
                cantidad: i.cantidad,
                unidad_medida: i.unidad_medida
            })) || []
        };
    } else {
        form.value = {
            producto_id: '',
            nombre: '',
            rendimiento: 1,
            instrucciones: '',
            insumos: [{ insumo_id: '', cantidad: 0, unidad_medida: '' }]
        };
    }
};

const addInsumo = () => {
    form.value.insumos.push({ insumo_id: '', cantidad: 0, unidad_medida: '' });
};

const removeInsumo = (index) => {
    form.value.insumos.splice(index, 1);
};

const updateInsumoUM = (index) => {
    const selected = insumosDisponibles.value.find(i => i.id === form.value.insumos[index].insumo_id);
    if (selected) {
        form.value.insumos[index].unidad_medida = selected.unidad_medida;
    }
};

const getError = (field) => {
    return errors.value[field]?.[0] || '';
};

const guardar = async () => {
    errors.value = {};
    if (!form.value.producto_id || !form.value.nombre || form.value.insumos.length === 0) {
        Swal.fire('Atención', 'Por favor complete todos los campos obligatorios.', 'warning');
        return;
    }

    try {
        if (isEditing.value) {
            await produccionStore.updateReceta(props.receta.id, form.value);
            Swal.fire('Editado', 'Receta actualizada correctamente', 'success');
        } else {
            await produccionStore.saveReceta(form.value);
            Swal.fire('Guardado', 'Receta creada exitosamente', 'success');
        }
        emit('saved');
        cerrar();
    } catch (e) {
        if (e.errors) {
            errors.value = e.errors;
        } else {
            Swal.fire('Error', 'Error al guardar la receta', 'error');
        }
    }
};

const cerrar = () => {
    modalInstance?.hide();
};
</script>

<template>
    <div class="modal fade" ref="modalRef" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-brown">
                        {{ isEditing ? 'Editar Receta' : 'Nueva Receta' }}
                    </h5>
                    <button type="button" class="btn-close" @click="cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <form @submit.prevent="guardar">
                        <div class="row g-3">
                            <!-- Producto Elaborado -->
                            <div class="col-md-7">
                                <label class="form-label small fw-bold">Producto Elaborado <span class="text-danger">*</span></label>
                                <select v-model="form.producto_id" class="form-select rounded-3">
                                    <option value="" disabled>Seleccione el producto final</option>
                                    <option v-for="p in productosElaborados" :key="p.id" :value="p.id">
                                        {{ p.nombre }} ({{ p.unidad_medida }})
                                    </option>
                                </select>
                            </div>

                            <!-- Rendimiento -->
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Rendimiento Base <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.001" v-model="form.rendimiento" class="form-control rounded-start-3" placeholder="Ej: 50">
                                    <span class="input-group-text bg-light">Cant. final</span>
                                </div>
                            </div>

                            <!-- Nombre de la Receta -->
                            <div class="col-12">
                                <label class="form-label small fw-bold">Nombre de la Receta / Versión <span class="text-danger">*</span></label>
                                <input type="text" v-model="form.nombre" class="form-control rounded-3" placeholder="Ejem: Receta Especial de Pan de Yema v1">
                            </div>

                            <!-- Instrucciones -->
                            <div class="col-12">
                                <label class="form-label small fw-bold">Instrucciones (Opcional)</label>
                                <textarea v-model="form.instrucciones" class="form-control rounded-3" rows="2" placeholder="Pasos clave para la producción..."></textarea>
                            </div>

                            <!-- Insumos -->
                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small fw-bold m-0"><i class="fas fa-mortar-pestle me-2"></i>Insumos y Cantidades</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addInsumo">
                                        <i class="fas fa-plus me-1"></i> Agregar Insumo
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th width="40%">Insumo</th>
                                                <th width="20%">Cantidad</th>
                                                <th width="12%">U.M.</th>
                                                <th width="18%" class="text-end text-success"><i class="fas fa-coins me-1"></i>Costo</th>
                                                <th width="10%"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in form.insumos" :key="index">
                                                <td>
                                                    <select v-model="item.insumo_id"
                                                    class="form-select form-select-sm"
                                                    :class="{ 'is-invalid': getError(`insumos.${index}.insumo_id`) }"
                                                    @change="updateInsumoUM(index)">
                                                        <option value="" disabled>Seleccionar...</option>
                                                        <option v-for="insumo in insumosDisponibles" :key="insumo.id" :value="insumo.id">
                                                            {{ insumo.nombre }} [Stock: {{ insumo.stock }}]
                                                        </option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        {{ getError(`insumos.${index}.insumo_id`) }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.0001"
                                                    v-model="item.cantidad"
                                                    class="form-control form-control-sm"
                                                    :class="{ 'is-invalid': getError(`insumos.${index}.cantidad`) }"
                                                    >
                                                    <div class="invalid-feedback">
                                                        {{ getError(`insumos.${index}.cantidad`) }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" v-model="item.unidad_medida"
                                                    class="form-control form-control-sm border-0 bg-transparent"
                                                    :class="{ 'is-invalid': getError(`insumos.${index}.unidad_medida`) }"
                                                    readonly>
                                                    <div class="invalid-feedback">
                                                        {{ getError(`insumos.${index}.unidad_medida`) }}
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span v-if="costosPorInsumo[index] !== null" class="small fw-bold text-success">
                                                        {{ formatCurrency(costosPorInsumo[index]) }}
                                                    </span>
                                                    <span v-else class="small text-muted">—</span>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm text-danger" @click="removeInsumo(index)" :disabled="form.insumos.length === 1">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de Análisis Financiero -->
                        <div class="financiero-panel rounded-4 p-3 mt-4" :class="{
                            'financiero-positivo': utilidadEstimada !== null && utilidadEstimada > 0,
                            'financiero-negativo': utilidadEstimada !== null && utilidadEstimada <= 0,
                            'financiero-neutro': utilidadEstimada === null
                        }">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-chart-pie me-2"></i>
                                <span class="small fw-bold">Análisis Financiero por Batch ({{ form.rendimiento || 1 }} und.)</span>
                            </div>
                            <div class="row g-2 text-center">
                                <div class="col-4">
                                    <div class="financiero-item">
                                        <div class="extrasmall text-muted mb-1">COSTO TOTAL</div>
                                        <div class="fw-bold">
                                            {{ costoTotalReceta !== null ? formatCurrency(costoTotalReceta) : '—' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="financiero-item">
                                        <div class="extrasmall text-muted mb-1">PRECIO VENTA TOTAL</div>
                                        <div class="fw-bold text-primary">
                                            <span v-if="precioVenta !== null">
                                                {{ formatCurrency(precioVenta * (form.rendimiento || 1)) }}
                                            </span>
                                            <span v-else class="text-muted">Sin precio</span>
                                        </div>
                                        <div v-if="precioVenta !== null" class="extrasmall text-muted">
                                            {{ formatCurrency(precioVenta) }} × {{ form.rendimiento || 1 }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="financiero-item">
                                        <div class="extrasmall text-muted mb-1">UTILIDAD ESTIMADA</div>
                                        <div class="fw-bold" :class="{
                                            'text-success': utilidadEstimada !== null && utilidadEstimada > 0,
                                            'text-danger': utilidadEstimada !== null && utilidadEstimada <= 0
                                        }">
                                            {{ utilidadEstimada !== null ? formatCurrency(utilidadEstimada) : '—' }}
                                        </div>
                                        <div v-if="margenPorcentaje !== null" class="extrasmall" :class="margenPorcentaje > 0 ? 'text-success' : 'text-danger'">
                                            {{ margenPorcentaje.toFixed(1) }}% margen
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-light rounded-pill px-4 me-2" @click="cerrar">Cancelar</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4" :disabled="produccionStore.loading">
                                <span v-if="produccionStore.loading" class="spinner-border spinner-border-sm me-2"></span>
                                {{ isEditing ? 'Actualizar Receta' : 'Guardar Receta' }}
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
.form-label { color: #57534e; }
.bg-light { background-color: #f8f9fa !important; }

/* Panel financiero */
.financiero-panel {
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    transition: all 0.3s ease;
}
.financiero-positivo {
    border-color: #bbf7d0;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}
.financiero-negativo {
    border-color: #fecaca;
    background: linear-gradient(135deg, #fff5f5 0%, #fee2e2 100%);
}
.financiero-neutro {
    border-color: #e2e8f0;
    background: #f8fafc;
}
.financiero-item {
    padding: 0.5rem;
    background: rgba(255,255,255,0.7);
    border-radius: 0.5rem;
}
.extrasmall {
    font-size: 0.7rem;
    letter-spacing: 0.03em;
}
</style>
