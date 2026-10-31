# Gestion de citas medicas y agenda - Semana 12

> Tema guia: **Segunda evaluacion parcial: defensa teorica y caso practico de diseno UX/movilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- [Presentacion breve (5 diapositivas)](./presentacion.md)
- Guion del recorrido navegable sobre el prototipo de la Semana 11 (tabla abajo).
- Respuesta al cambio solicitado: analisis de impacto, [pantallas navegables](./cambio/index.html) y
  [diagrama](./01-cambio-solicitado.svg) | [fuente](./fuentes/01-cambio-solicitado.puml).

## Recorrido navegable (5 minutos)

| Paso | Pantalla | Que se muestra | Practica que se defiende |
|---|---|---|---|
| 1 | [`#agenda`](../semana-11/prototipo/index.html#agenda) | Agenda del dia y filtro por doctor | tabla -> tarjetas en movil |
| 2 | [`#nueva`](../semana-11/prototipo/index.html#nueva) | Nueva cita con solo franjas libres | prevencion de errores (HE-01 corregido) |
| 3 | [`#confirmada`](../semana-11/prototipo/index.html#confirmada) | Confirmacion con el resultado concreto | `role=status` |
| 4 | [`#err-solape`](../semana-11/prototipo/index.html#err-solape) | Solape concurrente (409) | datos conservados |
| 5 | [`#err-sesion`](../semana-11/prototipo/index.html#err-sesion) | Sesion expirada (401) | borrador local |

En cada paso se alterna la vista desktop y movil con `../semana-11/prototipo/comparar.html`.

## Cambio solicitado

**Pedido:** Reprogramar una cita **en dos toques desde la agenda movil**, viendo solo franjas libres del mismo doctor.

| Area | Impacto |
|---|---|
| UI | boton "Reprogramar" en cada tarjeta de la agenda; hoja inferior con franjas |
| API | usa el `PUT /citas/{id}` ya documentado en el contrato; sin cambios de contrato |
| Dominio | misma regla anti-solape; la cita original se excluye al validar |
| Accesibilidad | franjas como grupo de radio con `legend`; foco vuelve a la tarjeta al cerrar |

**Respuesta:** se resuelve extendiendo el prototipo sin romper el flujo existente: `#agenda-movil` -> `#franjas` -> `#reprogramada`. Las
reglas de UX se mantienen (un primario por pantalla, confirmacion con resultado concreto, texto + icono)
y el contrato API solo crece de forma compatible.

**Criterio de aceptacion del cambio:** el usuario completa la nueva tarea en movil (375 px) sin scroll
horizontal, con objetivos de 44-48 px y la auditoria de la Semana 9 sin nuevos hallazgos.

## Preguntas teoricas preparadas

1. Diferencia entre usabilidad y accesibilidad, con un ejemplo del modulo.
2. Por que mobile-first y no adaptar el desktop al final.
3. Como se decide la severidad de un hallazgo heuristico.
4. Que criterio WCAG cubre los mensajes de estado y como se implementa.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-12/fuentes/*.puml -o ..
```
