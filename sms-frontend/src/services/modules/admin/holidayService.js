import api from '../../api';

export const holidayService = {
  getAll() {
    return api.get('/v1/admin/holidays');
  },
  create(payload) {
    return api.post('/v1/admin/holidays', payload);
  },
  delete(id) {
    return api.delete(`/v1/admin/holidays/${id}`);
  },
};
