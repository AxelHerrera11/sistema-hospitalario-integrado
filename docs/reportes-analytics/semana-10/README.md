# ASII-23 Semana 10 — Movilidad del módulo de reportes

**Issue:** `Refs #152`. El módulo no inventa una app aparte: hereda la base
móvil del SPA y concentra sus propios riesgos.

## Decisiones de movilidad

| Aspecto | Decisión | Evidencia |
|---|---|---|
| Viewport | Meta viewport global `width=device-width, initial-scale=1` | `resources/views/app.blade.php` (una sola fuente de verdad; ninguna pantalla la sobrescribe). |
| Inputs de fecha | `type="date"` nativos: el selector es el del sistema operativo (touch) | `ReportsPage.vue` (`rp-desde`, `rp-hasta`); `required` para bloquear envíos sin filtros. |
| Formulario | Etiquetas con `for`/`id` y foco de teclado/touch | `reportes.a11y.test.mjs` los comprueba en cada commit. |
| Errores en pantalla | `role="status"`/`role="alert"`: el lector de pantalla móvil lo anuncia | `ReportsPage.vue`; prueba estática. |
| Sesión | Token y tenant persistidos por el SPA; sin re-login por giro de pantalla | `resources/js/plugins/axios.js` + guardia del router. |

## Guía de verificación en dispositivo

1. Abrir `http://127.0.0.1:8000/reportes` en el navegador del teléfono
   (misma red; ajustar host de Vite si se usa `npm run dev -- --host`).
2. Confirmar: página a ancho de dispositivo sin zoom inicial; campos de
   fecha abren el selector nativo; "Calcular" táctil con un dedo.
3. Girar el dispositivo: el estado (filtros y último resultado) se
   conserva; no hay re-login.
4. Con lector de pantalla activado, oír el anuncio "Calculando…" y la
   leyenda del resultado.

## PR y promo

La pantalla, su ruta `/reportes` y su navegación con permiso
`reportes.ver` se integraron en PR #1248 (236 líneas) y se promovieron en
#1254; la base global del SPA era anterior y se reutiliza sin duplicar.
