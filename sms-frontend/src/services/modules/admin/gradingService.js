import api from '../../api';

export const adminGradingService = {
  getGradingSettings(academicYearId) {
    return api.get(`/v1/admin/grading-settings/${academicYearId}`);
  },

  updateGradingSettings(payload) {
    return api.post('/v1/admin/grading-settings', payload);
  },
};
