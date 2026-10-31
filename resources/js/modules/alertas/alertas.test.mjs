import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'alertas.js'), 'utf8');
const { codigoDeError, cuerpoAcuse, debeRecargar, direccionLegible, idCorto, lecturaCompleta, mensajeDeError, minutosRestantes, vencimientoLegible } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

const ahora = new Date('2026-10-23T08:00:00-06:00');
assert.equal(direccionLegible('low'), 'bajo el umbral crítico');
assert.equal(direccionLegible('high'), 'sobre el umbral crítico');
assert.equal(minutosRestantes('2026-10-23T08:15:00-06:00', ahora), 15);
assert.equal(vencimientoLegible('2026-10-23T07:50:00-06:00', ahora), 'Vencida hace 10 min');
assert.equal(vencimientoLegible('2026-10-23T08:05:00-06:00', ahora), 'Vence en 5 min');
assert.equal(idCorto('00000000-0000-4000-8000-0000000000a1'), '00000000');

// El acuse exige confirmar paciente y prueba y escribir un valor numérico.
assert.equal(lecturaCompleta({ confirmado: true, valor: '32' }), true);
assert.equal(lecturaCompleta({ confirmado: false, valor: '32' }), false);
assert.equal(lecturaCompleta({ confirmado: true, valor: '' }), false);
assert.equal(lecturaCompleta({ confirmado: true, valor: 'treinta' }), false);
assert.deepEqual(cuerpoAcuse({ local_patient_id: 'p', lab_test_id: 't', measured_value: 32 }, { confirmado: true, valor: '23' }),
    { read_back: { patient_id: 'p', lab_test_id: 't', value: 23 } });

// Códigos de los tres formatos de error, mensajes seguros y recarga tras un 409.
assert.equal(codigoDeError({ error: { code: 'READ_BACK_MISMATCH' } }), 'READ_BACK_MISMATCH');
assert.equal(codigoDeError({ code: 'RBAC_DENIED' }), 'RBAC_DENIED');
assert.ok(mensajeDeError('READ_BACK_MISMATCH', 422).includes('no coincide'));
assert.ok(mensajeDeError('CRITICAL_ALERTS_UNAVAILABLE', 503).includes('verifique por teléfono'));
assert.equal(debeRecargar('ALERT_ALREADY_ACKNOWLEDGED'), true);
assert.equal(debeRecargar('READ_BACK_MISMATCH'), false);

console.log('OK: 17 aserciones de la bandeja de alertas críticas aprobadas');
