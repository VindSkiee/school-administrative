import api from '../../api';

export const principalService = {
  getDashboardStats() {
    return api.get('/v1/principal/dashboard/stats');
  },

  // YoY School Performance Index (last 5 academic years)
  getYearOverYear() {
    return api.get('/v1/principal/dashboard/yoy');
  },

  // Current grade distribution (A/B/C/D)
  getGradeDistribution() {
    return api.get('/v1/principal/dashboard/grade-distribution');
  },

  // Curriculum Trend — grade-level perspective over published years
  getCurriculumTrend(gradeLevel, limit = 5) {
    return api.get('/v1/principal/dashboard/curriculum-trend', {
      params: { grade_level: gradeLevel, limit },
    });
  },

  // Cohort Trend — student batch growth across grade levels
  getCohortTrend(entryYearIds) {
    // Accepts single ID or array of IDs (combined odd+even)
    const ids = Array.isArray(entryYearIds) ? entryYearIds : [entryYearIds];
    const params = {};
    ids.forEach((id, i) => { params[`entry_year_ids[${i}]`] = id; });
    return api.get('/v1/principal/dashboard/cohort-trend', { params });
  },

  // Available academic years for cohort selection
  getCohortYears() {
    return api.get('/v1/principal/dashboard/academic-years');
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
