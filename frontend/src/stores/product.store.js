import { defineStore } from 'pinia';
import api from '@/services/api.service';

export const useProductStore = defineStore('product', {
    state: () => ({
        products: [],
        categories: [],
        loading: false,
    }),

    actions: {
        async fetchProducts(params = {}) {
            this.loading = true;
            try {
                const response = await api.get('/productos', { params });
                const payload = response?.data ?? response;
                const dataNode = payload?.data ?? payload;

                this.products = Array.isArray(dataNode?.data)
                    ? dataNode.data
                    : Array.isArray(dataNode)
                        ? dataNode
                        : [];
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