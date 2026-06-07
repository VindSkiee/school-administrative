import api from '../../api';

export const dashboardService = {
  getStats() {
    return api.get('/v1/admin/dashboard/stats');
  }
};