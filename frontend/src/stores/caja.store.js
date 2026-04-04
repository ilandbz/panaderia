import { defineStore } from 'pinia';
import api from '@/services/api.service';

export const useCajaStore = defineStore('caja', {
    state: () => ({
        aperturaActual: null,
        loading: false,
    }),
    getters: {
        isCajaAbierta: (state) => !!state.aperturaActual,
    },
    actions: {
        async fetchEstadoCaja() {
            try {
                const response = await api.get('/caja/estado');
                this.aperturaActual = response.data;
            } catch (error) {
                this.aperturaActual = null;
            }
        },
        async abrirCaja(data) {
            const response = await api.post('/caja/abrir', data);
            this.aperturaActual = response.data;
            return response;
        },
        async cerrarCaja(data) {
            const response = await api.post('/caja/cerrar', data);
            this.aperturaActual = null;
            return response;
        }
    }
});
