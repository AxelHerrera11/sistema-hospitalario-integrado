// Recorrido real de la interfaz del catálogo: Chrome → Vue → API Laravel (tenant, JWT, RBAC) → PostgreSQL.
// Uso: node catalogo-e2e.mjs <url-base> <hospital-uuid> (puppeteer-core 23.11.1 instalado aparte; ver instalacion-local.md).
import puppeteer from 'puppeteer-core';

const [base, hospital] = process.argv.slice(2);
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const results = [];
const check = (name, ok, detail = '') => { results.push({ name, ok }); console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? ' — ' + detail : ''}`); };

async function login(page, email) {
    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    const inputs = await page.$$('input');
    await inputs[0].click({ clickCount: 3 }); await inputs[0].type(hospital);
    await inputs[1].type(email);
    await inputs[2].type('Smoke-Prueba-2026!');
    const answer = page.waitForResponse((r) => r.url().endsWith('/api/v1/auth/login'));
    await page.click('button[type=submit]');
    const status = (await answer).status();
    if (status !== 200) throw new Error(`login de ${email} respondió ${status}`);
    console.log('  login '+email+': '+status);
    await page.waitForFunction(() => location.pathname !== '/login');
    // Navegación interna de la SPA: conserva el token en memoria (no se recarga la página).
    await page.evaluate(() => { document.querySelector('a[href="/medicamentos"]').click(); });
    await page.waitForSelector('.meds');
    await new Promise((r) => setTimeout(r, 300));
}
const text = (page) => page.$eval('.meds', (el) => el.innerText);
async function fillRegister(page, values) {
    for (const [key, value] of Object.entries(values)) {
        const input = await page.$(`#meds-${key}`);
        await input.click({ clickCount: 3 }); await input.press('Backspace'); await input.type(value);
    }
}
async function submitRegister(page) {
    const response = page.waitForResponse((r) => r.url().endsWith('/api/v1/medications') && r.request().method() === 'POST');
    await page.click('form:not([role=search]) button[type=submit]');
    return (await response).status();
}

const browser = await puppeteer.launch({ executablePath: chrome, headless: true, args: ['--no-sandbox'] });
try {
    // 1. Administración del hospital: alta, duplicado, validación, búsqueda con tildes y vigencia.
    const admin = await (await browser.createBrowserContext()).newPage();
    await admin.setViewport({ width: 1280, height: 900 });
    await login(admin, 'admin@prueba.local');
    check('admin_hospital ve el formulario de alta', Boolean(await admin.$('#meds-code')));
    await fillRegister(admin, { code: 'asa-100', name: 'Ácido acetilsalicílico 100 mg', active_ingredient: 'Ácido acetilsalicílico', pharmacological_group: 'Analgésico' });
    const created = await submitRegister(admin);
    await admin.waitForFunction(() => document.querySelector('.meds').innerText.includes('Registrado ASA-100'));
    check('alta desde la interfaz → 201 y mensaje de éxito', created === 201);
    await fillRegister(admin, { code: 'ASA-100', name: 'Duplicado', active_ingredient: 'Ácido acetilsalicílico', pharmacological_group: 'Analgésico' });
    const duplicate = await submitRegister(admin);
    await admin.waitForSelector('#meds-code-err');
    check('duplicado → 409 y error junto al código, sin éxito', duplicate === 409 && !(await text(admin)).includes('Registrado ASA-100 (Vigente). Registrado'));
    await fillRegister(admin, { code: 'x', name: 'A', active_ingredient: 'Ab', pharmacological_group: 'Cd' });
    await admin.click('form:not([role=search]) button[type=submit]');
    check('validación local antes de enviar (código y nombre)', Boolean(await admin.$('#meds-code-err')) && Boolean(await admin.$('#meds-name-err')));
    const search = async (term) => {
        const input = await admin.$('#meds-q'); await input.click({ clickCount: 3 }); await input.type(term);
        const r = admin.waitForResponse((x) => x.url().includes('/api/v1/medications?'));
        await admin.click('form[role=search] button[type=submit]');
        await r; await admin.waitForFunction(() => !document.querySelector('form[role=search] button').disabled);
        return admin.$$eval('.meds__item', (items) => items.map((i) => i.innerText));
    };
    check('búsqueda "ÁCIDO" encuentra el registro', (await search('ÁCIDO')).some((t) => t.includes('ASA-100')));
    check('búsqueda sin coincidencias muestra estado vacío', (await search('zzz')).length === 0 && (await text(admin)).includes('No hay medicamentos'));
    await search('asa');
    await admin.click('.meds__pick');
    await admin.waitForSelector('.meds__detail');
    const patch = admin.waitForResponse((r) => r.url().endsWith('/validity'));
    await admin.click('article button');
    check('cambio de vigencia → 200', (await patch).status() === 200);
    await admin.waitForFunction(() => document.querySelector('article').innerText.includes('No vigente'));
    // Salir de la pantalla y volver: el estado local se pierde y la vigencia se lee otra vez del servidor.
    await admin.evaluate(() => { document.querySelector('a[href="/"]').click(); });
    await admin.waitForFunction(() => !document.querySelector('.meds'));
    await admin.evaluate(() => { document.querySelector('a[href="/medicamentos"]').click(); });
    await admin.waitForSelector('#meds-q');
    check('al volver a la pantalla, la vigencia leída del servidor es "No vigente"', (await search('asa')).some((t) => t.includes('No vigente')));

    // 2. Enfermería: solo lectura (sin formulario ni botón de vigencia); el servidor también lo impone.
    const nurse = await (await browser.createBrowserContext()).newPage();
    await nurse.setViewport({ width: 375, height: 800, isMobile: true });
    await login(nurse, 'enfermera@prueba.local');
    check('enfermería no ve alta ni cambio de vigencia', !(await nurse.$('#meds-code')));
    const overflow = await nurse.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    check('en 375 px no hay desplazamiento horizontal', !overflow);

    // 3. Recepción: sin medicamentos.ver.
    const reception = await (await browser.createBrowserContext()).newPage();
    await login(reception, 'recepcion@prueba.local');
    check('recepción ve el aviso de permiso y no la búsqueda', (await text(reception)).includes('medicamentos.ver') && !(await reception.$('#meds-q')));
} catch (error) {
    const pages = await browser.pages();
    await pages[pages.length - 1].screenshot({ path: 'fallo.png' });
    console.log('ERROR ' + error.message);
    results.push({ ok: false });
} finally {
    await browser.close();
}
const failed = results.filter((r) => !r.ok).length;
console.log(`${results.length - failed}/${results.length} comprobaciones`);
process.exit(failed ? 1 : 0);
