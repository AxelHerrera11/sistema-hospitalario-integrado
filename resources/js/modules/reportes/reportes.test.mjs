import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'reportes.js'), 'utf8');
const { AYUDA_INTERVALO, porcentajeLegible, leyendaDelResultado, errorDeFiltros, trazabilidad, mensajeDeError } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

// Un porcentaje nulo nunca se muestra como 0 %: eso ocultaría una falta de datos.
assert.equal(porcentajeLegible(25), '25.00 %');
assert.equal(porcentajeLegible(0), '0.00 %');
assert.equal(porcentajeLegible(null), 'N/A');
assert.equal(porcentajeLegible(undefined), 'N/A');

// La leyenda distingue el estado publicado de un cálculo completado.
assert.equal(
    leyendaDelResultado({ state: 'SIN_DATOS_EN_PERIODO' }).includes('No hubo hospitalizaciones'),
    true,
);
assert.equal(
    leyendaDelResultado({ state: 'ESTADO_DESCONOCIDO' }).includes('no se puede calcular'),
    true,
);
assert.equal(
    leyendaDelResultado({ percentage: 33.33, computed_at: '2026-10-23T10:00:00-06:00' }).includes('TCI-1.0'),
    true,
);

// Los filtros se validan en la pantalla, sin datos personales en juego.
assert.equal(errorDeFiltros({ desde: '', hasta: '' }), 'Indica desde y hasta.');
assert.equal(errorDeFiltros({ desde: 'no-fecha', hasta: '2026-02-01' }), 'Las fechas deben ser válidas.');
assert.equal(errorDeFiltros({ desde: '2026-02-01', hasta: '2026-01-01' }), 'Desde debe ser anterior a hasta.');
assert.equal(errorDeFiltros({ desde: '2026-01-01', hasta: '2026-02-01' }), '');

// La trazabilidad mostrada nunca incluye datos personales.
assert.equal(trazabilidad(null), '');
assert.equal(trazabilidad({ computed_at: '2026-10-23T10:00:00-06:00' }).includes('IND-RA-01'), true);

// El mensaje de error nunca expone el detalle del servidor.
assert.equal(mensajeDeError('periodo_invalido', 422).includes('Revisa el periodo'), true);
assert.equal(mensajeDeError('REPORTES_UNAVAILABLE', 503).includes('no está disponible'), true);
assert.equal(mensajeDeError(null, 401).includes('Inicia sesión'), true);
assert.equal(mensajeDeError(null, 403).includes('permiso'), true);
assert.equal(mensajeDeError(null, 500).includes('detalle'), false);

assert.equal(errorDeFiltros({ desde: '2026-02-31', hasta: '2026-03-05' }), 'Las fechas deben ser válidas.');
assert.equal(errorDeFiltros({ desde: '2026-01-01', hasta: '2026-13-01' }), 'Las fechas deben ser válidas.');
assert.equal(errorDeFiltros({ desde: '2026-01-01', hasta: '2026-02-01' }), '');
assert.equal(mensajeDeError('HOSPITAL_DATABASE_TENANT_MISMATCH', 403).includes('no está disponible'), true);
assert.equal(AYUDA_INTERVALO.includes('no se incluye'), true);

console.log('OK: 20 aserciones de la pantalla de reportes aprobadas');
