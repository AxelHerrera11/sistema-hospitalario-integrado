// Recorrido de la pantalla en Chrome: Vue -> API Laravel (tenant, JWT, RBAC) -> PostgreSQL con datos ficticios.
// Uso: E2E_PASSWORD=... E2E_DOMINIO=a.local [E2E_CAPTURAS=dir] node recorrido-e2e.mjs <url-base> <hospital-a> <hospital-b>
// El caso 503 se simula interceptando la respuesta; la caída real de la base la cubre IndicatorHospitalIntegrationTest.
import puppeteer from 'puppeteer-core';

const [base, hospitalA, hospitalB] = process.argv.slice(2);
const { E2E_PASSWORD: password, E2E_DOMINIO: dominio = 'a.local', E2E_CAPTURAS: capturas } = process.env;
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const results = [];
const check = (name, ok, detail = '') => { results.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? ` — ${detail}` : ''}`); };
const captura = async (page, nombre) => { if (capturas) await page.screenshot({ path: `${capturas}/${nombre}.png`, fullPage: true }); };

async function entrar(browser, correo, hospital, ancho = 1280) {
    const page = await (await browser.createBrowserContext()).newPage();
    await page.setViewport({ width: ancho, height: 800, isMobile: ancho < 640 });
    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    const inputs = await page.$$('input');
    await inputs[0].click({ clickCount: 3 }); await inputs[0].type(hospital);
    await inputs[1].type(correo);
    await inputs[2].type(password);
    await page.click('button[type=submit]');
    await page.waitForFunction(() => location.pathname !== '/login');
    return page;
}
const abrir = async (page) => { await page.evaluate(() => document.querySelector('a[href="/reportes"]').click()); await page.waitForSelector('.reports'); };
async function fijar(page, desde, hasta, sala = '') {
    await page.evaluate((d, h, s) => {
        for (const [id, valor] of [['rp-desde', d], ['rp-hasta', h], ['rp-servicio', s]]) {
            const input = document.getElementById(id);
            input.value = valor;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }, desde, hasta, sala);
}
let llamadas = 0;
const contar = (page) => page.on('request', (r) => { if (r.url().includes('/IND-RA-01/')) llamadas++; });
async function calcular(page) {
    const antes = llamadas;
    await page.click('.reports__form button');
    await page.waitForFunction(() => !document.querySelector('.reports__form button').disabled);
    await new Promise((r) => setTimeout(r, 300));
    return llamadas - antes;
}
const texto = (page) => page.$eval('.reports', (el) => el.innerText);

const browser = await puppeteer.launch({ executablePath: chrome, headless: true, args: ['--no-sandbox'] });
try {
    const admin = await entrar(browser, `admin@${dominio}`, hospitalA);
    contar(admin);
    await abrir(admin);
    check('al cargar no hay mensajes de error', !(await admin.$('.reports__error')));
    check('la ayuda explica que «Hasta» no se incluye', (await texto(admin)).includes('no se incluye'));
    const estilo = await admin.$eval('#rp-desde', (e) => { const c = getComputedStyle(e); return { borde: parseFloat(c.borderTopWidth), alto: e.getBoundingClientRect().height }; });
    check('los campos se ven como campos (borde y 44 px)', estilo.borde >= 1 && estilo.alto >= 44, JSON.stringify(estilo));
    check('el botón se distingue del texto', (await admin.$eval('.reports__form button', (b) => getComputedStyle(b).backgroundColor)) !== 'rgba(0, 0, 0, 0)');
    await captura(admin, '01-formulario-1280');

    check('formulario vacío: mensaje y sin llamada', (await calcular(admin)) === 0 && (await texto(admin)).includes('Indica desde y hasta.'));
    await fijar(admin, '2026-01-01', '2026-02-01');
    check('al corregir el filtro desaparece el error', !(await admin.$('.reports__error')));
    await calcular(admin);
    check('enero: 25.00 %', (await texto(admin)).includes('25.00 %'));
    await captura(admin, '02-resultado-25-1280');
    await fijar(admin, '2026-01-01', '2026-02-01', 'HOSP-UCI');
    check('cambiar un filtro retira el resultado anterior', !(await texto(admin)).includes('25.00 %'));
    await calcular(admin);
    check('sala HOSP-UCI: 33.33 %', (await texto(admin)).includes('33.33 %'));
    await fijar(admin, '2026-02-01', '2026-01-01');
    const invertido = await calcular(admin);
    const trasInvertir = await texto(admin);
    check('periodo invertido: mensaje, sin llamada y sin cifra anterior', invertido === 0 && trasInvertir.includes('Desde debe ser anterior a hasta.') && !trasInvertir.includes('33.33'));
    await captura(admin, '03-periodo-invertido-1280');
    await fijar(admin, '2019-01-01', '2019-02-01');
    await calcular(admin);
    check('periodo sin datos: N/A con leyenda', (await texto(admin)).includes('N/A') && (await texto(admin)).includes('No hubo hospitalizaciones'));
    await captura(admin, '04-sin-datos-1280');

    await admin.setRequestInterception(true);
    admin.on('request', (r) => (r.url().includes('/IND-RA-01/')
        ? r.respond({ status: 503, contentType: 'application/json', body: JSON.stringify({ error: { code: 'REPORTES_UNAVAILABLE', message: 'x' } }) })
        : r.continue()));
    await fijar(admin, '2026-01-01', '2026-02-01');
    await calcular(admin);
    const caida = await texto(admin);
    check('503 simulado: aviso y ninguna cifra', caida.includes('no está disponible') && !caida.includes('%'));
    await captura(admin, '05-error-503-1280');

    const movil = await entrar(browser, `admin@${dominio}`, hospitalA, 375);
    await abrir(movil);
    await fijar(movil, '2026-01-01', '2026-02-01');
    await calcular(movil);
    const desborde = await movil.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    check('375 px: resultado sin desplazamiento horizontal', (await texto(movil)).includes('25.00 %') && !desborde);
    await captura(movil, '06-resultado-375');
    await fijar(movil, '2026-02-01', '2026-01-01');
    await calcular(movil);
    await captura(movil, '07-periodo-invertido-375');

    const b = await entrar(browser, `admin@b.local`, hospitalB);
    await abrir(b);
    await fijar(b, '2026-01-01', '2026-02-01');
    await calcular(b);
    const cruce = await texto(b);
    check('miembro de otro hospital: aviso y ninguna cifra', cruce.includes('no está disponible en esta instalación') && !cruce.includes('%'));
    await captura(b, '08-otro-hospital-1280');

    const recepcion = await entrar(browser, `recepcion@${dominio}`, hospitalA);
    check('recepción no tiene el enlace de Reportes', !(await recepcion.$('a[href="/reportes"]')));
} catch (error) {
    results.push(false);
    console.log(`ERROR ${error.message}`);
} finally {
    await browser.close();
}
console.log(`${results.filter(Boolean).length}/${results.length} comprobaciones`);
process.exit(results.every(Boolean) ? 0 : 1);
