// Cliente HTTP mínimo para las pruebas E2E (fetch de Node 22, sin dependencias nuevas).
export function client(base, defaults = {}) {
    if (!base) {
        throw new Error('Falta la URL base del ambiente E2E.');
    }
    const root = base.replace(/\/+$/, '');

    return async function request(method, path, { headers = {}, body, timeoutMs = 10000 } = {}) {
        const response = await fetch(root + path, {
            method,
            headers: { Accept: 'application/json', ...(body === undefined ? {} : { 'Content-Type': 'application/json' }), ...defaults, ...headers },
            body: body === undefined ? undefined : typeof body === 'string' ? body : JSON.stringify(body),
            redirect: 'manual',
            signal: AbortSignal.timeout(timeoutMs),
        });
        const text = await response.text();
        let json = null;
        try {
            json = text === '' ? null : JSON.parse(text);
        } catch {
            json = null; // HTML de error u otra respuesta no JSON: la prueba lo verá como body null.
        }

        return { status: response.status, body: json, text, headers: response.headers };
    };
}

/** Espera a que el servidor responda (cualquier estado HTTP) o falla con un mensaje claro. */
export async function waitForServer(url, attempts = 40, delayMs = 250) {
    for (let i = 0; i < attempts; i++) {
        try {
            await fetch(url, { signal: AbortSignal.timeout(1000) });
            return;
        } catch {
            await new Promise((resolve) => setTimeout(resolve, delayMs));
        }
    }
    throw new Error(`El ambiente E2E no respondió en ${url}.`);
}
