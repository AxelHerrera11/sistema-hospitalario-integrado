/**
 * Verificaciones de accesibilidad de la pantalla de reportes (ASII-23, S9).
 *
 * Prueba estática ejecutable con `node`: comprueba que el componente
 * mantiene los atributos y relaciones ARIA que garantizan lectura con
 * lector de pantalla, navegación con teclado y estados anunciados. No
 * sustituye una auditoría manual; evita regresiones evidentes entre commits.
 */

import { readFileSync } from 'node:fs';
import { mensajeDeError } from './reportes.js';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'pages', 'ReportsPage.vue'), 'utf8');

const tiene = (busqueda) => (busqueda instanceof RegExp ? busqueda.test(source) : source.includes(busqueda));

// Titular y regiones anunciadas por el lector de pantalla.
assert.ok(tiene(/aria-labelledby="reportes-title"/), 'la seccion lleva aria-labelledby');
assert.ok(tiene(/<h1 id="reportes-title">/), 'el h1 es el titulo referenciado');

// Estados y alertas se anuncian sin interaccion.
assert.ok(tiene(/role="status"/), 'los avisos informativos usan role=status');
assert.ok(tiene(/role="alert"/), 'los errores usan role=alert');

// Cada entrada tiene etiqueta explicita con for/id (no etiquetas flotantes).
for (const campo of ['rp-desde', 'rp-hasta', 'rp-servicio']) {
    assert.ok(tiene(`<label for="${campo}">`), `etiqueta explicita para ${campo}`);
    assert.ok(tiene(`id="${campo}"`), `id asociado para ${campo}`);
}

// El formulario se envia con un boton real (activable con teclado).
assert.ok(tiene(/<form[^>]*@submit\.prevent="calculate">/), 'submit por formulario y teclado (Enter)');
assert.ok(tiene(/<button type="submit"/), 'boton nativo de envio');
assert.ok(tiene(/:disabled="loading"/), 'el boton se desactiva mientras se calcula');

// Etiquetas del parentesco de la fecha nativa (date inputs usan el selector del SO).
assert.ok(tiene(/type="date"/), 'los periodos usan inputs de fecha nativos');
assert.ok(tiene(/required/), 'los filtros obligatorios se marcan como required');

// Foco visible y contraste dependen del sistema base; aqui garantizamos
// que los mensajes critico no dependan solo del color.
assert.ok(mensajeDeError(null, 403).includes('No tienes permiso para consultar reportes'), 'el 403 se anuncia con texto, no solo con color');

// La ayuda del intervalo [desde, hasta) se asocia a las dos fechas.
assert.equal(source.match(/aria-describedby="rp-ayuda"/g)?.length, 2, 'las dos fechas anuncian la ayuda del intervalo');
assert.ok(tiene(/<p id="rp-ayuda"/), 'la ayuda existe y es visible');

// Nada de una consulta anterior queda junto a un error nuevo.
assert.ok(tiene(/async function calculate\(\) \{\s+limpiar\(\);/), 'calcular limpia el resultado antes de validar');
assert.ok(tiene(/watch\(\[desde, hasta, servicio\], limpiar\)/), 'cambiar un filtro retira el resultado anterior');

console.log('OK: 15 comprobaciones de accesibilidad de la pantalla de reportes aprobadas');
