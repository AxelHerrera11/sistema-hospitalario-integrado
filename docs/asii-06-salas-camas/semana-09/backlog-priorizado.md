# Backlog priorizado — usabilidad y accesibilidad ASII-06

| Prioridad | Hallazgo | Acción antes/durante prototipo |
|---|---|---|
| P1 — Antes de integrar UI | UX-01 | Diseñar una entrada del catálogo en navegación y ruta protegida con `camas.ver`; reutilizar guardas y sesión HIS. |
| P2 — Antes de cerrar flujo | UX-02 | Definir contexto visible de ward seleccionada y regreso/cambio de ward. |
| P3 — Antes de fijar lenguaje visual | UX-03 | Acordar etiquetas de wards/camas con la responsable funcional; no traducir estados por suposición ni representar ocupación clínica. |
| P4 — Prototipo accesible | A11Y-01 | Especificar regiones de estado, errores, anuncio de resultados y reglas de foco para carga, selección, vacío y reintento. |
| P5 — Prototipo accesible | A11Y-02 | Definir lista/tabla semántica y cómo se relaciona cada cama con su ward al navegar con teclado/lector. |
| P6 — Validación visual | A11Y-03 | Medir contraste, zoom/reflow, foco visible y tamaño de controles cuando exista un prototipo renderizado. |

Las acciones no agregan endpoints ni permisos. El servidor sigue siendo la
autoridad RBAC. La conexión HOSPITAL fija y la falta de `tenant_id` no deben
ocultarse mediante decisiones de presentación.
