# Semana 11 — Mejores prácticas para diseño móvil/web

**Tarea:** construir un prototipo navegable desktop y móvil del flujo principal, cubriendo camino feliz y un error
crítico relacionado con expediente longitudinal, episodios clínicos, acceso autorizado, integridad y datos sensibles,
con consistencia de roles, estados y accesibilidad.

## Entregables

| Entregable | Ubicación |
|---|---|
| Prototipo (archivo) | `micro-his/expediente-medico/public/ui/` servido por `public/index.php` (API real del micro-HIS, datos ficticios) |
| Mapa de navegación | [`01-mapa-navegacion.svg`](01-mapa-navegacion.svg) · [`fuentes/01-mapa-navegacion.mmd`](fuentes/01-mapa-navegacion.mmd) |
| Capturas desktop y móvil | [`evidencias/`](evidencias/) (tabla abajo) |
| Herramienta de capturas | [`herramientas/capturar.mjs`](herramientas/capturar.mjs) (Chrome headless + DevTools Protocol, anchos exactos) |

No hay enlace público: el prototipo es local y no se publicó en ningún servicio.

## Cómo ejecutar el prototipo

```bash
php -S 127.0.0.1:8096 -t micro-his/expediente-medico/public micro-his/expediente-medico/public/index.php
```

Abrir `http://127.0.0.1:8096/ui/`. Recorrido sugerido: Dra. López + P-0001 (camino feliz) → Carla Ruiz + P-0001
(error crítico: sin vínculo) → Dra. López + P-0002 (vacío) → Diego Mora + P-0002 «Abrir expediente…» (apertura).
Para volver a los datos iniciales: detener el servidor y borrar `expediente-demo-hospital.sqlite` y
`expediente-demo-audit.sqlite` de la carpeta temporal del sistema.

## Capturas (generadas el 2026-10-23 con el código final, zona del equipo GMT-6)

| Caso | Escritorio (1280 px) | Móvil |
|---|---|---|
| Inicio | `d-01-inicio.png` | `m-01-inicio-375.png` |
| **Camino feliz** | `d-02-consulta-exito.png`, `d-03-motivo-desplegado.png` | `m-02-consulta-exito-375.png`, `m-05-consulta-320.png` |
| **Error crítico** (sin vínculo asistencial) | `d-04-error-critico-sin-vinculo.png` | `m-03-error-critico-sin-vinculo-375.png` |
| Hospital no disponible (503) | `d-05-hospital-no-disponible.png` | — |
| Vacío (sin expediente) | `d-06-vacio-sin-apertura.png` | — |
| Código inválido | — | `m-04-codigo-invalido-375.png` |
| Confirmación y apertura | `d-07-confirmacion-apertura.png`, `d-08-apertura-exito.png` | `m-06-confirmacion-375.png`, `m-07-apertura-ya-existia-375.png` |

Por qué el error crítico es «sin vínculo»: es el punto donde el sistema protege el expediente longitudinal de una
persona con permiso general (`expediente.ver`) pero sin relación asistencial; la respuesta no muestra datos y queda
auditada. `m-07` muestra «ya existía» porque la captura móvil se toma después de la de escritorio (idempotencia).

## Consistencia de roles, estados y accesibilidad

- Mismos 5 usuarios, mismos códigos y mismos mensajes en escritorio y móvil (salen de `estados.js`).
- Estados: inicial, carga, éxito, vacío, denegado y error (Semana 8). Accesibilidad evaluada en la Semana 9.

## Estado

Implementado. La SPA Vue del HIS aún no tiene ruta del expediente (archivo compartido del router; ver pendientes).
