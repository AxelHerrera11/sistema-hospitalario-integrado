// Cambio práctico de la Semana 12: retroceder con Escape y devolver el foco. Uso: node teclado.mjs <base>
// No confirma ninguna operación: solo navega entre pasos, así que no modifica HOSPITAL.
import puppeteer from 'puppeteer-core';

const base = process.argv[2];
const resultados = [];
const check = (nombre, ok, detalle) => { resultados.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${nombre} → ${detalle}`); };
const navegador = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });
const p = await navegador.newPage();
const foco = () => p.evaluate(() => document.activeElement.id || document.activeElement.tagName);
const visibles = () => p.evaluate(() => ['estado', 'formulario', 'confirmacion'].filter((id) => !document.getElementById(id).hidden).join('+'));
try {
    await p.goto(`${base}/ui/index.html`, { waitUntil: 'networkidle0' });
    await p.type('#actor', '00000000-0000-4000-8000-0000000000f1');
    await p.type('#estancia', '00000000-0000-4000-8000-0000000000e1');
    await p.click('#consulta button[type=submit]');
    await p.waitForSelector('#estado:not([hidden])');
    await p.click('#elegir-traslado');
    await p.click('#cancelar');
    check('«Cancelar» deja el foco en la situación', await foco() === 'titulo-estado', `foco en ${await foco()}`);
    await p.click('#elegir-traslado');
    await p.type('#cama', '00000000-0000-4000-8000-0000000000b2');
    await p.type('#motivo', 'Requiere aislamiento');
    await p.click('#formulario button[type=submit]');
    await p.keyboard.press('Escape');
    check('Escape en la confirmación vuelve a los datos con el foco en la cama', await visibles() === 'estado+formulario' && await foco() === 'cama', `${await visibles()}, foco en ${await foco()}`);
    await p.keyboard.press('Escape');
    check('Escape en los datos vuelve a la situación', await visibles() === 'estado' && await foco() === 'titulo-estado', `${await visibles()}, foco en ${await foco()}`);
    check('los datos escritos se conservan al retroceder', await p.$eval('#motivo', (e) => e.value) === 'Requiere aislamiento', 'motivo conservado');
} catch (error) {
    console.log(`ERROR ${error.message}`);
    resultados.push(false);
} finally {
    await navegador.close();
}
const fallas = resultados.filter((ok) => !ok).length;
console.log(`${resultados.length - fallas}/${resultados.length} comprobaciones`);
process.exit(fallas ? 1 : 0);
