// node --test resources/js/modules/traslados/traslados.test.mjs
import assert from 'node:assert/strict';
import test from 'node:test';
import { interpretar, PERMISOS, respuestaDeError } from './traslados.js';

test('los permisos son los propuestos en rbac.pending', () => {
    assert.deepEqual(PERMISOS, { trasladar: 'traslados.registrar', darAlta: 'altas.registrar', consultar: 'traslados.ver' });
});

test('un error con respuesta conserva su estado y su cuerpo', () => {
    const r = respuestaDeError({ response: { status: 403, data: { code: 'RBAC_DENIED' } } }, 'post');
    assert.deepEqual(r, { estado: 403, cuerpo: { code: 'RBAC_DENIED' } });
    assert.equal(interpretar(r.estado, r.cuerpo).tipo, 'rechazo');
});

test('sin respuesta: un POST es incierto y una lectura es reintentable', () => {
    const post = respuestaDeError(new Error('Network Error'), 'post');
    assert.equal(interpretar(post.estado, post.cuerpo).debeConsultar, true);
    const get = respuestaDeError(new Error('Network Error'), 'get');
    assert.equal(interpretar(get.estado, get.cuerpo).puedeReintentar, true);
});

test('otro hospital: mensaje propio, sin reintento ni consulta', () => {
    const r = interpretar(403, { error: 'HOSPITAL_DATABASE_TENANT_MISMATCH' });
    assert.equal(r.tipo, 'rechazo');
    assert.match(r.texto, /no está disponible en esta instalación/);
    assert.equal(r.puedeReintentar, false);
});
