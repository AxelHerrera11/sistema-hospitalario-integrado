import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'alergias.js'), 'utf8');
const { banner, campoInvalido, codigoDeError, cuerpoRegistro, etiqueta, mensajeDeCampo, mensajeDeError, SEVERIDADES } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

// Falla segura: un nivel desconocido o ausente se muestra como «no disponible» con role="alert".
assert.equal(banner('critical').rol, 'alert');
assert.equal(banner('none').rol, 'status');
assert.equal(banner(undefined).clase, 'unavailable');
assert.equal(banner('otro').rol, 'alert');

// Validación local en el mismo orden que Allergy::register.
const valido = { allergen_name: 'Penicilina', allergy_type: 'allergy', severity: 'severe' };
assert.equal(campoInvalido(valido), null);
assert.equal(campoInvalido({ ...valido, allergen_name: '   ' }), 'allergen_name');
assert.equal(campoInvalido({ ...valido, allergen_name: '', allergen_code: 'J01CA' }), null);
assert.equal(campoInvalido({ ...valido, allergy_type: 'grave' }), 'allergy_type');
assert.equal(campoInvalido({ ...valido, severity: '' }), 'severity');
assert.equal(mensajeDeCampo('severity'), 'Seleccione la severidad.');

// El cuerpo recorta y omite vacíos; nunca envía actor ni hospital.
assert.deepEqual(cuerpoRegistro({ ...valido, allergen_name: ' Penicilina ', reaction: '', actor: 'x' }),
    { allergy_type: 'allergy', severity: 'severe', allergen_name: 'Penicilina' });

assert.equal(etiqueta(SEVERIDADES, 'life_threatening'), 'Amenaza la vida');

// Códigos de los tres formatos de error presentes en develop.
assert.equal(codigoDeError({ error: { code: 'ALLERGY_ALREADY_ACTIVE' } }), 'ALLERGY_ALREADY_ACTIVE');
assert.equal(codigoDeError({ error: 'HOSPITAL_DATABASE_TENANT_MISMATCH' }), 'HOSPITAL_DATABASE_TENANT_MISMATCH');
assert.equal(codigoDeError({ code: 'RBAC_DENIED' }), 'RBAC_DENIED');
assert.equal(codigoDeError(undefined), null);

// Mensajes seguros: un 503 nunca dice «sin alergias».
assert.equal(mensajeDeError('ALLERGY_ALREADY_ACTIVE', 409), 'El paciente ya tiene esa alergia activa.');
assert.ok(mensajeDeError('ALLERGIES_UNAVAILABLE', 503).startsWith('Alergias no disponibles'));
assert.ok(! mensajeDeError(null, 503).includes('Sin alergias'));
assert.ok(mensajeDeError('RBAC_DENIED', 403).includes('permiso'));
assert.equal(mensajeDeError(null, 500), 'No se pudo completar la operación.');

console.log('OK: 21 aserciones de la pantalla de alergias aprobadas');
