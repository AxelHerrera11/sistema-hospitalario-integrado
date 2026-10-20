# Evidencia de la segunda evaluación parcial — medición y verificación

**Módulo:** ASII-04 · **Semana:** 12 · **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`).

Este documento deja por escrito lo que la [Semana 9](../semana-09/README.md) y la [Semana 11](../semana-11/README.md) dejaron pendiente de medir: **el contraste (1.4.3 y 1.4.11), el zoom al 200 % (1.4.4) y el reflow (1.4.10)** del prototipo, más la verificación de los caminos de rol, estado y error que se defienden en la segunda evaluación parcial.

Los dos guiones de la carpeta [`evidencia/`](./evidencia/) son reproducibles: leen el archivo real del prototipo y no dependen de ningún paquete externo (solo la biblioteca estándar de Node y Chrome instalado).

## 1. Comandos ejecutados

```bash
# contraste WCAG sobre la paleta real del prototipo
node docs/medicos-especialidades/semana-12/evidencia/medir-contraste.mjs \
  --prototipo docs/medicos-especialidades/semana-11/prototipo/index.html
# resultado: 16 pares de color medidos, 16 cumplen y 0 no cumplen (código de salida 0)

# verificación de roles, estados, errores, accesibilidad y reflow en Chrome sin interfaz
node docs/medicos-especialidades/semana-12/evidencia/verificar-prototipo.mjs \
  --prototipo docs/medicos-especialidades/semana-11/prototipo/index.html
# resultado: 27 comprobaciones ejecutadas, 27 cumplen y 0 no cumplen (código de salida 0)
```

## 2. Hallazgo de la medición y corrección

La primera medición, sobre el prototipo tal como estaba en el PR #408, **encontró un incumplimiento real**:

| Uso en pantalla | Color | Fondo | Razón | Mínimo | Criterio | Resultado |
|---|---|---|---:|---:|---|---|
| Indicador de foco sobre el fondo de la página | `#f59e0b` | `#f6f7fb` | 2,01:1 | 3,0:1 | 1.4.11 | **No cumple** |
| Indicador de foco sobre una tarjeta blanca | `#f59e0b` | `#ffffff` | 2,15:1 | 3,0:1 | 1.4.11 | **No cumple** |

El hallazgo se registra como **H10** (severidad media, área todas): el contorno de foco de 3 px que la Semana 9 pidió (H9) existed y cumplía 2.4.7, pero su color no alcanzaba el mínimo de 3:1 de **1.4.11 Contraste no textual**, de modo que quien usa teclado no distingue con suficiente claridad dónde está.

**Corrección:** se cambió la variable `--foco` de `#f59e0b` a `#b45309` en el prototipo de la [Semana 11](../semana-11/prototipo/index.html), en el commit `fix(semana-11): corregir el contraste del indicador de foco medido en la Semana 12 (ASII-04)` del PR #408. El color se sigue distinguiendo del azul de los controles (`#1d4ed8`) y mantiene la identidad visual ámbar del anillo de foco.

| Indicador de foco | Fondo | Razón | Mínimo | Criterio | Resultado |
|---|---|---:|---:|---|---|
| `#b45309` | `#f6f7fb` | 4,69:1 | 3,0:1 | 1.4.11 | Cumple |
| `#b45309` | `#ffffff` | 5,02:1 | 3,0:1 | 1.4.11 | Cumple |

## 3. Contraste medido (WCAG 1.4.3 y 1.4.11)

| Uso en pantalla | Primer plano | Fondo | Razón | Mínimo | Criterio | Resultado |
|---|---|---|---:|---:|---|---|
| Texto del cuerpo sobre el fondo de la página | `#1f2a44` | `#f6f7fb` | 13,32:1 | 4,5:1 | 1.4.3 | Cumple |
| Texto de las tarjetas y títulos de sección | `#1f2a44` | `#ffffff` | 14,26:1 | 4,5:1 | 1.4.3 | Cumple |
| Texto de ayuda y campo «ayuda» (14 px) | `#5b6478` | `#ffffff` | 5,93:1 | 4,5:1 | 1.4.3 | Cumple |
| Texto de ayuda sobre la banda del simulador | `#5b6478` | `#fef3c7` | 5,33:1 | 4,5:1 | 1.4.3 | Cumple |
| Texto de la banda del simulador | `#1f2a44` | `#fef3c7` | 12,80:1 | 4,5:1 | 1.4.3 | Cumple |
| Título del encabezado sobre la barra oscura | `#ffffff` | `#1f2a44` | 14,26:1 | 3,0:1 | 1.4.3 | Cumple |
| Botón principal y franja seleccionada | `#ffffff` | `#1d4ed8` | 6,70:1 | 4,5:1 | 1.4.3 | Cumple |
| Mensaje de error | `#b42318` | `#ffffff` | 6,57:1 | 4,5:1 | 1.4.3 | Cumple |
| Mensaje de éxito | `#166534` | `#ffffff` | 7,13:1 | 4,5:1 | 1.4.3 | Cumple |
| `trace_id` al pie del mensaje (13 px) | `#5b6478` | `#ffffff` | 5,93:1 | 4,5:1 | 1.4.3 | Cumple |
| Enlace de la barra de navegación | `#1f2a44` | `#ffffff` | 14,26:1 | 4,5:1 | 1.4.3 | Cumple |
| Borde de los campos de formulario | `#5b6478` | `#ffffff` | 5,93:1 | 3,0:1 | 1.4.11 | Cumple |
| Borde de la franja seleccionable | `#1d4ed8` | `#ffffff` | 6,70:1 | 3,0:1 | 1.4.11 | Cumple |
| Barra de la acción principal (fondo de página) | `#1d4ed8` | `#f6f7fb` | 6,26:1 | 3,0:1 | 1.4.11 | Cumple |
| Indicador de foco sobre el fondo de la página | `#b45309` | `#f6f7fb` | 4,69:1 | 3,0:1 | 1.4.11 | Cumple |
| Indicador de foco sobre una tarjeta blanca | `#b45309` | `#ffffff` | 5,02:1 | 3,0:1 | 1.4.11 | Cumple |

Medidos 16 pares de color: **16 cumplen y 0 no cumplen**.

## 4. Verificación de roles, estados, errores, accesibilidad y reflow

El guion abre el prototipo en Chrome sin interfaz y comprueba el DOM real después de cada interacción.

| # | Bloque | Comprobación | Criterio | Resultado | Valor observado |
|---|---|---|---|---|---|
| R1 | Roles y permisos | El administrador ve «+ Registrar médico» | medicos.registrar | Cumple | `true` |
| R2 | Roles y permisos | El administrador ve «Asociar especialidad» | medicos.asociar_especialidad | Cumple | `true` |
| R3 | Roles y permisos | El administrador ve el número de colegiado | RNF-MED-04 | Cumple | `true` |
| R4 | Roles y permisos | La recepcionista no ve el alta de médicos | medicos.registrar | Cumple | `true` |
| R5 | Roles y permisos | La recepcionista no ve el número de colegiado | RNF-MED-04 | Cumple | `true` |
| R6 | Roles y permisos | La recepcionista no ve «Asociar especialidad» | medicos.asociar_especialidad | Cumple | `true` |
| R7 | Roles y permisos | El médico ve «Mi disponibilidad» y no el filtro por especialidad | medicos.ver_disponibilidad | Cumple | `true` |
| R8 | Roles y permisos | Sin permiso: la recepcionista recibe el mensaje 403 | RNF-MED-01 | Cumple | `true` |
| E1 | Estados | Vacío: sin médicos vigentes para la especialidad | NO_DOCTORS_FOR_SPECIALTY | Cumple | `true` |
| E2 | Estados | Médico sin horario visible, no oculto | SCHEDULE_NOT_CONFIGURED | Cumple | `true` |
| E3 | Estados | Alta confirmada: mensaje de éxito con enlace a la ficha | 201 Created | Cumple | `true` |
| E4 | Estados | Elegir franja avisa que la reserva la confirma Citas | H2 · RF-MED-07 | Cumple | `true` |
| X1 | Errores | 409 de licencia repetida en el alta | LICENSE_ALREADY_REGISTERED | Cumple | `true` |
| X2 | Errores | Alta sin respuesta: pide buscar la licencia antes de repetir | H1 · idempotencia del alta | Cumple | `true` |
| X3 | Errores | 409 de principal única al asociar especialidad | PRIMARY_SPECIALTY_CONFLICT | Cumple | `true` |
| X4 | Errores | Error crítico: la franja tomada avisa, muestra el trace_id y desaparece de la lista | Conflicto de agenda | Cumple | `true` |
| X5 | Errores | Sin conexión: banda visible, franjas y acción principal deshabilitadas | RNF-MED-05 | Cumple | `true` |
| X6 | Errores | Consulta sin respuesta: reintento manual, sin reintentos infinitos | Cliente HTTP de la Semana 5 | Cumple | `true` |
| A1 | Accesibilidad | Las franjas son un radiogroup con nombre accesible por médico | 1.3.1 · H3 | Cumple | `Franjas de Dra. Ana López` |
| A2 | Accesibilidad | El mensaje de error se anuncia con role="alert" | 4.1.3 · H2 | Cumple | `true` |
| A3 | Accesibilidad | El estado de la pantalla se anuncia con role="status" | 4.1.3 | Cumple | `status` |
| A4 | Accesibilidad | El foco visible tiene contorno de 3 px al recorrer con Tab | 2.4.7 · H9 | Cumple | `3px` |
| A5 | Accesibilidad | La franja enfocada muestra el contorno de foco | 2.4.7 · H9 | Cumple | `3px` |
| A6 | Accesibilidad | Los objetivos táctiles miden al menos 44 px | 2.5.5 · Semana 10 | Cumple | `true` |
| T1 | Tamaño de texto y reflow | Al 200 % del tamaño de texto no hay desborde horizontal (1.4.4) | 1.4.4 | Cumple | `true` |
| T2 | Tamaño de texto y reflow | A 640 px (200 % de una pantalla de 1280) no hay desborde horizontal (1.4.10) | 1.4.10 | Cumple | `true` |
| T3 | Tamaño de texto y reflow | A 320 px no hay desborde horizontal y la acción principal sigue visible | Semana 10 · 320–430 px | Cumple | `true` |

Ejecutadas 27 comprobaciones sobre el prototipo real: **27 cumplen y 0 no cumplen**.

## 5. Qué no se midió y queda pendiente

- **Lector de pantalla real (NVDA o VoiceOver):** la Semana 9 recorrió los wireframes con NVDA, pero la medición de esta semana es sobre el DOM y los atributos ARIA del prototipo, no sobre un lector de pantalla instalado. Queda para la revisión técnica formal de la Semana 13.
- **Pruebas de contraste con la vista real del hospital:** la paleta del prototipo es la del módulo, no la del tema institucional definitivo; los valores se vuelven a medir cuando exista el tema final.
- **Errores de JavaScript en los caminos verificados:** el guion no registró excepciones en el DOM, pero no sustituye una prueba de integración con el servidor real.
