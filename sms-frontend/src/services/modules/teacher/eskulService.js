import api from '../../api';

export const eskulService = {
  getAssignedEskuls() {
    return api.get('/v1/teacher/eskul/assigned');
  },

  getStudents(params = {}) {
    return api.get('/v1/teacher/eskul/students', { params });
  },

  gradeStudents(grades) {
    return api.post('/v1/teacher/eskul/grade', { grades });
  },
};
