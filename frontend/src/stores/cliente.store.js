import { defineStore } from 'pinia';
import ClienteService from '@/services/cliente.service';

export const useClienteStore = defineStore('cliente', {
  state: () => ({
    clientes: [],
    pagination: {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 10
    },
    loading: false,
  }),
  actions: {
    async fetchClientes(params = {}) {
      // Si recibimos un string, lo convertimos al formato de búsqueda objeto
      if (typeof params === 'string') {
        params = { search: params };
      }

      this.loading = true;
      try {
        const response = await ClienteService.getClientes(params);
        const result = response.data.data;
        let dataToReturn = [];

        if (result && result.data) {
          this.clientes = result.data;
          this.pagination = {
            current_page: result.current_page,
            last_page: result.last_page,
            total: result.total,
            per_page: result.per_page
          };
          dataToReturn = result.data;
        } else {
          this.clientes = result || [];
          dataToReturn = this.clientes;
        }
        return dataToReturn;
      } catch (error) {
        console.error('Error al cargar clientes:', error);
        return [];
      } finally {
        this.loading = false;
      }
    },
    async createCliente(clienteData) {
      this.loading = true;
      try {
        const response = await ClienteService.createCliente(clienteData);
        return response.data;
      } finally {
        this.loading = false;
      }
    },
    async updateCliente(id, clienteData) {
      this.loading = true;
      try {
        const response = await ClienteService.updateCliente(id, clienteData);
        return response.data;
      } finally {
        this.loading = false;
      }
    },
    async deleteCliente(id) {
      this.loading = true;
      try {
        const response = await ClienteService.deleteCliente(id);
        return response.data;
      } finally {
        this.loading = false;
      }
    }
  }
});
