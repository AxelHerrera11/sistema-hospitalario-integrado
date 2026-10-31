# Semana 10 — Diseño para movilidad (320–430 px)

**Entregable:** propuesta responsive de 3–5 pantallas, reglas de *breakpoint* y dos escenarios móviles con decisiones de contenido y error.

Capturas reales a **360 px** (Chrome *headless*, iframe de ancho exacto) del prototipo implementado:

| Pantalla | Captura | Decisiones de jerarquía |
|---|---|---|
| M1 Lista | [m1-lista.png](capturas/m1-lista.png) | Tarjeta por ejecución: versión + veredicto arriba; estado, ambiente y commit abreviado debajo |
| M2 Detalle | [m2-detalle-aprobado.png](capturas/m2-detalle-aprobado.png) | El veredicto va **antes** de la tabla; la tabla se convierte en tarjetas con etiqueta por celda (`data-label`), sin *scroll* horizontal |
| M3 Gate rechazado | [m3-gate-rechazado.png](capturas/m3-gate-rechazado.png) | Hallazgos como lista corta «severidad · suite: detalle»; el SHA completo se parte (`overflow-wrap:anywhere`) |
| M4 Error recuperable | [m4-error-almacen.png](capturas/m4-error-almacen.png) | Aviso arriba con «Reintentar» de 44 px; nada se pierde |
| M5 Planificar | [m5-planificar.png](capturas/m5-planificar.png) | Una columna; cada suite apilada (nombre, tipo, obligatoria, quitar) |

## Reglas de breakpoint (`styles.css`)

| Ancho | Regla |
|---|---|
| < 720 px (móvil, 320–430) | Una columna; encabezado apilado; tabla → tarjetas; botones y campos de **44 px** mínimo; `inputmode="numeric"` en conteos |
| ≥ 720 px | Encabezado en dos columnas; formulario en 3 columnas; lista en 2 columnas; tabla real |
| Siempre | Sin ancho fijo mayor que la pantalla; `prefers-reduced-motion` respetado; foco visible de 3 px |

## Carga cognitiva

- En móvil solo se muestran las acciones permitidas para el estado actual (`allowedActions`): como máximo 2 botones a la vez.
- El commit se abrevia a 12 caracteres en la lista; completo solo en el detalle.
- El texto de ayuda de cada veredicto dice qué hacer después (corregir, completar o promover).

## Escenario móvil 1 — Revisora de integración en el teléfono, red móvil inestable

Contexto: Yamilet recibe el aviso de un PR y abre el detalle desde el teléfono.

- **Contenido:** ve primero «Quality gate: Rechazado» y el hallazgo `e2e-auth-rbac: Aprobó 87.5 % (7 de 8)` (M3); no necesita la tabla para decidir.
- **Error:** si la red cae, `api.js` devuelve `status: 0` (tiempo máximo 8 s) y aparece «Sin conexión — Sus datos del formulario se conservan» con «Reintentar». Nunca se muestra un veredicto viejo como si fuera actual: la vista se vuelve a pedir al servidor.

## Escenario móvil 2 — Líder QA registra un resultado desde el pasillo

Contexto: Cindy copia del artefacto la ruta, el tamaño y el SHA-256 de un reporte E2E.

- **Contenido:** el formulario solo ofrece suites sin resultado; teclado numérico en conteos y bytes.
- **Error:** si el servidor responde 503 por el almacén, el aviso dice «No se guardó nada; intente de nuevo»; el botón queda bloqueado mientras se envía (H-02), así que un toque doble no duplica. Si alguien completó la ejecución mientras tanto, llega 409 «Estado no válido» y se recarga el detalle.

## Recuperación ante conexión limitada (propuesta pendiente)

No se implementa cola *offline*: registrar evidencia sin servidor rompería RNF-03 (la huella y el estado los valida el servidor). Propuesta: guardar solo el **borrador** del formulario en `sessionStorage` y reenviarlo a mano. Pendiente de aprobación.
