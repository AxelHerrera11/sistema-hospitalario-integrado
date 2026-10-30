// Calcula el contraste WCAG 2.1 de los pares texto/fondo que usa la interfaz, leyendo las variables de estilos.css.
//   node docs/expedientes-medicos/semana-09/herramientas/contraste.mjs
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const css = readFileSync(fileURLToPath(new URL('../../../../micro-his/expediente-medico/public/ui/estilos.css', import.meta.url)), 'utf8');
const vars = Object.fromEntries([...css.matchAll(/--([a-z-]+):\s*(#[0-9a-f]{6})/gi)].map((m) => [m[1], m[2]]));
const color = (v) => (v.startsWith('#') ? v : vars[v]);

function luminancia(hex) {
    const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
        .map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}
function contraste(a, b) {
    const [l1, l2] = [luminancia(color(a)), luminancia(color(b))].sort((x, y) => y - x);
    return (l1 + 0.05) / (l2 + 0.05);
}

// [uso, texto, fondo, mínimo]. 4.5 texto normal; 3 para bordes de controles y foco (WCAG 1.4.11).
const pares = [
    ['Texto general', 'texto', '#ffffff', 4.5], ['Texto en fondo de página', 'texto', 'fondo', 4.5],
    ['Ayuda bajo el campo', 'texto-suave', '#ffffff', 4.5], ['Encabezado (aviso)', '#ffffff', 'primario', 4.5],
    ['Botón primario', '#ffffff', 'primario', 4.5], ['Botón secundario', 'primario', '#ffffff', 4.5],
    ['Estado éxito', 'exito-texto', 'exito-fondo', 4.5], ['Estado denegado', 'denegado-texto', 'denegado-fondo', 4.5],
    ['Estado error', 'error-texto', 'error-fondo', 4.5], ['Estado vacío/inicial', 'vacio-texto', 'vacio-fondo', 4.5],
    ['Error de campo', 'error-texto', '#ffffff', 4.5], ['Chip de resumen', 'texto', 'vacio-fondo', 4.5],
    ['Texto suave en episodio', 'texto-suave', '#ffffff', 4.5], ['Borde de campo', '#6b7682', '#ffffff', 3],
    ['Anillo de foco sobre blanco', 'foco', '#ffffff', 3], ['Contorno oscuro del foco', 'texto', '#ffffff', 3],
];
let fallos = 0;
for (const [uso, texto, fondo, minimo] of pares) {
    const r = contraste(texto, fondo);
    const ok = r >= minimo;
    fallos += ok ? 0 : 1;
    console.log(`${ok ? 'OK   ' : 'FALLA'} ${uso.padEnd(28)} ${color(texto)} sobre ${color(fondo)}  ${r.toFixed(2)}:1 (mín. ${minimo}:1)`);
}
console.log(`\n${pares.length - fallos} de ${pares.length} pares cumplen.`);
