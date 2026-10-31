# Tablero de indicadores (dashboard base) - Semana 12

> Tema guia: **Segunda evaluacion parcial: defensa teorica y caso practico de diseno UX/movilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
## Entregables

- [Presentacion breve (5 diapositivas)](./presentacion.md)
- Guion del recorrido navegable sobre el prototipo de la Semana 11 (tabla abajo).
- Respuesta al cambio solicitado: analisis de impacto, [pantallas navegables](./cambio/index.html) y
  [diagrama](./01-cambio-solicitado.svg) | [fuente](./fuentes/01-cambio-solicitado.puml).

## Recorrido navegable (5 minutos)

| Paso | Pantalla | Que se muestra | Practica que se defiende |
|---|---|---|---|
| 1 | [`#inicio`](../semana-11/prototipo/index.html#inicio) | Inicio con tarjetas y sentido de la meta | icono + texto + color (HE-01 corregido) |
| 2 | [`#detalle`](../semana-11/prototipo/index.html#detalle) | Como se calcula | ayuda contextual |
| 3 | [`#cargando`](../semana-11/prototipo/index.html#cargando) | Estado cargando | `aria-busy` |
| 4 | [`#vacio`](../semana-11/prototipo/index.html#vacio) | Estado vacio | enlace al periodo actual |
| 5 | [`#err-servicio`](../semana-11/prototipo/index.html#err-servicio) | Servicio caido | ultimos datos con hora |

En cada paso se alterna la vista desktop y movil con `../semana-11/prototipo/comparar.html`.

## Cambio solicitado

**Pedido:** Mostrar en cada tarjeta la **comparacion con el periodo anterior** (variacion y tendencia).

| Area | Impacto |
|---|---|
| UI | linea "▲ +4 pts vs octubre" en cada tarjeta; la flecha siempre va con texto |
| API | `GET /tablero?periodo=&comparar=anterior` agrega `variacion` por indicador (campo opcional, compatible) |
| Dominio | `ObtenerTablero` consulta dos periodos; el calculo de variacion respeta `menor es mejor` |
| Accesibilidad | la tendencia se lee completa: "subio 4 puntos respecto de octubre, mejora" |

**Respuesta:** se resuelve extendiendo el prototipo sin romper el flujo existente: `#inicio-comparado`. Las
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
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-12/fuentes/*.puml -o ..
```
