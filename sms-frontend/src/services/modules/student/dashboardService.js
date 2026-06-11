import api from '../../api';

export const studentDashboardService = {
  getDashboardStats() {
    return api.get('/v1/student/dashboard');
  }
};