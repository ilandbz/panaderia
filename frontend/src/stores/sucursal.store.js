import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useSucursalStore = defineStore('sucursal', () => {
    const sucursales = ref([]);
    const sucursalActual = ref(JSON.parse(localStorage.getItem('sucursal_actual')) || null);
    const loading = ref(false);

    const hasSucursal = computed(() => !!sucursalActual.value);

    async function fetchSucursales() {
        loading.value = true;
        try {
            const response = await axios.get('/api/sucursales');
            sucursales.value = response.data.data;
        } catch (error) {
            console.error('Error fetching sucursales:', error);
        } finally {
            loading.value = false;
        }
    }

    function setSucursal(sucursal) {
        sucursalActual.value = sucursal;
        localStorage.setItem('sucursal_actual', JSON.stringify(sucursal));
        
        // Configurar el header global de Axios
        if (sucursal) {
            axios.defaults.headers.common['X-Sucursal-Id'] = sucursal.id;
        } else {
            delete axios.defaults.headers.common['X-Sucursal-Id'];
        }
    }

    function clearSucursal() {
        sucursalActual.value = null;
        localStorage.removeItem('sucursal_actual');
        delete axios.defaults.headers.common['X-Sucursal-Id'];
    }

    // Inicializar el header si ya hay una sucursal en localStorage
    if (sucursalActual.value) {
        axios.defaults.headers.common['X-Sucursal-Id'] = sucursalActual.value.id;
    }

    return {
        sucursales,
        sucursalActual,
        loading,
        hasSucursal,
        fetchSucursales,
        setSucursal,
        clearSucursal
    };
});
