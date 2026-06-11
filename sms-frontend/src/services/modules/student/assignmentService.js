import api from '../../api';

export const studentAssignmentService = {
  // Ambil daftar tugas khusus untuk jadwal ini (beserta relasi submission & grade)
  getAssignments(params) {
    return api.get('/v1/student/assignments', { params });
  },
  
  // Submit jawaban tugas (Bisa untuk Insert baru atau Update/Edit)
  submitAssignment(assignmentId, formData) {
    return api.post(`/v1/student/assignments/${assignmentId}/submit`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },

  // Download lampiran soal dari guru (Opsional jika endpoint-nya sama dengan materi)
  downloadAttachment(assignmentId, filePath) {
    return api.get(`/v1/student/assignments/${assignmentId}/download`, {
      params: { file: filePath },
      responseType: 'blob'
    });
  }
};