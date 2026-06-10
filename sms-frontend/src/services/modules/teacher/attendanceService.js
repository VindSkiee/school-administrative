import api from "../../api";

export const attendanceService = {
  // Jadwal Mengajar (Sudah kita buat sebelumnya)
  getSchedules(day = null) {
    return api.get("/v1/teacher/schedules/today", {
      params: day ? { day } : {},
    });
  },

  // Mengambil daftar siswa untuk form absensi
  getStudentsForAttendance(scheduleId) {
    return api.get(`/v1/teacher/schedules/${scheduleId}/students`);
  },

  // Submit bulk absensi
  storeBulk(payload) {
    return api.post("/v1/teacher/attendances/bulk", payload);
  },

  // Mengambil request izin/sakit siswa
  getAttendanceRequests(params = {}) {
    return api.get("/v1/teacher/attendance-requests", { params });
  },

  // Menyetujui / Menolak request
  reviewRequest(id, status) {
    return api.patch(`/v1/teacher/attendance-requests/${id}/review`, {
      status,
    });
  },

  // Tambahkan di bawah getStudentsForAttendance
  getExistingAttendances(scheduleId, date) {
    return api.get(`/schedules/${scheduleId}/attendances`, {
      params: { date }
    });
  },

  // Tambahkan di dalam object attendanceService
  getScheduleDetail(scheduleId) {
    return api.get(`/v1/teacher/schedules/${scheduleId}`);
  },
};
