#!/usr/bin/env node
/**
 * Mide contraste, teclado, reflow y estructura de la pantalla /medicos (ASII-04) en Chrome sin interfaz (CDP), sin dependencias.
 *
 * Sobre la pantalla REAL (layout, router, estilos y componente) con el API simulado del arnés de semana-16/evidencia/pantalla:
 * el contraste y el teclado no dependen del backend. Qué NO es: no sustituye una prueba con lector de pantalla ni una auditoría.
 *
 *   1. CAPTURAS_DIST=<carpeta> npx vite build --config docs/medicos-especialidades/semana-16/evidencia/pantalla/vite.config.mjs
 *   2. node docs/medicos-especialidades/semana-18/evidencia/medir-accesibilidad.mjs <carpeta>
 *
 * Criterios WCAG 2.2 AA: 1.4.3 contraste de texto (4,5:1; 3:1 en texto grande), 1.4.11 contraste de componentes y del foco (3:1),
 * 1.4.10 reflow a 320 px, 2.1.1 teclado, 2.1.2 sin trampa de foco, 2.4.3 orden del foco, 2.4.7 foco visible, 2.5.8 tamaño del objetivo (24 px),
 * 1.3.1 y 4.1.2 etiquetas y nombres. Salida: tablas Markdown; código de salida 1 si algo no cumple.
 * Variable opcional: CHROME_PATH.
 */
import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { existsSync, mkdtempSync, readFileSync, rmSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { extname, join, resolve } from 'node:path';

const dist = resolve(process.argv[2] ?? (console.error('Uso: node medir-accesibilidad.mjs <carpeta-dist>'), process.exit(2)));
const CHROME = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const dormir = (ms) => new Promise((r) => setTimeout(r, ms));
const tipos = { '.html': 'text/html; charset=utf-8', '.js': 'text/javascript', '.css': 'text/css', '.svg': 'image/svg+xml' };

const servidor = createServer((req, res) => {
    let f = join(dist, decodeURIComponent(new URL(req.url, 'http://x').pathname));
    if (!existsSync(f) || statSync(f).isDirectory()) f = join(dist, 'index.html');
    res.writeHead(200, { 'Content-Type': tipos[extname(f)] ?? 'application/octet-stream' });
    res.end(readFileSync(f));
});
await new Promise((r) => servidor.listen(0, '127.0.0.1', r));
const base = `http://127.0.0.1:${servidor.address().port}`;

const puerto = 9500 + Math.floor(Math.random() * 200);
const perfil = mkdtempSync(join(tmpdir(), 'asii04-a11y-'));
const chrome = spawn(CHROME, ['--headless=new', '--disable-gpu', '--no-first-run', `--remote-debugging-port=${puerto}`, `--user-data-dir=${perfil}`, 'about:blank'], { stdio: 'ignore' });
let objetivos;
for (let i = 0; i < 50; i++) { try { objetivos = await (await fetch(`http://127.0.0.1:${puerto}/json/list`)).json(); if (objetivos.some((t) => t.type === 'page')) break; } catch { /* aún no */ } await dormir(200); }
const ws = new WebSocket(objetivos.find((t) => t.type === 'page').webSocketDebuggerUrl);
await new Promise((r) => (ws.onopen = r));
let id = 0; const pend = new Map(); const errores = [];
ws.onmessage = ({ data }) => { const m = JSON.parse(data); if (m.id && pend.has(m.id)) { pend.get(m.id)(m); pend.delete(m.id); } if (m.method === 'Runtime.exceptionThrown') errores.push(m.params.exceptionDetails.exception?.description ?? m.params.exceptionDetails.text); };
const cdp = (method, params = {}) => new Promise((r) => { const n = ++id; pend.set(n, r); ws.send(JSON.stringify({ id: n, method, params })); });
const ev = async (e) => (await cdp('Runtime.evaluate', { expression: e, returnByValue: true, awaitPromise: true })).result?.result?.value;
await cdp('Page.enable'); await cdp('Runtime.enable');

const esperar = async (c, ms = 8000) => { for (let t = 0; t < ms; t += 150) { if (await ev(c)) return true; await dormir(150); } return false; };
async function abrir(rol, ancho = 1280, alto = 900, movil = false) {
    await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: alto, deviceScaleFactor: 1, mobile: movil });
    await cdp('Page.navigate', { url: `${base}/medicos?rol=${rol}` });
    await esperar("document.querySelector('h1') && document.body.innerText.length > 40");
    await dormir(400);
}
const llenar = (s, v) => ev(`(() => { const e = document.querySelector(${JSON.stringify(s)}); if (!e) return false; e.value = ${JSON.stringify(v)}; e.dispatchEvent(new Event('input', { bubbles: true })); return true; })()`);
const pulsar = (t) => ev(`(() => { const b = [...document.querySelectorAll('button')].find((x) => x.textContent.trim().startsWith(${JSON.stringify(t)})); if (!b) return false; b.click(); return true; })()`);
const tecla = async (key, code, vk, extra = {}) => { for (const type of ['keyDown', 'keyUp']) await cdp('Input.dispatchKeyEvent', { type, key, code, windowsVirtualKeyCode: vk, ...extra }); await dormir(60); };

/** Se inyecta en la página: calcula contrastes con el color resuelto por el navegador (cualquier espacio de color, vía canvas). */
const SONDA = `(() => {
  const cv = document.createElement('canvas'); cv.width = cv.height = 1; const cx = cv.getContext('2d', { willReadFrequently: true });
  const rgba = (c) => { cx.clearRect(0, 0, 1, 1); cx.fillStyle = '#000'; cx.fillStyle = c; cx.fillRect(0, 0, 1, 1); const d = cx.getImageData(0, 0, 1, 1).data; return [d[0], d[1], d[2], d[3] / 255]; };
  const mezcla = (f, b) => [0, 1, 2].map((i) => f[i] * f[3] + b[i] * (1 - f[3]));
  const lum = ([r, g, b]) => { const k = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }; return 0.2126 * k(r) + 0.7152 * k(g) + 0.0722 * k(b); };
  const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p); return (x + 0.05) / (y + 0.05); };
  const fondo = (el) => { let capas = []; for (let e = el; e; e = e.parentElement) { const c = rgba(getComputedStyle(e).backgroundColor); if (c[3] > 0) capas.push(c); if (c[3] === 1) break; } let base = [255, 255, 255]; for (const c of capas.reverse()) base = mezcla(c, base); return base; };
  const visible = (e) => { const r = e.getBoundingClientRect(); const s = getComputedStyle(e); return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none' && !e.closest('[hidden]'); };
  const nombre = (e) => (e.id ? '#' + e.id : e.tagName.toLowerCase() + (e.className && typeof e.className === 'string' ? '.' + e.className.split(' ')[0] : '')) + ' «' + (e.innerText || e.value || '').trim().slice(0, 28) + '»';
  return { rgba, mezcla, ratio, fondo, visible, nombre };
})()`;

/** Contraste del texto (1.4.3) de todo elemento visible con texto propio, y del borde de los campos (1.4.11). */
async function contraste() {
    return ev(`(() => { const S = ${SONDA}; const out = { textos: [], campos: [] };
      const paso = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT); const vistos = new Set();
      while (paso.nextNode()) { const t = paso.currentNode; const e = t.parentElement; if (!e || vistos.has(e) || !t.textContent.trim() || !S.visible(e) || e.closest('script,style')) continue; vistos.add(e);
        const s = getComputedStyle(e); const bg = S.fondo(e); const fg = S.mezcla(S.rgba(s.color), bg); const px = parseFloat(s.fontSize); const grande = px >= 24 || (px >= 18.66 && parseInt(s.fontWeight) >= 700);
        out.textos.push({ main: !!e.closest('main'), el: S.nombre(e), r: +S.ratio(fg, bg).toFixed(2), min: grande ? 3 : 4.5, px }); }
      for (const e of document.querySelectorAll('input:not([type=hidden]), button')) { if (!S.visible(e)) continue; const s = getComputedStyle(e); const bg = S.fondo(e.parentElement);
        const borde = S.mezcla(S.rgba(s.borderTopColor), bg); const relleno = S.mezcla(S.rgba(s.backgroundColor), bg);
        const bw = parseFloat(s.borderTopWidth); const tieneBorde = bw >= 1 && s.borderTopStyle !== 'none';
        out.campos.push({ el: S.nombre(e), tag: e.tagName, type: e.type, borde: tieneBorde ? +S.ratio(borde, bg).toFixed(2) : null, relleno: +S.ratio(relleno, bg).toFixed(2) }); }
      return out; })()`);
}

/** Recorre la pantalla solo con Tab y registra el elemento enfocado, su indicador de foco y el orden visual. */
async function recorrerConTab(maximo = 40) {
    await ev('document.activeElement && document.activeElement.blur(); window.scrollTo(0, 0)');
    await ev(`(() => { const e = document.createElement('div'); e.tabIndex = -1; e.id = '__inicio'; document.body.prepend(e); e.focus(); })()`);
    const pasos = [];
    for (let i = 0; i < maximo; i++) {
        await tecla('Tab', 'Tab', 9);
        const info = await ev(`(() => { const S = ${SONDA}; const e = document.activeElement; if (!e || e === document.body || e.id === '__inicio') return { fin: true };
          const s = getComputedStyle(e); const r = e.getBoundingClientRect(); const bg = S.fondo(e.parentElement);
          const ow = parseFloat(s.outlineWidth); const hayOutline = s.outlineStyle !== 'none' && ow > 0; const col = hayOutline ? S.mezcla(S.rgba(s.outlineColor), bg) : null;
          return { fin: false, el: S.nombre(e), tag: e.tagName, y: Math.round(r.top + scrollY), x: Math.round(r.left), w: Math.round(r.width), h: Math.round(r.height),
            outline: hayOutline ? ow : 0, estiloOutline: s.outlineStyle, outlineRatio: col ? +S.ratio(col, bg).toFixed(2) : null, sombra: s.boxShadow !== 'none', tabindex: e.getAttribute('tabindex'),
            enMain: !!e.closest('main'), nombreAccesible: (e.getAttribute('aria-label') || (e.labels && e.labels[0] && e.labels[0].innerText) || e.innerText || e.getAttribute('title') || '').trim().slice(0, 40) }; })()`);
        if (info.fin) { pasos.push({ fin: true, i }); break; }
        pasos.push(info);
    }
    return pasos;
}

const filas = []; let fallos = 0;
const fila = (grupo, criterio, ok, detalle) => { filas.push(`| ${grupo} | ${criterio} | ${ok ? 'Cumple' : '**No cumple**'} | ${detalle} |`); ok || fallos++; };
const resumenTextos = (r) => { const mal = r.textos.filter((t) => t.r < t.min); return { n: r.textos.length, mal, peor: r.textos.reduce((a, t) => (t.r / t.min < a.r / a.min ? t : a), r.textos[0]) }; };

try {
    /* ------------------------------------------------------------ contraste en cada estado */
    const estados = [];
    await abrir('admin'); estados.push(['administrador, estado inicial', await contraste()]);
    await abrir('admin'); await llenar('#doc-specialty', 'NEURO'); await llenar('#doc-date', '2026-10-19'); await pulsar('Consultar'); await esperar("!!document.querySelector('table')"); await dormir(300);
    estados.push(['administrador, con la tabla de disponibilidad', await contraste()]);
    await abrir('admin'); await pulsar('Registrar'); await dormir(500); estados.push(['administrador, validación del alta (errores en rojo)', await contraste()]);
    await abrir('admin'); await pulsar('Asociar'); await dormir(500); estados.push(['administrador, validación de la asociación', await contraste()]);
    await abrir('admin'); await llenar('#doc-specialty', 'FALLA'); await llenar('#doc-date', '2026-10-19'); await pulsar('Consultar'); await dormir(600); estados.push(['administrador, servicio no disponible (503) con «Reintentar»', await contraste()]);
    await abrir('admin'); for (const [c, v] of [['local_user_ref_id', 'u1'], ['license_number', 'COL-9'], ['specialty_code', 'CARD'], ['valid_from', '2026-01-01']]) await llenar(`#doc-reg-${c}`, v); await pulsar('Registrar'); await dormir(600); estados.push(['administrador, error 409 de licencia', await contraste()]);
    await abrir('admin'); for (const [c, v] of [['local_user_ref_id', 'u1'], ['license_number', 'COL-1'], ['specialty_code', 'CARD'], ['valid_from', '2026-01-01']]) await llenar(`#doc-reg-${c}`, v); await pulsar('Registrar'); await dormir(600); estados.push(['administrador, alta registrada (éxito en verde)', await contraste()]);
    await abrir('recepcion'); estados.push(['recepción, solo consulta', await contraste()]);
    await abrir('admin', 390, 844, true); estados.push(['administrador a 390 px', await contraste()]);

    const textosOk = []; let totalTextos = 0; const malos = [];
    for (const [nombre, r] of estados) { const t = resumenTextos(r); totalTextos += t.n; for (const m of t.mal) malos.push(`${nombre}: ${m.el} ${m.r}:1 (mín. ${m.min}:1)`); textosOk.push(`${nombre}: ${t.n} textos, el más bajo ${t.peor.r}:1 sobre ${t.peor.min}:1 requerido (${t.peor.el})`); }
    fila('1.4.3 Contraste del texto', `${estados.length} estados, ${totalTextos} medidas de texto`, malos.length === 0, malos.length ? [...new Set(malos)].slice(0, 6).join(' · ') : 'todo texto visible alcanza su mínimo');
    const peorGlobal = estados.flatMap(([n, r]) => r.textos.map((t) => ({ ...t, n }))).reduce((a, t) => (t.r / t.min < a.r / a.min ? t : a));
    fila('1.4.3 Contraste del texto', 'Margen del peor caso', peorGlobal.r >= peorGlobal.min, `${peorGlobal.r}:1 en «${peorGlobal.el}» (${peorGlobal.n}); mínimo ${peorGlobal.min}:1`);
    const delModulo = estados.flatMap(([n, r]) => r.textos.filter((t) => t.main).map((t) => ({ ...t, n }))); const peorModulo = delModulo.reduce((a, t) => (t.r / t.min < a.r / a.min ? t : a));
    fila('1.4.3 Contraste del texto', `Peor caso solo del módulo (área principal, ${delModulo.length} medidas)`, peorModulo.r >= peorModulo.min, `${peorModulo.r}:1 en «${peorModulo.el}» (${peorModulo.n}); mínimo ${peorModulo.min}:1`);
    const camposTodos = estados.flatMap(([, r]) => r.campos.filter((c) => c.tag === 'INPUT' && c.type !== 'checkbox')); // la casilla es nativa: se mide aparte (tamaño)
    const sinBorde = camposTodos.filter((c) => c.borde === null || c.borde < 3);
    fila('1.4.11 Contraste de componentes', `${camposTodos.length} medidas de campos de texto: borde frente al fondo`, sinBorde.length === 0, sinBorde.length ? `${sinBorde.length} sin borde de 3:1 (p. ej. ${sinBorde[0].el}: ${sinBorde[0].borde ?? 'sin borde'})` : `borde mínimo ${Math.min(...camposTodos.map((c) => c.borde))}:1`);
    const botones = estados.flatMap(([, r]) => r.campos.filter((c) => c.tag === 'BUTTON' && c.relleno > 1.05));
    fila('1.4.11 Contraste de componentes', `${botones.length} botones con relleno: frente al fondo`, botones.every((b) => b.relleno >= 3), `relleno mínimo ${botones.length ? Math.min(...botones.map((b) => b.relleno)) : 'n/d'}:1`);

    /* ------------------------------------------------------------ teclado */
    await abrir('admin');
    const pasos = await recorrerConTab();
    const reales = pasos.filter((p) => !p.fin); const cierre = pasos.find((p) => p.fin);
    fila('2.1.1 / 2.1.2 Teclado', 'Se llega con Tab a todos los controles y el recorrido termina (sin trampa)', Boolean(cierre) && reales.length >= 8, `${reales.length} paradas y luego el foco sale del documento (parada ${cierre ? cierre.i + 1 : 'ninguna en 40'})`);
    const enMain = reales.filter((p) => p.enMain);
    const campos = enMain.filter((p) => ['INPUT', 'BUTTON'].includes(p.tag));
    fila('2.1.1 Teclado', 'Controles de la pantalla alcanzables: 2 campos + Consultar (3), 4 campos + Registrar (5), 4 campos + casilla + Asociar (6)', campos.length === 14, `${campos.length} controles de formulario en el área principal (esperados 14)`);
    const desordenado = enMain.some((p, i) => i > 0 && (p.y < enMain[i - 1].y - 2));
    fila('2.4.3 Orden del foco', 'El foco avanza de arriba abajo en el área principal, sin saltos hacia atrás', !desordenado, desordenado ? 'hay un retroceso vertical' : `orden: ${enMain.map((p) => p.nombreAccesible || p.tag).slice(0, 5).join(' → ')} …`);
    fila('2.4.3 Orden del foco', 'No se usa tabindex positivo', reales.every((p) => !(parseInt(p.tabindex) > 0)), reales.filter((p) => p.tabindex).length ? 'hay tabindex' : 'ninguno');
    const propios = reales.filter((p) => p.outline >= 2 && p.estiloOutline !== 'auto'); const delNavegador = reales.filter((p) => p.estiloOutline === 'auto'); const sinIndicador = reales.filter((p) => !p.sombra && p.outline < 1);
    fila('2.4.7 Foco visible', `Cada una de las ${reales.length} paradas muestra un indicador`, sinIndicador.length === 0, sinIndicador.length ? sinIndicador.slice(0, 4).map((p) => p.el).join(' · ') : `${propios.length} con contorno propio de 2 px o más (todos los controles del módulo) y ${delNavegador.length} con el anillo predeterminado del navegador (${delNavegador.map((p) => p.nombreAccesible).slice(0, 4).join(', ')}…)`);
    fila('2.4.7 Foco visible', 'Los controles del módulo (área principal) tienen contorno propio de 2 px o más', enMain.every((p) => p.outline >= 2 && p.estiloOutline !== 'auto'), `${enMain.length} controles; contorno ${[...new Set(enMain.map((p) => p.outline))].join('/')} px`);
    const bajoContraste = propios.filter((p) => p.outlineRatio !== null && p.outlineRatio < 3);
    fila('1.4.11 Contraste del foco', 'El contorno del foco contrasta 3:1 con su fondo', bajoContraste.length === 0, bajoContraste.length ? bajoContraste.slice(0, 4).map((p) => `${p.el} ${p.outlineRatio}:1`).join(' · ') : `mínimo ${Math.min(...propios.filter((p) => p.outlineRatio !== null).map((p) => p.outlineRatio))}:1 en los contornos propios (el anillo del navegador es de doble trazo y no se mide aquí)`);
    const chicos = reales.filter((p) => p.w < 24 || p.h < 24);
    fila('2.5.8 Tamaño del objetivo', 'Todo control enfocable mide al menos 24 × 24 px', chicos.length === 0, chicos.length ? chicos.slice(0, 4).map((p) => `${p.el} ${p.w}×${p.h}`).join(' · ') : `menor: ${Math.min(...reales.map((p) => Math.min(p.w, p.h)))} px de lado`);
    const m44 = reales.filter((p) => p.enMain && (p.h < 44) && p.tag !== 'A' && !(p.tag === 'INPUT' && p.w < 44));
    fila('Diseño propio (semana 10)', 'Campos y botones del área principal de 44 px de alto', m44.length === 0, m44.length ? m44.slice(0, 4).map((p) => `${p.el} ${p.h} px`).join(' · ') : 'todos ≥ 44 px (la casilla, de 24 px, se mide en 2.5.8; su etiqueta también la activa)');

    /* teclado: operar el formulario sin ratón */
    await abrir('admin');
    await ev("document.querySelector('#doc-specialty').focus()");
    await cdp('Input.insertText', { text: 'NEURO' }); await tecla('Tab', 'Tab', 9); await cdp('Input.insertText', { text: '2026-10-19' }); await tecla('Enter', 'Enter', 13, { text: '\r' });
    const consultaConEnter = await esperar("!!document.querySelector('table')", 4000);
    fila('2.1.1 Teclado', 'Escribir los dos campos y pulsar Enter ejecuta la consulta (sin ratón)', consultaConEnter, consultaConEnter ? 'la tabla de disponibilidad apareció' : 'no hubo resultado');
    await abrir('admin');
    await ev("document.querySelector('#doc-reg-license_number').focus()"); await tecla('Enter', 'Enter', 13, { text: '\r' }); await dormir(500);
    const errores1 = await ev(`(() => ({ invalidos: document.querySelectorAll('#doc-reg-local_user_ref_id[aria-invalid=true], #doc-reg-license_number[aria-invalid=true], #doc-reg-specialty_code[aria-invalid=true], #doc-reg-valid_from[aria-invalid=true]').length, alertas: document.querySelectorAll('.doc__card [role=alert]').length, foco: document.activeElement.id }))()`);
    fila('3.3.1 Identificación de errores', 'Enter en un campo vacío valida: 4 campos marcados con aria-invalid y 4 mensajes con role="alert"', errores1.invalidos === 4 && errores1.alertas >= 4, `${errores1.invalidos} campos aria-invalid, ${errores1.alertas} alertas; el foco queda en #${errores1.foco}`);
    const enlazados = await ev(`[...document.querySelectorAll('#doc-reg-local_user_ref_id, #doc-reg-license_number, #doc-reg-specialty_code, #doc-reg-valid_from')].filter((e) => e.getAttribute('aria-describedby')).length`);
    fila('1.3.1 / 3.3.1 Error asociado al campo', 'Cada campo inválido referencia su mensaje con aria-describedby', enlazados === 4, `${enlazados} de 4 campos lo referencian`);

    /* ------------------------------------------------------------ estructura, nombres y reflow */
    await abrir('admin');
    const estructura = await ev(`(() => { const vis = (e) => { const r = e.getBoundingClientRect(); return r.width > 0 && r.height > 0; };
      const inputs = [...document.querySelectorAll('main input:not([type=hidden])')].filter(vis); const sinEtiqueta = inputs.filter((i) => !(i.labels && i.labels.length) && !i.getAttribute('aria-label') && !i.getAttribute('aria-labelledby'));
      const botones = [...document.querySelectorAll('main button')].filter(vis).filter((b) => !b.innerText.trim() && !b.getAttribute('aria-label'));
      const h = [...document.querySelectorAll('main h1, main h2, main h3')].map((x) => +x.tagName[1]); const saltos = h.some((n, i) => i > 0 && n - h[i - 1] > 1);
      const ids = [...document.querySelectorAll('[id]')].map((e) => e.id); const dup = ids.filter((x, i) => ids.indexOf(x) !== i);
      return { inputs: inputs.length, sinEtiqueta: sinEtiqueta.map((i) => i.id), botonesSinNombre: botones.length, h1: document.querySelectorAll('h1').length, niveles: h.join(','), saltos, dup,
        lang: document.documentElement.lang, main: document.querySelectorAll('main').length, salto: !!document.querySelector('a[href="#contenido"]'), titulo: document.title }; })()`);
    fila('1.3.1 / 4.1.2 Nombres y etiquetas', `${estructura.inputs} campos del área principal con etiqueta`, estructura.sinEtiqueta.length === 0, estructura.sinEtiqueta.length ? `sin etiqueta: ${estructura.sinEtiqueta.join(', ')}` : 'todos tienen <label for> o nombre accesible');
    fila('4.1.2 Nombres', 'Todo botón tiene nombre', estructura.botonesSinNombre === 0, `${estructura.botonesSinNombre} sin nombre`);
    fila('1.3.1 Estructura', 'Un solo h1 y niveles de encabezado sin saltos', estructura.h1 === 1 && !estructura.saltos, `h1: ${estructura.h1}; niveles en la pantalla: ${estructura.niveles}`);
    fila('4.1.1 / 1.3.1 Identificadores', 'Sin id duplicados', estructura.dup.length === 0, estructura.dup.length ? estructura.dup.join(', ') : 'ninguno');
    fila('2.4.1 Saltar bloques', 'Hay enlace «Saltar al contenido» y un único main', estructura.salto && estructura.main === 1, `enlace: ${estructura.salto}; main: ${estructura.main}`);
    fila('3.1.1 Idioma de la página', `html lang en el arnés: «${estructura.lang}»`, estructura.lang.startsWith('es'), 'el arnés declara es; **la plantilla real usa app()->getLocale() y APP_LOCALE=en por defecto**: ver el informe (hallazgo de plataforma)');
    const tabla = await (async () => { await abrir('admin'); await llenar('#doc-specialty', 'NEURO'); await llenar('#doc-date', '2026-10-19'); await pulsar('Consultar'); await esperar("!!document.querySelector('table')"); return ev("(() => { const t = document.querySelector('table'); return { caption: !!t.querySelector('caption'), thCol: t.querySelectorAll('thead th[scope=col]').length, thRow: t.querySelectorAll('tbody th[scope=row]').length, filas: t.querySelectorAll('tbody tr').length }; })()"); })();
    fila('1.3.1 Tabla de resultados', 'Tiene caption, th scope="col" y th scope="row" por fila', tabla.caption && tabla.thCol === 3 && tabla.thRow === tabla.filas, `caption: ${tabla.caption}; encabezados de columna: ${tabla.thCol}; de fila: ${tabla.thRow}/${tabla.filas}`);

    for (const [ancho, etiqueta] of [[320, '320 px (1.4.10, equivale a 400 % de zoom)'], [640, '640 px (200 % de zoom sobre 1280)']]) {
        await abrir('admin', ancho, 800, true); await llenar('#doc-specialty', 'NEURO'); await llenar('#doc-date', '2026-10-19'); await pulsar('Consultar'); await esperar("!!document.querySelector('table')"); await dormir(300);
        const r = await ev("({ ancho: document.documentElement.scrollWidth, cliente: document.documentElement.clientWidth, accion: (() => { const b = [...document.querySelectorAll('button')].find((x) => x.textContent.trim().startsWith('Consultar')); const r = b.getBoundingClientRect(); return r.right <= innerWidth + 1 && r.left >= -1; })() })");
        fila('1.4.10 Reflow', `Sin desplazamiento horizontal a ${etiqueta}`, r.ancho <= r.cliente + 1 && r.accion, `contenido ${r.ancho} px sobre ${r.cliente} px; botón Consultar dentro de la ventana: ${r.accion}`);
    }
    fila('General', 'Sin errores de JavaScript durante toda la medición', errores.length === 0, errores.length ? errores[0].slice(0, 100) : 'ninguno');

    console.log('| Criterio | Qué se midió | Resultado | Detalle |\n|---|---|---|---|');
    console.log(filas.join('\n'));
    console.log('\nContraste por estado:\n' + textosOk.map((t) => `- ${t}`).join('\n'));
    console.log(`\n${filas.length - fallos} de ${filas.length} comprobaciones cumplen.`);
} finally {
    ws.close(); chrome.kill(); servidor.close(); await dormir(500); try { rmSync(perfil, { recursive: true, force: true }); } catch { /* perfil en uso */ }
}
process.exit(fallos ? 1 : 0);
