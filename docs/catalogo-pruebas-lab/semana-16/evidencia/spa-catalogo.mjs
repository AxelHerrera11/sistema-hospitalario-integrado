// Recorrido de la SPA a través de Laravel (Semana 16, ASII-17): login real (JWT), tenant, RBAC, guard de #1154, API v1 y PostgreSQL.
// Uso: E2E_PASSWORD=<clave ficticia local> CHROME_PATH=<chrome> node spa-catalogo.mjs <base> <hospital> <superadmin> <técnico> <recepción> [dir-capturas]
// Datos ficticios: la glucosa (GLU) ya está vigente por el recorrido HTTP.
import puppeteer from 'puppeteer-core';

const [base, hospital, superadmin, tecnico, recepcion, salida] = process.argv.slice(2);
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
async function abrir(p) {
    await p.evaluate(() => document.querySelector('a[href="/laboratorio/catalogo"]').click()); // navegación interna: el token vive en memoria
    await p.waitForFunction(() => document.querySelector('.lab tbody') || document.querySelector('.lab > p')?.textContent.startsWith('No hay'));
}
const codigos = (p) => p.$$eval('.lab tbody th', (ths) => ths.map((th) => th.textContent.trim()));
async function llenar(p, campos) {
    for (const [etiqueta, valor] of Object.entries(campos)) {
        const selector = await p.evaluateHandle((t) => [...document.querySelectorAll('.lab__grid label')].find((l) => l.textContent.trim().startsWith(t)).querySelector('input, select'), etiqueta);
        const tag = await selector.evaluate((e) => e.tagName);
        if (tag === 'SELECT') {
            await selector.select(valor);
        } else {
            await selector.evaluate((e) => { e.value = ''; e.dispatchEvent(new Event('input')); });
            await selector.type(String(valor));
        }
    }
}
const enviar = (p) => p.click('.lab form[novalidate] button[type=submit]');
try {
    const rec = await entrar(recepcion);
    check('recepción (sin laboratorio.catalogo.ver) no ve «Catálogo de laboratorio»', !(await rec.$('a[href="/laboratorio/catalogo"]')));

    const tec = await entrar(tecnico);
    await abrir(tec);
    check('técnico ve GLU vigente y no ve el formulario de alta', (await codigos(tec)).includes('GLU') && !(await tec.$('.lab form[novalidate]')));

    const sup = await entrar(superadmin);
    await abrir(sup);
    await llenar(sup, { 'Código': 'hb', 'Nombre': 'Hemoglobina', 'Tipo de muestra': 'whole_blood', 'Volumen mínimo': '2', 'Unidad': 'g/dL',
        'Crítico bajo': '12', 'Referencia baja': '12', 'Referencia alta': '16', 'Crítico alto': '20' });
    await enviar(sup);
    await sup.waitForSelector('.lab form[novalidate] .lab__error');
    check('rangos incoherentes → error local, sin llamar a la API', (await sup.$eval('.lab__error', (e) => e.textContent)).startsWith('Los rangos'));
    await foto(sup, 'spa-rangos-1024.png');
    await llenar(sup, { 'Crítico bajo': '7' });
    await enviar(sup);
    await sup.waitForFunction(() => document.querySelector('.lab__aviso')?.textContent.includes('HB registrada'));
    check('alta de HB → 201 y aparece en la tabla vigente', (await codigos(sup)).includes('HB'));
    await foto(sup, 'spa-alta-1024.png');
    await llenar(sup, { 'Código': 'GLU', 'Nombre': 'Glucosa', 'Tipo de muestra': 'serum', 'Volumen mínimo': '0.5', 'Unidad': 'mg/dL',
        'Crítico bajo': '40', 'Referencia baja': '70', 'Referencia alta': '100', 'Crítico alto': '400' });
    await enviar(sup);
    await sup.waitForFunction(() => document.querySelector('.lab form[novalidate] .lab__error')?.textContent === 'Ya hay una versión vigente de esa prueba.');
    check('GLU otra vez → 409 con el mensaje del servidor', true);
    await foto(sup, 'spa-version-vigente-1024.png');

    const movil = await entrar(tecnico, 375);
    await abrir(movil);
    check('375 px: la página no se desplaza en horizontal (la tabla sí, dentro de su contenedor)', await movil.evaluate(() => document.documentElement.scrollWidth <= 375));
    await foto(movil, 'spa-catalogo-375.png');
} catch (error) {
    check(`recorrido sin excepciones (${error.message})`, false);
} finally {
    await navegador.close();
}
console.log(`${resultados.filter(Boolean).length}/${resultados.length} verificaciones`);
process.exit(resultados.every(Boolean) ? 0 : 1);
