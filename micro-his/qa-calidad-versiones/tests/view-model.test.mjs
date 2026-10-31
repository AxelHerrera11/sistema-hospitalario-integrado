// Pruebas de la lógica pura del prototipo: node --test micro-his/qa-calidad-versiones/tests/view-model.test.mjs
import test from 'node:test';
import assert from 'node:assert/strict';
import { allowedActions, errorMessage, findingText, mainReason, validatePlan, validateResult, VERDICTS } from '../public/prototipo/view-model.js';

const plan = {
    release_version: '1.4.0',
    commit_sha: '4f2c9a1be07d3c6a8e5b9d0f1a2b3c4d5e6f7a8b',
    environment: 'ci',
    suites: [{ name: 'phpunit-unit', kind: 'unit', required: true }, { name: 'e2e-auth', kind: 'e2e', required: true }],
};

test('VM01 plan válido no tiene errores; producción y SHA corto sí', () => {
    assert.deepEqual(validatePlan(plan), {});
    const errors = validatePlan({ ...plan, environment: 'production', commit_sha: '4f2c9a1' });
    assert.ok(errors.environment.includes('Producción'));
    assert.ok(errors.commit_sha);
});

test('VM02 plan sin E2E obligatoria o con nombres repetidos se marca', () => {
    assert.ok(validatePlan({ ...plan, suites: [plan.suites[0], { ...plan.suites[1], required: false }] }).suites.includes('E2E'));
    assert.ok(validatePlan({ ...plan, suites: [plan.suites[0], plan.suites[0]] }).suites.includes('repetidos'));
});

test('VM03 resultado: límites de conteo y huella', () => {
    const ok = { total: 3, failed: 1, skipped: 2, duration_ms: 0, evidence: [{ path: 'e2e/a.xml', sha256: 'a'.repeat(64), bytes: 512 }] };
    assert.deepEqual(validateResult(ok), {});
    assert.ok(validateResult({ ...ok, skipped: 3 }).failed);
    assert.ok(validateResult({ ...ok, evidence: [{ path: '../x', sha256: 'a'.repeat(64), bytes: 512 }] }).evidence);
    // H-03: el tamaño ya no se inventa; sin bytes reales la evidencia no se envía.
    assert.match(validateResult({ ...ok, evidence: [{ path: 'e2e/a.xml', sha256: 'a'.repeat(64), bytes: NaN }] }).evidence, /tamaño real/);
});

test('VM04 acciones: el observador no ve botones de escritura; running sin pendientes no ofrece registrar', () => {
    const run = { status: 'running', suites: [{ result: {} }] };
    assert.deepEqual(allowedActions(run, { register: false, evaluate: false }), []);
    assert.deepEqual(allowedActions(run, { register: true, evaluate: true }), ['complete', 'abort']);
    assert.deepEqual(allowedActions({ status: 'completed', suites: [] }, { register: true, evaluate: true }), ['evaluate']);
});

test('VM05 mensajes de error: 503 permite reintentar, 403 no', () => {
    assert.equal(errorMessage(503, { code: 'QA_STORAGE_UNAVAILABLE' }).retry, true);
    assert.equal(errorMessage(403, { code: 'RBAC_DENIED' }).retry, false);
    assert.match(errorMessage(0, null).message, /se conservan/);
});

test('VM07 cambio de la semana 12: motivo principal, bloqueo antes que rechazo; aprobado sin motivo', () => {
    const gate = { findings: [{ rule: 'SUITE_BELOW_THRESHOLD', severity: 'rejection', suite: 'unit' }, { rule: 'SUITE_MISSING', severity: 'blocker', suite: 'e2e' }] };
    assert.equal(mainReason(gate), 'e2e: No se registró resultado.');
    assert.equal(mainReason({ findings: [{ rule: 'E2E_SLOW', severity: 'warning', suite: 'e2e' }] }), null);
    assert.equal(mainReason(null), null);
});

test('VM06 veredictos y hallazgos tienen texto para la persona usuaria', () => {
    assert.equal(VERDICTS.blocked.tone, 'warn');
    assert.equal(findingText({ rule: 'SUITE_MISSING', suite: 'e2e' }), 'e2e: No se registró resultado.');
});
