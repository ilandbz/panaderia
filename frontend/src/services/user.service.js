import api from './api.service';

const UserService = {
  getUsers() {
    return api.get('/users');
  },
  createUser(userData) {
    return api.post('/users', userData);
  },
  updateUser(id, userData) {
    return api.put(`/users/${id}`, userData);
  },
  deleteUser(id) {
    return api.delete(`/users/${id}`);
  },
  toggleStatus(id) {
    return api.patch(`/users/${id}/toggle-status`);
  }
};

export default UserService;
