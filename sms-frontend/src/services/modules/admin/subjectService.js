import api from '../../api';

export const subjectService = {
  /**
   * Mengambil data mata pelajaran (Mendukung pagination & search)
   * @param {Object} params - Contoh: { page: 1, search: 'Matematika' }
   */
  getAll(params = {}) {
    return api.get('/v1/admin/subjects', { params });
  },

  create(payload) {
    return api.post('/v1/admin/subjects', payload);
  },

  update(id, payload) {
    return api.put(`/v1/admin/subjects/${id}`, payload);
  },

  delete(id) {
    return api.delete(`/v1/admin/subjects/${id}`);
  }
};