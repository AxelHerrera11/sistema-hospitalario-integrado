import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'auditoria.js'), 'utf8');
const { etiquetaResultado, fechaLegible, filtroInvalido, filtrosVacios, mensajeDeError, parametros, recurso, resumenPagina } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

// Los parámetros usan los nombres públicos del contrato, omiten vacíos y nunca envían el tenant.
const filtros = { ...filtrosVacios(), modulo: ' expediente_medico ', resultado: 'denied', tenant_id: 'otro-hospital' };
assert.deepEqual(parametros(filtros, 2), { modulo: 'expediente_medico', resultado: 'denied', pagina: '2', por_pagina: '25' });
assert.equal(parametros(filtrosVacios(), 0).pagina, '1');
assert.equal(parametros(filtrosVacios(), 'x').pagina, '1');

// Validación local con las mismas reglas que AuditFilterParser.
assert.equal(filtroInvalido(filtrosVacios()), null);
assert.equal(filtroInvalido({ ...filtrosVacios(), modulo: 'Expediente' }), 'modulo');
assert.equal(filtroInvalido({ ...filtrosVacios(), accion: 'abrir..x' }), 'accion');
assert.equal(filtroInvalido({ ...filtrosVacios(), usuario: "1' OR '1'='1" }), 'usuario');
assert.equal(filtroInvalido({ ...filtrosVacios(), desde: '30/10/2026' }), 'desde');
assert.equal(filtroInvalido({ ...filtrosVacios(), resultado: 'unknown' }), 'resultado');
assert.equal(filtroInvalido({ ...filtrosVacios(), desde: '2026-10-30', hasta: '2026-10-01' }), 'hasta');
assert.equal(filtroInvalido({ ...filtrosVacios(), desde: '2026-10-01', hasta: '2026-10-01T23:59' }), null);

// Presentación.
assert.equal(etiquetaResultado('denied'), 'Denegado');
assert.equal(etiquetaResultado('raro'), 'Desconocido');
assert.equal(recurso({ resource: { type: 'medical_record', id: 'e1' } }), 'medical_record e1');
assert.equal(recurso({ resource: null }), '—');
assert.equal(fechaLegible('no-es-fecha'), 'no-es-fecha');
assert.match(fechaLegible('2026-10-30T18:00:00.000Z'), /30\/10\/(20)?26/);
assert.equal(resumenPagina({ page: 1, total: 0, total_pages: 0 }), 'Sin eventos para estos filtros.');
assert.equal(resumenPagina({ page: 2, total: 60, total_pages: 3 }), 'Página 2 de 3 · 60 eventos');

// Errores: mensaje por código, sin detalle interno.
assert.equal(mensajeDeError('RBAC_DENIED', 403), 'No tiene permiso para consultar la auditoría.');
assert.equal(mensajeDeError('AUDIT_UNAVAILABLE', 503), 'La bitácora no está disponible en este momento.');
assert.equal(mensajeDeError('SQLSTATE[42P01]', 500), 'No se pudo consultar la bitácora.');
assert.equal(mensajeDeError(null, 401), 'Inicie sesión para continuar.');

console.log('auditoria.test.mjs: OK');
