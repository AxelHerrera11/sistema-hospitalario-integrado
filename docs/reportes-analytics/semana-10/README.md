# ASII-23 Semana 10 — Movilidad

**Issue:** `Refs #152` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

El módulo no crea una aplicación aparte: adapta su pantalla real y hereda el menú móvil del sistema.

## Pantallas (capturas reales de Chrome emulando móvil)

| Pantalla | 320 px | 375 px | 430 px | 768 px |
|---|---|---|---|---|
| Formulario | [ver](./capturas/formulario-320.png) | [ver](./capturas/formulario-375.png) | [ver](./capturas/formulario-430.png) | [ver](./capturas/formulario-768.png) |
| Resultado | [ver](./capturas/resultado-320.png) | [ver](./capturas/resultado-375.png) | [ver](./capturas/resultado-430.png) | [ver](./capturas/resultado-768.png) |
| Error | [ver](./capturas/error-320.png) | — | — | — |

## Breakpoints

| Ancho | Formulario | Razón |
|---|---|---|
| Hasta 640 px | Una columna; botón a todo el ancho | Las fechas nativas necesitan ancho y el pulgar alcanza mejor el botón |
| Más de 640 px | Tres columnas; botón a la izquierda | Filtros comparables en una sola línea |

Los controles miden 44 px o más en todos los anchos. Medición: [`evidencia/salida-movilidad.txt`](./evidencia/salida-movilidad.txt).

## Escenarios móviles

### 1. Revisar la tasa durante un recorrido por las salas

Una coordinadora abre Reportes en el teléfono, elige del 01/01 al 01/02 con el selector nativo y calcula. Lee el 25.00 % sin hacer zoom. Si gira el teléfono, los filtros se conservan.

### 2. Equivocarse de fechas con la conexión inestable

Invierte las fechas y ve el error sin que se envíe nada. Corrige y calcula. Si la red o la base fallan, ve un aviso sin cifra y puede volver a pulsar «Calcular» sin perder los filtros.

Son escenarios de diseño: **no se probaron con personas ni con un teléfono físico.**

## Reproducir

```bash
E2E_PASSWORD=... E2E_DOMINIO=a.local node docs/reportes-analytics/semana-10/evidencia/movilidad.mjs <url> <hospital> <carpeta>
```
