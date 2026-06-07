import api from '../../api';

export const activityLogService = {
  getAll(params) {
    return api.get('/v1/admin/activity-logs', { params });
  }
};