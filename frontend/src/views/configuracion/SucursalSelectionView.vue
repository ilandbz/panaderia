<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSucursalStore } from '@/stores/sucursal.store';
import { useAuthStore } from '@/stores/auth.store';
import Swal from 'sweetalert2';

const router = useRouter();
const sucursalStore = useSucursalStore();
const authStore = useAuthStore();

onMounted(async () => {
    await sucursalStore.fetchSucursales();
    
    // Si solo hay una sucursal, seleccionarla automáticamente
    if (sucursalStore.sucursales.length === 1) {
        seleccionarSucursal(sucursalStore.sucursales[0]);
    }
});

const seleccionarSucursal = (sucursal) => {
    sucursalStore.setSucursal(sucursal);
    Swal.fire({
        icon: 'success',
        title: 'Sede Seleccionada',
        text: `Bienvenido a ${sucursal.nombre}`,
        timer: 1500,
        showConfirmButton: false
    });
    router.push({ name: 'Dashboard' });
};

const handleLogout = async () => {
    await authStore.logout();
    router.push({ name: 'Login' });
};

</script>

<template>
    <div class="selection-container d-flex align-items-center justify-content-center min-vh-100">
        <div class="selection-card p-4 shadow-lg text-center">
            <div class="brand-logo mb-4">
                <i class="fas fa-bread-slice fa-3x text-warning mb-2"></i>
                <h2 class="bakery-title">Panadería Jara</h2>
                <p class="text-muted">Seleccione la sede para iniciar operaciones</p>
            </div>

            <div v-if="sucursalStore.loading" class="py-5">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>

            <div v-else class="row g-3 justify-content-center mt-2">
                <div 
                    v-for="sucursal in sucursalStore.sucursales" 
                    :key="sucursal.id" 
                    class="col-md-6"
                >
                    <div 
                        class="sucursal-item p-3 border rounded-3 h-100 d-flex flex-column align-items-center justify-content-center"
                        @click="seleccionarSucursal(sucursal)"
                    >
                        <i class="fas fa-store-alt mb-2 fa-2x text-orange"></i>
                        <span class="fw-bold d-block">{{ sucursal.nombre }}</span>
                        <small class="text-muted">{{ sucursal.direccion }}</small>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-top">
                <button @click="handleLogout" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.selection-container {
    background: linear-gradient(135deg, #fffcf5 0%, #f7e0c1 100%);
}

.selection-card {
    background: white;
    border-radius: 20px;
    max-width: 600px;
    width: 90%;
    border: 1px solid rgba(217, 119, 6, 0.1);
}

.bakery-title {
    color: #d97706;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.sucursal-item {
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    border-color: #f3f4f6 !important;
}

.sucursal-item:hover {
    transform: translateY(-5px);
    border-color: #f59e0b !important;
    background-color: #fffbeb;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.text-orange {
    color: #f59e0b;
}

.sucursal-item .fw-bold {
    color: #4b5563;
}
</style>
