import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL ?? '/api/v1',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    const tenantId = localStorage.getItem('tenant_id');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    if (tenantId) {
        config.headers['X-Tenant-ID'] = tenantId;
    }

    return config;
});

// Token vencido o inválido: limpiar sesión y volver al login.
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error?.response?.status;
        const isLoginRequest = error?.config?.url?.includes('/auth/login');

        if (status === 401 && ! isLoginRequest) {
            ['auth_token', 'auth_user', 'auth_permissions'].forEach((key) => localStorage.removeItem(key));

            if (window.location.pathname !== '/login') {
                window.location.assign('/login');
            }
        }

        return Promise.reject(error);
    },
);

export default api;
