import { defineStore } from 'pinia';
import api, { registerRefreshHandler, setAccessToken } from '@/plugins/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: null,
        tenantId: localStorage.getItem('tenant_id'),
        user: null,
        hospital: null,
        roles: [],
        authError: '',
        initialized: false,
    }),
    actions: {
        setTenantId(tenantId) {
            this.tenantId = tenantId;
            localStorage.setItem('tenant_id', tenantId);
        },
        persistSession(payload) {
            this.token = payload.access_token;
            this.user = payload.user ?? null;
            this.hospital = payload.hospital ?? null;
            this.roles = payload.roles ?? [];
            this.authError = '';
            localStorage.removeItem('auth_token');
            setAccessToken(this.token);
        },
        async login(credentials) {
            registerRefreshHandler(() => this.refreshSession());
            const { data } = await api.post('/auth/login', credentials);
            this.persistSession(data);
            this.initialized = true;

            return data;
        },
        async refreshSession() {
            const { data } = await api.post('/auth/refresh');
            this.persistSession(data);

            return data.access_token;
        },
        async initialize() {
            if (this.initialized) {
                return;
            }

            this.initialized = true;
            registerRefreshHandler(() => this.refreshSession());

            if (! this.tenantId) {
                return;
            }

            try {
                await this.refreshSession();
            } catch (error) {
                this.clearSession();
                if (! error.response || error.response.status !== 401) {
                    this.authError = 'No fue posible restaurar la sesión. Compruebe la conexión e inicie sesión.';
                }
            }
        },
        async logout() {
            let requestError = null;

            try {
                await api.post('/auth/logout');
            } catch (error) {
                requestError = error;
            } finally {
                this.clearSession();
            }

            if (requestError) {
                throw requestError;
            }
        },
        clearSession() {
            this.token = null;
            this.user = null;
            this.hospital = null;
            this.roles = [];
            localStorage.removeItem('auth_token');
            setAccessToken(null);
        },
    },
});
