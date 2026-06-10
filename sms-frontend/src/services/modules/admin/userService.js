import api from "../../api";

export const userService = {
  /**
   * Mengambil daftar semua pengguna
   */
  getAll(params = {}) {
    return api.get("/v1/admin/users", { params });
  },

  /**
   * Mengambil detail pengguna berdasarkan ID
   * @param {Number|String} id ID pengguna
   */
  getById(id) {
    return api.get(`/v1/admin/users/${id}`);
  },

  /**
   * Membuat pengguna baru
   * @param {Object} payload { name, email, role, password }
   */
  create(payload) {
    return api.post("/v1/admin/users", payload);
  },

  /**
   * Memperbarui data pengguna
   * @param {Number} id ID pengguna
   * @param {Object} payload Data yang diubah
   */
  update(id, payload) {
    return api.put(`/v1/admin/users/${id}`, payload);
  },

  /**
   * Menghapus pengguna
   * @param {Number} id ID pengguna
   */
  delete(id) {
    return api.delete(`/v1/admin/users/${id}`);
  },

  /**
   * Reset password pengguna ke default
   * @param {Number|String} id ID pengguna
   */
  resetPassword(id) {
    return api.patch(`/v1/admin/users/${id}/reset-password`);
  },

  uploadAvatar(id, formData) {
    return api.post(`/users/${id}/avatar`, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  },
};
