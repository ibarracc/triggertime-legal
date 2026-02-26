import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor to add JWT token
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('tt_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

// Response interceptor to handle errors
api.interceptors.response.use((response) => {
    return response.data;
}, (error) => {
    if (error.response && error.response.status === 401) {
        // Optional: handle logout on 401
        const isLoginRequest = error.config && error.config.url && error.config.url.includes('/auth/login');
        const isLoginPage = window.location.pathname === '/login';

        if (!isLoginRequest && !isLoginPage) {
            localStorage.removeItem('tt_token');
            window.location.href = '/login';
        }
    }
    return Promise.reject(error);
});

export default api;
