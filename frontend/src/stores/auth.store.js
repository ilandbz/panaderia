import { defineStore } from 'pinia';
import api from '@/services/api.service';
import { useRouter } from 'vue-router';



export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: (() => {
            try {
                const item = localStorage.getItem('user');
                return item && item !== 'undefined' ? JSON.parse(item) : null;
            } catch (e) {
                return null;
            }
        })(),
        token: localStorage.getItem('token') || null,
    }),

    getters: {
        isLoggedIn: (state) => !!state.token,
        hasPermission: (state) => (permission) => {
            if (!state.user) return false;

            const roles = state.user.roles || [];
            if (roles.some(r => r.name === 'administrador')) return true;

            const userPermissions = state.user.permissions || state.user.permisos || [];
            const rolePermissions = roles.flatMap(r => r.permissions || []).map(p => p.name || p);

            const allPermissions = [...new Set([...userPermissions, ...rolePermissions])];

            return allPermissions.includes(permission);
        },
    },

    actions: {
        clearAuth() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('user');
            localStorage.removeItem('token');
        },

        async login(credentials) {
            try {
                const response = await api.post('/login', credentials);

                const authData = response.data || {};

                this.user = authData.user ?? null;
                this.token = authData.access_token ?? null;

                if (this.user) {
                    localStorage.setItem('user', JSON.stringify(this.user));
                } else {
                    localStorage.removeItem('user');
                }

                if (this.token) {
                    localStorage.setItem('token', this.token);
                } else {
                    localStorage.removeItem('token');
                }

                return response;
            } catch (error) {
                throw error;
            }
        },

        async logout() {
            try {
                if (this.token) {
                    await api.post('/logout');
                }
            } catch (error) {
                //console.error('Error logging out', error);
            } finally {
                //const router = useRouter();
                this.clearAuth();
                //router.push('/login');
            }
        },

        async fetchUser() {
            try {
                const response = await api.get('/user');

                this.user = response.data ?? response;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (error) {
                this.clearAuth();
                throw error;
            }
        }
    }
});