# Matriz decisión → RF/RNF → evidencia (Semana 12)

| Decisión de diseño | RF/RNF | Evidencia |
|---|---|---|
| Etiqueta «Hasta (no incluido)» y ayuda con ejemplo | RF periodo `[desde, hasta)`; RNF comprensión | `reportes.a11y.test.mjs`; captura 01 |
| Limpiar el resultado antes de validar y al cambiar filtros | RNF confiabilidad | `recorrido-e2e.mjs`; captura 03 |
| Validar en la pantalla sin llamar a la API | RNF eficiencia; la API sigue siendo la autoridad | `recorrido-e2e.mjs` y `run.php` |
| Leyenda fija junto al porcentaje | RF trazabilidad | `reportes.test.mjs`; captura 02 |
| N/A con la razón del estado | RF `SIN_DATOS_EN_PERIODO` | `reportes.test.mjs`; captura 04 |
| Mensajes por código, sin detalle interno | RNF seguridad | `reportes.test.mjs`; captura 05 |
| Columna única hasta 640 px | RNF movilidad | `semana-10/evidencia/movilidad.mjs` |
| Mensaje propio para otro hospital | RNF aislamiento | `recorrido-e2e.mjs`; captura 08 |
