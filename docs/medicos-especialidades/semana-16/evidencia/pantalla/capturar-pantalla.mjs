// Captura la pantalla /medicos (ASII-04, Semana 16) con Chrome sin interfaz, por el protocolo CDP, sin dependencias.
//
// Requiere el código de la pantalla (PR #1045) y un Chrome instalado. Pasos, desde la raíz del repositorio:
//   1. CAPTURAS_DIST=<carpeta> npx vite build --config docs/medicos-especialidades/semana-16/evidencia/pantalla/vite.config.mjs
//   2. node docs/medicos-especialidades/semana-16/evidencia/pantalla/capturar-pantalla.mjs <carpeta> <salida>
// El API es simulado (ver harness.js): las capturas muestran la pantalla real, no un backend real.
// Chrome: CHROME_PATH o, por defecto, la ruta de Windows. Salida: 9 PNG y, por consola, desborde horizontal en móvil y errores de consola.
import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { mkdtempSync, mkdirSync, readFileSync, writeFileSync, existsSync, statSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, extname, resolve } from 'node:path';

const dist = resolve(process.argv[2]);
const salida = resolve(process.argv[3]);
mkdirSync(salida, { recursive: true });
const CHROME = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const dormir = (ms) => new Promise((r) => setTimeout(r, ms));
const tipos = { '.html': 'text/html; charset=utf-8', '.js': 'text/javascript', '.css': 'text/css', '.svg': 'image/svg+xml' };

const servidor = createServer((req, res) => {
    const ruta = decodeURIComponent(new URL(req.url, 'http://x').pathname);
    let archivo = join(dist, ruta);
    if (!existsSync(archivo) || statSync(archivo).isDirectory()) archivo = join(dist, 'index.html');
    res.writeHead(200, { 'Content-Type': tipos[extname(archivo)] ?? 'application/octet-stream' });
    res.end(readFileSync(archivo));
});
await new Promise((r) => servidor.listen(0, '127.0.0.1', r));
const base = `http://127.0.0.1:${servidor.address().port}`;

const puerto = 9700 + Math.floor(Math.random() * 200);
const perfil = mkdtempSync(join(tmpdir(), 'asii04-cap-'));
const chrome = spawn(CHROME, ['--headless=new', '--disable-gpu', '--no-first-run', '--hide-scrollbars', `--remote-debugging-port=${puerto}`, `--user-data-dir=${perfil}`, 'about:blank'], { stdio: 'ignore' });

let objetivos;
for (let i = 0; i < 50; i++) {
    try { objetivos = await (await fetch(`http://127.0.0.1:${puerto}/json/list`)).json(); if (objetivos.some((t) => t.type === 'page')) break; } catch {}
    await dormir(200);
}
const ws = new WebSocket(objetivos.find((t) => t.type === 'page').webSocketDebuggerUrl);
await new Promise((r) => (ws.onopen = r));
let id = 0; const pendientes = new Map(); const errores = [];
ws.onmessage = ({ data }) => {
    const m = JSON.parse(data);
    if (m.id && pendientes.has(m.id)) { pendientes.get(m.id)(m); pendientes.delete(m.id); }
    if (m.method === 'Runtime.exceptionThrown') errores.push(m.params.exceptionDetails.exception?.description ?? m.params.exceptionDetails.text);
    if (m.method === 'Log.entryAdded' && m.params.entry.level === 'error') errores.push(m.params.entry.text + ' ' + (m.params.entry.url ?? ''));
};
const cdp = (method, params = {}) => new Promise((r) => { const n = ++id; pendientes.set(n, r); ws.send(JSON.stringify({ id: n, method, params })); });
const evalua = async (expresion) => (await cdp('Runtime.evaluate', { expression: expresion, returnByValue: true, awaitPromise: true })).result?.result?.value;
await cdp('Page.enable'); await cdp('Runtime.enable'); await cdp('Log.enable');

let vista = { ancho: 1280, movil: false };
async function abrir(url, ancho, alto, movil) {
    vista = { ancho, movil };
    await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: alto, deviceScaleFactor: 1, mobile: movil });
    await cdp('Page.navigate', { url: `${base}${url}` });
    for (let i = 0; i < 60; i++) { if (await evalua("!!document.querySelector('h1') && document.body.innerText.length > 50")) break; await dormir(150); }
    await dormir(300);
}
const llenar = (selector, valor) => evalua(`(() => { const e = document.querySelector(${JSON.stringify(selector)}); if (!e) return false; e.value = ${JSON.stringify(valor)}; e.dispatchEvent(new Event('input', { bubbles: true })); return true; })()`);
const pulsar = (texto) => evalua(`(() => { const b = [...document.querySelectorAll('button')].find((x) => x.textContent.trim().startsWith(${JSON.stringify(texto)})); if (!b) return false; b.click(); return true; })()`);
async function foto(nombre) {
    await dormir(500);
    const alto = await evalua("Math.max(document.documentElement.scrollHeight, ...[...document.querySelectorAll('*')].map((e) => e.scrollHeight))");
    await cdp('Emulation.setDeviceMetricsOverride', { width: vista.ancho, height: Math.min(alto, 4000), deviceScaleFactor: 1, mobile: vista.movil });
    await dormir(300);
    const r = await cdp('Page.captureScreenshot', { format: 'png' });
    writeFileSync(join(salida, nombre), Buffer.from(r.result.data, 'base64'));
    console.log(`OK ${nombre} (${vista.ancho}x${alto})`);
}
const consultar = async (especialidad) => { await llenar('#doc-specialty', especialidad); await llenar('#doc-date', '2026-10-19'); await pulsar('Consultar'); await dormir(500); };
const alta = async (licencia) => {
    for (const [c, v] of [['local_user_ref_id', '3f6c1a52-8e47-4d0b-9a13-7c2d5e8b1f40'], ['license_number', licencia], ['specialty_code', 'CARD'], ['valid_from', '2026-01-01']]) await llenar(`#doc-reg-${c}`, v);
    await pulsar('Registrar'); await dormir(500);
};

try {
    await abrir('/medicos?rol=admin', 1280, 800, false); await consultar('NEURO'); await foto('01-disponibilidad-escritorio.png');
    await abrir('/medicos?rol=admin', 1280, 800, false); await pulsar('Registrar'); await foto('02-validacion-alta-escritorio.png');
    await abrir('/medicos?rol=admin', 1280, 800, false); await alta('COL-100'); await foto('03-alta-registrada-escritorio.png');
    await abrir('/medicos?rol=admin', 1280, 800, false); await alta('COL-9'); await foto('04-licencia-duplicada-escritorio.png');
    await abrir('/medicos?rol=admin', 1280, 800, false); await consultar('FALLA'); await foto('05-servicio-no-disponible-escritorio.png');
    await abrir('/medicos?rol=recepcion', 1280, 800, false); await consultar('NEURO'); await foto('06-recepcion-solo-consulta-escritorio.png');
    await abrir('/medicos?rol=sinpermiso', 1280, 800, false); await foto('07-sin-permiso-escritorio.png');
    await abrir('/medicos?rol=admin', 390, 844, true); await consultar('NEURO'); await foto('08-disponibilidad-movil.png');
    await abrir('/medicos?rol=admin', 390, 844, true); await pulsar('Registrar'); await foto('09-validacion-alta-movil.png');
    const desborde = await evalua('document.documentElement.scrollWidth > document.documentElement.clientWidth');
    console.log('Desborde horizontal en móvil (390 px):', desborde);
    console.log('Errores de consola:', errores.length ? errores : 'ninguno');
} finally {
    ws.close(); chrome.kill(); servidor.close();
    await dormir(500); try { rmSync(perfil, { recursive: true, force: true }); } catch {}
}
