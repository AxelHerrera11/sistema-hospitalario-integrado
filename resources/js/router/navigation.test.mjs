import assert from 'node:assert/strict';
import { authorizeNavigation } from './navigation.js';

const route = { meta: { requiresAuth: true, permission: 'pacientes.editar' } };
function session(permissions = []) {
    return {
        token: 'test-token', user: { permissions }, notice: '', calls: 0,
        async initialize() {},
        async synchronizeIdentity() { this.calls++; },
        setNotice(message) { this.notice = message; },
        clearNotice() { this.notice = ''; },
        clearSession() { this.token = null; this.user = null; },
    };
}
let auth = session(['pacientes.editar']);
assert.equal(await authorizeNavigation(auth, route), undefined);
assert.equal(auth.calls, 1);
auth.synchronizeIdentity = async function () { this.user = { permissions: ['pacientes.ver'] }; };
assert.deepEqual(await authorizeNavigation(auth, route), { name: 'home' });
assert.ok(auth.notice);
for (const status of [401, 403]) {
    auth = session(['pacientes.editar']);
    auth.synchronizeIdentity = async () => { throw { response: { status } }; };
    assert.deepEqual(await authorizeNavigation(auth, route), { name: 'login' });
    assert.equal(auth.token, null);
    assert.equal(auth.user, null);
}
auth = session(['pacientes.editar']);
auth.synchronizeIdentity = async () => { throw new Error('offline'); };
assert.deepEqual(await authorizeNavigation(auth, route), { name: 'home' });
assert.equal(auth.user, null);
assert.ok(auth.notice);
auth = session();
auth.token = null;
assert.deepEqual(await authorizeNavigation(auth, route), { name: 'login' });
assert.equal(auth.calls, 0);
assert.equal(await authorizeNavigation(auth, { meta: {} }), undefined);
console.log('OK: navegacion revalida permisos, revocacion y fallo seguro');
