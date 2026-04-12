import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api.service';

export const useVentaStore = defineStore('venta', {
    state: () => ({
        loading: false,
        apiBaseUrl: import.meta.env.VITE_API_URL || 'http://panaderia_pasteleria_harold.test/api',
    }),
    actions: {
        async getTicketUrl(id, format = '80mm', tipo = 'ticket') {
            const response = await api.get(`/ventas/${id}/impresion`, {
                params: { format, tipo },
                responseType: 'blob'
            });
            const file = new Blob([response], { type: 'application/pdf' });
            return URL.createObjectURL(file);
        },
        async fetchVentas(params = {}) {
            this.loading = true;
            try {
                const response = await api.get('/ventas', { params });
                return response.data;
            } finally {
                this.loading = false;
            }
        },
        async registrarVenta(ventaData) {
            this.loading = true;
            try {
                const response = await api.post('/ventas', ventaData);
                return response.data; // Return the created sale
            } finally {
                this.loading = false;
            }
        },
        async fetchVenta(id) {
            this.loading = true;
            try {
                const response = await api.get(`/ventas/${id}`);
                return response.data;
            } finally {
                this.loading = false;
            }
        },
        async generarComprobante(id, tipo, formatoImpresion) {
            this.loading = true;
            try {
                const response = await api.post(`/ventas/${id}/generar-comprobante`, { tipo, formatoImpresion });
                return response;
            } finally {
                this.loading = false;
            }
        },
        async actualizarVenta(id, data) {
            this.loading = true;
            try {
                const response = await api.patch(`/ventas/${id}`, data);
                return response.data;
            } finally {
                this.loading = false;
            }
        },
        async anularVenta(id, motivo) {
            this.loading = true;
            try {
                const response = await api.post(`/ventas/${id}/anular`, { motivo });
                return response.data;
            } finally {
                this.loading = false;
            }
        },
        async reenviarComprobante(id) {
            this.loading = true;
            try {
                const response = await api.post(`/ventas/${id}/reenviar-comprobante`);
                return response.data;
            } finally {
                this.loading = false;
            }
        },
        async getPdf(id, format) {
            try {
                const data = await api.get(`/ventas/${id}/pdf`, {
                    params: { format },
                    responseType: 'blob'
                });

                // 🔥 data YA ES EL BLOB
                return data;
            } catch (error) {
                console.error('Error fetching PDF:', error);
                throw error;
            }
        },
        async fetchSunatConfig() {
            try {
                const response = await api.get('/ventas/sunat-config');
                return response.data;
            } catch (error) {
                console.error('Error fetching SUNAT config:', error);
                return null;
            }
        }
    }
});
