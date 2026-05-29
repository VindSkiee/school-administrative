import api from '../../api';

export const scheduleService = {
  /**
   * Mengambil jadwal, mendukung filter berdasarkan class_id atau teacher_id
   */
  getAll(params = {}) {
    return api.get('/v1/admin/schedules', { params });
  },

  create(payload) {
    return api.post('/v1/admin/schedules', payload);
  },

  update(id, payload) {
    return api.put(`/v1/admin/schedules/${id}`, payload);
  },

  delete(id) {
    return api.delete(`/v1/admin/schedules/${id}`);
  }
};