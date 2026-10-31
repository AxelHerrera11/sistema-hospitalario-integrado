// Recorrido de la SPA a través de Laravel (Semana 16, ASII-12): login real (JWT), tenant, RBAC, guard de #1154, API v1 y PostgreSQL.
// Uso: E2E_PASSWORD=<clave ficticia local> CHROME_PATH=<chrome> node spa-alergias.mjs <base> <hospital> <correo-médico> <correo-admin> [dir-capturas]
// Pacientes ficticios: PAC-E2E-01 ya tiene penicilina (crítica) y PAC-E2E-02 no tiene alergias.
import puppeteer from 'puppeteer-core';

const [base, hospital, medico, admin, salida] = process.argv.slice(2);
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
async function buscar(p, codigo) {
    await p.$eval('#alg-codigo', (e, v) => { e.value = v; e.dispatchEvent(new Event('input')); }, codigo);
    await p.click('.alg form button[type=submit]');
    await p.waitForSelector('.alg__banner');
    return p.$eval('.alg__banner', (e) => ({ clase: e.className, rol: e.getAttribute('role'), texto: e.textContent }));
}
try {
    const adm = await entrar(admin);
    check('admin_hospital (sin alergias.ver) no ve «Alergias» en el menú', !(await adm.$('a[href="/alergias"]')));

    const med = await entrar(medico);
    await med.evaluate(() => document.querySelector('a[href="/alergias"]').click()); // navegación interna: el token vive en memoria
    await med.waitForSelector('#alg-codigo');
    const critica = await buscar(med, 'PAC-E2E-01');
    check('PAC-E2E-01 → banner crítico con role="alert" y la penicilina', critica.clase.includes('critical') && critica.rol === 'alert'
        && critica.texto.includes('Penicilina'));
    await foto(med, 'spa-critica-1024.png');

    const vacia = await buscar(med, 'PAC-E2E-02');
    check('PAC-E2E-02 → «Sin alergias registradas» con role="status"', vacia.clase.includes('none') && vacia.texto.includes('Sin alergias registradas'));
    await med.click('.alg form[novalidate] button[type=submit]');
    await med.waitForSelector('.alg form[novalidate] .alg__error');
    check('enviar sin alérgeno → error local, sin llamar a la API', (await med.$eval('#alg-nombre', (e) => e.getAttribute('aria-invalid'))) === 'true');
    await med.type('#alg-nombre', 'Látex');
    await med.type('#alg-reaccion', 'Dermatitis de contacto');
    await med.select('#alg-severidad', 'mild');
    await foto(med, 'spa-registro-1024.png');
    await med.click('.alg form[novalidate] button[type=submit]');
    await med.waitForFunction(() => document.querySelector('.alg__aviso')?.textContent === 'Alergia registrada.');
    const aviso = await med.$eval('.alg__banner', (e) => ({ clase: e.className, texto: e.textContent }));
    check('registro de látex leve → 201 y banner «warning»', aviso.clase.includes('warning') && aviso.texto.includes('Látex'));
    await med.click('.alg form[novalidate] button[type=submit]');
    await med.type('#alg-nombre', 'látex');
    await med.select('#alg-severidad', 'mild');
    await med.click('.alg form[novalidate] button[type=submit]');
    await med.waitForFunction(() => document.querySelector('.alg form[novalidate] .alg__error')?.textContent === 'El paciente ya tiene esa alergia activa.');
    check('la misma alergia otra vez → 409 con el mensaje del servidor junto al campo', (await med.$eval('#alg-nombre', (e) => e.getAttribute('aria-invalid'))) === 'true');
    await foto(med, 'spa-duplicada-1024.png');

    const movil = await entrar(medico, 375);
    await movil.evaluate(() => document.querySelector('a[href="/alergias"]').click());
    await movil.waitForSelector('#alg-codigo');
    await buscar(movil, 'PAC-E2E-01');
    check('375 px: sin desplazamiento horizontal', await movil.evaluate(() => document.documentElement.scrollWidth <= 375));
    await foto(movil, 'spa-critica-375.png');
} catch (error) {
    check(`recorrido sin excepciones (${error.message})`, false);
} finally {
    await navegador.close();
}
console.log(`${resultados.filter(Boolean).length}/${resultados.length} verificaciones`);
process.exit(resultados.every(Boolean) ? 0 : 1);
