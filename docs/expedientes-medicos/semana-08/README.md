# Semana 08 — Diseño de experiencia de usuario

**Tarea:** diseñar el flujo UX de «apertura y consulta autorizada del expediente longitudinal» por sus roles
autorizados, con inicio, decisión, confirmación, estados vacío/carga/éxito, errores recuperables, ayuda contextual y
protección de datos.

## Entregables

| Archivo | Contenido |
|---|---|
| [`flujo-ux.md`](flujo-ux.md) | Roles, estados, mensajes, validaciones, reglas de interacción, ayuda y protección de datos |
| [`01-flujo-medico.svg`](01-flujo-medico.svg), [`02-flujo-recepcion.svg`](02-flujo-recepcion.svg), [`03-flujo-enfermeria-admin.svg`](03-flujo-enfermeria-admin.svg) | User flow por rol (fuentes en [`fuentes/`](fuentes/)) |
| [`wireframes/`](wireframes/) | 5 wireframes anotados (SVG editables: el propio SVG es la fuente) |

## Ubicación de la implementación

Los wireframes se implementaron en `micro-his/expediente-medico/public/ui/`. Las capturas del resultado real están en
`docs/expedientes-medicos/semana-11/evidencias/`.

## Cómo verificar

```bash
node --test micro-his/expediente-medico/tests/ui-estados.test.mjs
php -S 127.0.0.1:8096 -t micro-his/expediente-medico/public micro-his/expediente-medico/public/index.php
```

Abrir `http://127.0.0.1:8096/ui/` y recorrer cada rol del selector «Usuario (simulado)» con P-0001, P-0002 y P-0003.

## Estado

Implementado. El selector de usuario existe solo en el prototipo (en el sistema la identidad sale de la sesión).
