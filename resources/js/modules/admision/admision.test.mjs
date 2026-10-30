import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'admision.js'), 'utf8');
const { campoInvalido, codigoDeError, cuerpoAdmision, debeRecargarCamas, mensajeDeCampo, mensajeDeError, tiposDeCama } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

// El filtro solo ofrece tipos que existen entre las camas disponibles.
assert.deepEqual(tiposDeCama([{ type: 'icu' }, { type: 'general' }, { type: 'icu' }, { type: '' }]), ['general', 'icu']);
assert.deepEqual(tiposDeCama(undefined), []);

// Validación local en el mismo orden que el caso de uso.
const valido = { patient_id: 'p-1', bed_id: 'b-1', reason: 'Neumonía' };
assert.equal(campoInvalido(valido), null);
assert.equal(campoInvalido({ ...valido, patient_id: '' }), 'patient_id');
assert.equal(campoInvalido({ ...valido, bed_id: null }), 'bed_id');
assert.equal(campoInvalido({ ...valido, reason: '   ' }), 'reason');
assert.equal(mensajeDeCampo('bed_id'), 'Seleccione una cama disponible.');
assert.equal(mensajeDeCampo('reason'), 'Indique el motivo de ingreso.');

// El cuerpo recorta el motivo y nunca envía actor, hospital ni fecha.
assert.deepEqual(cuerpoAdmision({ ...valido, reason: ' Neumonía ', actor: 'x', started_at: 'ayer' }),
    { patient_id: 'p-1', bed_id: 'b-1', reason: 'Neumonía' });

// Códigos de los tres formatos de error presentes en develop.
assert.equal(codigoDeError({ error: { code: 'BED_NOT_AVAILABLE' } }), 'BED_NOT_AVAILABLE');
assert.equal(codigoDeError({ error: 'HOSPITAL_DATABASE_TENANT_MISMATCH' }), 'HOSPITAL_DATABASE_TENANT_MISMATCH');
assert.equal(codigoDeError({ code: 'RBAC_DENIED' }), 'RBAC_DENIED');

// Mensajes seguros y recarga de camas tras perder la carrera por una cama.
assert.equal(mensajeDeError('PATIENT_ALREADY_ADMITTED', 409), 'El paciente ya tiene una hospitalización abierta.');
assert.ok(mensajeDeError('ADMISSIONS_UNAVAILABLE', 503).includes('no está disponible'));
assert.ok(mensajeDeError('RBAC_DENIED', 403).includes('permiso'));
assert.equal(debeRecargarCamas('BED_NOT_AVAILABLE'), true);
assert.equal(debeRecargarCamas('REASON_REQUIRED'), false);

console.log('OK: 17 aserciones de la pantalla de admisión aprobadas');
