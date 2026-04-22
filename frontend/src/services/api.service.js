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

api.interceptors.response.use(
    (response) => response.data,
    (error) => {
        console.log(error)
        if (error.response?.status === 401) {
            const authStore = useAuthStore();

            authStore.clearAuth(); // 🔥 SOLO limpiar
            window.location.href = '/login'; // 🔥 redirección segura
        }

        return Promise.reject(error.response?.data || error);
    }
);

export default api;
