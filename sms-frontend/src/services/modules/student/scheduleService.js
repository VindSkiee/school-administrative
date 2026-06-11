import api from "../../api"; 

export const studentScheduleService = {
  getSchedules(day) {
    return api.get(`/v1/student/schedules`, { params: { day } });
  },
  // TAMBAHKAN FUNGSI INI 👇
  getScheduleDetail(id) {
    return api.get(`/v1/student/schedules/${id}`);
  },
  // --- TAMBAHAN UNTUK ABSENSI & PERIZINAN ---
  submitAttendanceRequest(data) {
    // Ingat: Data berbentuk FormData karena mengandung file (proof_file)
    return api.post('/v1/student/attendance-requests', data, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
  },
  getAttendanceRequests() {
    return api.get('/v1/student/attendance-requests');
  }
};