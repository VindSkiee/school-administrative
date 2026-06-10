import api from '../../api';

export const homeroomService = {
  getHomeroomDetail() {
    return api.get('/v1/teacher/homeroom-class');
  }
};