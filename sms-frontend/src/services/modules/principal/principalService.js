import api from '../../api';

export const principalService = {
  getDashboardStats() {
    return api.get('/v1/principal/dashboard/stats');
  },

  getStaff() {
    return api.get('/v1/principal/staff');
  },

  getGradingSettings() {
    return api.get('/v1/principal/settings/grading');
  },

  updateGradingSettings(payload) {
    return api.put('/v1/principal/settings/grading', payload);
  },
};
