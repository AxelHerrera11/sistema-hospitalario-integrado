// Recorrido de la SPA a través de Laravel (Semana 16, ASII-21): login real (JWT), tenant, RBAC, guard de #1154, API v1 y PostgreSQL.
// Uso: E2E_PASSWORD=<clave> CHROME_PATH=<chrome> node spa-alertas.mjs <base> <hospital> <bioquímico> <médico> <jefatura> <prueba> <pac-1> <epi-1> <pac-2> <epi-2> [dir-capturas]
// Antes de la SPA, el bioquímico publica por la API un resultado crítico nuevo (glucosa 450) para el paciente 1, que tiene médica tratante.
import puppeteer from 'puppeteer-core';

const [base, hospital, bioquimico, medico, jefatura, prueba, p1, e1, , , salida] = process.argv.slice(2);
const resultados = [];
const check = (nombre, ok) => { resultados.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${nombre}`); };
const foto = (p, nombre) => salida && p.screenshot({ path: `${salida}/${nombre}`, fullPage: true });

async function token(correo) {
    const r = await fetch(`${base}/api/v1/auth/login`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Tenant-ID': hospital },
        body: JSON.stringify({ email: correo, password: process.env.E2E_PASSWORD }) });
    const j = await r.json();
    return j.access_token ?? j.data?.access_token;
}
const publicado = await fetch(`${base}/api/v1/critical-alerts/result-validated`, { method: 'POST', headers: { 'Content-Type': 'application/json',
    'X-Tenant-ID': hospital, Authorization: `Bearer ${await token(bioquimico)}` }, body: JSON.stringify({ result_version_id: 'e2e00000-0000-4000-8000-0000000000e5',
    lab_test_id: prueba, patient_id: p1, episode_id: e1, value: 450 }) });
check('el bioquímico publica glucosa 450 por la API → 201', publicado.status === 201);

const navegador = await puppeteer.launch({ executablePath: process.env.CHROME_PATH, headless: true, args: ['--no-sandbox'] });
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
const cargada = (p) => p.waitForFunction(() => document.querySelector('.alr__item') || document.querySelector('.alr [role=status]')?.textContent.startsWith('No hay'));
async function abrir(p) {
    await p.evaluate(() => document.querySelector('a[href="/alertas"]').click()); // navegación interna: el token vive en memoria
    await p.waitForSelector('.alr');
    await cargada(p);
}
const error = (p, texto) => p.waitForFunction((t) => document.querySelector('.alr__acuse .alr__error')?.textContent.startsWith(t), {}, texto);
try {
    const bio = await entrar(bioquimico);
    check('bioquímico (sin alertas.ver) no ve «Alertas críticas»', !(await bio.$('a[href="/alertas"]')));

    const med = await entrar(medico);
    await abrir(med);
    const items = await med.$$eval('.alr__item', (li) => li.map((e) => ({ texto: e.textContent, rol: e.getAttribute('role') })));
    check('la médica ve solo su alerta pendiente (450, sobre el umbral) con role="alert"', items.length === 1 && items[0].texto.includes('450')
        && items[0].texto.includes('sobre el umbral') && items[0].rol === 'alert');
    await foto(med, 'spa-bandeja-1024.png');
    await med.click('.alr__item button');
    await med.click('.alr__acuse button[type=submit]');
    await error(med, 'Confirme paciente y prueba');
    check('acusar sin confirmar ni escribir el valor → error local, sin llamar a la API', true);
    await med.click('.alr__acuse input[type=checkbox]');
    await med.type('.alr__acuse input[inputmode]', '45');
    await med.click('.alr__acuse button[type=submit]');
    await error(med, 'El valor leído no coincide');
    check('lectura 45 en lugar de 450 → 422 READ_BACK_MISMATCH con mensaje seguro', true);
    await foto(med, 'spa-lectura-incorrecta-1024.png');
    await med.$eval('.alr__acuse input[inputmode]', (e) => { e.value = ''; e.dispatchEvent(new Event('input')); });
    await med.type('.alr__acuse input[inputmode]', '450');
    await med.click('.alr__acuse button[type=submit]');
    await med.waitForFunction(() => document.querySelector('.alr__aviso')?.textContent.startsWith('Alerta acusada'));
    check('lectura correcta → acusada y la bandeja queda vacía', (await med.$$('.alr__item')).length === 0);
    await foto(med, 'spa-acusada-1024.png');

    const jef = await entrar(jefatura);
    await abrir(jef);
    await jef.select('.alr__barra select', 'jefatura');
    await jef.waitForFunction(() => document.querySelector('.alr__item')?.textContent.includes('500'));
    check('la jefatura médica ve en la bandeja del servicio la alerta sin médico tratante (500, nivel 1)',
        (await jef.$eval('.alr__item', (e) => e.textContent)).includes('nivel 1'));
    await foto(jef, 'spa-jefatura-1024.png');
    await jef.click('.alr__item button');
    await jef.click('.alr__acuse input[type=checkbox]');
    await jef.type('.alr__acuse input[inputmode]', '500');
    await jef.click('.alr__acuse button[type=submit]');
    await jef.waitForFunction(() => document.querySelector('.alr__aviso')?.textContent.startsWith('Alerta acusada'));
    check('la jefatura acusa la alerta del servicio (ruta con alertas.supervisar) y su bandeja queda vacía', (await jef.$$('.alr__item')).length === 0);

    const movil = await entrar(medico, 375);
    await abrir(movil);
    check('375 px: sin desplazamiento horizontal', await movil.evaluate(() => document.documentElement.scrollWidth <= 375));
    await foto(movil, 'spa-bandeja-vacia-375.png');
} catch (e) {
    check(`recorrido sin excepciones (${e.message})`, false);
} finally {
    await navegador.close();
}
console.log(`${resultados.filter(Boolean).length}/${resultados.length} verificaciones`);
process.exit(resultados.every(Boolean) ? 0 : 1);
