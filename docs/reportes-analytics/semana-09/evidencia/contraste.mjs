// Calcula el contraste WCAG de los colores de ReportsPage. Uso: node contraste.mjs
const lum = (hex) => { const c = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255).map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4)); return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]; };
const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((m, n) => n - m); return (x + 0.05) / (y + 0.05); };
const pares = [['Texto de ayuda', '#475569', '#f8fafc', 4.5], ['Trazabilidad', '#475569', '#f8fafc', 4.5], ['Error', '#991b1b', '#fef2f2', 4.5], ['Botón', '#ffffff', '#1e3a8a', 4.5], ['Porcentaje', '#1e3a8a', '#f8fafc', 3], ['Borde del campo (no texto)', '#64748b', '#f8fafc', 3], ['Foco (no texto)', '#1d4ed8', '#f8fafc', 3]];
let ok = true;
for (const [nombre, a, b, min] of pares) { const r = ratio(a, b); ok &&= r >= min; console.log(`${r >= min ? 'PASS' : 'FAIL'}  ${nombre}: ${r.toFixed(2)}:1 (mínimo ${min}:1)`); }
process.exit(ok ? 0 : 1);
