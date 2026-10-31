import { fileURLToPath, pathToFileURL } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

// Import directo: el módulo reutiliza view-model.js del prototipo con una ruta relativa.
const here = dirname(fileURLToPath(import.meta.url));
const { allowedActions, cuerpoPlan, cuerpoResultado, errorQa, permisosQa, planVacio, validatePlan, validateResult } =
    await import(pathToFileURL(join(here, 'qaCalidad.js')).href);

// Permisos efectivos de la sesión → formato `can` del prototipo; sin lista, nada.
assert.deepEqual(permisosQa({ permissions: ['qa.ejecuciones.ver', 'qa.ejecuciones.registrar', 'qa.gates.evaluar'] }), { register: true, evaluate: true });
assert.deepEqual(permisosQa({ permissions: ['qa.ejecuciones.ver'] }), { register: false, evaluate: false });
assert.deepEqual(permisosQa(null), { register: false, evaluate: false });

// El plan por defecto incluye las suites obligatorias unit y e2e que exige el dominio.
const plan = { ...planVacio(), release_version: ' 1.4.0-rc.1 ', commit_sha: '4F2C9A1BE07D3C6A8E5B9D0F1A2B3C4D5E6F7A8B' };
const cuerpo = cuerpoPlan(plan);
assert.equal(cuerpo.release_version, '1.4.0-rc.1');
assert.equal(cuerpo.commit_sha, '4f2c9a1be07d3c6a8e5b9d0f1a2b3c4d5e6f7a8b');
assert.deepEqual(validatePlan(cuerpo), {});
assert.ok(validatePlan(cuerpoPlan(planVacio())).commit_sha);
assert.ok(validatePlan({ ...cuerpo, suites: [cuerpo.suites[0]] }).suites);

// Resultado: enteros desde el formulario; vacío o texto queda inválido para la validación previa.
const resultado = cuerpoResultado('phpunit-unit', { total: '10', failed: '0', skipped: '0', duration_ms: '1500', path: 'phpunit-unit.xml', sha256: 'A'.repeat(64), bytes: '512' });
assert.equal(resultado.total, 10);
assert.equal(resultado.evidence[0].sha256, 'a'.repeat(64));
assert.deepEqual(validateResult(resultado), {});
assert.ok(validateResult(cuerpoResultado('x', { total: '', failed: '0', skipped: '0', duration_ms: '1', path: 'a.xml', sha256: 'a'.repeat(64), bytes: '1' })).total);
assert.ok(validateResult({ ...resultado, evidence: [{ ...resultado.evidence[0], path: '../secreto' }] }).evidence);

// Acciones según estado y permisos (lógica del prototipo).
const run = { status: 'running', suites: [{ result: null }] };
assert.deepEqual(allowedActions(run, { register: true, evaluate: true }), ['record', 'complete', 'abort']);
assert.deepEqual(allowedActions({ ...run, status: 'completed' }, { register: false, evaluate: true }), ['evaluate']);
assert.deepEqual(allowedActions(run, { register: false, evaluate: false }), []);

// Errores: 401 de la aplicación; el resto con los mensajes del prototipo, sin detalle interno.
assert.equal(errorQa(401, {}).message, 'Inicie sesión para continuar.');
assert.equal(errorQa(403, { code: 'RBAC_DENIED' }).title, 'Acceso denegado');
assert.equal(errorQa(404, {}).message, 'La ejecución no existe en su hospital.');
assert.equal(errorQa(503, { code: 'RBAC_UNAVAILABLE' }).retry, true);

console.log('qaCalidad.test.mjs: OK');
