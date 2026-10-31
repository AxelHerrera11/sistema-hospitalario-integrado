import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'medicos.js'), 'utf8');
const {
    isRealDate, availabilityError, doctorErrors, associationErrors, failureMessage, isRetryable, availabilityRows, PERMISSIONS,
} = await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

// Fechas: existen en el calendario.
assert.equal(isRealDate('2026-10-19'), true);
assert.equal(isRealDate('2026-02-30'), false);
assert.equal(isRealDate('19/10/2026'), false);
assert.equal(isRealDate(20261019), false);

// Consulta de disponibilidad.
assert.equal(availabilityError({ specialty: 'NEURO', date: '2026-10-19' }), '');
assert.match(availabilityError({ specialty: '  ', date: '2026-10-19' }), /especialidad/);
assert.match(availabilityError({ specialty: 'NEURO', date: '2026-02-30' }), /fecha/);

// Alta: cada campo obligatorio se señala por separado.
const alta = { local_user_ref_id: 'u1', license_number: 'COL-1', specialty_code: 'CARD', valid_from: '2026-01-01' };
assert.deepEqual(doctorErrors(alta), {});
assert.deepEqual(Object.keys(doctorErrors({ ...alta, license_number: ' ', valid_from: '' })).sort(), ['license_number', 'valid_from']);

// Asociación: la vigencia final es opcional pero posterior al inicio.
const asociacion = { doctor_id: 'd1', specialty_code: 'NEURO', valid_from: '2026-02-01', valid_to: '' };
assert.deepEqual(associationErrors(asociacion), {});
assert.ok(associationErrors({ ...asociacion, valid_to: '2026-02-01' }).valid_to);
assert.ok(associationErrors({ ...asociacion, doctor_id: '' }).doctor_id);

// Mensajes: por código del módulo, luego por estado; nunca el texto del servidor.
assert.match(failureMessage('LICENSE_ALREADY_REGISTERED', 409), /licencia/);
assert.match(failureMessage('PRIMARY_SPECIALTY_CONFLICT', 409), /principal/);
assert.match(failureMessage(undefined, 403), /permiso/);
assert.match(failureMessage('DOCTOR_STORAGE_UNAVAILABLE', 503), /no está disponible/);
assert.equal(failureMessage('X', 418), 'No se pudo completar la operación.');

// Reintento solo ante fallos del servidor o de red.
assert.equal(isRetryable(503), true);
assert.equal(isRetryable(undefined), true);
assert.equal(isRetryable(422), false);
assert.equal(isRetryable(403), false);

// Disponibilidad: sin horario no es lo mismo que sin franjas libres.
const filas = availabilityRows({
    doctors: [
        { doctor_id: 'a', status: 'OK', slots: ['08:00', '09:00'] },
        { doctor_id: 'b', status: 'OK', slots: [] },
        { doctor_id: 'c', status: 'SCHEDULE_NOT_CONFIGURED', slots: [] },
    ],
});
assert.deepEqual(filas.map((f) => f.estado), ['Con franjas libres', 'Sin franjas libres', 'Sin horario configurado']);
assert.deepEqual(availabilityRows(undefined), []);

// Los permisos coinciden con los del catálogo RBAC del backend.
assert.deepEqual(Object.values(PERMISSIONS).sort(), ['medicos.asociar_especialidad', 'medicos.registrar', 'medicos.ver_disponibilidad']);

console.log('OK: aserciones de la pantalla de médicos aprobadas');
