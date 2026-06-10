import api from '../../api';

export const assignmentService = {
  getAssignments(scheduleId) {
    return api.get(`/v1/teacher/schedules/${scheduleId}/assignments`);
  },
  createAssignment(formData) {
    return api.post('/v1/teacher/assignments', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },
  deleteAssignment(id) {
    return api.delete(`/v1/teacher/assignments/${id}`);
  },
  getSubmissions(assignmentId) {
    return api.get(`/v1/teacher/assignments/${assignmentId}/submissions`);
  },
  submitGrade(submissionId, payload) {
    return api.post(`/v1/teacher/submissions/${submissionId}/grade`, payload);
  },
  // Tambahkan fungsi ini
  getAllAssignments() {
    return api.get('/v1/teacher/assignments');
  },
  // Mengambil detail tugas beserta semua jawaban siswa
  getAssignmentDetail(id) {
    return api.get(`/v1/teacher/assignments/${id}/submissions`);
  },

  // Mengirim nilai (score & feedback)
  submitGrade(submissionId, payload) {
    return api.post(`/v1/teacher/submissions/${submissionId}/grade`, payload);
  }
};