#!/usr/bin/env node
// Captura la pantalla /medicos (ASII-04) contra el backend REAL con una sesión real (login por la interfaz).
// A diferencia de semana-16/evidencia/pantalla (API simulado), aquí todo pasa por Laravel, JWT, tenant, RBAC y PostgreSQL.
//
// Requiere el servidor levantado con datos ficticios y el frontend compilado (`npm run build`); ver `e2e-real.mjs`.
// Variables: E2E_BASE, E2E_HOSPITAL_ID, E2E_ADMIN_EMAIL, E2E_MEDICO_EMAIL, E2E_PASSWORD; CHROME_PATH (opcional).
// Uso: node capturar-real.mjs <carpeta-de-salida>
import { spawn } from 'node:child_process';
import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';

const need = (n) => process.env[n] ?? (console.error(`Falta la variable ${n}`), process.exit(2));
const BASE = need('E2E_BASE'), TENANT = need('E2E_HOSPITAL_ID'), PASS = need('E2E_PASSWORD');
const ADMIN = need('E2E_ADMIN_EMAIL'), MEDICO = need('E2E_MEDICO_EMAIL');
const salida = resolve(process.argv[2] ?? 'capturas-reales');
mkdirSync(salida, { recursive: true });
const CHROME = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const dormir = (ms) => new Promise((r) => setTimeout(r, ms));

const puerto = 9900 + Math.floor(Math.random() * 100);
const perfil = mkdtempSync(join(tmpdir(), 'asii04-real-'));
const chrome = spawn(CHROME, ['--headless=new', '--disable-gpu', '--no-first-run', '--hide-scrollbars', `--remote-debugging-port=${puerto}`, `--user-data-dir=${perfil}`, 'about:blank'], { stdio: 'ignore' });
let objetivos;
for (let i = 0; i < 50; i++) {
    try { objetivos = await (await fetch(`http://127.0.0.1:${puerto}/json/list`)).json(); if (objetivos.some((t) => t.type === 'page')) break; } catch { /* Chrome aún no responde */ }
    await dormir(200);
}
const ws = new WebSocket(objetivos.find((t) => t.type === 'page').webSocketDebuggerUrl);
await new Promise((r) => (ws.onopen = r));
let id = 0; const pendientes = new Map(); const errores = []; const respuestas = [];
ws.onmessage = ({ data }) => {
    const m = JSON.parse(data);
    if (m.id && pendientes.has(m.id)) { pendientes.get(m.id)(m); pendientes.delete(m.id); }
    if (m.method === 'Runtime.exceptionThrown') errores.push(m.params.exceptionDetails.exception?.description ?? m.params.exceptionDetails.text);
    if (m.method === 'Network.responseReceived' && m.params.response.url.includes('/api/v1/')) respuestas.push(`${m.params.response.status} ${new URL(m.params.response.url).pathname}`);
};
const cdp = (method, params = {}) => new Promise((r) => { const n = ++id; pendientes.set(n, r); ws.send(JSON.stringify({ id: n, method, params })); });
const evalua = async (e) => (await cdp('Runtime.evaluate', { expression: e, returnByValue: true, awaitPromise: true })).result?.result?.value;
await cdp('Page.enable'); await cdp('Runtime.enable'); await cdp('Network.enable');

let vista = { ancho: 1280, movil: false };
const poner = async (ancho, alto, movil) => { vista = { ancho, movil }; await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: alto, deviceScaleFactor: 1, mobile: movil }); };
const esperar = async (condicion, ms = 8000) => { for (let t = 0; t < ms; t += 150) { if (await evalua(condicion)) return true; await dormir(150); } return false; };
const llenar = (sel, valor) => evalua(`(() => { const e = document.querySelector(${JSON.stringify(sel)}); if (!e) return false; e.value = ${JSON.stringify(valor)}; e.dispatchEvent(new Event('input', { bubbles: true })); return true; })()`);
const pulsar = (texto) => evalua(`(() => { const b = [...document.querySelectorAll('button')].find((x) => x.textContent.trim().startsWith(${JSON.stringify(texto)})); if (!b) return false; b.click(); return true; })()`);
async function foto(nombre) {
    await dormir(500);
    const alto = await evalua("Math.max(document.documentElement.scrollHeight, ...[...document.querySelectorAll('*')].map((e) => e.scrollHeight))");
    await cdp('Emulation.setDeviceMetricsOverride', { width: vista.ancho, height: Math.min(alto, 4000), deviceScaleFactor: 1, mobile: vista.movil });
    await dormir(300);
    writeFileSync(join(salida, nombre), Buffer.from((await cdp('Page.captureScreenshot', { format: 'png' })).result.data, 'base64'));
    await cdp('Emulation.setDeviceMetricsOverride', { width: vista.ancho, height: 800, deviceScaleFactor: 1, mobile: vista.movil });
    console.log(`OK ${nombre}`);
}
async function iniciarSesion(correo) {
    await cdp('Network.clearBrowserCookies'); // la sesión anterior queda en una cookie: sin limpiarla, /login redirige
    await cdp('Page.navigate', { url: `${BASE}/login` });
    if (!(await esperar("!!document.querySelector('input[type=email]')"))) throw new Error('No apareció el formulario de login');
    await evalua('localStorage.clear()');
    await llenar('input[type=text]', TENANT); await llenar('input[type=email]', correo); await llenar('input[type=password]', PASS);
    await evalua("document.querySelector('button[type=submit]').click()");
    if (!(await esperar("!document.querySelector('input[type=email]')"))) throw new Error('El login no terminó');
    await esperar("!!document.querySelector('a[href=\"/medicos\"]')");
    await evalua("document.querySelector('a[href=\"/medicos\"]').click()");
    if (!(await esperar("!!document.querySelector('#doc-specialty') || document.body.innerText.includes('no tiene permiso')"))) throw new Error('No cargó /medicos');
}
const consultar = async (esp, fecha) => { await llenar('#doc-specialty', esp); await llenar('#doc-date', fecha); await pulsar('Consultar'); await esperar("!!document.querySelector('table') || !!document.querySelector('[role=alert]:not([hidden])') || document.body.innerText.includes('No hay médicos')"); };

try {
    await poner(1280, 800, false);
    await iniciarSesion(ADMIN);
    await consultar('NEURO', '2026-10-19'); await foto('r01-disponibilidad-real-escritorio.png');
    await consultar('NEURO', '2026-02-30'); await foto('r02-fecha-invalida-real-escritorio.png');
    await consultar('CARD', '2026-10-20'); await foto('r03-otra-consulta-real-escritorio.png');
    for (const [c, v] of [['local_user_ref_id', process.env.E2E_DOCTOR_REF_2 ?? '00000000-0000-4000-8000-000000000000'], ['license_number', 'col-e2e-1'], ['specialty_code', 'CARD'], ['valid_from', '2026-01-01']]) await llenar(`#doc-reg-${c}`, v);
    await pulsar('Registrar'); await esperar("document.body.innerText.includes('ya está registrada')");
    await foto('r04-licencia-duplicada-real-escritorio.png');
    await poner(390, 844, true);
    await consultar('NEURO', '2026-10-19'); await foto('r05-disponibilidad-real-movil.png');
    await poner(1280, 800, false);
    await iniciarSesion(MEDICO);
    await consultar('NEURO', '2026-10-19'); await foto('r06-medico-solo-consulta-real-escritorio.png');
    console.log('Respuestas del API vistas por el navegador:', [...new Set(respuestas)].join(' · '));
    console.log('Errores de JavaScript:', errores.length ? errores : 'ninguno');
} finally {
    ws.close(); chrome.kill(); await dormir(500); try { rmSync(perfil, { recursive: true, force: true }); } catch { /* perfil en uso */ }
}
