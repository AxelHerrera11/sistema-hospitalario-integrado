// Renderiza cada wireframe a PNG. Uso: node wireframes.mjs <wireframes.html> <carpeta-salida>
import puppeteer from 'puppeteer-core';
import { pathToFileURL } from 'node:url';
const [html, salida] = process.argv.slice(2);
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
const page = await browser.newPage();
await page.setViewport({ width: 700, height: 1600, deviceScaleFactor: 1.5 });
await page.goto(pathToFileURL(html).href);
const nombres = { w1: '03-formulario', w2: '04-resultado', w3: '05-sin-datos', w4: '06-periodo-invalido', w5: '07-error-servicio', w6: '08-movil' };
for (const [id, nombre] of Object.entries(nombres)) await (await page.$(`#${id}`)).screenshot({ path: `${salida}/${nombre}.png` });
await browser.close();
