# Órdenes de laboratorio desde EMR — Semana 11: prototipo navegable

**Módulo:** Órdenes de laboratorio desde EMR (ASII-16) · **Entrega académica:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09)
**Prototipo:** [`prototype/index.html`](../../../micro-his/ordenes-laboratorio/prototype/index.html) · **Mapa:** [`navegacion.svg`](./navegacion.svg) · **Fuente:** [`fuentes/navegacion.mmd`](./fuentes/navegacion.mmd)
**Base:** flujo de la [semana 8](../semana-08/README.md), hallazgos de la [semana 9](../semana-09/README.md) y diseño móvil de la [semana 10](../semana-10/README.md).

## 1. Objetivo y alcance

Prototipo navegable en HTML, CSS y JavaScript sin dependencias, con datos en memoria, que permite recorrer el flujo completo de una orden de laboratorio en escritorio y móvil:

1. consultar y filtrar la bandeja;
2. emitir una orden desde el contexto del expediente;
3. revisar y confirmar la emisión;
4. seguir el detalle, el historial y las transiciones;
5. cancelar con motivo o explicar un error recuperable.

No es el micro-HIS real: no llama a la API, no persiste datos y no implementa autenticación. La emisión simula una latencia de 800 ms para mostrar el estado de envío.

## 2. Cómo ejecutarlo

```bash
cd micro-his/ordenes-laboratorio/prototype
python3 -m http.server 8099
```

Abrir `http://127.0.0.1:8099/`. Rutas directas:

| Vista | URL |
|---|---|
| Bandeja | `/?vista=lista` |
| Emisión | `/?vista=emision` |
| Confirmación | `/?vista=confirmar` |
| Detalle | `/?vista=detalle&orden=ORD-1042` |
| Error de prueba inactiva | `/?vista=error&code=LAB_TEST_NOT_ACTIVE` |
| Error al cancelar una orden validada | `/?vista=detalle&orden=ORD-1041&accion=cancelar` |

## 3. Mapa de navegación

![Mapa de navegación del prototipo](./navegacion.svg)

El mapa incluye las cinco vistas, el diálogo de cancelación y los retornos desde el error. La fuente Mermaid está en [`fuentes/navegacion.mmd`](./fuentes/navegacion.mmd); el SVG se regenera con:

```bash
mmdc -i fuentes/navegacion.mmd -o navegacion.svg -b transparent
```

En este entorno `mmdc` se ejecutó desde la instalación cacheada de `@mermaid-js/mermaid-cli` 12.0.0.

## 4. Inventario de pantallas

| Pantalla | Ruta | Propósito | Evidencia |
|---|---|---|---|
| Bandeja escritorio | `?vista=lista` | Buscar, filtrar y abrir órdenes | [`desktop-lista.jpg`](./capturas/desktop-lista.jpg) |
| Emisión escritorio | `?vista=emision` | Elegir pruebas y prioridad | [`desktop-emision.jpg`](./capturas/desktop-emision.jpg) |
| Confirmación escritorio | `?vista=confirmar` | Revisar antes de crear | [`desktop-confirmacion.jpg`](./capturas/desktop-confirmacion.jpg) |
| Detalle escritorio | `?vista=detalle&orden=ORD-1042` | Estado, pruebas, historial y acciones | [`desktop-detalle.jpg`](./capturas/desktop-detalle.jpg) |
| Error escritorio | `?vista=detalle&orden=ORD-1041&accion=cancelar` | Cancelación rechazada (409) | [`desktop-error.jpg`](./capturas/desktop-error.jpg) |
| Bandeja móvil | `?vista=lista` a 390 px | Prioriza estado y acción | [`movil-lista.jpg`](./capturas/movil-lista.jpg) |
| Emisión móvil | `?vista=emision` a 390 px | Formulario de una columna | [`movil-emision.jpg`](./capturas/movil-emision.jpg) |
| Detalle móvil | `?vista=detalle&orden=ORD-1042` a 390 px | Contexto, acciones e historial | [`movil-detalle.jpg`](./capturas/movil-detalle.jpg) |
| Error móvil | `?vista=detalle&orden=ORD-1041&accion=cancelar` a 390 px | Recuperación sin perder datos | [`movil-error.jpg`](./capturas/movil-error.jpg) |

Las capturas se generan a **2×** (2880×2000 en escritorio y 780 px de ancho en móvil) y se normalizan a JPEG 4:4:4 para conservar el texto legible sin duplicados por subpíxeles.

## 5. Flujo feliz

1. En la bandeja, escribir `Ana` o seleccionar el estado `En proceso`; el contador se actualiza con `aria-live`.
2. Abrir **Nueva orden**: paciente, episodio y nota llegan como datos de solo lectura.
3. Agregar `GLU` y `HB`; las pruebas inactivas (`TSH`) aparecen deshabilitadas. El botón **Revisar y emitir** permanece deshabilitado hasta elegir al menos una.
4. En **Confirmar emisión**, revisar el resumen y el aviso de que la orden nace `EMITIDA`.
5. **Emitir orden** muestra `Emitiendo…`, deshabilita el botón y, tras 800 ms, abre el detalle de la nueva orden con su evento inicial y un `toast` de confirmación.
6. En el detalle, **Avanzar estado** recorre `Emitida → Tomada → En proceso → Con resultado → Validada`; cada transición agrega un evento al historial y conserva el `correlation_id`.

Los pasos 1–6 se evidencian en `desktop-lista.jpg`, `desktop-emision.jpg`, `desktop-confirmacion.jpg` y `desktop-detalle.jpg`; el equivalente móvil está en las capturas `movil-*.jpg`.

## 6. Flujo crítico: cancelar una orden validada

La orden `ORD-1041` está validada. Al abrir `?vista=detalle&orden=ORD-1041&accion=cancelar`, el prototipo intenta la cancelación y muestra un error recuperable con:

- mensaje comprensible: «La orden ya fue validada y no se puede cancelar.»;
- código `ORDER_ALREADY_VALIDATED` y estado HTTP `409`;
- `request_id` copiable para soporte;
- acciones **Volver al detalle**, **Ir a la bandeja** y **Copiar request_id**.

El foco se mueve al panel `role="alert"` y no se modifica ninguna orden. La misma vista se usa para una prueba inactiva (`LAB_TEST_NOT_ACTIVE`) desde `?vista=error&code=LAB_TEST_NOT_ACTIVE`.

## 7. Estados y transiciones

| Estado | Etiqueta | Siguiente | Cancelable en el prototipo |
|---|---|---|---|
| `ISSUED` | Emitida | `COLLECTED` | Sí |
| `COLLECTED` | Tomada | `IN_PROCESS` | Sí |
| `IN_PROCESS` | En proceso | `RESULTED` | Sí |
| `RESULTED` | Con resultado | `VALIDATED` | Sí |
| `VALIDATED` | Validada | — | No |
| `CANCELLED` | Cancelada | — | No |

Cancelar exige un motivo de 5 a 250 caracteres en un diálogo modal; el motivo queda en el evento del historial. El prototipo no permite editar una orden después de emitida y no borra eventos.

## 8. Comportamiento responsive

| Rango | Comportamiento observado |
|---|---|
| ≥1024 px | Dos columnas en el detalle (contenido + historial), barra superior y tabla de pruebas |
| 431–1023 px | Una columna, contexto no fijo, filtros apilados |
| ≤430 px | Barra superior en dos líneas, botones a ancho completo, pie de formulario fijo y tarjetas en lugar de tablas |

Las capturas móviles usan 390×844 px. En ellas no hay scroll horizontal; las acciones principales quedan al alcance del pulgar y el historial se lee en orden cronológico inverso.

## 9. Accesibilidad implementada

- Enlace **Saltar al contenido principal** y foco programático en `main` al cambiar de vista.
- Etiquetas persistentes, `autocomplete` en búsquedas y textos de ayuda asociados por contexto.
- Estados con texto e icono, no solo color.
- `aria-live="polite"` para el contador, el `toast` y el estado de emisión; `role="alert"` en el error.
- Diálogo nativo con `showModal()`, foco inicial en el motivo y cierre con **Volver**.
- Objetivos táctiles de al menos 44×44 px y foco visible con `outline` de 3 px.
- Tabla del detalle con `caption` y `scope="col"`.

Pendiente: auditoría formal con lector de pantalla y pruebas con usuarios; el alcance de esta semana es el prototipo y su evidencia visual.

## 10. Evidencia reproducible

Con el servidor en `127.0.0.1:8099`:

```bash
cd /var/home/javier/shi-asii-09-ordenes-laboratorio
NODE_PATH=/var/home/javier/.npm/_npx/668c188756b835f3/node_modules \
  node docs/ordenes-laboratorio/semana-11/capturar.cjs
```

El script abre cada ruta, espera las fuentes y el selector clave, captura a 2× con `--disable-lcd-text` y luego ejecuta [`normalizar.py`](./normalizar.py), que convierte los PNG a JPEG de calidad 95 con submuestreo 4:4:4. Esto evita el aspecto de texto duplicado que producía el antialiasing subpíxel al visualizar los PNG originales.

Verificación ejecutada el 22 de octubre de 2026:

- 5 capturas de escritorio y 4 de móvil generadas sin errores de página.
- SVG del mapa validado con `xmllint`.
- Rutas de error y de detalle abiertas directamente para comprobar que el estado se reconstruye desde la URL.

## 11. Limitaciones declaradas

- Los datos viven en memoria y se pierden al recargar; no hay API ni base de datos.
- La emisión es una simulación con `setTimeout`; no usa `Idempotency-Key` real.
- No hay autenticación ni control de permisos en el prototipo; el contexto «Dra. Méndez · Médico» es fijo.
- No se implementa cola offline ni sincronización en segundo plano.
- La cancelación se permite desde estados no finales para poder demostrar el flujo; la política definitiva debe validarse con negocio.
- La validación de accesibilidad es heurística; falta la auditoría formal.

## 12. Criterios de aceptación

1. Existe un mapa de navegación que cubre bandeja, emisión, confirmación, detalle, error y cancelación.
2. El flujo feliz se completa sin escribir URLs manualmente y termina en el detalle de la orden creada.
3. El error crítico muestra mensaje, código, HTTP y `request_id`, y ofrece una salida.
4. Las nueve capturas existen, se ven sin texto duplicado y corresponden a las rutas indicadas.
5. A 390 px no hay scroll horizontal y las acciones principales son alcanzables.
6. El diálogo de cancelación exige motivo y puede cerrarse sin cancelar.

## 13. Declaración de IA

[`DECLARACION_IA.md`](./DECLARACION_IA.md), con validación humana pendiente.
