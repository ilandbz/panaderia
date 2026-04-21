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
            per_page: 20,
        },
        filters: {
            search: '',
            categoria_id: '',
        },
        loading: false,
    }),

    getters: {
        /**
         * Retorna los productos filtrados según el estado de filters
         */
        filteredProducts: (state) => {
            return state.products.filter(p => {
                const matchesSearch = !state.filters.search || 
                    p.nombre.toLowerCase().includes(state.filters.search.toLowerCase()) || 
                    (p.codigo && p.codigo.toLowerCase().includes(state.filters.search.toLowerCase()));
                
                const matchesCat = !state.filters.categoria_id || 
                    p.categoria_id == state.filters.categoria_id;
                
                return matchesSearch && matchesCat;
            });
        },

        /**
         * Retorna los productos ya filtrados y paginados para la vista actual
         */
        paginatedProducts() {
            const filtered = this.filteredProducts;
            const start = (this.pagination.current_page - 1) * this.pagination.per_page;
            return filtered.slice(start, start + this.pagination.per_page);
        },

        /**
         * Calcula el total de páginas basado en los productos filtrados
         */
        totalPages() {
            return Math.ceil(this.filteredProducts.length / this.pagination.per_page);
        }
    },

    actions: {
        async fetchProducts(params = null) {
            this.loading = true;
            try {
                // Si se pasan parámetros, actualizamos los filtros locales
                if (params) {
                    if (params.search !== undefined) this.filters.search = params.search;
                    if (params.categoria_id !== undefined) this.filters.categoria_id = params.categoria_id;
                    if (params.page !== undefined) this.pagination.current_page = params.page;
                }

                // Para unificación, pedimos 'all' por defecto si no se especifica lo contrario
                const queryParams = {
                    all: true,
                    ...params
                };

                const response = await api.get('/productos', { params: queryParams });
                const apiData = response.data;

                if (apiData && Array.isArray(apiData.data)) {
                    // Si viene la estructura de Laravel Paginate (backend paginated)
                    this.products = apiData.data;
                    this.pagination.total = apiData.total;
                    this.pagination.last_page = apiData.last_page;
                    this.pagination.current_page = apiData.current_page;
                } else if (Array.isArray(apiData)) {
                    // Si viene un array simple (full list para frontend paginated)
                    this.products = apiData;
                    // No sobreescribimos total/last_page aquí, los getters se encargarán
                    // de la visualización reactiva basada en filteredProducts
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