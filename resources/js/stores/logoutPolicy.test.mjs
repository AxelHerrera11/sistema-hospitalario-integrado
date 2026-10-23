import assert from 'node:assert/strict';
import { createLogoutGuard, isSessionAlreadyClosed } from './logoutPolicy.js';

// 401 con SESSION_REVOKED: la sesion ya esta revocada en el servidor.
assert.ok(isSessionAlreadyClosed({ response: { status: 401, data: { error: 'SESSION_REVOKED' } } }));

// 401 con TOKEN_EXPIRED lo maneja el interceptor de refresco, no el logout.
assert.ok(! isSessionAlreadyClosed({ response: { status: 401, data: { error: 'TOKEN_EXPIRED' } } }));
assert.ok(! isSessionAlreadyClosed({ response: { status: 401, data: {} } }));

// Otros fallos reales conservan el aviso de revocacion no confirmada.
assert.ok(! isSessionAlreadyClosed({ response: { status: 500, data: {} } }));
assert.ok(! isSessionAlreadyClosed({ response: { status: 403, data: { error: 'FORBIDDEN' } } }));
assert.ok(! isSessionAlreadyClosed(new Error('red sin respuesta')));

// El doble clic comparte la misma promesa: no se envia un segundo logout.
const guard = createLogoutGuard();
let envios = 0;
const primera = guard.run(async () => {
    await Promise.resolve();
    envios += 1;
    return 'ok';
});
const segunda = guard.run(async () => {
    envios += 1;
    return 'no';
});
assert.equal(primera, segunda);
assert.equal(await primera, 'ok');
assert.equal(envios, 1);
assert.equal(guard.pending, false);

console.log('OK: la politica de logout distingue revocacion de fallo real y bloquea doble clic');
