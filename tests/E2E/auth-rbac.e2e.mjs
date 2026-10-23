// E2E de pila completa: HTTP → TenantMiddleware → JwtAuth → RBAC → PostgreSQL efímero de CI.
// Solo corre si E2E_API_BASE está definido (job e2e del workflow, BD sembrada con
// tests/E2E/fixtures/seed-e2e.php). Sin esa variable cada caso queda "skipped": nunca cuenta como aprobado.
import { before, describe, test } from 'node:test';
import assert from 'node:assert/strict';
import { client, waitForServer } from './support/http.mjs';

const env = process.env;
const base = env.E2E_API_BASE;
const skip = base ? false : 'E2E_API_BASE no definido: requiere el ambiente aislado del job e2e';
const H1 = env.E2E_HOSPITAL_ID;
const H2 = env.E2E_HOSPITAL_2_ID;
const password = env.E2E_PASSWORD;
const api = base ? client(`${base}/api/v1`) : null;

async function login(email, hospital = H1, pass = password) {
    return api('POST', '/auth/login', { headers: hospital ? { 'X-Tenant-ID': hospital } : {}, body: { email, password: pass } });
}
const bearer = (token, hospital = H1) => ({ Authorization: `Bearer ${token}`, 'X-Tenant-ID': hospital });

describe('E2E-AUTH: autenticación, tenant y RBAC de punta a punta', () => {
    let adminToken;
    let medicoToken;

    base && before(async () => {
        await waitForServer(`${base}/up`);
        for (const name of ['E2E_HOSPITAL_ID', 'E2E_HOSPITAL_2_ID', 'E2E_PASSWORD', 'E2E_ADMIN_EMAIL', 'E2E_MEDICO_EMAIL']) {
            assert.ok(env[name], `${name} es obligatorio en el ambiente E2E`);
        }
    });

    test('E2E-AUTH-01 login sin X-Tenant-ID → 400 TENANT_REQUIRED', { skip }, async () => {
        const response = await login(env.E2E_ADMIN_EMAIL, null);
        assert.equal(response.status, 400);
        assert.equal(response.body.error, 'TENANT_REQUIRED');
    });

    test('E2E-AUTH-02 contraseña incorrecta → 401 INVALID_CREDENTIALS', { skip }, async () => {
        const response = await login(env.E2E_ADMIN_EMAIL, H1, `${password}-x`);
        assert.equal(response.status, 401);
        assert.equal(response.body.error, 'INVALID_CREDENTIALS');
    });

    test('E2E-AUTH-03 login válido de admin_hospital y medico → 200 con access_token', { skip }, async () => {
        const admin = await login(env.E2E_ADMIN_EMAIL);
        assert.equal(admin.status, 200, admin.text);
        adminToken = admin.body.access_token;
        const medico = await login(env.E2E_MEDICO_EMAIL);
        assert.equal(medico.status, 200, medico.text);
        medicoToken = medico.body.access_token;
        assert.ok(adminToken && medicoToken);
    });

    test('E2E-AUTH-04 /auth/me con token → 200 y roles del hospital; sin token → 401', { skip }, async () => {
        const me = await api('GET', '/auth/me', { headers: bearer(adminToken) });
        assert.equal(me.status, 200, me.text);
        assert.ok(me.body.roles.includes('admin_hospital'));
        const anonymous = await api('GET', '/auth/me', { headers: { 'X-Tenant-ID': H1 } });
        assert.equal(anonymous.status, 401);
        assert.equal(anonymous.body.error, 'TOKEN_MISSING');
    });

    test('E2E-AUTH-05 RBAC: admin_hospital lista usuarios (200); medico → 403 RBAC_DENIED', { skip }, async () => {
        const allowed = await api('GET', '/admin/users', { headers: bearer(adminToken) });
        assert.equal(allowed.status, 200, allowed.text);
        const denied = await api('GET', '/admin/users', { headers: bearer(medicoToken) });
        assert.equal(denied.status, 403);
        assert.equal(denied.body.code, 'RBAC_DENIED');
    });

    test('E2E-AUTH-06 medico sin ocupacion.ver → 403 en /occupancy', { skip }, async () => {
        const denied = await api('GET', '/occupancy', { headers: bearer(medicoToken) });
        assert.equal(denied.status, 403);
        assert.equal(denied.body.code, 'RBAC_DENIED');
    });

    test('E2E-AUTH-07 token del hospital 1 con X-Tenant-ID del hospital 2 → 403 TENANT_MISMATCH', { skip }, async () => {
        const response = await api('GET', '/auth/me', { headers: bearer(adminToken, H2) });
        assert.equal(response.status, 403);
        assert.equal(response.body.error, 'TENANT_MISMATCH');
    });

    test('E2E-AUTH-08 logout revoca la sesión: el mismo token → 401 SESSION_REVOKED', { skip }, async () => {
        const out = await api('POST', '/auth/logout', { headers: bearer(medicoToken) });
        assert.equal(out.status, 200, out.text);
        const reused = await api('GET', '/auth/me', { headers: bearer(medicoToken) });
        assert.equal(reused.status, 401);
        assert.equal(reused.body.error, 'SESSION_REVOKED');
    });
});
