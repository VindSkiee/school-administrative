import api from '../../api';

export const eskulService = {
  getAll(params = {}) {
    return api.get('/v1/admin/eskuls', { params });
  },

  getById(id) {
    return api.get(`/v1/admin/eskuls/${id}`);
  },

  create(payload) {
    return api.post('/v1/admin/eskuls', payload);
  },

  update(id, payload) {
    return api.put(`/v1/admin/eskuls/${id}`, payload);
  },

  delete(id) {
    return api.delete(`/v1/admin/eskuls/${id}`);
  },

  assignTeacher(id, teacherId) {
    return api.patch(`/v1/admin/eskuls/${id}/assign-teacher`, { teacher_id: teacherId });
  },

  getTeacherOptions() {
    return api.get('/v1/admin/teachers/options');
  },

  getDeadline(academicYearId) {
    return api.get(`/v1/admin/academic-years/${academicYearId}/eskul-deadline`);
  },

  updateDeadline(academicYearId, deadline) {
    return api.put(`/v1/admin/academic-years/${academicYearId}/eskul-deadline`, { deadline });
  },
};
