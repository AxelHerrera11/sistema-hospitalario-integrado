import { defineStore } from 'pinia';
import api from '@/plugins/axios';

const read = (key, fallback = null) => {
    try {
        return JSON.parse(localStorage.getItem(key) ?? 'null') ?? fallback;
    } catch {
        return fallback;
    }
};

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('auth_token'),
        tenantId: localStorage.getItem('tenant_id'),
        user: read('auth_user'),
        permissions: read('auth_permissions', []),
    }),
    getters: {
        roles: (state) => (state.user?.roles ?? []).map((role) => role.name),
        hasRole() {
            return (role) => this.roles.includes(role);
        },
        can: (state) => (permission) => state.permissions.includes(permission),
    },
    actions: {
        setTenantId(tenantId) {
            this.tenantId = tenantId;
            localStorage.setItem('tenant_id', tenantId);
        },
        persistSession(payload) {
            this.token = payload.access_token;
            this.user = payload.user ?? null;

            if (payload.access_token) {
                localStorage.setItem('auth_token', payload.access_token);
            }

            localStorage.setItem('auth_user', JSON.stringify(this.user));
        },
        async login(credentials) {
            const { data } = await api.post('/auth/login', credentials);

            this.persistSession(data);
            await this.fetchMe();

            return data;
        },
        async fetchMe() {
            const { data } = await api.get('/auth/me');

            this.user = data.user;
            this.permissions = data.permissions ?? [];
            localStorage.setItem('auth_user', JSON.stringify(this.user));
            localStorage.setItem('auth_permissions', JSON.stringify(this.permissions));
        },
        async logout() {
            try {
                await api.post('/auth/logout');
            } catch {
                // Ignorar errores de red al cerrar sesión.
            }

            this.clearSession();
        },
        clearSession() {
            this.token = null;
            this.user = null;
            this.permissions = [];
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            localStorage.removeItem('auth_permissions');
        },
    },
});
