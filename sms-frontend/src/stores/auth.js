import { defineStore } from 'pinia';
import api from '../services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user_data')) || null,
        token: localStorage.getItem('access_token') || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        userRole: (state) => state.user?.role || null,
    },
    actions: {
        async login(credentials) {
            const response = await api.post('/v1/auth/login', credentials);
            
            this.token = response.data.access_token;
            this.user = response.data.user;
            
            localStorage.setItem('access_token', this.token);
            localStorage.setItem('user_data', JSON.stringify(this.user));
            
            return response.data;
        },
        logout() {
            api.post('/v1/auth/logout').finally(() => {
                this.token = null;
                this.user = null;
                localStorage.removeItem('access_token');
                localStorage.removeItem('user_data');
            });
        }
    }
});