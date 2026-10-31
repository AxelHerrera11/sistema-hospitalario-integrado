import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'catalogo.js'), 'utf8');
const { campoInvalido, codigoDeError, cuerpoRegistro, mensajeDeCampo, mensajeDeError, rangoLegible } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

const hoy = '2026-10-23';
const glucosa = { code: 'glu', name: 'Glucosa', specimen_type: 'serum', min_volume_ml: '0.5', result_type: 'numeric', unit: 'mg/dL',
    critical_low: '40', reference_low: '70', reference_high: '100', critical_high: '400', valid_from: hoy };

// Validación local en el mismo orden que LabTest::register.
assert.equal(campoInvalido(glucosa, hoy), null);
assert.equal(campoInvalido({ ...glucosa, name: ' ' }, hoy), 'name');
assert.equal(campoInvalido({ ...glucosa, code: 'G LU' }, hoy), 'code');
assert.equal(campoInvalido({ ...glucosa, min_volume_ml: '0' }, hoy), 'min_volume_ml');
assert.equal(campoInvalido({ ...glucosa, critical_low: '80' }, hoy), 'reference_low');
assert.equal(campoInvalido({ ...glucosa, unit: '' }, hoy), 'unit');
assert.equal(campoInvalido({ ...glucosa, valid_from: '2026-10-22' }, hoy), 'valid_from');
assert.equal(campoInvalido({ ...glucosa, result_type: 'qualitative', unit: '', critical_low: '' }, hoy), null);
assert.equal(mensajeDeCampo('name', { name: '' }), 'Complete los campos obligatorios.');
assert.ok(mensajeDeCampo('reference_low', glucosa).startsWith('Los rangos'));

// El cuerpo normaliza el código, convierte números y omite rangos si el resultado no es numérico; nunca envía el autor.
assert.deepEqual(cuerpoRegistro({ ...glucosa, created_by_user_ref_id: 'x' }), { code: 'GLU', name: 'Glucosa', specimen_type: 'serum',
    min_volume_ml: 0.5, result_type: 'numeric', valid_from: hoy, unit: 'mg/dL', critical_low: 40, reference_low: 70, reference_high: 100, critical_high: 400 });
assert.equal('critical_low' in cuerpoRegistro({ ...glucosa, result_type: 'text' }), false);

assert.equal(rangoLegible({ result_type: 'numeric', reference_low: 70, reference_high: 100, unit: 'mg/dL' }), '70–100 mg/dL');
assert.equal(rangoLegible({ result_type: 'qualitative' }), '—');

// Códigos de los tres formatos de error y mensajes seguros.
assert.equal(codigoDeError({ error: { code: 'LAB_TEST_VALIDITY_OVERLAP' } }), 'LAB_TEST_VALIDITY_OVERLAP');
assert.equal(codigoDeError({ code: 'RBAC_DENIED' }), 'RBAC_DENIED');
assert.equal(mensajeDeError('LAB_TEST_VALIDITY_OVERLAP', 409), 'Ya hay una versión vigente de esa prueba.');
assert.ok(mensajeDeError('LAB_CATALOG_UNAVAILABLE', 503).includes('no está disponible'));
assert.ok(mensajeDeError('RBAC_DENIED', 403).includes('permiso'));

console.log('OK: 19 aserciones de la pantalla del catálogo de laboratorio aprobadas');
