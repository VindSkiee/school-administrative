import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8000/api', // Sesuaikan dengan URL Laravel Anda
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
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('access_token');
            localStorage.removeItem('user_data');
            window.location.href = '/login'; // Paksa ke halaman login
        }
        return Promise.reject(error);
    }
);

export default api;