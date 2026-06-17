import api from '../../api';

export const teacherStudentService = {
  /**
   * Fetch student profile with academic data from teacher context.
   * @param {number|string} studentId - The student's user ID
   * @param {number|string|null} academicYearId - Optional academic year filter
   */
  getStudentProfile(studentId, academicYearId = null) {
    const params = {};
    if (academicYearId) params.academic_year_id = academicYearId;
    return api.get(`/v1/teacher/students/${studentId}`, { params });
  }
};
