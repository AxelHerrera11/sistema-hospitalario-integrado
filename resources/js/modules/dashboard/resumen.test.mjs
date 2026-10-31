import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'resumen.js'), 'utf8');
const { estadoDePagina, filasPorEstado, textoIndicador, mensajeDeError, fechaLocal, enlaceDisponible } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

assert.equal(estadoDePagina(null, true), 'cargando');
assert.equal(estadoDePagina({ tarjetas: [] }, false), 'vacio');
assert.equal(estadoDePagina({ tarjetas: [{ clave: 'citas_hoy' }] }, false), 'listo');

assert.deepEqual(filasPorEstado({ cancelled: 2 }).map((f) => f.valor), [0, 0, 0, 2]);

assert.equal(
    textoIndicador({ valor: 20, meta: 10, sentido: 'menor_mejor', cumple_meta: false }),
    '✖ No cumple: 20 (meta 10, menor es mejor)',
);
assert.match(mensajeDeError(503), /intente de nuevo/);
assert.match(mensajeDeError(401), /sesión/);

// 23:30 hora local sigue siendo el mismo día aunque en UTC ya sea el siguiente.
assert.equal(fechaLocal(new Date(2026, 9, 30, 23, 30)), '2026-10-30');
const resolver = (ruta) => ({ matched: ruta === '/reportes' ? [{}] : [] });
assert.equal(enlaceDisponible(resolver, '/reportes'), true);
assert.equal(enlaceDisponible(resolver, '/citas'), false);

console.log('resumen.test.mjs: 10 verificaciones OK');
