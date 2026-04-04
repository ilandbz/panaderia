import { defineStore } from 'pinia';
import api from '@/services/api.service';

export const useProveedorStore = defineStore('proveedor', {
    state: () => ({
        proveedores: [],
        loading: false,
    }),
    actions: {
        async fetchProveedores() {
            this.loading = true;
            try {
                const response = await api.get('/proveedores');
                // Ajustar según estructura successResponse {success: true, data: [...]}
                this.proveedores = response.data?.data ?? response.data ?? [];
            } catch (error) {
                console.error('Error al cargar proveedores:', error);
                this.proveedores = [];
            } finally {
                this.loading = false;
            }
        },
        async crearProveedor(data) {
            this.loading = true;
            try {
                const response = await api.post('/proveedores', data);
                await this.fetchProveedores();
                return response.data;
            } finally {
                this.loading = false;
            }
        },
        async updateProveedor(id, data) {
            this.loading = true;
            try {
                const response = await api.put(`/proveedores/${id}`, data);
                await this.fetchProveedores();
                return response.data;
            } finally {
                this.loading = false;
            }
        },
        async deleteProveedor(id) {
            this.loading = true;
            try {
                const response = await api.delete(`/proveedores/${id}`);
                await this.fetchProveedores();
                return response.data;
            } finally {
                this.loading = false;
            }
        }
    }
});
