const { execFileSync } = require("node:child_process");
const fs = require("node:fs");
const path = require("node:path");
const puppeteer = require("puppeteer");

const chrome = process.env.CHROME_PATH || "/var/home/javier/.cache/puppeteer/chrome/linux-155.0.8059.84/chrome-linux64/chrome";
const base = process.env.BASE_URL || "http://127.0.0.1:8099";
const out = path.join(__dirname, "capturas");
const scale = 2;

const shots = [
  { name: "desktop-lista", viewport: { width: 1440, height: 1000 }, url: "/?vista=lista", wait: "h1" },
  { name: "desktop-emision", viewport: { width: 1440, height: 1000 }, url: "/?vista=emision", wait: "h1" },
  { name: "desktop-confirmacion", viewport: { width: 1440, height: 1000 }, url: "/?vista=confirmar", wait: "h1" },
  { name: "desktop-detalle", viewport: { width: 1440, height: 1000 }, url: "/?vista=detalle&orden=ORD-1042", wait: "h1" },
  { name: "desktop-error", viewport: { width: 1440, height: 1000 }, url: "/?vista=detalle&orden=ORD-1041&accion=cancelar", wait: "#error-panel" },
  { name: "movil-lista", viewport: { width: 390, height: 844, isMobile: true, hasTouch: true }, url: "/?vista=lista", wait: "h1" },
  { name: "movil-emision", viewport: { width: 390, height: 844, isMobile: true, hasTouch: true }, url: "/?vista=emision", wait: "h1" },
  { name: "movil-detalle", viewport: { width: 390, height: 844, isMobile: true, hasTouch: true }, url: "/?vista=detalle&orden=ORD-1042", wait: "h1" },
  { name: "movil-error", viewport: { width: 390, height: 844, isMobile: true, hasTouch: true }, url: "/?vista=detalle&orden=ORD-1041&accion=cancelar", wait: "#error-panel" },
];

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await puppeteer.launch({
    executablePath: chrome,
    args: ["--no-sandbox", "--disable-gpu", "--disable-lcd-text", "--force-color-profile=srgb", "--hide-scrollbars"],
  });
  try {
    for (const shot of shots) {
      const page = await browser.newPage();
      const errors = [];
      page.on("pageerror", (error) => errors.push(error.message));
      await page.setViewport({ ...shot.viewport, deviceScaleFactor: scale });
      await page.emulateMediaFeatures([{ name: "prefers-reduced-motion", value: "reduce" }]);
      await page.goto(base + shot.url, { waitUntil: "networkidle0" });
      await page.waitForSelector(shot.wait, { timeout: 5000 });
      await page.evaluate(() => document.fonts.ready);
      await new Promise((resolve) => setTimeout(resolve, 250));
      if (errors.length > 0) throw new Error(`${shot.name}: ${errors.join("; ")}`);
      const file = path.join(out, `${shot.name}.png`);
      await page.screenshot({ path: file, fullPage: true });
      console.log(`OK ${shot.name} -> ${file}`);
      await page.close();
    }
  } finally {
    await browser.close();
  }
  execFileSync("python3", [path.join(__dirname, "normalizar.py")], { stdio: "inherit" });
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
