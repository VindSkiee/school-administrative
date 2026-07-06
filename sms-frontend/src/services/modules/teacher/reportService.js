import api from '../../api';

export const reportService = {
  getAcademicYears() {
    return api.get('/v1/teacher/report/academic-years');
  },
  getHomeroomClass(academicYearId) {
    return api.get('/v1/teacher/report/homeroom-class', {
      params: { academic_year_id: academicYearId },
    });
  },
  getStudents(academicYearId) {
    return api.get('/v1/teacher/report/students', {
      params: { academic_year_id: academicYearId },
    });
  },
  saveNotes(payload) {
    return api.post('/v1/teacher/report/notes', payload);
  },
  downloadPdf(studentId, academicYearId) {
    return api.get(`/v1/teacher/report/pdf/${studentId}`, {
      params: { academic_year_id: academicYearId },
      responseType: 'blob',
    });
  },
};
