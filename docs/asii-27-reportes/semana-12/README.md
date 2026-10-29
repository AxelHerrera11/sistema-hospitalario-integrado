# Reportes exportables - Semana 12

> Tema guia: **Segunda evaluacion parcial: defensa teorica y caso practico de diseno UX/movilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- [Presentacion breve (5 diapositivas)](./presentacion.md)
- Guion del recorrido navegable sobre el prototipo de la Semana 11 (tabla abajo).
- Respuesta al cambio solicitado: analisis de impacto, [pantallas navegables](./cambio/index.html) y
  [diagrama](./01-cambio-solicitado.svg) | [fuente](./fuentes/01-cambio-solicitado.puml).

## Recorrido navegable (5 minutos)

| Paso | Pantalla | Que se muestra | Practica que se defiende |
|---|---|---|---|
| 1 | [`#filtros`](../semana-11/prototipo/index.html#filtros) | Filtros con limite visible | validacion en linea (HE-02 corregido) |
| 2 | [`#previa`](../semana-11/prototipo/index.html#previa) | Vista previa paginada | motivo del PDF visible (HE-01 corregido) |
| 3 | [`#exportado`](../semana-11/prototipo/index.html#exportado) | Exportacion lista | vuelve con los mismos filtros |
| 4 | [`#err-rango`](../semana-11/prototipo/index.html#err-rango) | Rango > 366 dias (422) | limite calculado |
| 5 | [`#err-formato`](../semana-11/prototipo/index.html#err-formato) | Formato no soportado (406) | alternativa CSV |

En cada paso se alterna la vista desktop y movil con `../semana-11/prototipo/comparar.html`.

## Cambio solicitado

**Pedido:** Permitir **guardar combinaciones de filtros** con nombre (p. ej. "Mensual pediatria") y reutilizarlas.

| Area | Impacto |
|---|---|
| UI | boton "Guardar filtros" en la vista previa; lista "Mis filtros" en el catalogo |
| API | nuevo recurso `/reportes/{id}/filtros-guardados` (GET/POST/DELETE), sin cambiar `/export` |
| Dominio | se guardan filtros relativos ("mes anterior") para que sigan siendo reproducibles |
| Accesibilidad | nombre obligatorio con etiqueta; confirmacion con `role=status` |

**Respuesta:** se resuelve extendiendo el prototipo sin romper el flujo existente: `#guardar` -> `#mis-filtros`. Las
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
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-12/fuentes/*.puml -o ..
```
