import api from '../../api';

export const studentReportService = {
  // Ambil data agregat nilai harian + detail tugas (Untuk tabel & dropdown)
  getGradesAggregate() {
    return api.get('/v1/student/grades/aggregate');
  },
  
  // Ambil metadata status publikasi rapor
  getSemesterReportStatus() {
    return api.get('/v1/student/reports/semester');
  },

  // Unduh dokumen PDF rapor resmi
  downloadReportPdf() {
    return api.get('/v1/student/reports/semester/pdf', { responseType: 'blob' });
  }
};