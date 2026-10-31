import assert from 'node:assert/strict';
import { validityConfirmation, failureMessage, isRetryable, nextValidity, searchTermError, validateDraft, validityLabel } from './medicamentos.js';

const valid = { code: 'amx-500', name: 'Amoxicilina 500 mg', active_ingredient: 'Amoxicilina', pharmacological_group: 'Antibiótico' };

assert.deepEqual(validateDraft(valid), {}, 'un alta válida no tiene errores (el código se normaliza en mayúsculas)');
assert.ok(validateDraft({ ...valid, code: '-x' }).code, 'el código no puede empezar con guion');
assert.ok(validateDraft({ ...valid, code: 'AMX_500' }).code, 'el guion bajo no es válido');
assert.ok(validateDraft({ ...valid, name: ' A ' }).name, 'el nombre recortado necesita 2 caracteres');
assert.ok(validateDraft({ ...valid, active_ingredient: 'x'.repeat(121) }).active_ingredient, 'máximo 120 caracteres');
assert.equal(Object.keys(validateDraft({})).length, 4, 'un formulario vacío marca los cuatro campos');

assert.equal(searchTermError(' a '), 'Escriba de 2 a 60 caracteres para buscar.');
assert.equal(searchTermError('ác'), null);
assert.ok(searchTermError('x'.repeat(61)));

assert.equal(failureMessage(422, { message: 'El campo name es obligatorio.' }), 'El campo name es obligatorio.');
assert.match(failureMessage(409), /Ya existe/);
assert.match(failureMessage(403, { code: 'RBAC_DENIED', trace_id: 't' }), /No tiene permiso/);
assert.match(failureMessage(503, { message: 'SQLSTATE[08006] detalle interno' }), /No se confirmó/);
assert.doesNotMatch(failureMessage(500, { message: 'PDOException' }), /PDO|SQLSTATE/);
assert.equal(isRetryable(0), true);
assert.equal(isRetryable(503), true);
assert.equal(isRetryable(409), false);
assert.match(failureMessage(429, { error: 'CATALOG_RATE_LIMITED' }), /Espere un minuto/);
assert.equal(isRetryable(429), true);
assert.equal(isRetryable(422), false);

assert.equal(validityLabel('active'), 'Vigente');
assert.equal(nextValidity('active'), 'inactive');
assert.equal(nextValidity('inactive'), 'active');
assert.match(failureMessage(403, { error: 'HOSPITAL_DATABASE_TENANT_MISMATCH' }), /no está disponible en esta instalación/);
assert.deepEqual(validityConfirmation({ code: 'AMX-500', validity: 'active' }),
    { title: 'Inactivar AMX-500', action: 'Inactivar AMX-500', target: 'No vigente' });
assert.equal(validityConfirmation({ code: 'AMX-500', validity: 'inactive' }).action, 'Activar AMX-500');

console.log('OK: reglas de la pantalla del catálogo de medicamentos');
