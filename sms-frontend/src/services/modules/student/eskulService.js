import api from '../../api';

export const eskulService = {
  getOptions() {
    return api.get('/v1/student/eskuls/options');
  },

  submitSelection(eskulIds) {
    return api.post('/v1/student/eskuls', { eskul_ids: eskulIds });
  },

  getMyEskuls() {
    return api.get('/v1/student/eskuls/my');
  },
};
