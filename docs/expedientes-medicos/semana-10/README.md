# Semana 10 — Diseño para movilidad

**Tarea:** adaptar «apertura y consulta autorizada del expediente longitudinal» a móvil (320–430 px): jerarquizar
datos y acciones, reducir carga cognitiva y definir navegación, tablas/formularios, confirmaciones y recuperación
ante conexión limitada.

## Entregables

| Archivo | Contenido |
|---|---|
| [`propuesta-movil.md`](propuesta-movil.md) | Jerarquía de contenido, 5 pantallas, reglas de breakpoint, navegación/formularios/confirmaciones y conexión limitada |
| [`escenarios-moviles.md`](escenarios-moviles.md) | Dos escenarios con decisiones de contenido y de error |

Las capturas móviles reales (320 y 375 px) se generan una sola vez y se guardan en `../semana-11/evidencias/` para no
duplicar binarios entre PR.

## Ubicación del código

`micro-his/expediente-medico/public/ui/estilos.css` (breakpoints 768 y 1024 px), `app.js` (tiempo límite y
reintentos) y `estados.js` (`siguienteReintento`, mensajes de conexión).

## Cómo verificar

```bash
node --test micro-his/expediente-medico/tests/ui-estados.test.mjs
node docs/expedientes-medicos/semana-11/herramientas/capturar.mjs docs/expedientes-medicos/semana-11/evidencias
```

(El segundo comando necesita el prototipo corriendo con la base de demo recién creada.)

## Estado

Implementado y capturado. No se probó en un teléfono físico ni con red limitada real (pendiente manual).
