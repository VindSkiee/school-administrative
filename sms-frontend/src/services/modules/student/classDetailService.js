import api from '../../api';

export const studentClassDetailService = {
  getClassDetail() {
    return api.get('/v1/student/class-detail');
  }
};