// Recorrido de la SPA a través de Laravel (Semana 16): login real (JWT), tenant, RBAC, API v1 y PostgreSQL.
// Uso: E2E_PASSWORD=<clave ficticia local> node spa-traslados.mjs <base> <hospital> <estancia> <cama-libre> <pendiente|activada> [dir-capturas]
import puppeteer from 'puppeteer-core';

const [base, hospital, estancia, cama, fase, salida] = process.argv.slice(2);
const resultados = [];
const check = (nombre, ok) => { resultados.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${nombre}`); };
const navegador = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });

async function entrar(correo, ancho = 1024) {
    const p = await (await navegador.createBrowserContext()).newPage();
    await p.setViewport({ width: ancho, height: 900, isMobile: ancho < 500 });
    await p.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    const campos = await p.$$('input');
    await campos[0].click({ clickCount: 3 });
    await campos[0].type(hospital);
    await campos[1].type(correo);
    await campos[2].type(process.env.E2E_PASSWORD ?? '');
    const login = p.waitForResponse((r) => r.url().endsWith('/api/v1/auth/login'));
    await p.click('button[type=submit]');
    if ((await login).status() !== 200) throw new Error(`login de ${correo} falló`);
    await p.waitForFunction(() => location.pathname !== '/login');
    await p.evaluate(() => document.querySelector('a[href="/traslados"]').click()); // navegación interna: el token vive en memoria
    await p.waitForSelector('.ta');
    return p;
}
const texto = (p, sel) => p.$eval(sel, (e) => e.textContent);
const resultado = (p) => p.waitForFunction(() => { const t = document.querySelector('.ta [role=status]').textContent;
    return t && !['Consultando…', 'Procesando…', 'Situación actualizada.'].includes(t); }).then(() => texto(p, '.ta [role=status]'));
async function consultar(p, id) {
    await p.$eval('#ta-estancia', (e, v) => { e.value = v; e.dispatchEvent(new Event('input')); }, id);
    await p.click('.ta form button[type=submit]');
    await p.waitForFunction(() => ['Situación actualizada.', 'La estancia no existe.'].includes(document.querySelector('.ta [role=status]').textContent)
        || document.querySelector('.ta__msg--reintentar, .ta__msg--rechazo'));
}
try {
    const enf = await entrar('enfermera@prueba.local');
    if (fase === 'pendiente') {
        check('permiso pending: la página avisa que falta traslados.ver y no muestra el formulario', (await texto(enf, '.ta')).includes('traslados.ver')
            && !(await enf.$('#ta-estancia')));
        salida && await enf.screenshot({ path: `${salida}/spa-pendiente-1024.png`, fullPage: true });
    } else {
        await consultar(enf, estancia);
        check('enfermería ve «Trasladar» y no «Dar de alta» (roles propuestos)', (await texto(enf, '.ta__acciones')).includes('Trasladar')
            && !(await texto(enf, '.ta__acciones')).includes('Dar de alta'));
        await enf.click('.ta__acciones button');
        await enf.type('#ta-cama', cama);
        await enf.type('#ta-motivo', 'Requiere aislamiento');
        await enf.click('.ta form[novalidate] button[type=submit]');
        await enf.waitForFunction(() => document.activeElement?.textContent === 'Confirmar');
        salida && await enf.screenshot({ path: `${salida}/spa-confirmacion-1024.png`, fullPage: true });
        await enf.click('section[aria-labelledby="ta-confirmar"] button');
        const exito = await resultado(enf);
        check('traslado desde la SPA → confirmado con la nueva estancia', exito.includes('Traslado confirmado') && exito.includes('Nueva estancia'));
        salida && await enf.screenshot({ path: `${salida}/spa-exito-1024.png`, fullPage: true });
        const nueva = await enf.$eval('#ta-estancia', (e) => e.value);
        const med = await entrar('medico@prueba.local', 375);
        await consultar(med, nueva);
        await med.click('.ta__acciones button');
        await med.click('.ta form[novalidate] button[type=submit]');
        await med.waitForFunction(() => document.activeElement?.textContent === 'Confirmar');
        await med.click('section[aria-labelledby="ta-confirmar"] button');
        check('alta desde la SPA a 375 px por el médico → confirmada', (await resultado(med)).includes('Alta confirmada'));
        salida && await med.screenshot({ path: `${salida}/spa-alta-375.png`, fullPage: true });
        await consultar(med, nueva);
        check('tras el alta, episodio cerrado y sin acciones', (await texto(med, '.ta__datos')).includes('cerrado') && !(await med.$('.ta__acciones')));
        check('sin desplazamiento horizontal a 375 px', await med.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth));
        console.log(`estancia destino: ${nueva}`);
    }
} catch (error) {
    console.log(`ERROR ${error.message}`);
    resultados.push(false);
} finally {
    await navegador.close();
}
const fallas = resultados.filter((ok) => !ok).length;
console.log(`${resultados.length - fallas}/${resultados.length} comprobaciones`);
process.exit(fallas ? 1 : 0);
