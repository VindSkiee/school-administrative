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

  skip() {
    return api.post('/v1/student/eskuls/skip');
  },

  getDeadline() {
    return api.get('/v1/student/eskuls/deadline');
  },

  submitChangeRequest() {
    return api.post('/v1/student/eskuls/change-request');
  },

  cancelChangeRequest() {
    return api.delete('/v1/student/eskuls/change-request');
  },
};
