import { defineStore } from 'pinia';
import api from '@/services/api.service';

export const useClienteStore = defineStore('cliente', {
    state: () => ({
        clientes: [],
        loading: false,
    }),
    actions: {
        async fetchClientes(search = '') {
            this.loading = true;
            try {
                const response = await api.get('/clientes', { params: { search } });
                this.clientes = response.data.data || response.data;
                return this.clientes;
            } finally {
                this.loading = false;
            }
        },
        async createCliente(clienteData) {
            this.loading = true;
            try {
                const response = await api.post('/clientes', clienteData);
                return response.data;
            } finally {
                this.loading = false;
            }
        }
    }
});
