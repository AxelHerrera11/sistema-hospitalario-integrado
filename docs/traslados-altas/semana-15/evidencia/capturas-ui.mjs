// Capturas de la ejecución en Chrome: SPA -> API Laravel (JWT, tenant, RBAC) -> PostgreSQL con datos ficticios.
// Necesita los permisos activados: sobre la integración actual todo responde 403. Se ejecutó con la rama de la propuesta RBAC.
// Uso: E2E_PASSWORD=... node capturas-ui.mjs <url> <hospital-a> <hospital-b> <estancia> <cama-ocupada> <cama-libre> <carpeta>
import puppeteer from 'puppeteer-core';

const [base, hospitalA, hospitalB, estancia, ocupada, libre, salida] = process.argv.slice(2);
const navegador = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });
const resultados = [];
const check = (nombre, ok, detalle = '') => { resultados.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${nombre}${ok ? '' : ` — ${detalle}`}`); };
const captura = (p, nombre) => p.screenshot({ path: `${salida}/${nombre}.png`, fullPage: true });

async function entrar(correo, hospital, ancho = 1024, abrir = true) {
    const p = await (await navegador.createBrowserContext()).newPage();
    await p.setViewport({ width: ancho, height: 900, isMobile: ancho < 500 });
    await p.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    const campos = await p.$$('input');
    await campos[0].click({ clickCount: 3 }); await campos[0].type(hospital); await campos[1].type(correo); await campos[2].type(process.env.E2E_PASSWORD ?? '');
    await p.click('button[type=submit]');
    await p.waitForFunction(() => location.pathname !== '/login');
    if (abrir) { await p.evaluate(() => document.querySelector('a[href="/traslados"]').click()); await p.waitForSelector('.ta'); }
    return p;
}
const texto = (p) => p.$eval('.ta', (e) => e.innerText);
async function consultar(p, id) {
    await p.$eval('#ta-estancia', (e, v) => { e.value = v; e.dispatchEvent(new Event('input')); }, id);
    await p.click('.ta form button[type=submit]');
    await new Promise((r) => setTimeout(r, 200));
    await p.waitForFunction(() => !document.querySelector('.ta form button[type=submit]')?.disabled);
    await new Promise((r) => setTimeout(r, 1500));
}
async function enviarTraslado(p, cama, motivo) {
    await p.click('.ta__acciones button');
    await p.waitForSelector('#ta-cama');
    await p.$eval('#ta-cama', (e, v) => { e.value = v; e.dispatchEvent(new Event('input')); }, cama);
    await p.$eval('#ta-motivo', (e, v) => { e.value = v; e.dispatchEvent(new Event('input')); }, motivo);
    await p.click('.ta form[novalidate] button[type=submit]');
}
async function confirmar(p) {
    await p.waitForFunction(() => document.activeElement?.textContent === 'Confirmar');
    await p.click('section[aria-labelledby="ta-confirmar"] button');
    await new Promise((r) => setTimeout(r, 2500));
}
try {
    const enf = await entrar('enfermera@a.local', hospitalA);
    await consultar(enf, estancia);
    check('consulta muestra la estancia y permite trasladar', (await texto(enf)).includes('Trasladar'), await texto(enf));
    await captura(enf, '01-consulta-1024');
    await enviarTraslado(enf, libre, '');
    await new Promise((r) => setTimeout(r, 300));
    check('motivo vacío: aviso local', /motivo/i.test(await texto(enf)), await texto(enf));
    await captura(enf, '02-motivo-vacio-1024');
    await consultar(enf, estancia);
    await enviarTraslado(enf, ocupada, 'Requiere aislamiento');
    await confirmar(enf);
    check('cama ocupada: rechazo sin cambios', /ocupad/i.test(await texto(enf)), await texto(enf));
    await captura(enf, '03-cama-ocupada-1024');
    await consultar(enf, estancia);
    await enviarTraslado(enf, libre, 'Requiere aislamiento');
    await confirmar(enf);
    check('traslado confirmado con nueva estancia', (await texto(enf)).includes('Traslado confirmado'), await texto(enf));
    await captura(enf, '04-traslado-confirmado-1024');
    const nueva = await enf.$eval('#ta-estancia', (e) => e.value);

    const med = await entrar('medico@a.local', hospitalA, 375);
    await consultar(med, nueva);
    await med.click('.ta__acciones button');
    await med.click('.ta form[novalidate] button[type=submit]');
    await confirmar(med);
    check('alta confirmada a 375 px', (await texto(med)).includes('Alta confirmada'), await texto(med));
    check('375 px sin desplazamiento horizontal', await med.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
    await captura(med, '05-alta-375');

    const recepcion = await entrar('recepcion@a.local', hospitalA, 1024, false);
    check('recepción no tiene la entrada del menú', !(await recepcion.$('a[href="/traslados"]')));
    await captura(recepcion, '06-recepcion-sin-acceso-1024');

    const otra = await entrar('enfermera@b.local', hospitalB);
    await consultar(otra, estancia);
    const ajeno = await texto(otra);
    check('miembro de otro hospital: rechazo y sin datos de la estancia', !ajeno.includes('Trasladar') && !ajeno.includes(libre), ajeno);
    await captura(otra, '07-otro-hospital-1024');
} catch (error) {
    resultados.push(false);
    console.log(`ERROR ${error.message}`);
} finally {
    await navegador.close();
}
console.log(`${resultados.filter(Boolean).length}/${resultados.length} comprobaciones`);
process.exit(resultados.every(Boolean) ? 0 : 1);
