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
  },

  /**
   * Mengambil meeting sessions untuk jadwal tertentu
   */
  getMeetingSessions(scheduleId) {
    return api.get(`/v1/admin/schedules/${scheduleId}/meeting-sessions`);
  },

  /**
   * Menukar waktu antara dua jadwal
   * @param {Number} scheduleId1 ID jadwal pertama
   * @param {Number} scheduleId2 ID jadwal kedua
   */
  swap(scheduleId1, scheduleId2) {
    return api.post('/v1/admin/schedules/swap', {
      schedule_id_1: scheduleId1,
      schedule_id_2: scheduleId2,
    });
  },
};