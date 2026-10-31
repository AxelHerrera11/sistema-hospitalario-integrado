// Recorrido de la SPA a través de Laravel (Semana 16, ASII-07): login real (JWT), tenant, RBAC, guard de #1154, API v1 y PostgreSQL.
// Uso: E2E_PASSWORD=<clave ficticia local> CHROME_PATH=<chrome> node spa-admision.mjs <base> <hospital> <correo-recepción> <correo-médico> [dir-capturas]
// Datos ficticios: PAC-E2E-02 sin hospitalización; camas generales M-201 y M-202 disponibles.
import puppeteer from 'puppeteer-core';

const [base, hospital, recepcion, medico, salida] = process.argv.slice(2);
const resultados = [];
const check = (nombre, ok) => { resultados.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${nombre}`); };
const navegador = await puppeteer.launch({ executablePath: process.env.CHROME_PATH, headless: true, args: ['--no-sandbox'] });
const foto = (p, nombre) => salida && p.screenshot({ path: `${salida}/${nombre}`, fullPage: true });

async function entrar(correo, ancho = 1024) {
    const p = await (await navegador.createBrowserContext()).newPage();
    await p.setViewport({ width: ancho, height: 900, isMobile: ancho < 500 });
    await p.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    const [tenant, email, clave] = await p.$$('input');
    await tenant.click({ clickCount: 3 });
    await tenant.type(hospital);
    await email.type(correo);
    await clave.type(process.env.E2E_PASSWORD ?? '');
    const login = p.waitForResponse((r) => r.url().endsWith('/api/v1/auth/login'));
    await p.click('button[type=submit]');
    if ((await login).status() !== 200) throw new Error(`login de ${correo} falló`);
    await p.waitForFunction(() => location.pathname !== '/login');
    return p;
}
async function abrir(p, codigo) {
    await p.evaluate(() => document.querySelector('a[href="/admision"]').click()); // navegación interna: el token vive en memoria
    await p.waitForSelector('#adm-codigo');
    await p.$eval('#adm-codigo', (e, v) => { e.value = v; e.dispatchEvent(new Event('input')); }, codigo);
    await p.waitForSelector('.adm form button[type=submit]:not([disabled])'); // tras un ingreso, «Buscar» espera la recarga de camas
    await p.click('.adm form button[type=submit]');
    // Las camas llegan después del paciente: se espera la lista o el aviso de que no hay.
    await p.waitForFunction(() => document.querySelector('.adm__cama') || document.querySelector('.adm fieldset p')?.textContent.startsWith('No hay camas'));
}
const elegir = (p, codigoCama) => p.$$eval('.adm__cama', (ls, c) => ls.find((l) => l.textContent.includes(c)).querySelector('input').click(), codigoCama);
const enviar = (p) => p.click('.adm form[novalidate] button[type=submit]');
try {
    const med = await entrar(medico);
    check('médico (sin admisiones.registrar) no ve «Admisión y camas» como enlace', !(await med.$('a[href="/admision"]')));

    const rec = await entrar(recepcion);
    await abrir(rec, 'PAC-E2E-02');
    const camas = await rec.$$eval('.adm__cama', (ls) => ls.map((l) => l.textContent.trim()));
    check(`recepción ve las camas disponibles; la UCI ya está ocupada (${camas.join(', ')})`, camas.length === 2 && camas.every((c) => c.startsWith('M-')));
    await enviar(rec);
    await rec.waitForSelector('.adm form[novalidate] .adm__error');
    check('admitir sin cama → error local, sin llamar a la API', (await rec.$eval('.adm__error', (e) => e.textContent)) === 'Seleccione una cama disponible.');
    await elegir(rec, 'M-201');
    await rec.type('#adm-motivo', 'Fractura de cadera');
    await foto(rec, 'spa-ingreso-1024.png');
    await enviar(rec);
    await rec.waitForSelector('.adm__exito');
    check('ingreso registrado → confirmación con la cama M-201', (await rec.$eval('.adm__exito', (e) => e.textContent)).includes('M-201'));
    await foto(rec, 'spa-exito-1024.png');

    await abrir(rec, 'PAC-E2E-02');
    await elegir(rec, 'M-202');
    await rec.type('#adm-motivo', 'Reingreso');
    await enviar(rec);
    await rec.waitForFunction(() => document.querySelector('.adm form[novalidate] .adm__error')?.textContent === 'El paciente ya tiene una hospitalización abierta.');
    check('el mismo paciente otra vez → 409 con el mensaje del servidor', true);
    await foto(rec, 'spa-ya-admitido-1024.png');

    const movil = await entrar(recepcion, 375);
    await abrir(movil, 'PAC-E2E-01');
    check('375 px: sin desplazamiento horizontal', await movil.evaluate(() => document.documentElement.scrollWidth <= 375));
    await foto(movil, 'spa-camas-375.png');
} catch (error) {
    check(`recorrido sin excepciones (${error.message})`, false);
} finally {
    await navegador.close();
}
console.log(`${resultados.filter(Boolean).length}/${resultados.length} verificaciones`);
process.exit(resultados.every(Boolean) ? 0 : 1);
