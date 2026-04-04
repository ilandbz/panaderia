import axios from 'axios';
import { useAuthStore } from '@/stores/auth.store';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://panaderia_pasteleria_harold.test/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Interceptor para agregar el token
api.interceptors.request.use((config) => {
    const authStore = useAuthStore();
    if (authStore.token) {
        config.headers.Authorization = `Bearer ${authStore.token}`;
    }
    return config;
});

// Interceptor para manejar errores globales (ej: 401)
api.interceptors.response.use(
    (response) => response.data,
    (error) => {
        if (error.response?.status === 401) {
            const authStore = useAuthStore();
            authStore.logout();
        }
        return Promise.reject(error.response?.data || error);
    }
);

export default api;
