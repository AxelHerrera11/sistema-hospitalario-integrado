// UI component tests with explicit HTTP doubles. Real JWT/PostgreSQL tests are in tests/Feature/Reception.
const { chromium } = require('playwright');
const AxeBuilder = require('@axe-core/playwright').default;
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const output = path.resolve('docs/recepcion-muestras/evidencias');
fs.mkdirSync(output, { recursive: true });

(async () => {
    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    const context = await browser.newContext({ timezoneId: 'America/Guatemala' });
    const page = await context.newPage();
    const results = [];
    const requests = [];
    let interrupt = false;
    let failReadOnce = false;
    const code = 'AAAAAAAAAAAAAAAAAAAAAAAA-6';
    const sample = { id: 'demo', item_id: 'I1', status: 'accepted', code, received_at: '2026-10-20T10:01:00-06:00' };
    await page.route('**/api/v1/lab/specimens**', async route => {
        if (route.request().method() === 'POST') {
            const body = route.request().postDataJSON(); requests.push(body);
            if (interrupt) { interrupt = false; await route.abort(); return; }
            const data = body.hemolizada ? { ...sample, status: 'rejected', reason: 'hemolizada', code: null } : sample;
            await route.fulfill({ status: 201, json: { data } });
        } else {
            if (failReadOnce) { failReadOnce = false; await route.abort(); return; }
            const data = route.request().url().includes('pending-orders') ? [{ item_id: 'I1', patient_code: 'P001',
                patient_name: 'Ana Demo', type: 'blood', container: 'EDTA', min_volume: 3, max_minutes: 60,
                min_temperature: 15, max_temperature: 25 }] : sample;
            await route.fulfill({ status: 200, json: { data } });
        }
    });
    await page.goto(process.env.RECEPTION_UI_URL || 'http://127.0.0.1:5179/tests/Browser/Reception/index.html');
    await page.getByRole('heading', { name: 'Recepción de muestras' }).waitFor();
    for (const [width, height] of [[320, 568], [390, 844], [768, 1024], [1440, 900]]) {
        await page.setViewportSize({ width, height });
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Sin overflow '+width);
        results.push('PASS responsive sin scroll horizontal '+width+'x'+height);
    }
    await page.screenshot({ path: path.join(output, 'recepcion-desktop.png'), fullPage: true });
    const axe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    assert.equal(axe.violations.length, 0, JSON.stringify(axe.violations.map(v => v.id)));
    results.push('PASS axe: cero violaciones automáticas en formulario (no certifica WCAG)');
    await page.locator('#order-search').fill('P0');
    await page.getByRole('button', { name: 'Buscar orden', exact: true }).click();
    await page.getByRole('button', { name: 'Seleccionar ítem I1' }).click();
    assert.equal(await page.locator('#item').inputValue(), 'I1');
    assert.equal(await page.locator('#patient').inputValue(), '');
    results.push('PASS búsqueda muestra requisitos y selección no autocompleta identidad observada');
    await page.locator('#item').fill('I1');
    await page.locator('#patient').fill('P001');
    await page.locator('#name').fill('Ana Demo');
    await page.locator('#container').fill('EDTA');
    await page.locator('#volume').fill('3');
    await page.locator('#collected').fill('2026-10-20T10:00');
    interrupt = true;
    await page.getByRole('button', { name: 'Registrar recepción' }).click();
    await page.getByRole('button', { name: 'Recuperar la misma recepción' }).waitFor();
    await page.getByRole('button', { name: 'Recuperar la misma recepción' }).click();
    await page.getByRole('heading', { name: 'Muestra aceptada' }).waitFor();
    assert.deepEqual(requests[0], requests[1]);
    results.push('PASS corte de red: recuperación conserva clave y payload');
    await page.evaluate(() => { window.print = () => { window.__printCount = (window.__printCount || 0) + 1; }; });
    await page.getByRole('button', { name: 'Imprimir etiqueta' }).click();
    await page.waitForFunction(() => window.__printCount === 1);
    assert.equal(await page.evaluate(() => window.__printCount), 1);
    assert.equal(requests.length, 2);
    results.push('PASS impresión no repite recepción');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({ path: path.join(output, 'recepcion-movil.png'), fullPage: true });
    await page.getByRole('button', { name: 'Iniciar otra muestra' }).click();
    assert.equal(await page.locator('#patient').inputValue(), '');
    assert.equal(await page.locator('#name').inputValue(), '');
    assert.equal(await page.evaluate(() => document.activeElement.id), 'item');
    results.push('PASS nueva muestra limpia identidad y restaura foco');
    await page.locator('#code').fill(code);
    await page.getByRole('button', { name: 'Consultar', exact: true }).click();
    await page.getByRole('heading', { name: 'Muestra aceptada' }).waitFor();
    assert.equal(requests.length, 2);
    results.push('PASS consulta y reimpresión usan GET');
    failReadOnce = true;
    await page.getByRole('button', { name: 'Consultar', exact: true }).click();
    await page.getByRole('alert').waitFor();
    assert.equal(await page.getByRole('button', { name: 'Iniciar otra muestra' }).isEnabled(), true);
    results.push('PASS fallo de consulta GET no bloquea una nueva recepción');
    await page.goto((process.env.RECEPTION_UI_URL || 'http://127.0.0.1:5179/tests/Browser/Reception/index.html')+'?role=reader');
    await page.getByRole('heading', { name: 'Recepción de muestras' }).waitFor();
    assert.equal(await page.getByRole('button', { name: 'Registrar recepción' }).count(), 0);
    assert.equal(await page.getByRole('button', { name: 'Buscar orden', exact: true }).count(), 0);
    assert.equal(await page.getByRole('button', { name: 'Consultar', exact: true }).count(), 1);
    results.push('PASS rol lector presenta consulta y oculta recepción y búsqueda');
    fs.writeFileSync(path.join(output, 'navegador.txt'), results.join('\n')+'\n');
    console.log(results.join('\n'));
    await browser.close();
})().catch(error => { console.error(error); process.exit(1); });
