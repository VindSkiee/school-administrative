import api from '../../api';

export const teacherStudentService = {
  // Mengambil detail profil siswa (Endpoint khusus jalur Guru)
  getStudentProfile(studentId) {
    return api.get(`/v1/teacher/students/${studentId}`);
  }
};