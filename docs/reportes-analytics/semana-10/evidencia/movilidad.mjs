// Mide la pantalla real en anchos móviles y guarda capturas.
// Uso: E2E_PASSWORD=... E2E_DOMINIO=a.local node movilidad.mjs <url-base> <hospital> <carpeta-capturas>
import puppeteer from 'puppeteer-core';
const [base, hospital, salida] = process.argv.slice(2);
const { E2E_PASSWORD: password, E2E_DOMINIO: dominio = 'a.local' } = process.env;
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });
let ok = true;
const check = (n, c) => { ok &&= c; console.log(`${c ? 'PASS' : 'FAIL'}  ${n}`); };
for (const ancho of [320, 375, 430, 768]) {
    const page = await (await browser.createBrowserContext()).newPage();
    await page.setViewport({ width: ancho, height: 800, isMobile: ancho < 640, hasTouch: ancho < 640 });
    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    const i = await page.$$('input');
    await i[0].click({ clickCount: 3 }); await i[0].type(hospital); await i[1].type(`admin@${dominio}`); await i[2].type(password);
    await page.click('button[type=submit]');
    await page.waitForFunction(() => location.pathname !== '/login');
    await page.evaluate(() => document.querySelector('a[href="/reportes"]').click());
    await page.waitForSelector('.reports');
    const medir = () => page.evaluate(() => ({ desborde: document.documentElement.scrollWidth > window.innerWidth, alto: Math.min(...[...document.querySelectorAll('.reports input, .reports button')].map((e) => e.getBoundingClientRect().height)), columnas: getComputedStyle(document.querySelector('.reports__form')).gridTemplateColumns.split(' ').length }));
    const fijar = (d, h) => page.evaluate((a, b) => { for (const [id, v] of [['rp-desde', a], ['rp-hasta', b]]) { const e = document.getElementById(id); e.value = v; e.dispatchEvent(new Event('input', { bubbles: true })); } }, d, h);
    const calcular = async () => { await page.click('.reports__form button'); await new Promise((r) => setTimeout(r, 100)); await page.waitForFunction(() => !document.querySelector('.reports__form button').disabled); await new Promise((r) => setTimeout(r, 300)); };
    const m = await medir();
    check(`${ancho} px: sin desborde, controles de 44 px o más, ${m.columnas} columna(s)`, !m.desborde && m.alto >= 44 && m.columnas === (ancho <= 640 ? 1 : 3));
    await page.screenshot({ path: `${salida}/formulario-${ancho}.png`, fullPage: true });
    await fijar('2026-01-01', '2026-02-01'); await calcular();
    check(`${ancho} px: resultado visible sin desborde`, (await page.$eval('.reports', (e) => e.innerText)).includes('25.00 %') && !(await medir()).desborde);
    await page.screenshot({ path: `${salida}/resultado-${ancho}.png`, fullPage: true });
    await fijar('2026-02-01', '2026-01-01'); await calcular();
    check(`${ancho} px: error visible sin desborde`, Boolean(await page.$('.reports__error')) && !(await medir()).desborde);
    if (ancho === 320) await page.screenshot({ path: `${salida}/error-${ancho}.png`, fullPage: true });
    if (ancho < 640) { await page.setViewport({ width: 800, height: ancho, isMobile: true, hasTouch: true }); await new Promise((r) => setTimeout(r, 300)); check(`${ancho} px girado: filtros conservados`, (await page.$eval('#rp-hasta', (e) => e.value)) === '2026-01-01'); }
}
await browser.close();
process.exit(ok ? 0 : 1);
