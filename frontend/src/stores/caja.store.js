import { defineStore } from 'pinia';
import api from '@/services/api.service';

export const useCajaStore = defineStore('caja', {
    state: () => ({
        aperturaActual: null,
        historial: [],
        detalleApertura: null,
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
        async fetchHistorial(page = 1) {
            this.loading = true;
            try {
                const response = await api.get(`/caja/historial?page=${page}`);
                this.historial = response.data;
            } finally {
                this.loading = false;
            }
        },
        async fetchDetalleApertura(id) {
            this.loading = true;
            try {
                const response = await api.get(`/caja/${id}`);
                this.detalleApertura = response.data;
                return response.data;
            } finally {
                this.loading = false;
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
        },
        async registrarMovimiento(tipo, data) {
            const endpoint = tipo === 'ingreso' ? '/caja/ingreso' : '/caja/gasto';
            const response = await api.post(endpoint, data);
            await this.fetchEstadoCaja();
            return response;
        }
    }
});
