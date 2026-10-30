// node --test micro-his/expediente-medico/tests/ui-estados.test.mjs
import assert from 'node:assert/strict';
import test from 'node:test';
import {
    debeOcultar, etiquetaPaciente, etiquetaSexo, formatearFecha, formatearNacimiento, interpretar, MAX_REINTENTOS, siguienteReintento, validarCodigo,
} from '../public/ui/estados.js';

test('V-10 (H-01) editar el código a otro paciente obliga a ocultar el expediente mostrado', () => {
    assert.equal(debeOcultar('P-0001', 'P 0002'), true);
    assert.equal(debeOcultar('P-0001', ' P-0001 '), false);
    assert.equal(debeOcultar(null, 'P-0002'), false);
});

test('V-11 (H-02/H-03) las horas llevan zona y el nacimiento no cambia de día', () => {
    const texto = formatearFecha('2026-03-02T09:00:00+00:00', 'UTC');
    assert.match(texto, /02\/03\/2026/);
    assert.match(texto, /09:00/);
    assert.match(texto, /UTC/);
    assert.equal(formatearNacimiento('1980-05-14'), '14/05/1980');
    assert.equal(formatearNacimiento('no-fecha'), '');
});

test('V-09 (DEF-EXP-02) sexo y estado del paciente se muestran en español, nunca el código crudo', () => {
    assert.equal(etiquetaSexo('female'), 'Femenino');
    assert.equal(etiquetaPaciente('deceased'), 'Fallecido');
    assert.equal(etiquetaSexo('x'), 'No registrado');
});

test('V-01 código vacío, con espacios o de 41 caracteres no se envía; 40 sí', () => {
    assert.equal(validarCodigo('').valido, false);
    assert.equal(validarCodigo('P 0001').valido, false);
    assert.equal(validarCodigo('A'.repeat(41)).valido, false);
    assert.deepEqual(validarCodigo('  P-0001 '), { valido: true, codigo: 'P-0001', mensaje: '' });
    assert.equal(validarCodigo('A'.repeat(40)).valido, true);
});

test('V-02 consulta 200 con episodios es éxito; sin episodios es estado vacío', () => {
    assert.equal(interpretar('consultar', 200, { episodes: [{}, {}] }).tipo, 'exito');
    assert.equal(interpretar('consultar', 200, { episodes: [] }).tipo, 'vacio');
});

test('V-03 apertura 201 informa el número; 200 aclara que no se creó otro', () => {
    assert.match(interpretar('abrir', 201, { record_number: 'EXP-P-0002' }).mensaje, /Se abrió el expediente EXP-P-0002/);
    assert.match(interpretar('abrir', 200, { record_number: 'EXP-P-0002', created: false }).mensaje, /no se creó otro/);
});

test('V-04 los 403 son acceso denegado y conservan el trace_id, sin datos del paciente', () => {
    const rbac = interpretar('consultar', 403, { code: 'ACCESO_DENEGADO', trace_id: 'trace-abc' });
    assert.equal(rbac.tipo, 'denegado');
    assert.equal(rbac.traza, 'trace-abc');
    assert.equal(interpretar('consultar', 403, { code: 'SIN_VINCULO_ASISTENCIAL' }).tipo, 'denegado');
});

test('V-05 los 503 se pueden reintentar; un 409 o 404 no', () => {
    for (const code of ['HOSPITAL_NO_DISPONIBLE', 'EXPEDIENTE_NO_DISPONIBLE', 'AUDITORIA_NO_DISPONIBLE']) {
        assert.equal(interpretar('consultar', 503, { code }).puedeReintentar, true, code);
    }
    assert.equal(interpretar('abrir', 409, { code: 'PACIENTE_NO_ACTIVO' }).puedeReintentar, false);
    assert.equal(interpretar('consultar', 404, { code: 'EXPEDIENTE_NO_ABIERTO' }).tipo, 'vacio');
});

test('V-06 sin respuesta: la apertura avisa que reintentar es seguro (idempotente)', () => {
    const r = interpretar('abrir', 0);
    assert.equal(r.puedeReintentar, true);
    assert.match(r.mensaje, /Reintentar es seguro/);
});

test('V-07 los reintentos tienen salida: al llegar al máximo se pide avisar a soporte', () => {
    assert.equal(siguienteReintento(0).permitido, true);
    assert.equal(siguienteReintento(MAX_REINTENTOS).permitido, false);
    assert.match(siguienteReintento(MAX_REINTENTOS).aviso, /soporte/);
});

test('V-08 un código desconocido no muestra detalles internos', () => {
    const r = interpretar('consultar', 500, { code: 'ERROR_INTERNO', detail: 'SQLSTATE' });
    assert.equal(r.tipo, 'error');
    assert.doesNotMatch(r.mensaje, /SQLSTATE/);
});
