import api from './api.service';

const ClienteService = {
  getClientes(params = {}) {
    return api.get('/clientes', { params });
  },
  getCliente(id) {
    return api.get(`/clientes/${id}`);
  },
  createCliente(clienteData) {
    return api.post('/clientes', clienteData);
  },
  updateCliente(id, clienteData) {
    return api.put(`/clientes/${id}`, clienteData);
  },
  deleteCliente(id) {
    return api.delete(`/clientes/${id}`);
  }
};

export default ClienteService;
