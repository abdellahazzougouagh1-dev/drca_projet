import axios from 'axios';

const rawApiUrl = String(import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api');
const cleanApiUrl = rawApiUrl.split(/[\r\n]+/)[0].trim().replace(/\/+$/, '');

const api = axios.create({
    baseURL: cleanApiUrl,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');

    if (token && config.headers) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('token');
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;
