import api from '../../api';

export const teacherDashboardService = {
  /**
   * Mengambil data statistik, jadwal hari ini, dan tugas untuk Dashboard Guru
   */
  getStats() {
    return api.get('/v1/teacher/dashboard/stats');
  }
};