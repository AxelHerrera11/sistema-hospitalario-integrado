# Gestion de citas medicas y agenda - Semana 11

> Tema guia: **Mejores practicas para diseno movil y web**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- Prototipo navegable responsive: [`prototipo/index.html`](./prototipo/index.html) (desktop y movil).
- Vista lado a lado 1024 px / 375 px: [`prototipo/comparar.html`](./prototipo/comparar.html).
- [Camino feliz y errores criticos](./01-camino-feliz-errores.svg) | [fuente](./fuentes/01-camino-feliz-errores.puml)

Pantallas: `#agenda`, `#nueva`, `#confirmada`, `#err-solape`, `#err-validacion`, `#err-sesion`, `#err-red`, `#errores`.

## Camino feliz

Agenda -> Nueva cita (solo franjas libres) -> Guardar -> Cita agendada -> Agenda

## Escenarios de error critico

| Escenario | Disparador | Pantalla | Respuesta API | Recuperacion |
|---|---|---|---|---|
| Solape concurrente | otra recepcion toma la franja entre la carga y el guardado | `#err-solape` | 409 `appointments_overlap` | franjas actualizadas; se conservan paciente y doctor |
| Datos incompletos | falta doctor o franja | `#err-validacion` | 422 `validation_failed` | error junto al campo con `aria-describedby` |
| Sesion expirada | JWT vencido al guardar | `#err-sesion` | 401 `unauthenticated` | borrador local y vuelta al formulario tras iniciar sesion |
| Sin conexion | red caida o servicio no responde | `#err-red` | error de red / 503 | reintento manual; nada se pierde |

Los codigos coinciden con el contrato API (Semana 5) y el formato comun de errores de ASII-24.

## Mejores practicas aplicadas

| Practica | Como se aplica |
|---|---|
| Prevenir antes que corregir | se muestran solo opciones validas y se valida en linea |
| Errores accionables | que paso + que hacer, junto al campo, enlazado con `aria-describedby` |
| No perder trabajo | los datos se conservan en 409/422/401 y en fallas de red |
| Retroalimentacion inmediata | `role=status` en exitos, `role=alert` en errores, esqueleto al cargar |
| Mismo contenido en movil y web | un solo HTML responsive; en movil tablas -> tarjetas y botones a todo el ancho |
| Accesibilidad | `h1` en cada pantalla, foco al cambiar de pantalla, objetivos de 44 px, contraste AA |
| Seguridad en mensajes | 401/403 no revelan permisos ni existencia de recursos |

## Correcciones de la evaluacion de la Semana 9

- HE-01 (sev 3): el formulario muestra solo franjas libres del doctor; el 409 queda para la concurrencia real.
- HE-04: el paciente se elige con sugerencias (`datalist`).
- WCAG 3.3.1 y 1.3.1: errores en linea y `h1` en la confirmacion.

**Re-auditoria:** la auditoria automatica de la Semana 9 sobre este prototipo da **12 chequeos, 0 a revisar**:

```bash
php ../semana-09/evidencia/auditoria-accesibilidad.php prototipo/index.html
```

## Como revisarlo

Abrir `prototipo/index.html`, seguir el camino feliz desde el menu y luego la entrada **Errores**; en
movil, usar `comparar.html` o DevTools a 375 x 812.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-11/fuentes/*.puml -o ..
```
