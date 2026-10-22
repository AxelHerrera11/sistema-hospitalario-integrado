import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'ocupacion.js'), 'utf8');
const { METRICAS_GLOBAL, metricasGlobales, tasaLegible, estaDesactualizada, mensajeDeError } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

const global = {
    total_beds: 12,
    occupied: 5,
    available: 4,
    maintenance: 2,
    inactive: 0,
    unverified_occupied: 1,
    inconsistencies: 3,
    occupancy_rate: 50,
};

const metricas = metricasGlobales(global);
assert.equal(metricas.length, METRICAS_GLOBAL.length);
assert.equal(metricas[0].clave, 'total_beds');
assert.equal(metricas[0].valor, 12);
assert.equal(metricas.at(-1).valor, 3);

// Una carga sin datos no rompe la pantalla ni inventa ceros fantasma.
const vacio = metricasGlobales(undefined);
assert.equal(vacio.length, METRICAS_GLOBAL.length);
assert.equal(vacio[0].valor, 0);

// La tasa nula se muestra como N/A: no hay camas operativas que evaluar.
assert.equal(tasaLegible(66.67), '66.67 %');
assert.equal(tasaLegible(null), 'N/A');
assert.equal(tasaLegible(undefined), 'N/A');

// Una fotografía vieja se marca como desactualizada; una reciente, no.
const ahora = new Date('2026-10-20T10:00:00Z');
assert.equal(estaDesactualizada('2026-10-20T09:50:00Z', ahora, 15), false);
assert.equal(estaDesactualizada('2026-10-20T09:30:00Z', ahora, 15), true);
assert.equal(estaDesactualizada(null, ahora), true);
assert.equal(estaDesactualizada('no-es-una-fecha', ahora), true);

// El mensaje de error nunca expone el detalle del servidor.
assert.equal(mensajeDeError('OCCUPANCY_UNAVAILABLE', 503).includes('no está disponible'), true);
assert.equal(mensajeDeError(null, 401).includes('Inicia sesión'), true);
assert.equal(mensajeDeError(null, 403).includes('permiso'), true);
assert.equal(mensajeDeError(null, 500).includes('detalle interno'), false);

console.log('OK: 14 aserciones de la pantalla de ocupación aprobadas');
