// node --test micro-his/traslados-altas/tests/ui-mensajes.test.mjs
import assert from 'node:assert/strict';
import test from 'node:test';
import { interpretar, validarConsulta, validarTraslado } from '../public/ui/mensajes.js';

test('solo 200 y 201 son éxito', () => {
    assert.equal(interpretar(201, { estancia_destino_id: 'x' }).tipo, 'exito');
    assert.match(interpretar(200, {}).texto, /Alta confirmada/);
    for (const estado of [403, 409, 422, 500, 503, 0]) assert.notEqual(interpretar(estado, {}).tipo, 'exito');
});

test('un rechazo explica el motivo sin ofrecer reintento', () => {
    const r = interpretar(409, { motivo: 'ESTANCIA_NO_VIGENTE' });
    assert.equal(r.tipo, 'rechazo');
    assert.match(r.texto, /Vuelva a consultar/);
    assert.equal(r.puedeReintentar, false);
});

test('503 permite reintentar; 500 incierto obliga a consultar y no permite reintentar', () => {
    assert.equal(interpretar(503, {}).puedeReintentar, true);
    const incierto = interpretar(500, { resultado: 'incierto' });
    assert.deepEqual([incierto.tipo, incierto.debeConsultar, incierto.puedeReintentar], ['consultar', true, false]);
    assert.equal(interpretar(500, {}).tipo, 'error');
});

test('validación local del traslado', () => {
    assert.equal(validarTraslado({ cama_destino_id: '00000000-0000-4000-8000-0000000000b2', motivo: 'Aislamiento' }), null);
    assert.equal(validarTraslado({ cama_destino_id: 'cama 2', motivo: 'x' }), 'cama_destino_id');
    assert.equal(validarTraslado({ cama_destino_id: '00000000-0000-4000-8000-0000000000b2', motivo: '   ' }), 'motivo');
    assert.equal(validarTraslado({ cama_destino_id: '00000000-0000-4000-8000-0000000000b2', motivo: 'm'.repeat(501) }), 'motivo');
});

test('H1: actor y estancia se validan antes de consultar', () => {
    assert.equal(validarConsulta({ actor: '', estancia: '' }), 'actor');
    assert.equal(validarConsulta({ actor: '00000000-0000-4000-8000-0000000000f1', estancia: 'e1' }), 'estancia');
    assert.equal(validarConsulta({ actor: '00000000-0000-4000-8000-0000000000f1', estancia: '00000000-0000-4000-8000-0000000000e1' }), null);
});

test('H6: el éxito muestra la nueva estancia y la hora', () => {
    const r = interpretar(201, { estancia_destino_id: '00000000-0000-4000-8000-0000000000e2', t: '2026-10-21T15:04:05Z' });
    assert.match(r.texto, /Nueva estancia: 00000000-0000-4000-8000-0000000000e2/);
    assert.match(r.texto, /Hora: \d{2}:\d{2}/);
    assert.equal(r.estanciaNueva, '00000000-0000-4000-8000-0000000000e2');
});

test('conexión limitada: sin red se reintenta; tiempo agotado obliga a consultar', () => {
    assert.deepEqual([interpretar(0, { motivo: 'sin_red' }).tipo, interpretar(0, { motivo: 'sin_red' }).puedeReintentar], ['reintentar', true]);
    const agotado = interpretar(0, { motivo: 'tiempo_agotado' });
    assert.deepEqual([agotado.tipo, agotado.debeConsultar, agotado.puedeReintentar], ['consultar', true, false]);
});
