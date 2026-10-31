// Capturas del prototipo con Chrome headless por DevTools Protocol (Node 22: WebSocket y fetch globales).
// Requisito: prototipo corriendo (ver micro-his/expediente-medico/README.md) con la base de demo recién creada.
//   node docs/expedientes-medicos/semana-11/herramientas/capturar.mjs docs/expedientes-medicos/semana-11/evidencias
// Variables: CHROME (ruta del ejecutable), BASE (por defecto http://127.0.0.1:8096/ui/).
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const salida = process.argv[2] ?? '.';
const BASE = process.env.BASE ?? 'http://127.0.0.1:8096/ui/';
const CHROME = process.env.CHROME ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9333;
const LOPEZ = '11111111-1111-4111-8111-111111111111';
const RUIZ = '33333333-3333-4333-8333-333333333333';
const MORA = '44444444-4444-4444-8444-444444444444';

// Pasos que se ejecutan en la página, igual que lo haría una persona con la interfaz.
const pasos = {
    inicio: '',
    consulta: `await accion('${LOPEZ}', 'hospital-demo', 'P-0001', 'consultar');`,
    motivo: `await accion('${LOPEZ}', 'hospital-demo', 'P-0001', 'consultar'); document.querySelector('details').open = true;`,
    sinVinculo: `await accion('${RUIZ}', 'hospital-demo', 'P-0001', 'consultar');`,
    hospitalCaido: `await accion('${LOPEZ}', 'hospital-norte', 'P-0001', 'consultar');`,
    codigoInvalido: `await accion('${LOPEZ}', 'hospital-demo', 'P 0001', 'consultar');`,
    vacio: `await accion('${LOPEZ}', 'hospital-demo', 'P-0002', 'consultar');`,
    confirmacion: `await accion('${MORA}', 'hospital-demo', 'P-0002', 'dialogo');`,
    apertura: `await accion('${MORA}', 'hospital-demo', 'P-0002', 'abrir');`,
};
const AYUDA = `const $ = (id) => document.getElementById(id); const espera = (ms) => new Promise((r) => setTimeout(r, ms));
async function accion(u, h, c, op) { $('usuario').value = u; $('hospital').value = h; $('codigo').value = c;
  if (op === 'consultar') $('consultar').click(); else { $('abrir').click(); await espera(150); if (op === 'abrir') $('confirmar').click(); }
  await espera(900); }`;

// [archivo, ancho, alto, paso, página completa]. La apertura va al final porque modifica la base de demo.
const escenarios = [
    ['d-01-inicio.png', 1280, 800, 'inicio', false], ['d-02-consulta-exito.png', 1280, 800, 'consulta', true],
    ['d-03-motivo-desplegado.png', 1280, 800, 'motivo', true], ['d-04-error-critico-sin-vinculo.png', 1280, 800, 'sinVinculo', false],
    ['d-05-hospital-no-disponible.png', 1280, 800, 'hospitalCaido', false], ['d-06-vacio-sin-apertura.png', 1280, 800, 'vacio', false],
    ['m-01-inicio-375.png', 375, 812, 'inicio', true], ['m-02-consulta-exito-375.png', 375, 812, 'consulta', true],
    ['m-03-error-critico-sin-vinculo-375.png', 375, 812, 'sinVinculo', true], ['m-04-codigo-invalido-375.png', 375, 812, 'codigoInvalido', true],
    ['m-05-consulta-320.png', 320, 640, 'consulta', true], ['m-06-confirmacion-375.png', 375, 812, 'confirmacion', false],
    ['d-07-confirmacion-apertura.png', 1280, 800, 'confirmacion', false], ['d-08-apertura-exito.png', 1280, 800, 'apertura', false],
    ['m-07-apertura-ya-existia-375.png', 375, 812, 'apertura', true],
];

const perfil = mkdtempSync(join(tmpdir(), 'expediente-chrome-'));
const chrome = spawn(CHROME, ['--headless=new', `--remote-debugging-port=${PUERTO}`, `--user-data-dir=${perfil}`, '--no-first-run', '--hide-scrollbars', 'about:blank']);
const espera = (ms) => new Promise((r) => setTimeout(r, ms));

async function destino() {
    for (let i = 0; i < 50; i++) {
        try {
            const paginas = await (await fetch(`http://127.0.0.1:${PUERTO}/json/list`)).json();
            const pagina = paginas.find((p) => p.type === 'page');
            if (pagina) return pagina.webSocketDebuggerUrl;
        } catch { /* Chrome todavía no escucha */ }
        await espera(200);
    }
    throw new Error('Chrome no respondió en el puerto de depuración');
}

const ws = new WebSocket(await destino());
await new Promise((r) => ws.addEventListener('open', r, { once: true }));
let siguiente = 0;
const pendientes = new Map();
ws.addEventListener('message', (m) => {
    const datos = JSON.parse(m.data);
    if (pendientes.has(datos.id)) { pendientes.get(datos.id)(datos); pendientes.delete(datos.id); }
});
const cdp = (method, params = {}) => new Promise((resolve, reject) => {
    const id = ++siguiente;
    pendientes.set(id, (d) => (d.error ? reject(new Error(`${method}: ${d.error.message}`)) : resolve(d.result)));
    ws.send(JSON.stringify({ id, method, params }));
});

try {
    for (const [archivo, ancho, alto, paso, completa] of escenarios) {
        await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: alto, deviceScaleFactor: 1, mobile: ancho < 768 });
        await cdp('Page.navigate', { url: BASE });
        await espera(700);
        await cdp('Runtime.evaluate', { expression: `(async () => { ${AYUDA} ${pasos[paso]} })()`, awaitPromise: true });
        let clip;
        if (completa) {
            const { cssContentSize } = await cdp('Page.getLayoutMetrics');
            clip = { x: 0, y: 0, width: ancho, height: Math.ceil(Math.max(alto, cssContentSize.height)), scale: 1 };
        }
        const { data } = await cdp('Page.captureScreenshot', { format: 'png', captureBeyondViewport: completa, ...(clip ? { clip } : {}) });
        writeFileSync(join(salida, archivo), Buffer.from(data, 'base64'));
        console.log(`captura ${archivo} (${ancho}px)`);
    }
} finally {
    ws.close();
    chrome.kill();
    await espera(300);
    rmSync(perfil, { recursive: true, force: true });
}
