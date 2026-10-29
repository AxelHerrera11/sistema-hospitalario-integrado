# Centro de documentacion y manuales por rol - Semana 12

> Tema guia: **Segunda evaluacion parcial: defensa teorica y caso practico de diseno UX/movilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
## Entregables

- [Presentacion breve (5 diapositivas)](./presentacion.md)
- Guion del recorrido navegable sobre el prototipo de la Semana 11 (tabla abajo).
- Respuesta al cambio solicitado: analisis de impacto, [pantallas navegables](./cambio/index.html) y
  [diagrama](./01-cambio-solicitado.svg) | [fuente](./fuentes/01-cambio-solicitado.puml).

## Recorrido navegable (5 minutos)

| Paso | Pantalla | Que se muestra | Practica que se defiende |
|---|---|---|---|
| 1 | [`#catalogo`](../semana-11/prototipo/index.html#catalogo) | Catalogo filtrado por rol con buscador | solo lo visible para el rol |
| 2 | [`#sin-resultados`](../semana-11/prototipo/index.html#sin-resultados) | Busqueda sin resultados | sugerencias (HE-02 corregido) |
| 3 | [`#publicar`](../semana-11/prototipo/index.html#publicar) | Publicar con roles obligatorios | validacion en linea |
| 4 | [`#err-roles`](../semana-11/prototipo/index.html#err-roles) | Error sin roles (422) | mensaje junto al grupo |
| 5 | [`#err-permiso`](../semana-11/prototipo/index.html#err-permiso) | Sin permiso (403) | mensaje neutro |

En cada paso se alterna la vista desktop y movil con `../semana-11/prototipo/comparar.html`.

## Cambio solicitado

**Pedido:** Permitir **marcar documentos como favoritos** y mostrarlos primero en el catalogo movil.

| Area | Impacto |
|---|---|
| UI | boton estrella con `aria-pressed` en cada tarjeta; seccion "Favoritos" arriba |
| API | nuevo recurso `PUT/DELETE /documentos/{id}/favorito` (cambio compatible, se agrega a OpenAPI) |
| Dominio | favorito pertenece al usuario, no al documento; no altera la visibilidad por rol |
| Accesibilidad | el estado se anuncia: "Agregado a favoritos" con `role=status` |

**Respuesta:** se resuelve extendiendo el prototipo sin romper el flujo existente: `#catalogo-favoritos` -> `#agregado`. Las
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
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-24-centro-documentacion/semana-12/fuentes/*.puml -o ..
```
