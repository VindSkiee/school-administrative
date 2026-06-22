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
  getAllAssignments() {
    return api.get('/v1/teacher/assignments');
  },
  getAssignmentDetail(id) {
    return api.get(`/v1/teacher/assignments/${id}/submissions`);
  },

  // === Gradebook endpoints ===
  getGradebook(scheduleId, academicYearId) {
    return api.get('/v1/teacher/gradebook', {
      params: { schedule_id: scheduleId, academic_year_id: academicYearId }
    });
  },
  inlineSaveGrade(payload) {
    return api.post('/v1/teacher/gradebook/inline-save', payload);
  }
};