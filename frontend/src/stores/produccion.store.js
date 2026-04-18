import { defineStore } from 'pinia';
import api from '@/services/api.service';

export const useProduccionStore = defineStore('produccion', {
    state: () => ({
        recetas: [],
        loading: false,
    }),
    actions: {
        async fetchRecetas() {
            this.loading = true;
            try {
                const response = await api.get('/recetas');
                this.recetas = response.data; // Adjusted for standard API response
            } finally {
                this.loading = false;
            }
        },
        async saveReceta(recetaData) {
            this.loading = true;
            try {
                const response = await api.post('/recetas', recetaData);
                await this.fetchRecetas();
                return response.data;
            } catch (error) {
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async updateReceta(id, recetaData) {
            this.loading = true;
            try {
                const response = await api.put(`/recetas/${id}`, recetaData);
                await this.fetchRecetas();
                return response.data;
            } catch (error) {
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async deleteReceta(id) {
            this.loading = true;
            try {
                const response = await api.delete(`/recetas/${id}`);
                await this.fetchRecetas();
                return response.data;
            } finally {
                this.loading = false;
            }
        },
        async ejecutarProduccion(recetaId, cantidad) {
            this.loading = true;
            try {
                const response = await api.post(`/recetas/${recetaId}/producir`, { cantidad });
                return response;
            } finally {
                this.loading = false;
            }
        }
    }
});
