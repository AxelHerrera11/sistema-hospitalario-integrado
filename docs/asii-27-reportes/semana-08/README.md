# Reportes exportables - Semana 8

> Tema guia: **Diseno de experiencia de usuario (UX)**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- Wireframes/mockups navegables: [`prototipo/index.html`](./prototipo/index.html) (desktop y movil, mismo archivo responsive).
- Comparacion lado a lado desktop (1024 px) y movil (375 px): [`prototipo/comparar.html`](./prototipo/comparar.html).
- [Flujo de interaccion](./01-flujo-interaccion.svg) | [fuente](./fuentes/01-flujo-interaccion.puml)
- [Guia de estilos UI](./guia-estilos.md)

## Usuarios y objetivo de experiencia

- **Usuarios:** Personal administrativo que genera reportes periodicos y jefaturas que los revisan.
- **Objetivo UX:** Obtener un CSV con exactamente los filtros elegidos, viendo una vista previa antes de exportar.

## Pantallas del prototipo

| Ancla | Pantalla |
|---|---|
| `#catalogo` | Catalogo |
| `#filtros` | Filtros |
| `#previa` | Previa |
| `#exportado` | Exportado |

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
start docs/asii-27-reportes/semana-08/prototipo/index.html      # Windows
open docs/asii-27-reportes/semana-08/prototipo/comparar.html    # macOS
```

Para probar movil sin el comparador: DevTools -> modo dispositivo -> 375 x 812.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-08/fuentes/*.puml -o ..
```
