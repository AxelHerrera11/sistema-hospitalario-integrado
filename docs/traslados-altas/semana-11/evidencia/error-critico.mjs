// Error crítico del prototipo (Semana 11): HOSPITAL inaccesible. Uso: node error-critico.mjs <base-con-hospital-caido> <dir-capturas>
// Escritorio (1024 px) y móvil (375 px): la consulta responde 503, la interfaz no muestra datos ni acciones y ofrece reintentar.
import puppeteer from 'puppeteer-core';

const [base, salida] = process.argv.slice(2);
const resultados = [];
const check = (nombre, ok) => { resultados.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${nombre}`); };
const navegador = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });
try {
    for (const ancho of [1024, 375]) {
        const p = await navegador.newPage();
        await p.setViewport({ width: ancho, height: 800, isMobile: ancho < 500 });
        await p.goto(`${base}/ui/index.html`, { waitUntil: 'networkidle0' });
        await p.type('#actor', '00000000-0000-4000-8000-0000000000f1');
        await p.type('#estancia', '00000000-0000-4000-8000-0000000000e1');
        const respuesta = p.waitForResponse((r) => r.url().includes('/internacion'));
        await p.click('#consulta button[type=submit]');
        const estado = (await respuesta).status();
        await p.waitForFunction(() => document.getElementById('resultado').textContent !== 'Consultando…');
        const vista = await p.evaluate(() => ({ texto: document.getElementById('resultado').textContent,
            situacion: !document.getElementById('estado').hidden, tipo: document.getElementById('resultado').className }));
        check(`${ancho} px: HOSPITAL caído → ${estado}, mensaje de reintento y sin datos ni acciones`, estado === 503 && vista.tipo === 'reintentar'
            && vista.texto.includes('puede reintentar') && !vista.situacion);
        salida && await p.screenshot({ path: `${salida}/error-critico-${ancho}.png`, fullPage: true });
        await p.close();
    }
} catch (error) {
    console.log(`ERROR ${error.message}`);
    resultados.push(false);
} finally {
    await navegador.close();
}
const fallas = resultados.filter((ok) => !ok).length;
console.log(`${resultados.length - fallas}/${resultados.length} comprobaciones`);
process.exit(fallas ? 1 : 0);
