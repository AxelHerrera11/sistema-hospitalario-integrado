import axios from 'axios';

let accessToken = null;
let refreshHandler = null;
let refreshPromise = null;

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL ?? '/api/v1',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export function setAccessToken(token) {
    accessToken = token;
}

export function registerRefreshHandler(handler) {
    refreshHandler = handler;
}

api.interceptors.request.use((config) => {
    const tenantId = localStorage.getItem('tenant_id');

    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`;
    }

    if (tenantId) {
        config.headers['X-Tenant-ID'] = tenantId;
    }

    return config;
});

api.interceptors.response.use((response) => response, async (error) => {
    const request = error.config;
    const refreshable = error.response?.status === 401
        && error.response?.data?.error === 'TOKEN_EXPIRED'
        && request
        && ! request._retried
        && ! request.url?.includes('/auth/login')
        && ! request.url?.includes('/auth/refresh');

    if (! refreshable || ! refreshHandler) {
        return Promise.reject(error);
    }

    request._retried = true;

    try {
        refreshPromise ??= refreshHandler().finally(() => {
            refreshPromise = null;
        });
        const token = await refreshPromise;
        request.headers.Authorization = `Bearer ${token}`;

        return api(request);
    } catch (refreshError) {
        setAccessToken(null);
        window.dispatchEvent(new CustomEvent('auth:session-expired'));

        return Promise.reject(refreshError);
    }
});

export default api;
