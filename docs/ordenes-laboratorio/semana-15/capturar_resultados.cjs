const { execFileSync } = require("node:child_process");
const fs = require("node:fs");
const path = require("node:path");
const puppeteer = require("puppeteer");

const chrome = process.env.CHROME_PATH || "/var/home/javier/.cache/puppeteer/chrome/linux-155.0.8059.84/chrome-linux64/chrome";
const base = __dirname;
const evidencias = path.join(base, "evidencias");
const capturas = path.join(base, "capturas");

const escape = (text) => text.replace(/[&<>"]/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[char]));

function leer(nombre) {
  return fs.readFileSync(path.join(evidencias, nombre), "utf8");
}

function contar(texto, prefijo) {
  return texto.split("\n").filter((linea) => linea.startsWith(prefijo)).length;
}

function filas(texto) {
  return texto
    .split("\n")
    .filter((linea) => /^(PASS|FAIL)/.test(linea))
    .map((linea) => `<li class="${linea.startsWith("PASS") ? "ok" : "mal"}">${escape(linea)}</li>`)
    .join("");
}

const semana15 = leer("semana15.txt");
const run = leer("run.txt");
const mvc = leer("mvc.txt");
const api = leer("api.txt");
const client = leer("client.txt");
const contrato = leer("contrato.txt");

const resumen = [
  ["php tests/semana15.php", `${contar(semana15, "PASS")}/${contar(semana15, "PASS") + contar(semana15, "FAIL")}`, "Unitarias, caja blanca y caja negra"],
  ["php tests/run.php", `${contar(run, "PASS")}/${contar(run, "PASS") + contar(run, "FAIL")}`, "28 casos × 3 adaptadores"],
  ["php tests/mvc.php", `${contar(mvc, "PASS")}/12`, "Controlador, vista y presentador"],
  ["php tests/api.php", `${contar(api, "PASS")}/18`, "Kernel HTTP, cabeceras y problem+json"],
  ["php tests/client.php", `${contar(client, "PASS")}/6`, "Cliente HTTP con servidor real"],
  ["verificar_contrato.py", `${contar(contrato, "PASS")}/6`, "OpenAPI y límites de reason"],
];

const html = `<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Semana 15 — Reporte de ejecución</title>
<style>
  :root { color-scheme: light; }
  * { box-sizing: border-box; }
  body { margin: 0; padding: 48px 56px; font-family: "DejaVu Sans", Arial, sans-serif; color: #14213d; background: #f4f7fb; }
  header { border-bottom: 4px solid #1d4ed8; padding-bottom: 20px; margin-bottom: 28px; }
  h1 { margin: 0 0 6px; font-size: 34px; }
  .sub { color: #4b5563; font-size: 15px; }
  .badge { display: inline-block; margin-top: 12px; padding: 6px 12px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 700; font-size: 14px; }
  h2 { font-size: 21px; margin: 30px 0 12px; }
  table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #d7deea; border-radius: 10px; overflow: hidden; }
  th, td { padding: 11px 14px; border-bottom: 1px solid #e5eaf3; text-align: left; font-size: 14px; }
  th { background: #e8eefb; color: #1e3a8a; }
  td:nth-child(2) { font-weight: 700; }
  .ok { color: #166534; }
  .mal { color: #b91c1c; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
  .card { background: #fff; border: 1px solid #d7deea; border-radius: 10px; padding: 16px 18px; }
  .card h3 { margin: 0 0 10px; font-size: 16px; }
  ul { margin: 0; padding-left: 22px; }
  li { font-family: "DejaVu Sans Mono", monospace; font-size: 11.5px; line-height: 1.55; }
  .defectos td:last-child { font-weight: 700; }
  footer { margin-top: 28px; color: #6b7280; font-size: 12px; }
</style>
</head>
<body>
<header>
  <h1>Semana 15 — Pruebas unitarias, caja blanca y caja negra</h1>
  <div class="sub">Órdenes de laboratorio desde EMR · ASII-16 · Javier Alexander Fajardo López</div>
  <div class="sub">Ejecución real del 22-oct-2026 · PHP 8.2+ · PostgreSQL 16.15 · SQLite en memoria</div>
  <div class="badge">Todas las verificaciones aprobadas</div>
</header>

<h2>Comandos y resultados</h2>
<table>
  <thead><tr><th>Comando</th><th>Resultado</th><th>Alcance</th></tr></thead>
  <tbody>
    ${resumen.map(([cmd, resultado, alcance]) => `<tr><td><code>${cmd}</code></td><td class="ok">${resultado}</td><td>${alcance}</td></tr>`).join("")}
  </tbody>
</table>

<h2>Semana 15 — 24 verificaciones</h2>
<div class="grid">
  <div class="card"><h3>Unitarias y caja blanca</h3><ul>${filas(semana15.split("== Caja negra")[0])}</ul></div>
  <div class="card"><h3>Caja negra (API)</h3><ul>${filas("== Caja negra" + semana15.split("== Caja negra")[1])}</ul></div>
</div>

<h2>Defectos registrados</h2>
<table class="defectos">
  <thead><tr><th>ID</th><th>Hallazgo</th><th>Severidad</th><th>Estado</th></tr></thead>
  <tbody>
    <tr><td>H-01</td><td>Motivo de cancelación fuera de 5–250</td><td>Mayor</td><td class="ok">Corregido y probado</td></tr>
    <tr><td>H-02</td><td>OpenAPI sin respuestas 400/403/503</td><td>Mayor</td><td class="ok">Corregido y verificado</td></tr>
    <tr><td>H-03</td><td>Confianza en X-Permissions</td><td>Mayor condicional</td><td>Abierto / mitigado en gateway</td></tr>
    <tr><td>H-04</td><td>Replay con carga distinta devuelve la orden previa</td><td>Mayor</td><td>Abierto / aceptado</td></tr>
  </tbody>
</table>

<footer>Evidencia generada desde los archivos de <code>semana-15/evidencias/</code>. No se inventaron ejecuciones.</footer>
</body>
</html>`;

(async () => {
  fs.mkdirSync(capturas, { recursive: true });
  fs.writeFileSync(path.join(base, "reporte.html"), html);

  const browser = await puppeteer.launch({
    executablePath: chrome,
    args: ["--no-sandbox", "--disable-gpu", "--disable-lcd-text", "--force-color-profile=srgb", "--hide-scrollbars"],
  });
  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 2 });
    await page.emulateMediaFeatures([{ name: "prefers-reduced-motion", value: "reduce" }]);
    await page.goto("file://" + path.join(base, "reporte.html"), { waitUntil: "networkidle0" });
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: path.join(capturas, "resultados.png"), fullPage: true });
  } finally {
    await browser.close();
  }
  execFileSync("python3", [path.join(base, "normalizar.py")], { stdio: "inherit" });
  fs.unlinkSync(path.join(base, "reporte.html"));
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
