// La base HOSPITAL no responde: la pantalla no debe anunciar éxito y debe ofrecer reintentar.
// Uso: node fallo-e2e.mjs <url-base> <hospital-uuid> con un servidor cuya conexión HOSPITAL apunta a una base inexistente.
import puppeteer from 'puppeteer-core';
const [base, hospital] = process.argv.slice(2);
const b = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
let ok = 0, total = 0; const check = (n, v) => { total++; if (v) ok++; console.log(`${v ? 'PASS' : 'FAIL'}  ${n}`); };
try {
    const p = await (await b.createBrowserContext()).newPage();
    await p.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    const i = await p.$$('input'); await i[0].click({ clickCount: 3 }); await i[0].type(hospital); await i[1].type('admin@prueba.local'); await i[2].type('Smoke-Prueba-2026!');
    const l = p.waitForResponse((r) => r.url().endsWith('/auth/login')); await p.click('button[type=submit]'); await l;
    await p.waitForFunction(() => location.pathname !== '/login');
    await p.evaluate(() => document.querySelector('a[href="/medicamentos"]').click()); await p.waitForSelector('#meds-code');
    for (const [k, v] of Object.entries({ code: 'ibu-400', name: 'Ibuprofeno 400 mg', active_ingredient: 'Ibuprofeno', pharmacological_group: 'AINE' })) await p.type(`#meds-${k}`, v);
    const r = p.waitForResponse((x) => x.url().endsWith('/api/v1/medications') && x.request().method() === 'POST');
    await p.click('form:not([role=search]) button[type=submit]');
    const status = (await r).status();
    await p.waitForFunction(() => !document.querySelector('form:not([role=search]) button[type=submit]').disabled);
    const text = await p.$eval('.meds', (e) => e.innerText);
    check(`alta con HOSPITAL caída → ${status}`, status === 503);
    check('la pantalla no anuncia éxito', !text.includes('Registrado'));
    check('mensaje de fallo recuperable con «Reintentar»', text.includes('No se confirmó la operación') && text.includes('Reintentar'));
    check('los datos del formulario se conservan para reintentar', (await p.$eval('#meds-code', (e) => e.value)) === 'ibu-400');
} finally { await b.close(); }
console.log(`${ok}/${total} comprobaciones`); process.exit(ok === total ? 0 : 1);
