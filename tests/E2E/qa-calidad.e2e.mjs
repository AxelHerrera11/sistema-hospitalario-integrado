// E2E del módulo QA/CI contra su servidor HTTP aislado (SQLite temporal, datos ficticios).
// Uso: php -S 127.0.0.1:8093 micro-his/qa-calidad-versiones/public/index.php
//      QA_E2E_BASE=http://127.0.0.1:8093 node --test tests/E2E/qa-calidad.e2e.mjs
import { before, describe, test } from 'node:test';
import assert from 'node:assert/strict';
import { client, waitForServer } from './support/http.mjs';

const base = process.env.QA_E2E_BASE ?? 'http://127.0.0.1:8093';
const as = (session) => client(base, session ? { 'X-Demo-Session': session } : {});
const lider = as('lider');
const sha = '4f2c9a1be07d3c6a8e5b9d0f1a2b3c4d5e6f7a8b';
const evidence = (path) => ({ kind: 'junit', path, sha256: 'a'.repeat(64), bytes: 512 });
const plan = {
    release_version: '1.4.0-rc.1',
    commit_sha: sha,
    environment: 'ci',
    suites: [
        { name: 'phpunit-unit', kind: 'unit', required: true },
        { name: 'e2e-auth-rbac', kind: 'e2e', required: true },
    ],
};

async function runWith(results) {
    const created = await lider('POST', '/api/v1/qa/runs', { body: plan });
    assert.equal(created.status, 201, created.text);
    const id = created.body.data.id;
    assert.equal((await lider('POST', `/api/v1/qa/runs/${id}/transitions`, { body: { transition: 'start' } })).status, 200);
    for (const result of results) {
        const recorded = await lider('POST', `/api/v1/qa/runs/${id}/results`, { body: result });
        assert.equal(recorded.status, 201, recorded.text);
    }
    assert.equal((await lider('POST', `/api/v1/qa/runs/${id}/transitions`, { body: { transition: 'complete' } })).status, 200);
    const gate = await lider('POST', `/api/v1/qa/runs/${id}/gate-decisions`);
    assert.equal(gate.status, 201, gate.text);

    return { id, gate: gate.body.data };
}

const result = (suite, total, failed = 0) => ({ suite, total, failed, skipped: 0, duration_ms: 1500, evidence: [evidence(`${suite}.xml`)] });

before(async () => {
    await waitForServer(`${base}/prototipo/`);
    const reset = await client(base)('POST', '/demo/reset');
    assert.equal(reset.status, 200, 'el ambiente E2E debe poder reiniciarse antes de las pruebas');
});

describe('E2E-QA: planificación → ejecución → evidencia → quality gate', () => {
    test('E2E-QA-01 camino feliz: suites verdes con evidencia → approved y visible en el detalle', async () => {
        const { id, gate } = await runWith([result('phpunit-unit', 107), result('e2e-auth-rbac', 10)]);
        assert.equal(gate.verdict, 'approved');
        const detail = await as('observador')('GET', `/api/v1/qa/runs/${id}`);
        assert.equal(detail.status, 200);
        assert.equal(detail.body.data.gate.verdict, 'approved');
        assert.equal(detail.body.data.suites[0].result.evidence[0].path, 'phpunit-unit.xml');
    });

    test('E2E-QA-02 una prueba E2E obligatoria en rojo → rejected con la regla SUITE_BELOW_THRESHOLD', async () => {
        const { gate } = await runWith([result('phpunit-unit', 107), result('e2e-auth-rbac', 10, 1)]);
        assert.equal(gate.verdict, 'rejected');
        assert.ok(gate.findings.some((f) => f.rule === 'SUITE_BELOW_THRESHOLD' && f.suite === 'e2e-auth-rbac'));
    });

    test('E2E-QA-03 la etapa E2E no reportó → blocked (falta evidencia, no se aprueba por omisión)', async () => {
        const { gate } = await runWith([result('phpunit-unit', 107)]);
        assert.equal(gate.verdict, 'blocked');
        assert.ok(gate.findings.some((f) => f.rule === 'SUITE_MISSING' && f.severity === 'blocker'));
    });
});

describe('E2E-QA: permisos, tenant y errores', () => {
    test('E2E-QA-04 sin sesión → 401; observador no planifica → 403 RBAC_DENIED', async () => {
        assert.equal((await as(null)('GET', '/api/v1/qa/runs')).status, 401);
        const denied = await as('observador')('POST', '/api/v1/qa/runs', { body: plan });
        assert.equal(denied.status, 403);
        assert.equal(denied.body.code, 'RBAC_DENIED');
    });

    test('E2E-QA-05 una ejecución del hospital 1 no existe para el hospital 2 (404) y no aparece en su listado', async () => {
        const { id } = await runWith([result('phpunit-unit', 5), result('e2e-auth-rbac', 2)]);
        const other = as('lider_h2');
        assert.equal((await other('GET', `/api/v1/qa/runs/${id}`)).status, 404);
        const list = await other('GET', '/api/v1/qa/runs');
        assert.equal(list.status, 200);
        assert.ok(list.body.data.every((run) => run.tenant_id !== 'hospital-pruebas-1'));
    });

    test('E2E-QA-06 X-Tenant-ID ajeno → 403 TENANT_MISMATCH', async () => {
        const response = await lider('GET', '/api/v1/qa/runs', { headers: { 'X-Tenant-ID': 'hospital-pruebas-2' } });
        assert.equal(response.status, 403);
        assert.equal(response.body.code, 'TENANT_MISMATCH');
    });

    test('E2E-QA-07 almacén o RBAC caídos → 503 con código, sin detalle técnico', async () => {
        const storage = await lider('GET', '/api/v1/qa/runs?demo_falla=almacen');
        assert.equal(storage.status, 503);
        assert.equal(storage.body.code, 'QA_STORAGE_UNAVAILABLE');
        assert.equal(storage.body.message, undefined);
        const rbac = await lider('GET', '/api/v1/qa/runs?demo_falla=rbac');
        assert.equal(rbac.status, 503);
        assert.equal(rbac.body.code, 'RBAC_UNAVAILABLE');
    });

    test('E2E-QA-08 una ejecución completada no admite más resultados (409)', async () => {
        const { id } = await runWith([result('phpunit-unit', 5), result('e2e-auth-rbac', 2)]);
        const late = await lider('POST', `/api/v1/qa/runs/${id}/results`, { body: result('phpunit-unit', 5) });
        assert.equal(late.status, 409);
        assert.equal(late.body.code, 'QA_RUN_STATE_CONFLICT');
    });

    test('E2E-QA-09 el prototipo navegable se sirve y respeta la cabecera de idioma', async () => {
        const page = await client(base)('GET', '/prototipo/');
        assert.equal(page.status, 200);
        assert.match(page.text, /<html lang="es">/);
    });
});
