import api from './api.service';

const RoleService = {
  getRoles() {
    return api.get('/roles');
  },
  createRole(roleData) {
    return api.post('/roles', roleData);
  },
  updateRole(id, roleData) {
    return api.put(`/roles/${id}`, roleData);
  },
  deleteRole(id) {
    return api.delete(`/roles/${id}`);
  },
  getPermissions() {
    return api.get('/permissions');
  }
};

export default RoleService;
