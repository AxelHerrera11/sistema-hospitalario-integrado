// Sondas de usabilidad y accesibilidad del prototipo (Semana 9). Uso: node sondas-ux.mjs <base>
// Cada sonda observa el prototipo real en Chrome sin interfaz; "cumple" se calcula, no se supone.
import puppeteer from 'puppeteer-core';

const base = process.argv[2];
const ACTOR = '00000000-0000-4000-8000-0000000000f1';
const ESTANCIA = '00000000-0000-4000-8000-0000000000e1';
const sondas = [];
const anotar = (id, criterio, observado, cumple) => { sondas.push({ id, criterio, observado, cumple }); console.log(`${cumple ? 'CUMPLE' : 'NO CUMPLE'}  ${id} ${criterio} → ${observado}`); };
const navegador = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });
const p = await navegador.newPage();
await p.setViewport({ width: 1024, height: 900 });
await p.goto(`${base}/ui/index.html`, { waitUntil: 'networkidle0' });

// S1 — Orden de tabulación del primer paso (WCAG 2.1.1 y 2.4.3).
const orden = [];
for (let i = 0; i < 3; i++) { await p.keyboard.press('Tab'); orden.push(await p.evaluate(() => document.activeElement.id || document.activeElement.tagName)); }
anotar('S1', 'Teclado: actor → estancia → Consultar', orden.join(' → '), orden.join() === 'actor,estancia,BUTTON');

// S2 — Consultar sin actor: ¿el foco va al campo que falta? (WCAG 3.3.1)
await p.keyboard.press('Enter');
await p.waitForFunction(() => document.getElementById('resultado').textContent !== 'Consultando…');
const focoSinActor = await p.evaluate(() => document.activeElement.id || document.activeElement.tagName);
anotar('S2', 'Error de actor faltante lleva el foco al campo', `foco en ${focoSinActor}; mensaje «${await p.$eval('#resultado', (e) => e.textContent)}»`, focoSinActor === 'actor');

// S3 — Tras una consulta exitosa, ¿el foco o un anuncio llevan a la situación? (WCAG 2.4.3 y 4.1.3)
await p.type('#actor', ACTOR);
await p.$eval('#estancia', (e, v) => { e.value = v; }, ESTANCIA);
await p.click('#consulta button[type=submit]');
await p.waitForSelector('#estado:not([hidden])');
const tras = await p.evaluate(() => ({ foco: document.activeElement.id || document.activeElement.tagName, anuncio: document.getElementById('resultado').textContent }));
anotar('S3', 'La situación se anuncia o recibe el foco', `foco en ${tras.foco}; anuncio «${tras.anuncio}»`, tras.foco === 'titulo-estado' || tras.anuncio !== '');

// S4 — Error de validación: aria-invalid y anuncio (WCAG 3.3.1 y 4.1.2).
await p.click('#elegir-traslado');
await p.click('#formulario button[type=submit]');
const invalido = await p.evaluate(() => ({ ariaInvalid: document.getElementById('cama').getAttribute('aria-invalid'), rol: document.getElementById('error-cama').getAttribute('role') }));
anotar('S4', 'Campo con error marcado aria-invalid o mensaje con role=alert', `aria-invalid=${invalido.ariaInvalid}; role=${invalido.rol}`, invalido.ariaInvalid === 'true' || invalido.rol === 'alert');

// S5 — Campos obligatorios del traslado declarados (WCAG 3.3.2).
const requeridos = await p.evaluate(() => ['cama', 'motivo'].map((id) => `${id}:${document.getElementById(id).required || document.getElementById(id).getAttribute('aria-required') === 'true'}`));
anotar('S5', 'Cama y motivo declarados obligatorios', requeridos.join(' '), requeridos.every((r) => r.endsWith(':true')));

// S6 — Contraste del texto de ayuda, de error y de los botones (WCAG 1.4.3, mínimo 4,5:1).
const contrastes = await p.evaluate(() => {
    const rgb = (c) => c.match(/\d+(\.\d+)?/g).slice(0, 3).map(Number);
    const lum = (c) => { const [r, g, b] = rgb(c).map((v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }); return 0.2126 * r + 0.7152 * g + 0.0722 * b; };
    const fondo = (e) => { while (e && getComputedStyle(e).backgroundColor.includes('rgba(0, 0, 0, 0)')) e = e.parentElement; return e ? getComputedStyle(e).backgroundColor : 'rgb(255,255,255)'; };
    const razon = (sel) => { const e = document.querySelector(sel); const [a, b] = [lum(getComputedStyle(e).color), lum(fondo(e))].sort((x, y) => y - x); return Math.round(((a + 0.05) / (b + 0.05)) * 100) / 100; };
    return { ayuda: razon('.ayuda'), error: razon('.error'), primario: razon('button'), secundario: razon('button.secundario') };
});
anotar('S6', 'Contraste ≥ 4,5:1 en ayuda, error y botones', JSON.stringify(contrastes), Object.values(contrastes).every((r) => r >= 4.5));

// S7 — Reconocer en lugar de recordar: ¿hay que escribir identificadores? (heurística 6 de Nielsen)
const campoCama = await p.evaluate(() => document.getElementById('cama').tagName + (document.getElementById('cama').getAttribute('list') ? ' con lista' : ' libre'));
anotar('S7', 'La cama destino se elige de opciones, no se teclea un uuid', campoCama, campoCama !== 'INPUT libre');

// S8 — Tras el traslado, ¿se muestra la nueva estancia y la hora? (heurística 1, visibilidad del estado)
await p.type('#cama', '00000000-0000-4000-8000-0000000000b2');
await p.type('#motivo', 'Requiere aislamiento');
await p.click('#formulario button[type=submit]');
await p.click('#confirmar');
await p.waitForFunction(() => document.getElementById('resultado').textContent.includes('confirmado'));
const exito = await p.$eval('#resultado', (e) => e.textContent);
anotar('S8', 'El éxito muestra la nueva estancia y la hora de la operación', `«${exito}»`, /[0-9a-f]{8}-/.test(exito) && /\d{2}:\d{2}/.test(exito));

// S9 — Datos residuales: después del éxito, ¿el motivo anterior sigue cargado? (heurística 5, prevención de errores)
const motivoResidual = await p.$eval('#motivo', (e) => e.value);
anotar('S9', 'El formulario se limpia tras una operación confirmada', `motivo residual «${motivoResidual}»`, motivoResidual === '');

// S10 — Título de la página por paso (WCAG 2.4.2).
anotar('S10', 'El título de la página identifica el contenido', `«${await p.title()}»`, (await p.title()).length > 0);

await navegador.close();
const incumplidas = sondas.filter((s) => !s.cumple).length;
console.log(`${sondas.length} sondas: ${sondas.length - incumplidas} cumplen, ${incumplidas} no cumplen`);
