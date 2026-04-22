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
  },
  buscarEntidad(data) {
    return api.post('/clientes/buscar-entidad', data);
  }
};

export default ClienteService;
