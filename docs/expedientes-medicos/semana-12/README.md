# Semana 12 — Segunda evaluación parcial

**Tarea:** defender las decisiones de componentes, UX, accesibilidad y movilidad y demostrar con el prototipo cómo
«apertura y consulta autorizada del expediente longitudinal» trata roles, estados, errores y riesgos.

## Entregables

| Archivo | Contenido |
|---|---|
| [`presentacion.md`](presentacion.md) | Presentación breve (6 diapositivas, formato Marp) |
| [`recorrido.md`](recorrido.md) | Guion del recorrido navegable de 10 pasos con riesgo tratado y captura |
| [`matriz-decision-requisito-evidencia.md`](matriz-decision-requisito-evidencia.md) | U-D01…U-D11 → RF/RNF → evidencia |
| [`cambio-solicitado.md`](cambio-solicitado.md) · [`evidencias/verificacion-despues.json`](evidencias/verificacion-despues.json) | Cambio pedido, cambios aplicados y verificación |

## Ubicación del código cambiado

`micro-his/expediente-medico/public/ui/{app.js, estados.js, vista.js, estilos.css}` y
`micro-his/expediente-medico/tests/ui-estados.test.mjs` (V-10, V-11).

## Cómo verificar

```bash
node --test micro-his/expediente-medico/tests/ui-estados.test.mjs
```

Y el recorrido de [`recorrido.md`](recorrido.md) en el prototipo.

## Estado

Implementado y verificado. Pendientes externos: H-05 (permisos en la sesión de la SPA) y H-07 (tiempo de inactividad).
