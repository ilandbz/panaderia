import { defineStore } from 'pinia';
import api from '@/services/api.service';

export const useProductStore = defineStore('product', {
    state: () => ({
        products: [],
        categories: [],
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 15,
        },
        loading: false,
    }),

    actions: {
        async fetchProducts(params = {}) {
            this.loading = true;
            try {
                const response = await api.get('/productos', { params });
                // En el interceptor ya tenemos response.data (el JSON {success, data, message})
                const apiData = response.data; // Esto es el paginador de Laravel: { current_page, data: [], total, ... }

                if (apiData && Array.isArray(apiData.data)) {
                    // Si viene la estructura de Laravel Paginate
                    this.products = apiData.data;
                    this.pagination = {
                        current_page: apiData.current_page,
                        last_page: apiData.last_page,
                        total: apiData.total,
                        per_page: apiData.per_page,
                    };
                } else if (Array.isArray(apiData)) {
                    // Si viene un array simple (fallback)
                    this.products = apiData;
                    this.pagination.total = apiData.length;
                    this.pagination.last_page = 1;
                } else if (Array.isArray(response)) {
                    // Si el interceptor no devolvió .data y recibimos el array directamente
                    this.products = response;
                } else {
                    this.products = [];
                }
            } catch (error) {
                console.error('Error al cargar productos:', error);
                this.products = [];
            } finally {
                this.loading = false;
            }
        },

        async fetchCategories() {
            try {
                const response = await api.get('/categorias');

                // Ajusta según tu estructura real
                this.categories = Array.isArray(response.data?.data)
                    ? response.data.data
                    : Array.isArray(response.data)
                        ? response.data
                        : [];
            } catch (error) {
                console.error('Error al cargar categorías:', error);
                this.categories = [];
            }
        },

        async addProduct(data) {
            this.loading = true;
            try {
                const response = await api.post('/productos', data);
                await this.fetchProducts();
                return response;
            } catch (error) {
                console.error('Error al crear producto:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateProduct(id, data) {
            this.loading = true;
            try {
                const response = await api.put(`/productos/${id}`, data);
                await this.fetchProducts();
                return response;
            } catch (error) {
                console.error('Error al actualizar producto:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteProduct(id) {
            this.loading = true;
            try {
                const response = await api.delete(`/productos/${id}`);
                await this.fetchProducts();
                return response;
            } catch (error) {
                console.error('Error al eliminar producto:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchKardex(productoId, page = 1) {
            try {
                const response = await api.get(`/productos/${productoId}/movimientos`, { params: { page } });
                return response.data; // Retornamos los datos directamente
            } catch (error) {
                console.error('Error al cargar kardex:', error);
                throw error;
            }
        },

        async ajustarStock(productoId, data) {
            this.loading = true;
            try {
                const response = await api.post(`/productos/${productoId}/ajuste-stock`, data);
                await this.fetchProducts(); // Refrescar lista para ver stock actualizado
                return response.data;
            } catch (error) {
                console.error('Error al ajustar stock:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});