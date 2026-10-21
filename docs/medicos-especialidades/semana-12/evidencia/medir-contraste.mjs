#!/usr/bin/env node
/**
 * Medición del contraste del prototipo de la Semana 11 (WCAG 2.1 AA).
 *
 * Lee la paleta real del bloque `:root` del prototipo, calcula la razón de contraste de cada
 * par de color que se usa en pantalla y la compara con el mínimo que exige la norma:
 *   - 1.4.3 Contraste mínimo: 4,5:1 en texto normal y 3:1 en texto grande.
 *   - 1.4.11 Contraste no textual: 3:1 en bordes de control, foco y estados.
 *
 * Uso: node medir-contraste.mjs [--prototipo <ruta/index.html>]
 * Salida: tabla Markdown por stdout y código de salida 1 si algún par no cumple.
 */
import { readFileSync } from 'node:fs';
import { join, resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const aqui = dirname(fileURLToPath(import.meta.url));
const opcion = (nombre, porDefecto) => {
  const i = process.argv.indexOf(nombre);
  return i === -1 ? porDefecto : process.argv[i + 1];
};
const rutaPrototipo = resolve(
  opcion('--prototipo', join(aqui, '..', '..', 'semana-11', 'prototipo', 'index.html'))
);
const BLANCO = '#ffffff';
const TEXTO_NORMAL = 4.5;
const TEXTO_GRANDE = 3;
const NO_TEXTUAL = 3;

/** Luminancia relativa WCAG de un color hexadecimal de 6 dígitos. */
const luminancia = (hex) => {
  const valor = parseInt(hex.slice(1), 16);
  const canales = [(valor >> 16) & 255, (valor >> 8) & 255, valor & 255].map((c) => {
    const srgb = c / 255;
    return srgb <= 0.04045 ? srgb / 12.92 : ((srgb + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * canales[0] + 0.7152 * canales[1] + 0.0722 * canales[2];
};

/** Razón de contraste entre dos colores, redondeada a dos decimales. */
const contraste = (a, b) => {
  const x = luminancia(a);
  const y = luminancia(b);
  return Math.round(((Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05)) * 100) / 100;
};

/** Variables CSS declaradas en el bloque `:root` del prototipo, sin los guiones. */
const leerPaleta = (css) => {
  const bloque = css.match(/:root\s*\{([^}]*)\}/);
  if (!bloque) throw new Error('No se encontró el bloque :root con la paleta del prototipo');
  const paleta = {};
  for (const linea of bloque[1].split(';')) {
    const [nombre, valor] = linea.split(':').map((p) => p.trim());
    if (nombre && valor?.startsWith('#')) paleta[nombre.replace(/^--/, '')] = valor;
  }
  return paleta;
};

// [uso en pantalla, primer plano, fondo, mínimo, criterio]
const PARES = [
  ['Texto del cuerpo sobre el fondo de la página', 'tinta', 'fondo', TEXTO_NORMAL, '1.4.3'],
  ['Texto de las tarjetas y títulos de sección', 'tinta', BLANCO, TEXTO_NORMAL, '1.4.3'],
  ['Texto de ayuda y campo «ayuda» (14 px)', 'suave', BLANCO, TEXTO_NORMAL, '1.4.3'],
  ['Texto de ayuda sobre la banda del simulador', 'suave', 'aviso', TEXTO_NORMAL, '1.4.3'],
  ['Texto de la banda del simulador', 'tinta', 'aviso', TEXTO_NORMAL, '1.4.3'],
  ['Título del encabezado sobre la barra oscura', BLANCO, 'tinta', TEXTO_GRANDE, '1.4.3'],
  ['Botón principal y franja seleccionada', BLANCO, 'azul', TEXTO_NORMAL, '1.4.3'],
  ['Mensaje de error', 'error', BLANCO, TEXTO_NORMAL, '1.4.3'],
  ['Mensaje de éxito', 'ok', BLANCO, TEXTO_NORMAL, '1.4.3'],
  ['`trace_id` al pie del mensaje (13 px)', 'suave', BLANCO, TEXTO_NORMAL, '1.4.3'],
  ['Enlace de la barra de navegación', 'tinta', BLANCO, TEXTO_NORMAL, '1.4.3'],
  ['Borde de los campos de formulario', 'suave', BLANCO, NO_TEXTUAL, '1.4.11'],
  ['Borde de la franja seleccionable', 'azul', BLANCO, NO_TEXTUAL, '1.4.11'],
  ['Barra de la acción principal (fondo de página)', 'azul', 'fondo', NO_TEXTUAL, '1.4.11'],
  ['Indicador de foco sobre el fondo de la página', 'foco', 'fondo', NO_TEXTUAL, '1.4.11'],
  ['Indicador de foco sobre una tarjeta blanca', 'foco', BLANCO, NO_TEXTUAL, '1.4.11'],
];

const paleta = leerPaleta(readFileSync(rutaPrototipo, 'utf8'));
const filas = PARES.map(([uso, fg, bg, minimo, criterio]) => {
  const primerPlano = fg === BLANCO ? BLANCO : paleta[fg];
  const fondo = bg === BLANCO ? BLANCO : paleta[bg];
  if (!primerPlano || !fondo) throw new Error(`Falta la variable de color: ${fg} o ${bg}`);
  const ratio = contraste(primerPlano, fondo);
  return { uso, primerPlano, fondo, ratio, minimo, criterio, cumple: ratio >= minimo };
});

const fallan = filas.filter((f) => !f.cumple);
const decimals = (n) => n.toFixed(2).replace('.', ',');
console.log(
  [
    '| Uso en pantalla | Primer plano | Fondo | Razón | Mínimo | Criterio | Resultado |',
    '|---|---|---|---:|---:|---|---|',
    ...filas.map(
      (f) =>
        `| ${f.uso} | \`${f.primerPlano}\` | \`${f.fondo}\` | ${decimals(f.ratio)}:1 | ${decimals(f.minimo)}:1 | ${f.criterio} | ${f.cumple ? 'Cumple' : '**No cumple**'} |`
    ),
    '',
    `Medidos ${filas.length} pares de color sobre el prototipo \`${rutaPrototipo}\`: ${filas.length - fallan.length} cumplen y ${fallan.length} no cumplen.`,
    ...(fallan.length
      ? ['', '**Pares que fallan:**', ...fallan.map((f) => `- ${f.uso}: ${decimals(f.ratio)}:1 con un mínimo de ${decimals(f.minimo)}:1 (${f.criterio}).`)]
      : []),
  ].join('\n')
);
process.exit(fallan.length > 0 ? 1 : 0);
