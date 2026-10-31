# Recorrido práctico y evidencia nueva

Fuente fijada: [prototipo de Semana 11](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/bd89ef5f85d81d2d867dad51e2f68911496331dc/docs/asii-28-qa-regresion/semana-11/prototipo-navegable.html).
#1492 sigue abierto: usar Raw/guardar archivo HTML desde ese commit y abrir localmente en Edge.
No abrir la página GitHub como si ejecutara el HTML. No se necesita servidor/login.
Recargar antes de empezar; usar únicamente los textos ficticios siguientes.

| Paso | Acción reproducible | Resultado observado / alcance |
|---|---|---|
| 1 | Abrir HTML, panel inicial | Una campaña, un caso, cero ejecuciones/defectos; banner de simulación |
| 2 | Consultar campañas; buscar inexistente y limpiar | Vacío recuperable; vuelve la campaña A. No se crean campañas |
| 3 | Seleccionar campaña A y revisar Detalle | Caso C-demo-01 asociado; abrirlo muestra pasos y esperado |
| 4 | Nuevo caso; enviar vacío | Resumen de errores recibe foco; no se registra caso |
| 5 | Propósito: Caso ficticio creado; pasos: 1. Abrir, 2. Revisar; esperado: Campos visibles; guardar | C-demo-2 y asociación simulados; retorna al detalle |
| 6 | Abrir caso y enviar ejecución incompleta | Resumen de errores con foco; no se confirma ejecución |
| 7 | Ver evidencia de ejemplo y cerrar diálogo | Referencia evidencia-demo-01 ficticia, sin cargar archivo |
| 8 | Observado: No aparecen campos; resultado Fallido; marcar evidencia; confirmar | E-demo-1 fallido en memoria; el analista declara la comparación |
| 9 | Defecto; enviar vacío; luego descripción Falta campo en ejemplo ficticio y severidad Por clasificar; guardar | Error primero; después D-demo-1 vinculado a E-demo-1. Severidad solo de demostración |
| 10 | Cancelar defecto con formulario ya vacío | Retorna a ejecución y conserva los registros; no prueba confirmación de descarte con texto pendiente |
| 11 | Observado: Campos visibles; resultado Aprobado; marcar evidencia; confirmar; ir a Defecto | E-demo-2 aprobado; formulario de defecto deshabilitado |
| 12 | Volver al panel y recargar | Antes: 2 casos, 2 ejecuciones, 1 defecto; después: 1 caso, 0 ejecuciones, 0 defectos |

## Reejecución del 29/10/2026

Se extrajeron HTML y verificador de #1492 con git show a TEMP, sin modificar fuentes.
Blobs idénticos antes de ejecutar (git hash-object frente a git rev-parse fuente):
HTML `2bf0afaeb4cc39e4d08ac8fde2f39e6356b6d9e1`; script `3498fcb7dc098a546258c7ce94f315a742084074`.
Usar [verificador publicado](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/bd89ef5f85d81d2d867dad51e2f68911496331dc/docs/asii-28-qa-regresion/semana-11/verificar-prototipo.mjs) junto al HTML, ambos en TEMP.

```text
msedge.exe --headless --disable-gpu --no-first-run --remote-debugging-port=9341 --user-data-dir=<TEMP>/perfil-qa about:blank
node --check <TEMP>/verificar-prototipo.mjs
node <TEMP>/verificar-prototipo.mjs http://127.0.0.1:9341 <TEMP>/capturas
php micro-his/qa-regresion/tests/run.php
```

Edge 156.0.4314.50, Node 24, perfil aislado cerrado al finalizar: **17 PASS, 0 fallos**,
ejecutados nuevamente, no transcritos de Semana 11. Seis vistas/una visible, cero IDs
duplicados, cero controles sin etiqueta y cero enlaces internos sin destino.
Tab/Enter: #campaigns y foco campaigns-title, contorno 3 px. Errores de caso/ejecución/
defecto, mensajes, diálogo, guardas, contadores y reinicio comprobados; cero excepciones JS observadas.
PHP 8.2.12: suite existente **17 aprobadas, 0 fallidas**, distinta de HTML.
Demos separadas: JSON purpose=“Caso ficticio S12”, steps=“1. Abrir formulario”, expected=“Campos visibles” → 201 con ID;
propósito vacío con los otros textos presentes → 422 CASE_INCOMPLETE. Son resultados CLI.

## Desktop y móvil

| Viewport CSS px (alto inicial 900) | scrollWidth nuevo | Pantalla / resultado |
|---|---|---|
| 1280 | 1280 | Panel, sin desborde; defecto también capturado |
| 390 | 375 | Panel, menú envuelto, sin desborde |
| 320 | 305 | Panel, columna única, sin desborde |

Las cuatro capturas nuevas quedaron solo en TEMP; se inspeccionaron defecto desktop
y panel 320. Se reutilizan las cuatro imágenes reales publicadas de Semana 11, misma
versión y fecha, sin duplicarlas ni presentarlas como nuevas capturas versionadas:
[defecto desktop](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/bd89ef5f85d81d2d867dad51e2f68911496331dc/docs/asii-28-qa-regresion/semana-11/capturas/desktop-defecto.png),
[panel 1280](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/bd89ef5f85d81d2d867dad51e2f68911496331dc/docs/asii-28-qa-regresion/semana-11/capturas/panel-1280.png),
[panel 390](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/bd89ef5f85d81d2d867dad51e2f68911496331dc/docs/asii-28-qa-regresion/semana-11/capturas/panel-390.png),
[panel 320](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/bd89ef5f85d81d2d867dad51e2f68911496331dc/docs/asii-28-qa-regresion/semana-11/capturas/panel-320.png).

Límites: no API, almacenamiento durable, comparación semántica automática, consulta
completa de historial ni creación arbitraria de campañas. No se probó todo por teclado,
lector de pantalla, zoom real, confirmación de descarte con cambios o usabilidad humana.
Reflow emulado no certifica WCAG. Datos en memoria no acreditan RF/RNF completos.
