import api from '../../api';

export const studentMaterialService = {
  getMaterials(params) {
    return api.get('/v1/student/materials', { params });
  },
  // Tambahkan parameter filePath
  downloadMaterial(id, filePath) {
    return api.get(`/v1/student/materials/${id}/download`, { 
      params: { file: filePath }, // <--- INI PENTING
      responseType: 'blob' 
    });
  }
};