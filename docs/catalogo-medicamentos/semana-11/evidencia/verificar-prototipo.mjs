// Verificación del prototipo de Semana 11 en Chrome sin interfaz (protocolo DevTools), solo con la biblioteca estándar.
// Ejecuta interacciones reales (teclado, clics, foco) a 1280 y 360 px y guarda capturas en ../capturas/.
// Uso: node verificar-prototipo.mjs [--chrome <ruta>]
import { spawn } from 'node:child_process';
import { mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const aqui = dirname(fileURLToPath(import.meta.url));
const argChrome = process.argv.indexOf('--chrome');
const chrome = argChrome > -1 ? process.argv[argChrome + 1] : 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const prototipo = pathToFileURL(resolve(aqui, '..', 'prototipo', 'index.html')).href;
const capturas = resolve(aqui, '..', 'capturas');
if (!existsSync(chrome)) { console.error(`No se encontró Chrome en ${chrome}`); process.exit(2); }
mkdirSync(capturas, { recursive: true });

const perfil = mkdtempSync(join(tmpdir(), 'asii14-proto-'));
const proceso = spawn(chrome, ['--headless=new', '--remote-debugging-port=0', `--user-data-dir=${perfil}`, '--no-first-run', 'about:blank'], { stdio: 'ignore' });
const esperar = (ms) => new Promise((r) => setTimeout(r, ms));
let puerto;
for (let i = 0; i < 100 && !puerto; i++) {
  await esperar(100);
  try { puerto = readFileSync(join(perfil, 'DevToolsActivePort'), 'utf8').split('\n')[0]; } catch { /* aún no existe */ }
}
const objetivo = (await (await fetch(`http://127.0.0.1:${puerto}/json/list`)).json()).find((t) => t.type === 'page');
const ws = new WebSocket(objetivo.webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener('open', r, { once: true }));
let siguiente = 0;
const pendientes = new Map();
ws.addEventListener('message', (e) => { const m = JSON.parse(e.data); pendientes.get(m.id)?.(m); pendientes.delete(m.id); });
const cdp = (method, params = {}) => new Promise((r) => { const id = ++siguiente; pendientes.set(id, r); ws.send(JSON.stringify({ id, method, params })); });
const js = async (expr) => (await cdp('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true })).result.result.value;
const tecla = async (key, code = key, keyCode = 0) => {
  // Enter solo envía un formulario si el evento lleva text '\r', como una tecla física.
  await cdp('Input.dispatchKeyEvent', { type: 'keyDown', key, code, windowsVirtualKeyCode: keyCode, ...(key === 'Enter' ? { text: '\r' } : {}) });
  await cdp('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode: keyCode });
  await esperar(80);
};
const escribir = async (selector, texto) => { await js(`document.querySelector('${selector}').focus()`); await cdp('Input.insertText', { text: texto }); };
const clic = async (selector) => {
  const c = await js(`(() => { const e = document.querySelector(${JSON.stringify(selector)}); e.scrollIntoView({ block: "center" }); const r = e.getBoundingClientRect(); return [r.x + r.width / 2, r.y + r.height / 2]; })()`);
  for (const type of ['mousePressed', 'mouseReleased']) { await cdp('Input.dispatchMouseEvent', { type, x: c[0], y: c[1], button: 'left', clickCount: 1 }); }
  await esperar(80);
};
const captura = async (nombre) => writeFileSync(join(capturas, `${nombre}.png`), Buffer.from((await cdp('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true })).result.data, 'base64'));
const abrir = async (ancho, alto) => {
  await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: alto, deviceScaleFactor: 1, mobile: ancho < 768 });
  await cdp('Page.navigate', { url: prototipo });
  await esperar(500);
};
const resultados = [];
const comprobar = (id, descripcion, ok) => { resultados.push({ id, descripcion, ok: Boolean(ok) }); };

await cdp('Page.enable');
for (const [modo, ancho, alto] of [['desktop', 1280, 800], ['movil', 360, 740]]) {
  await abrir(ancho, alto);
  const P = modo === 'desktop' ? 'D' : 'M';
  comprobar(`${P}1`, `${ancho} px: el aviso de datos simulados es visible y el clínico no ve "Dar de alta"`, await js(`!!document.querySelector('.aviso').offsetHeight && document.getElementById('abrir-alta').hidden`));
  await escribir('#termino', 'a');
  await tecla('Enter', 'Enter', 13);
  comprobar(`${P}2`, `${ancho} px: un término de 1 carácter muestra el error, marca aria-invalid y deja el foco en el campo`, await js(`!document.getElementById('error-termino').hidden && document.getElementById('termino').getAttribute('aria-invalid') === 'true' && document.activeElement.id === 'termino'`));
  await escribir('#termino', 'mox');
  await tecla('Enter', 'Enter', 13);
  comprobar(`${P}3`, `${ancho} px: Enter busca y role="status" anuncia "2 resultados"`, await js(`document.getElementById('estado').textContent === '2 resultados.'`));
  comprobar(`${P}4`, `${ancho} px: la tabla tiene caption y encabezados con scope; la vigencia está en texto`, await js(`!!document.querySelector('caption') && document.querySelectorAll('th[scope="col"]').length === 4 && document.body.innerText.includes('Inactivo - no disponible para nuevas indicaciones')`));
  comprobar(`${P}5`, `${ancho} px: sin desplazamiento horizontal`, await js(`document.documentElement.scrollWidth <= ${ancho}`));
  await captura(`${modo}-01-resultados-clinico`);
  await js(`(() => { const s = document.getElementById('rol'); s.value = 'gestor'; s.dispatchEvent(new Event('change')); })()`);
  comprobar(`${P}6`, `${ancho} px: el gestor ve las acciones con nombre accesible distinto por código`, await js(`!document.getElementById('abrir-alta').hidden && !!document.querySelector('button[aria-label="Inactivar AMX-500"]') && !!document.querySelector('button[aria-label="Activar AMX-250"]')`));
  await clic('button[aria-label="Inactivar AMX-500"]');
  comprobar(`${P}7`, `${ancho} px: la confirmación muestra el registro completo y el foco inicial está en Cancelar`, await js(`document.getElementById('dlg-vigencia').open && document.activeElement.id === 'dlg-cancelar' && document.getElementById('dlg-vigencia-datos').innerText.includes('Amoxicilina 500 mg')`));
  await captura(`${modo}-02-confirmar-vigencia`);
  await tecla('Escape', 'Escape', 27);
  comprobar(`${P}8`, `${ancho} px: Esc cierra sin cambios y el foco vuelve al botón de origen`, await js(`!document.getElementById('dlg-vigencia').open && document.activeElement.getAttribute('aria-label') === 'Inactivar AMX-500' && document.body.innerText.includes('Activo')`));
  await clic('button[aria-label="Inactivar AMX-500"]');
  await clic('#dlg-confirmar');
  comprobar(`${P}9`, `${ancho} px: camino feliz: "Vigencia actualizada" y AMX-500 pasa a Inactivo sin desaparecer`, await js(`document.getElementById('estado').textContent.startsWith('Vigencia actualizada: AMX-500') && !!document.querySelector('button[aria-label="Activar AMX-500"]')`));
  await captura(`${modo}-03-vigencia-actualizada`);
  await clic('#abrir-alta');
  await escribir('#f-code', 'amx-500');
  await escribir('#f-name', '<img src=x onerror="window.inyectado=1">');
  await escribir('#f-active_ingredient', 'Amoxicilina');
  await escribir('#f-pharmacological_group', 'Antibiótico');
  await clic('#form-alta button[type="submit"]');
  comprobar(`${P}10`, `${ancho} px: alta duplicada: mensaje en el campo, foco en Código y datos conservados`, await js(`document.getElementById('e-code').textContent === 'Ya existe un medicamento con este código.' && document.activeElement.id === 'f-code' && document.getElementById('f-active_ingredient').value === 'Amoxicilina'`));
  await captura(`${modo}-04-alta-duplicada`);
  await js(`document.getElementById('f-code').value = 'NEW-1'`);
  await clic('#form-alta button[type="submit"]');
  await js(`(() => { document.getElementById('termino').value = 'inyect'; document.getElementById('buscar').requestSubmit(); document.getElementById('termino').value = 'img'; document.getElementById('buscar').requestSubmit(); })()`);
  comprobar(`${P}11`, `${ancho} px: un nombre con HTML se muestra como texto y no se ejecuta`, await js(`window.inyectado === undefined && !document.querySelector('#resultados img') && document.getElementById('resultados').innerText.includes('<img')`));
  await js(`document.getElementById('caida').checked = true`);
  await escribir('#termino', '');
  await js(`document.getElementById('buscar').requestSubmit()`);
  comprobar(`${P}12`, `${ancho} px: error crítico: catálogo no disponible con Reintentar, sin datos parciales ni un estado anterior contradictorio`, await js(`document.getElementById('estado').textContent === '' && !document.getElementById('alerta').hidden && !!document.getElementById('reintentar') && !document.querySelector('#resultados table')`));
  await captura(`${modo}-05-error-catalogo`);
}

ws.close();
proceso.kill();
await esperar(300);
try { rmSync(perfil, { recursive: true, force: true }); } catch { /* Chrome puede tardar en liberar el perfil */ }
const fallas = resultados.filter((r) => !r.ok);
console.log('| ID | Comprobación | Resultado |\n|---|---|---|');
for (const r of resultados) { console.log(`| ${r.id} | ${r.descripcion} | ${r.ok ? 'Cumple' : 'NO CUMPLE'} |`); }
console.log(`\n${resultados.length} comprobaciones: ${resultados.length - fallas.length} cumplen y ${fallas.length} no cumplen. Capturas en ${capturas}`);
process.exit(fallas.length === 0 ? 0 : 1);
