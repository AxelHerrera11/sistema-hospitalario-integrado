# Gobernanza y Auditoría — Semana 10: propuesta responsive

## Objetivo

Proponer cómo se adapta la consulta de la bitácora a distintos tamaños de pantalla. El proyecto no tiene una aplicación móvil nativa para este módulo, así que es una **propuesta responsive** de la interfaz web.

## Entregables

| Entregable | Ubicación |
|---|---|
| 5 pantallas representativas (P1–P5) | [propuesta-responsive.md §2](propuesta-responsive.md#2-pantallas-representativas) |
| Tabla o listado adaptable | [§3](propuesta-responsive.md#3-tabla-o-listado-adaptable) |
| Filtros en dispositivos pequeños | P2 (propuesto) |
| Estados de error y sin resultados | P3 y P4 |
| Consideraciones de accesibilidad | [§4](propuesta-responsive.md#4-consideraciones-de-accesibilidad) |
| Escenarios por rol | [§5](propuesta-responsive.md#5-escenarios-de-uso-por-rol) |
| Navegación | `01-navegacion-responsive.svg`, [fuente](fuentes/01-navegacion-responsive.mmd) |

## Archivos creados o modificados

- **Creados:** `README.md`, `propuesta-responsive.md`, `fuentes/01-navegacion-responsive.mmd` y su SVG.

## Relación con semanas anteriores

Parte de los wireframes de la Semana 8 y del hallazgo F-01 de la Semana 9.

## Uso

Abrir el prototipo y cambiar el ancho de la ventana, o usar el modo dispositivo del navegador (375 × 812).

## Pruebas ejecutadas y resultados reales

A 375 × 812 (emulación del navegador integrado): `scrollWidth = 375`, `thead` en posición absoluta y `td` en `display: block`, sin desplazamiento horizontal ([captura](../evidencias/s11-04-movil-375-tarjetas.jpg)).

## Dependencias pendientes

Ninguna externa. P2 (filtros plegables) queda en el backlog (B-01).

## Limitaciones conocidas

No se probó en dispositivos físicos; solo en la emulación de viewport del navegador.
