import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

// Request Interceptor: Suntikkan JWT Token otomatis
api.interceptors.request.use(config => {
    const token = localStorage.getItem('access_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Response Interceptor: Tangkap error 401 global (Sesi Habis)
api.interceptors.response.use(
    response => response,
    async error => {
        const redirectTo = (path) => {
            if (window.location.pathname !== path) {
                window.location.href = path;
            }
        };

        if (!error.response) {
            redirectTo('/server-down');
            return Promise.reject(error);
        }

        const status = error.response.status;
        const errorCode = error.response.data?.error;

        if (status === 403 && errorCode === 'PASSWORD_CHANGE_REQUIRED') {
            try {
                const routerModule = await import('../router');
                const router = routerModule.default;
                const currentPath = router.currentRoute.value.path;

                if (currentPath !== '/force-change-password') {
                    await router.push('/force-change-password');
                }
            } catch {
                redirectTo('/force-change-password');
            }

            return Promise.reject(error);
        }

        // Tangkap error 401 (Unauthorized / Sesi Habis / Kredensial Salah)
        if (status === 401) {
            localStorage.removeItem('access_token');
            localStorage.removeItem('user_data');
            
            // CEGAH HARD RELOAD JIKA USER SEDANG BERADA DI HALAMAN LOGIN
            // Hard reload HANYA terjadi jika sesi expired saat user berada di dalam Dashboard
            if (window.location.pathname !== '/login') {
                window.location.href = '/login'; 
            }
        }

        if (status === 403) {
            // Mencegah hard-redirect untuk endpoint API siswa.
            // Biarkan halaman Vue (seperti StudentDashboard) menangani UI-nya sendiri.
            const url = error.config.url || '';
            const isStudentRequest = url.includes('/student/');

            if (!isStudentRequest) {
                redirectTo('/unauthorized');
            }
        }

        if ([502, 503, 504].includes(status)) {
            redirectTo('/server-down');
        }

        return Promise.reject(error);
    }
);

export default api;