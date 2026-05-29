import api from '../../api';

export const academicYearService = {
    getAll(params = {}) {
        return api.get('/v1/admin/academic-years', { params });
    },
    create(payload) {
        // payload: { name: '2025/2026', semester: 'odd' }
        return api.post('/v1/admin/academic-years', payload);
    },
    update(id, payload) {
        return api.put(`/v1/admin/academic-years/${id}`, payload);
    },
    delete(id) {
        return api.delete(`/v1/admin/academic-years/${id}`);
    },
    setActive(id) {
        // Akan mematikan tahun ajaran lain dan mengaktifkan yang ini
        return api.patch(`/v1/admin/academic-years/${id}/set-active`);
    }
};