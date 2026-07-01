import api from '../../api';

export const classService = {
    getAll(params = {}) {
        return api.get('/v1/admin/classes', { params });
    },
    getById(id) {
        return api.get(`/v1/admin/classes/${id}`);
    },
    create(payload) {
        // payload: { name, academic_year_id }
        return api.post('/v1/admin/classes', payload);
    },
    update(id, payload) {
        return api.put(`/v1/admin/classes/${id}`, payload);
    },
    delete(id) {
        return api.delete(`/v1/admin/classes/${id}`);
    },
    assignTeacher(id, teacherId) {
        // Hanya menerima role guru
        return api.post(`/v1/admin/classes/${id}/assign-teacher`, { teacher_id: teacherId });
    },
    assignStudents(id, studentIds) {
        // Array of student user_ids
        return api.post(`/v1/admin/classes/${id}/assign-students`, { student_ids: studentIds });
    },
    migrateSemester(payload) {
        // payload = { from_academic_year_id, to_academic_year_id }
        return api.post('/v1/admin/classes/migrate-semester', payload);
    },
    migrateClass(payload) {
        // payload = { to_academic_year_id }
        return api.post('/v1/admin/classes/migrate-class', payload);
    },
    getStudentOptions(classId) {
        return api.get(`/v1/admin/classes/${classId}/student-options`);
    }
};