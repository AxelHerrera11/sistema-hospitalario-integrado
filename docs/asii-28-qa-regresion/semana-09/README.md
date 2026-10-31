# ASII-28 - QA Funcional - Semana 9 - Usabilidad y accesibilidad

Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Rama feature/asii-28-semana-09-qa-regresion-madelinceron; base develop.
HEAD inicial: 52097752bd37ea40b37f986259fc4c0aec0da2c6. Git status/branch/log/fetch
confirmaron rama limpia y base igual a origin/develop (0/0), sin pull ni worktrees.

## Alcance y fuentes

Evaluación técnica de los seis wireframes estáticos, no del sistema funcional.
#1400/#1401/#1402/#1403 siguen abiertos/no fusionados según GitHub; Semana 8 no
está en origin/develop. Se consultaron contratos/componentes desde sus ramas y
los [trece RF/RNF integrados](../semana-02/rf-rnf-criterios.md).
Material evaluado: [HTML de Semana 8, commit 508deb1b](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/508deb1b276d574cec5c6f179b3f067d875b8572/docs/asii-28-qa-regresion/semana-08/wireframes.html),
user-flow.md y reglas-interaccion.md del mismo commit. Sin copiar archivos al PR
ni modificar entregas previas. La copia temporal tiene el mismo blob Git que la fuente:
`36794d5cbfe212d46aacad727205da8c32297084`.

- [Checklist de 26 criterios](./checklist-usabilidad-accesibilidad.md).
- [Seis hallazgos con evidencia y clasificación](./hallazgos.md).
- [Backlog priorizado, trazabilidad y protocolo pendiente](./backlog-mejoras.md).

## Procedimiento y resultados nuevos — 29/10/2026

Se extrajo la fuente con git show a TEMP y se verificó git hash-object frente a
git rev-parse origin/feature/asii-28-semana-08-qa-regresion-madelinceron:docs/asii-28-qa-regresion/semana-08/wireframes.html.
Edge headless **156.0.4314.50**, perfil nuevo aislado, archivo local sin credenciales.
Protocolo DevTools con Node 24: inspección DOM/CSS, eventos reales Tab/Enter,
capturas temporales y viewport 1280/390/320 × 900. No se alteró la maqueta.

```text
msedge.exe --headless --disable-gpu --no-first-run --remote-debugging-port=9339 --user-data-dir=<TEMP>/profile about:blank
node <TEMP>/inspect.mjs <TEMP>
```

El script temporal usó Runtime.evaluate para selectores/estilos, Input.dispatchKeyEvent
para seis Tab y un Enter, Emulation.setDeviceMetricsOverride y Page.captureScreenshot.
Consultas reproducibles en consola: document.querySelectorAll('section').length;
document.querySelectorAll('[aria-describedby],[aria-errormessage],[aria-invalid]').length;
document.documentElement.scrollWidth; getComputedStyle(document.querySelector('main')).gridTemplateColumns.
Datos observados: seis secciones, doce controles etiquetados, cero IDs duplicados,
cero destinos rotos, cero controles de datos habilitados y cero asociaciones de error.
Tab recorrió Panel/Campañas/Caso/Detalle/Ejecución/Defecto, outline solid de 3 px;
Enter terminó en #wf-6. No se afirma prueba de teclado de formularios inactivos.

| Viewport CSS px | scrollWidth | Columnas main | Resultado observado |
|---|---|---|---|
| 1280 | 1265 | 596.5 / 596.5 | Dos columnas, sin desborde horizontal |
| 390 | 375 | 347 | Una columna, sin desborde horizontal |
| 320 | 305 | 277 | Una columna, sin desborde horizontal |

Se inspeccionaron capturas de escritorio/320 px; no son fotografías de usuarios.
Contraste calculado desde colores CSS opacos con luminancia sRGB: #171717/blanco
17.93:1; #171717/#eee 15.45:1; enlaces #202020/blanco 16.29:1; foco #333/blanco
12.63:1. Alcance: esos pares, sin certificar todos los estados o controles futuros.

Resultado: tres defectos observables de rotulado/navegación y tres riesgos de diseño,
todos abiertos, sin declarar seis fallos WCAG. Sin tests funcionales nuevos ni estudio
con participantes. Zoom real, lector de pantalla, validación dinámica y guardado:
no verificables aquí. Referencias W3C en checklist; no certificación WCAG.
Semana 10: revisar backlog, resolver política de evidencia/estados y preparar prototipo
interactivo para tareas autorizadas. Madelin mantiene evaluación; ejecución de mejoras
y aprobación pendientes de asignación. Sin PHP, PostgreSQL, .env o CI modificados.

Validación final documental: 26 criterios, seis hallazgos distintos, seis mejoras
vinculadas y trece filas de trazabilidad comprobados; enlaces locales existentes.
`git diff --check`, `git diff --cached --check` y revisión del diff completo contra
origin/develop: PASS; solo cuatro documentos propios de Semana 9, dentro de 400 líneas.
