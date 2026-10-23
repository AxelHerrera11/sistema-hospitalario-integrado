// Recorrido del prototipo de interfaz ASII-08 contra la API real y PostgreSQL. Uso: node recorrido-ui.mjs <base> <dir-capturas> [ancho]
import puppeteer from 'puppeteer-core';

const [base, salida, ancho = '1024'] = process.argv.slice(2);
const ACTOR = '00000000-0000-4000-8000-0000000000f1';
const ESTANCIA = '00000000-0000-4000-8000-0000000000e1';
const resultados = [];
const check = (nombre, ok) => { resultados.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${nombre}`); };
const navegador = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });
const p = await navegador.newPage();
await p.setViewport({ width: Number(ancho), height: 900, isMobile: Number(ancho) < 500 });
const captura = (nombre) => salida && p.screenshot({ path: `${salida}/${nombre}-${ancho}.png`, fullPage: true });
const texto = (sel) => p.$eval(sel, (e) => e.textContent.trim());
const visible = (sel) => p.$eval(sel, (e) => !e.closest('[hidden]'));
const esperarResultado = () => p.waitForFunction(() => { const t = document.getElementById('resultado').textContent; return t && t !== 'Consultando…'; });
async function consultar(estancia) {
    await p.$eval('#estancia', (e, v) => { e.value = v; }, estancia);
    await p.click('#consulta button[type=submit]');
    await p.waitForFunction(() => document.getElementById('resultado').textContent !== 'Consultando…');
}
try {
    await p.goto(`${base}/ui/index.html`, { waitUntil: 'networkidle0' });
    await captura('01-inicio');
    await p.type('#actor', ACTOR);
    await consultar(ESTANCIA);
    check('consulta: episodio abierto con acciones', await visible('#acciones') && (await texto('#datos')).includes('abierto'));
    await captura('02-situacion');
    await p.click('#elegir-traslado');
    await p.click('#formulario button[type=submit]');
    check('validación local: cama y motivo antes de enviar', await visible('#error-cama'));
    await captura('03-validacion');
    await p.type('#cama', '00000000-0000-4000-8000-0000000000b3');
    await p.type('#motivo', 'Requiere aislamiento');
    await p.click('#formulario button[type=submit]');
    check('confirmación con resumen y foco en Confirmar', await visible('#confirmacion') && await p.evaluate(() => document.activeElement.id === 'confirmar'));
    await captura('04-confirmacion');
    await p.click('#confirmar');
    await esperarResultado();
    check('cama en mantenimiento → rechazo explicado, sin éxito', (await p.$eval('#resultado', (e) => e.className)) === 'rechazo' && (await texto('#resultado')).includes('no está disponible'));
    await captura('05-rechazo');
    await consultar(ESTANCIA);
    await p.click('#elegir-traslado');
    await p.$eval('#cama', (e) => { e.value = '00000000-0000-4000-8000-0000000000b2'; });
    await p.click('#formulario button[type=submit]');
    await p.click('#confirmar');
    await esperarResultado();
    check('traslado a cama libre → éxito confirmado', (await texto('#resultado')).includes('Traslado confirmado'));
    await captura('06-exito');
    await consultar(ESTANCIA);
    check('la estancia de origen ya no está vigente y no ofrece acciones', (await texto('#datos')).includes('no (los datos cambiaron)') && !(await visible('#acciones')));
    const destino = (await (await fetch(`${base}/estancias/${ESTANCIA}/internacion`, { headers: { 'X-Actor-Ref': ACTOR } })).json()).estancia_abierta_id;
    await consultar(destino);
    await p.click('#elegir-alta');
    await p.click('#formulario button[type=submit]');
    await p.click('#confirmar');
    await esperarResultado();
    check('alta de la nueva estancia → éxito confirmado', (await texto('#resultado')).includes('Alta confirmada'));
    await consultar(destino);
    check('tras el alta, episodio cerrado sin acciones', (await texto('#datos')).includes('cerrado') && !(await visible('#acciones')));
    check('sin desplazamiento horizontal', await p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
} catch (error) {
    console.log(`ERROR ${error.message}`);
    resultados.push(false);
} finally {
    await navegador.close();
}
const fallas = resultados.filter((ok) => !ok).length;
console.log(`${resultados.length - fallas}/${resultados.length} comprobaciones`);
process.exit(fallas ? 1 : 0);
