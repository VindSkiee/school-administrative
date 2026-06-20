import api from '../../api';

export const studentReportService = {
  // List academic years the student has classes in
  getAcademicYears() {
    return api.get('/v1/student/reports/academic-years');
  },

  // Ambil data agregat nilai harian + detail tugas (Untuk tabel & dropdown)
  getGradesAggregate(academicYearId) {
    return api.get('/v1/student/grades/aggregate', {
      params: academicYearId ? { academic_year_id: academicYearId } : {},
    });
  },
  
  // Ambil metadata status publikasi rapor (lightweight — never returns 403)
  getSemesterReportStatus(academicYearId) {
    return api.get('/v1/student/reports/report-status', {
      params: academicYearId ? { academic_year_id: academicYearId } : {},
    });
  },

  // Unduh dokumen PDF rapor resmi
  downloadReportPdf(academicYearId) {
    return api.get('/v1/student/reports/semester/pdf', {
      params: academicYearId ? { academic_year_id: academicYearId } : {},
      responseType: 'blob',
    });
  }
};