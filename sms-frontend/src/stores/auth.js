import { defineStore } from "pinia";
import api from "../services/api";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: JSON.parse(localStorage.getItem("user_data")) || null,
    token: localStorage.getItem("access_token") || null,
    expiresAt: localStorage.getItem("token_expires_at") || null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role || null,
    mustChangePassword: (state) => !!state.user?.must_change_password,
    isTokenExpired: (state) => {
      if (!state.expiresAt) return false;
      return Date.now() > new Date(state.expiresAt).getTime();
    },
  },
  actions: {
    async checkEmail(email) {
      try {
        const response = await api.post("/v1/auth/check-requirements", {
          email,
        });
        return response.data.requires_captcha;
      } catch (error) {
        console.error("Gagal mengecek status email:", error);
        return false; // Fallback jika network error, biarkan validasi password yang bekerja nanti
      }
    },
    async login(credentials) {
      const response = await api.post("/v1/auth/login", credentials);

      this.token = response.data.access_token;
      this.user = response.data.user;
      this.expiresAt = response.data.expires_at;

      localStorage.setItem("access_token", this.token);
      localStorage.setItem("user_data", JSON.stringify(this.user));
      localStorage.setItem("token_expires_at", this.expiresAt);

      return response.data;
    },
    async logout() {
      try {
        // Beri tahu backend untuk menghancurkan token (jika ada route-nya)
        await api.post("/v1/auth/logout");
      } catch (error) {
        // Abaikan jika error (misalnya koneksi putus atau token sudah expired)
        console.warn("Backend logout failed, proceeding with local logout.");
      } finally {
        // Langkah ini WAJIB dijalankan bagaimanapun hasil dari API
        this.token = null;
        this.user = null;
        this.expiresAt = null;
        localStorage.removeItem("access_token");
        localStorage.removeItem("user_data");
        localStorage.removeItem("token_expires_at");
      }
    },
    markPasswordAsChanged() {
      if (!this.user) {
        return;
      }

      this.user = {
        ...this.user,
        must_change_password: false,
      };

      localStorage.setItem("user_data", JSON.stringify(this.user));
    },
    updateUserAvatar(newAvatarUrl) {
      if (this.user) {
        this.user.avatar_url = newAvatarUrl;
        localStorage.setItem("user_data", JSON.stringify(this.user));
      }
    },
  },
});
