# ASII-23 Semana 9 — Accesibilidad del módulo de reportes

**Issue:** `Refs #152`. WCAG 2.1 nivel AA aplicado al alcance del módulo
(pantalla `ReportsPage.vue` + navegación compartida del SPA).

## Criterios WCAG 2.1 y cómo se cumplen

| Criterio (WCAG) | Qué exige | Evidencia |
|---|---|---|
| 1.1.1 Texto alternativo | Todo control no textual con nombre accesible | Solo controles textuales y botones con texto en español. |
| 1.3.1 Info y relaciones | Etiquetas, títulos y estados en código | `aria-labelledby="reportes-title"` con `<h1>`; cada campo con `<label for>`; tabla-free sea el formulario. |
| 1.4.1 Uso del color | Sin depender solo del color | Errores con texto (`No tienes permiso…`, `Revisa el periodo…) y roles ARIA. |
| 2.1.1 Teclado | Todo funcional con teclado | `<form @submit.prevent>` + `<button type="submit">`: Enter envía; inputs nativos reciben foco. |
| 2.4.2 Titulación de páginas/títulos | Títulos y encabezados descriptivos | `h1` de la sección, `p` explicativo, `small` de trazabilidad. |
| 3.3.1/3.3.3 Identificación y sugerencia de errores | Mensajes claros y corrección guiada | `errorDeFiltros` valida antes de enviar; mensajes por código sin detalle interno. |
| 4.1.2 Nombre, función, valor | Estados programáticos correctos | `role="status"` (cargando/avisos), `role="alert"` (errores), `:disabled` durante cálculo. |

## Prueba ejecutable (evita regresiones entre commits)

```bash
node resources/js/modules/reportes/reportes.a11y.test.mjs
# OK: 11 comprobaciones de accesibilidad de la pantalla de reportes aprobadas
```

Además `navigation.test.mjs` y `permissions.test.mjs` del router validan la
falta segura de navegación sin permisos (indicador filtrado por rol; el
usuario no ve entradas a módulos restringidos).

## Fuera de alcance con registro

La auditoría manual con lector de pantalla (NVDA/VoiceOver) en dispositivo
real y contraste en modo oscuro quedan como acciones reales del estudiante;
esta semana entrega la base programática que las soporta y el test que
impide degradar entre commits.
