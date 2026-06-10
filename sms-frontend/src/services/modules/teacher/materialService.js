import api from '../../api';

export const materialService = {
  getMaterials(scheduleId, date) {
    return api.get(`/v1/teacher/schedules/${scheduleId}/materials`, {
      params: { date }
    });
  },

  // Gunakan FormData karena kita mengirim File biner
  uploadMaterial(formData) {
    return api.post('/v1/teacher/materials', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
  },

  deleteMaterial(id) {
    return api.delete(`/v1/teacher/materials/${id}`);
  }
};