import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api.service';

export const useSucursalStore = defineStore('sucursal', () => {
    const sucursales = ref([]);
    const sucursalActual = ref(JSON.parse(localStorage.getItem('sucursal_actual')) || null);
    const loading = ref(false);

    const hasSucursal = computed(() => !!sucursalActual.value);

    async function fetchSucursales() {
        loading.value = true;
        try {
            const response = await api.get('/sucursales');
            if (response.data) {
                sucursales.value = response.data;
            } else {
                console.warn('Respuesta de sucursales no válida:', response);
                sucursales.value = [];
            }
        } catch (error) {
            console.error('Error fetching sucursales:', error);
            sucursales.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function saveSucursal(data) {
        loading.value = true;
        try {
            const response = await api.post('/sucursales', data);
            await fetchSucursales();
            return response;
        } catch (error) {
            console.error('Error saving sucursal:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function updateSucursal(id, data) {
        loading.value = true;
        try {
            const response = await api.put(`/sucursales/${id}`, data);
            await fetchSucursales();
            if (sucursalActual.value && sucursalActual.value.id === id) {
                setSucursal(response.data);
            }
            return response;
        } catch (error) {
            console.error('Error updating sucursal:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    }

    function setSucursal(sucursal) {
        sucursalActual.value = sucursal;
        localStorage.setItem('sucursal_actual', JSON.stringify(sucursal));
        
        // Configurar el header global para todos los servicios que usen el api service
        if (sucursal) {
            api.defaults.headers.common['X-Sucursal-Id'] = sucursal.id;
        } else {
            delete api.defaults.headers.common['X-Sucursal-Id'];
        }
    }

    function clearSucursal() {
        sucursalActual.value = null;
        localStorage.removeItem('sucursal_actual');
        delete api.defaults.headers.common['X-Sucursal-Id'];
    }

    // Inicializar el header si ya hay una sucursal en localStorage
    if (sucursalActual.value) {
        api.defaults.headers.common['X-Sucursal-Id'] = sucursalActual.value.id;
    }

    return {
        sucursales,
        sucursalActual,
        loading,
        hasSucursal,
        fetchSucursales,
        setSucursal,
        saveSucursal,
        updateSucursal,
        clearSucursal
    };
});
