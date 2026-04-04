import { defineStore } from 'pinia';
import api from '@/services/api.service';

export const useCompraStore = defineStore('compra', {
    state: () => ({
        compras: [],
        loading: false,
    }),
    actions: {
        async fetchCompras() {
            this.loading = true;
            try {
                const response = await api.get('/compras');
                this.compras = response.data;
            } finally {
                this.loading = false;
            }
        },
        async registrarCompra(data) {
            const response = await api.post('/compras', data);
            return response;
        },
        async fetchCompra(id) {
            const response = await api.get(`/compras/${id}`);
            return response.data;
        },
        async anularCompra(id) {
            const response = await api.delete(`/compras/${id}`);
            await this.fetchCompras(); // Refrescar lista
            return response.data;
        }
    }
});
