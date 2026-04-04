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
                this.recetas = response.data;
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
