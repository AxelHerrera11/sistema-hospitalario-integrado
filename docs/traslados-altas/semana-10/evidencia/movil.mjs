// Escenarios móviles con conexión limitada (Semana 10). Uso: node movil.mjs <base> <dir-capturas>
// E1: a 375 px, la red se corta antes de confirmar → no se envía nada y el estado no cambia.
// E2: a 375 px, red con 20 s de latencia → «Procesando…» bloquea un doble envío; a los 15 s se informa incierto;
//     al volver la red, «Consultar estado» muestra el estado real.
import puppeteer from 'puppeteer-core';

const [base, salida] = process.argv.slice(2);
const ACTOR = '00000000-0000-4000-8000-0000000000f1';
const ESTANCIA = '00000000-0000-4000-8000-0000000000e1';
const resultados = [];
const check = (nombre, ok) => { resultados.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${nombre}`); };
const navegador = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });
const p = await navegador.newPage();
await p.setViewport({ width: 375, height: 760, isMobile: true, hasTouch: true });
const cdp = await p.createCDPSession();
const red = (offline, latency = 0) => cdp.send('Network.emulateNetworkConditions', { offline, latency, downloadThroughput: -1, uploadThroughput: -1 });
const texto = () => p.$eval('#resultado', (e) => e.textContent);
const vigente = async () => (await (await fetch(`${base}/estancias/${ESTANCIA}/internacion`, { headers: { 'X-Actor-Ref': ACTOR } })).json()).estancia_consultada_abierta;
async function prepararTraslado() {
    await p.goto(`${base}/ui/index.html`, { waitUntil: 'networkidle0' });
    await p.type('#actor', ACTOR);
    await p.type('#estancia', ESTANCIA);
    await p.tap('#consulta button[type=submit]');
    await p.waitForSelector('#estado:not([hidden])');
    await p.tap('#elegir-traslado');
    await p.type('#cama', '00000000-0000-4000-8000-0000000000b2');
    await p.type('#motivo', 'Requiere aislamiento');
    await p.tap('#formulario button[type=submit]');
}
try {
    await prepararTraslado();
    await red(true);
    await p.tap('#confirmar');
    await p.waitForFunction(() => document.getElementById('resultado').textContent.includes('Sin conexión'));
    salida && await p.screenshot({ path: `${salida}/movil-sin-red-375.png`, fullPage: true });
    await red(false);
    check('E1: sin red no se envía nada y se ofrece reintentar', (await texto()).includes('No se envió nada') && await vigente() === true);

    await prepararTraslado();
    await red(false, 20000);
    await p.tap('#confirmar');
    const bloqueado = await p.$eval('#confirmar', (b) => b.disabled && b.textContent === 'Procesando…');
    check('E2: mientras espera, el botón queda bloqueado (sin doble envío)', bloqueado);
    await p.waitForFunction(() => document.getElementById('resultado').textContent.includes('consulte el estado'), { timeout: 30000 });
    check('E2: a los 15 s se informa incierto y solo se ofrece consultar', !(await p.$eval('#reconsultar', (b) => b.hidden)));
    salida && await p.screenshot({ path: `${salida}/movil-incierto-375.png`, fullPage: true });
    await red(false, 0);
    const real = await vigente();
    await p.tap('#reconsultar');
    await p.waitForFunction(() => document.getElementById('resultado').textContent === 'Situación actualizada.');
    const pantalla = await p.$eval('#datos', (e) => e.textContent);
    check(`E2: «Consultar estado» muestra el estado real (vigente=${real})`, pantalla.includes(real ? 'sí' : 'no (los datos cambiaron)'));
} catch (error) {
    console.log(`ERROR ${error.message}`);
    resultados.push(false);
} finally {
    await navegador.close();
}
const fallas = resultados.filter((ok) => !ok).length;
console.log(`${resultados.length - fallas}/${resultados.length} comprobaciones`);
process.exit(fallas ? 1 : 0);
