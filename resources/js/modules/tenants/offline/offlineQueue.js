// Cola offline de Tenants (Semana 10). Política segura por diseño:
// - La autenticación y la administración de usuarios NUNCA se encolan: login, refresh, MFA,
//   logout, revocaciones y altas requieren conexión (llevan credenciales o tokens).
// - Solo se encolan tipos registrados explícitamente, con sus campos permitidos; cualquier
//   otro campo se descarta y los nombres sensibles se rechazan aunque estén permitidos.
// - Las rutas deben ser de /api/v1/ y el token de acceso nunca se guarda en la cola:
//   se agrega al enviar, desde la sesión vigente.

export const BLOCKED_TYPES = ['login', 'refresh', 'mfa', 'logout', 'forceRevoke', 'createUser', 'updateUser'];
const SENSITIVE_KEY = /pass|token|secret|otp|code|authorization|cookie/i;
const STORAGE_KEY = 'tenants.offline-queue.v1';

export class OfflineQueue {
    constructor({ storage, now = () => Date.now(), maxRetries = 3 } = {}) {
        this.storage = storage;
        this.now = now;
        this.maxRetries = maxRetries;
        this.definitions = new Map();
        this.listeners = new Set();
        this.syncing = false;
        this.actions = this.load();
    }

    // definition: { method: 'POST'|'PUT'|'PATCH'|'DELETE', url: '/api/v1/...', fields: ['campo'], label }
    register(type, definition) {
        if (BLOCKED_TYPES.includes(type)) throw new Error(`La acción ${type} requiere conexión y no se puede encolar`);
        if (! String(definition.url).startsWith('/api/v1/')) throw new Error('Solo se encolan rutas /api/v1/');
        const sensitive = (definition.fields ?? []).filter((f) => SENSITIVE_KEY.test(f));
        if (sensitive.length) throw new Error(`Campos sensibles no permitidos en la cola: ${sensitive.join(', ')}`);
        this.definitions.set(type, { label: type, fields: [], ...definition });
    }

    enqueue(type, payload = {}) {
        const definition = this.definitions.get(type);
        if (! definition) throw new Error(`La acción ${type} no está permitida sin conexión`);
        const data = Object.fromEntries(definition.fields.filter((f) => f in payload).map((f) => [f, payload[f]]));
        const action = { id: `${this.now()}-${Math.random().toString(36).slice(2, 8)}`, type, data, createdAt: this.now(), attempts: 0, status: 'pending' };
        this.actions.push(action);
        this.save();

        return action.id;
    }

    // send(request) recibe { method, url, data } y debe devolver una promesa (p. ej. axios).
    async process(send) {
        if (this.syncing) return { synced: 0, failed: 0 };
        this.syncing = true;
        this.notify();
        let synced = 0;
        let failed = 0;
        try {
            for (const action of this.actions.filter((a) => a.status === 'pending')) {
                const { method, url } = this.definitions.get(action.type) ?? {};
                try {
                    if (! url) throw new Error('Acción sin definición');
                    await send({ method, url, data: action.data });
                    action.status = 'done';
                    synced += 1;
                } catch (error) {
                    action.attempts += 1;
                    action.lastError = error?.response?.status ? `HTTP ${error.response.status}` : 'Sin respuesta';
                    // Un 4xx no mejora con reintentos; un fallo de red sí.
                    const definitivo = error?.response?.status >= 400 && error?.response?.status < 500;
                    if (definitivo || action.attempts >= this.maxRetries) {
                        action.status = 'failed';
                        failed += 1;
                    }
                }
            }
            this.actions = this.actions.filter((a) => a.status !== 'done');
        } finally {
            this.syncing = false;
            this.save();
        }

        return { synced, failed };
    }

    retry(id) {
        const action = this.actions.find((a) => a.id === id && a.status === 'failed');
        if (action) Object.assign(action, { status: 'pending', attempts: 0 });
        this.save();
    }

    clearFailed() {
        this.actions = this.actions.filter((a) => a.status !== 'failed');
        this.save();
    }

    // Lo que puede mostrarse en pantalla: metadatos, nunca el contenido de la acción.
    describe(action) {
        const definition = this.definitions.get(action.type);

        return { id: action.id, etiqueta: definition?.label ?? action.type, estado: action.status, intentos: action.attempts, creada: action.createdAt, error: action.lastError ?? null };
    }

    summary() {
        return {
            pendientes: this.actions.filter((a) => a.status === 'pending').map((a) => this.describe(a)),
            fallidas: this.actions.filter((a) => a.status === 'failed').map((a) => this.describe(a)),
            sincronizando: this.syncing,
        };
    }

    subscribe(listener) {
        this.listeners.add(listener);

        return () => this.listeners.delete(listener);
    }

    notify() {
        const snapshot = this.summary();
        this.listeners.forEach((listener) => listener(snapshot));
    }

    load() {
        try {
            const raw = this.storage?.getItem(STORAGE_KEY);

            return raw ? JSON.parse(raw) : [];
        } catch {
            return [];
        }
    }

    save() {
        this.storage?.setItem(STORAGE_KEY, JSON.stringify(this.actions));
        this.notify();
    }
}
