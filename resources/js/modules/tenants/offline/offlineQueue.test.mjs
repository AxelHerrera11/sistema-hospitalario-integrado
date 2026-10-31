import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'offlineQueue.js'), 'utf8');
const { OfflineQueue, BLOCKED_TYPES } = await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

function memoryStorage() {
    const data = new Map();

    return { getItem: (k) => data.get(k) ?? null, setItem: (k, v) => data.set(k, v), raw: data };
}

const storage = memoryStorage();
const queue = new OfflineQueue({ storage, now: () => 1_000 });

// La autenticación y la administración de usuarios nunca se pueden encolar.
for (const type of BLOCKED_TYPES) {
    assert.throws(() => queue.register(type, { method: 'POST', url: '/api/v1/auth/login', fields: [] }), /requiere conexión/);
}
assert.throws(() => queue.enqueue('login', { email: 'a@b.co', password: 'x' }), /no está permitida/);

// Solo rutas vigentes /api/v1/ (no /api/auth/*) y sin campos sensibles declarados.
assert.throws(() => queue.register('nota', { method: 'POST', url: '/api/auth/notas', fields: ['texto'] }), /api\/v1/);
assert.throws(() => queue.register('nota', { method: 'POST', url: '/api/v1/notas', fields: ['texto', 'access_token'] }), /sensibles/);

// Un tipo permitido guarda solo sus campos declarados: lo demás se descarta antes de persistir.
queue.register('preferencia', { method: 'PATCH', url: '/api/v1/preferencias', fields: ['tema'], label: 'Preferencia de tema' });
queue.enqueue('preferencia', { tema: 'oscuro', password: 'Clave-Segura-2026!', refresh_token: 'abc.def' });
const persisted = [...storage.raw.values()].join('');
assert.equal(persisted.includes('Clave-Segura-2026!'), false);
assert.equal(persisted.includes('abc.def'), false);
assert.equal(persisted.includes('oscuro'), true);

// El panel solo recibe metadatos, nunca el contenido de la acción.
const [pendiente] = queue.summary().pendientes;
assert.deepEqual(Object.keys(pendiente).sort(), ['creada', 'error', 'estado', 'etiqueta', 'id', 'intentos']);
assert.equal(pendiente.etiqueta, 'Preferencia de tema');

// Al volver la conexión se envía con la ruta y los datos filtrados; luego sale de la cola.
const enviados = [];
const ok = await queue.process(async (req) => { enviados.push(req); });
assert.deepEqual(ok, { synced: 1, failed: 0 });
assert.deepEqual(enviados[0], { method: 'PATCH', url: '/api/v1/preferencias', data: { tema: 'oscuro' } });
assert.equal(queue.summary().pendientes.length, 0);

// Fallos de red se reintentan hasta maxRetries; un 4xx falla de inmediato.
queue.enqueue('preferencia', { tema: 'claro' });
const redCaida = async () => { throw new Error('offline'); };
await queue.process(redCaida);
await queue.process(redCaida);
assert.equal(queue.summary().pendientes.length, 1, 'sigue pendiente tras 2 fallos de red');
await queue.process(redCaida);
assert.equal(queue.summary().fallidas.length, 1);
assert.equal(queue.summary().fallidas[0].error, 'Sin respuesta');

queue.enqueue('preferencia', { tema: 'alto-contraste' });
await queue.process(async (req) => {
    if (req.data.tema === 'alto-contraste') throw { response: { status: 422 } };
});
const fallidas = queue.summary().fallidas;
assert.equal(fallidas.length, 2);
assert.equal(fallidas[1].error, 'HTTP 422');
assert.equal(fallidas[1].intentos, 1, 'un 4xx no se reintenta');

// Reintentar una fallida la devuelve a pendientes; limpiar descarta el resto.
queue.retry(fallidas[0].id);
assert.equal(queue.summary().pendientes.length, 1);
assert.deepEqual(await queue.process(async () => {}), { synced: 1, failed: 0 });
queue.clearFailed();
assert.equal(queue.summary().fallidas.length + queue.summary().pendientes.length, 0);

// La cola sobrevive a una recarga y tolera almacenamiento corrupto.
const otra = new OfflineQueue({ storage, now: () => 2_000 });
otra.register('preferencia', { method: 'PATCH', url: '/api/v1/preferencias', fields: ['tema'] });
otra.enqueue('preferencia', { tema: 'oscuro' });
assert.equal(new OfflineQueue({ storage }).summary().pendientes.length, 1);
storage.setItem('tenants.offline-queue.v1', '{no-json');
assert.equal(new OfflineQueue({ storage }).summary().pendientes.length, 0);

console.log('OK: política offline segura de Tenants aprobada');
