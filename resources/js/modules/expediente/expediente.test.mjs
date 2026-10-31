import { fileURLToPath, pathToFileURL } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

// Import directo (no data: URL): el módulo reutiliza estados.js del micro-HIS con una ruta relativa.
const here = dirname(fileURLToPath(import.meta.url));
const { codigoDeRespuesta, debeOcultar, estadoDesdeApi, etiquetaTipo, validarCodigo } =
    await import(pathToFileURL(join(here, 'expediente.js')).href);

// Código del error: `code` del micro-HIS/RBAC o `error` del guard hospitalario.
assert.equal(codigoDeRespuesta({ code: 'RBAC_DENIED' }), 'RBAC_DENIED');
assert.equal(codigoDeRespuesta({ error: 'HOSPITAL_DATABASE_TENANT_MISMATCH' }), 'HOSPITAL_DATABASE_TENANT_MISMATCH');
assert.equal(codigoDeRespuesta(undefined), null);

// Éxitos: los decide la lógica del prototipo.
const consulta = estadoDesdeApi('consultar', 200, { episodes: [{ type: 'inpatient' }] });
assert.equal(consulta.tipo, 'exito');
assert.equal(estadoDesdeApi('abrir', 201, { record_number: 'EXP-P-0002' }).mensaje, 'Se abrió el expediente EXP-P-0002.');
assert.equal(estadoDesdeApi('abrir', 200, { record_number: 'EXP-P-0002' }).titulo, 'El expediente ya existía');

// Códigos de la capa Laravel traducidos al mensaje equivalente del micro-HIS.
assert.equal(estadoDesdeApi('consultar', 403, { code: 'RBAC_DENIED', trace_id: 't1' }).titulo, 'Acceso denegado');
assert.equal(estadoDesdeApi('consultar', 403, { code: 'RBAC_DENIED', trace_id: 't1' }).traza, 't1');
assert.equal(estadoDesdeApi('consultar', 503, { error: 'HOSPITAL_DATABASE_TENANT_UNCONFIGURED' }).puedeReintentar, true);
assert.equal(estadoDesdeApi('abrir', 503, { code: 'RBAC_UNAVAILABLE' }).titulo, 'Servicio no disponible');

// Códigos propios de la aplicación.
assert.equal(estadoDesdeApi('consultar', 403, { code: 'ACTOR_SIN_REFERENCIA_LOCAL' }).tipo, 'denegado');
assert.equal(estadoDesdeApi('consultar', 403, { code: 'TENANT_MISMATCH' }).titulo, 'Hospital distinto');
assert.equal(estadoDesdeApi('consultar', 401, {}).titulo, 'Sesión vencida');

// Sin vínculo y sin expediente siguen con los mensajes del módulo; un código desconocido no filtra detalle.
assert.equal(estadoDesdeApi('consultar', 403, { code: 'SIN_VINCULO_ASISTENCIAL' }).titulo, 'Sin vínculo asistencial');
assert.equal(estadoDesdeApi('consultar', 404, { code: 'EXPEDIENTE_NO_ABIERTO' }).tipo, 'vacio');
assert.doesNotMatch(estadoDesdeApi('consultar', 500, { code: 'SQLSTATE', message: 'detalle' }).mensaje, /SQLSTATE|detalle/);
assert.equal(estadoDesdeApi('consultar', 0).puedeReintentar, true);

// Reexportados del prototipo.
assert.equal(validarCodigo(' P-0001 ').codigo, 'P-0001');
assert.equal(validarCodigo("P' OR 1=1").valido, false);
assert.equal(etiquetaTipo('emergency'), 'Emergencia');
assert.equal(debeOcultar('P-0001', 'P-0002'), true);

console.log('expediente.test.mjs: OK');
