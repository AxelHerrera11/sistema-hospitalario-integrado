// Cliente de la API /api/v1/qa del servidor de demostración.
// La sesión de demo viaja en X-Demo-Session; en Laravel la reemplazan JWT + X-Tenant-ID.

const PROFILES = {
    lider: { register: true, evaluate: true },
    observador: { register: false, evaluate: false },
    lider_h2: { register: true, evaluate: true },
    '': { register: false, evaluate: false },
};

export function permissionsFor(profile) {
    // Solo decide qué botones se muestran; el servidor vuelve a verificar cada acción.
    return PROFILES[profile] ?? PROFILES[''];
}

export function createApi(getContext) {
    async function request(method, path, body) {
        const { profile, failure } = getContext();
        const url = new URL(path, location.origin);
        if (failure) url.searchParams.set('demo_falla', failure);
        try {
            const response = await fetch(url, {
                method,
                headers: { Accept: 'application/json', ...(profile ? { 'X-Demo-Session': profile } : {}), ...(body ? { 'Content-Type': 'application/json' } : {}) },
                body: body ? JSON.stringify(body) : undefined,
                signal: AbortSignal.timeout(8000),
            });
            const json = await response.json().catch(() => null);
            return { ok: response.ok, status: response.status, body: json };
        } catch {
            // Sin red o tiempo agotado: estado 0, la vista conserva lo escrito.
            return { ok: false, status: 0, body: null };
        }
    }

    return {
        list: () => request('GET', '/api/v1/qa/runs?limit=20'),
        get: (id) => request('GET', `/api/v1/qa/runs/${encodeURIComponent(id)}`),
        plan: (plan) => request('POST', '/api/v1/qa/runs', plan),
        transition: (id, transition, reason) => request('POST', `/api/v1/qa/runs/${encodeURIComponent(id)}/transitions`, { transition, reason }),
        record: (id, result) => request('POST', `/api/v1/qa/runs/${encodeURIComponent(id)}/results`, result),
        evaluate: (id) => request('POST', `/api/v1/qa/runs/${encodeURIComponent(id)}/gate-decisions`),
    };
}
