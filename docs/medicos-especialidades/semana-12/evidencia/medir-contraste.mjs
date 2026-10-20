#!/usr/bin/env node
/**
 * Medición del contraste del prototipo de la Semana 11 (WCAG 2.1 AA).
 *
 * Lee la paleta real del archivo `:root` del prototipo, calcula la razón de
 * contraste de cada par de color que se usa en pantalla y la compara con el
 * mínimo que exige la norma:
 *   - 1.4.3 Contraste mínimo (mínimo): 4.5:1 en texto normal, 3:1 en texto grande.
 *   - 1.4.11 Contraste no textual (mínimo): 3:1 en bordes de control, foco y estados.
 *
 * Uso:  node medir-contraste.mjs [--prototipo <ruta/index.html>]
 * Salida: tabla Markdown por stdout y código de salida 1 si algún par falla.
 */
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const aqui = dirname(fileURLToPath(import.meta.url));
const argPrototipo = process.argv.indexOf('--prototipo');
const rutaPrototipo = resolve(
  argPrototipo === -1
    ? process.argv[2] ?? resolve(aqui, '..', '..', 'semana-11', 'prototipo', 'index.html')
    : process.argv[argPrototipo + 1]
);

const TEXTO_NORMAL = 4.5;
const TEXTO_GRANDE = 3;
const NO_TEXTUAL = 3;

/** Convierte un color hexadecimal de 6 dígitos a luminancia relativa WCAG. */
function luminancia(hex) {
  const valor = parseInt(hex.slice(1), 16);
  const canales = [(valor >> 16) & 255, (valor >> 8) & 255, valor & 255].map((c) => {
    const srgb = c / 255;
    return srgb <= 0.04045 ? srgb / 12.92 : ((srgb + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * canales[0] + 0.7152 * canales[1] + 0.0722 * canales[2];
}

/** Razón de contraste entre dos colores, redondeada a dos decimales. */
function contraste(hexA, hexB) {
  const a = luminancia(hexA);
  const b = luminancia(hexB);
  const claro = Math.max(a, b);
  const oscuro = Math.min(a, b);
  return Math.round(((claro + 0.05) / (oscuro + 0.05)) * 100) / 100;
}

/** Extrae las variables CSS declaradas en el bloque `:root` del prototipo. */
function leerPaleta(css) {
  const bloque = css.match(/:root\s*\{([^}]*)\}/);
  if (!bloque) throw new Error('No se encontró el bloque :root con la paleta del prototipo');
  const paleta = {};
  for (const linea of bloque[1].split(';')) {
    const [nombre, valor] = linea.split(':').map((p) => p.trim());
    if (nombre && valor?.startsWith('#')) paleta[nombre.replace(/^--/, '')] = valor;
  }
  return paleta;
}

/** Pares medidos: dónde se usa cada color en pantalla y qué criterio exige. */
const PARES = [
  { uso: 'Texto del cuerpo sobre el fondo de la página', fg: 'tinta', bg: 'fondo', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Texto de las tarjetas y títulos de sección', fg: 'tinta', bg: 'ffffff', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Texto de ayuda y campo «ayuda» (14 px)', fg: 'suave', bg: 'ffffff', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Texto de ayuda sobre la banda del simulador', fg: 'suave', bg: 'aviso', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Texto de la banda del simulador', fg: 'tinta', bg: 'aviso', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Título del encabezado sobre la barra oscura', fg: 'ffffff', bg: 'tinta', minimo: TEXTO_GRANDE, criterio: '1.4.3' },
  { uso: 'Botón principal y franja seleccionada', fg: 'ffffff', bg: 'azul', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Mensaje de error', fg: 'error', bg: 'ffffff', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Mensaje de éxito', fg: 'ok', bg: 'ffffff', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: '`trace_id` al pie del mensaje (13 px)', fg: 'suave', bg: 'ffffff', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Enlace de la barra de navegación', fg: 'tinta', bg: 'ffffff', minimo: TEXTO_NORMAL, criterio: '1.4.3' },
  { uso: 'Borde de los campos de formulario', fg: 'suave', bg: 'ffffff', minimo: NO_TEXTUAL, criterio: '1.4.11' },
  { uso: 'Borde de la franja seleccionable', fg: 'azul', bg: 'ffffff', minimo: NO_TEXTUAL, criterio: '1.4.11' },
  { uso: 'Barra de la acción principal (fondo de página)', fg: 'azul', bg: 'fondo', minimo: NO_TEXTUAL, criterio: '1.4.11' },
  { uso: 'Indicador de foco sobre el fondo de la página', fg: 'foco', bg: 'fondo', minimo: NO_TEXTUAL, criterio: '1.4.11' },
  { uso: 'Indicador de foco sobre una tarjeta blanca', fg: 'foco', bg: 'ffffff', minimo: NO_TEXTUAL, criterio: '1.4.11' },
];

const css = readFileSync(rutaPrototipo, 'utf8');
const paleta = leerPaleta(css);
const blanco = '#ffffff';

const filas = PARES.map((par) => {
  const fg = par.fg === 'ffffff' ? blanco : paleta[par.fg];
  const bg = par.bg === 'ffffff' ? blanco : paleta[par.bg];
  if (!fg || !bg) throw new Error(`Falta la variable de color: ${par.fg} o ${par.bg}`);
  const ratio = contraste(fg, bg);
  return { ...par, fg, bg, ratio, cumple: ratio >= par.minimo };
});

const fallan = filas.filter((f) => !f.cumple);
const lineas = [
  '| Uso en pantalla | Primer plano | Fondo | Razón | Mínimo | Criterio | Resultado |',
  '|---|---|---|---:|---:|---|---|',
  ...filas.map(
    (f) => `| ${f.uso} | \`${f.fg}\` | \`${f.bg}\` | ${f.ratio.toFixed(2).replace('.', ',')}:1 | ${f.minimo.toFixed(1).replace('.', ',')}:1 | ${f.criterio} | ${f.cumple ? 'Cumple' : '**No cumple**'} |`
  ),
  '',
  `Medidos ${filas.length} pares de color sobre el prototipo \`${rutaPrototipo}\`: ${filas.length - fallan.length} cumplen y ${fallan.length} no cumplen.`,
];

if (fallan.length > 0) {
  lineas.push('', '**Pares que fallan:**');
  for (const f of fallan) lineas.push(`- ${f.uso}: ${f.ratio.toFixed(2)}:1 con un mínimo de ${f.minimo}:1 (${f.criterio}).`);
}

console.log(lineas.join('\n'));
process.exit(fallan.length > 0 ? 1 : 0);
