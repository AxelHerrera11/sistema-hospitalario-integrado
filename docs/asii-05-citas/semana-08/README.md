# Gestion de citas medicas y agenda - Semana 8

> Tema guia: **Diseno de experiencia de usuario (UX)**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- Wireframes/mockups navegables: [`prototipo/index.html`](./prototipo/index.html) (desktop y movil, mismo archivo responsive).
- Comparacion lado a lado desktop (1024 px) y movil (375 px): [`prototipo/comparar.html`](./prototipo/comparar.html).
- [Flujo de interaccion](./01-flujo-interaccion.svg) | [fuente](./fuentes/01-flujo-interaccion.puml)
- [Guia de estilos UI](./guia-estilos.md)

## Usuarios y objetivo de experiencia

- **Usuarios:** Recepcion (agenda en mostrador, desktop) y medico (consulta su agenda en el celular).
- **Objetivo UX:** Agendar una cita sin solapes en menos de 1 minuto y cancelar con confirmacion explicita.

## Pantallas del prototipo

| Ancla | Pantalla |
|---|---|
| `#agenda` | Agenda |
| `#nueva` | Nueva |
| `#confirmada` | Confirmada |
| `#detalle` | Detalle |
| `#cancelar` | Cancelar |

Cada pantalla se enlaza con las demas como en el flujo real (botones y menu). El prototipo es HTML y
CSS sin dependencias; un script de 3 lineas solo marca el item activo del menu y mueve el foco.

## Decisiones de UX

1. **Un objetivo por pantalla** y un solo boton primario: reduce la carga cognitiva.
2. **Confirmacion explicita** en acciones irreversibles y mensaje de exito con el resultado concreto.
3. **Estados visibles** (badges, alertas) con texto e icono, no solo color.
4. **Movil primero en tablas:** cada fila se convierte en tarjeta con etiquetas (< 768 px).
5. **Consistencia transversal:** mismo layout, menu y estados que el contrato UI base (ASII-26).

## Como revisarlo

```bash
start docs/asii-05-citas/semana-08/prototipo/index.html      # Windows
open docs/asii-05-citas/semana-08/prototipo/comparar.html    # macOS
```

Para probar movil sin el comparador: DevTools -> modo dispositivo -> 375 x 812.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-08/fuentes/*.puml -o ..
```
