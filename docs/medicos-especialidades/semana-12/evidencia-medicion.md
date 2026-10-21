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

El hallazgo se registra como **H10** (severidad media, área todas): el contorno de foco de 3 px que la Semana 9 pidió (H9) existía y cumplía 2.4.7, pero su color no alcanzaba el mínimo de 3:1 de **1.4.11 Contraste no textual**, de modo que quien usa teclado no distingue con suficiente claridad dónde está.

**Corrección:** se cambió la variable `--foco` de `#f59e0b` a `#b45309` en el prototipo de la [Semana 11](../semana-11/prototipo/index.html), en el commit `fix(semana-11): corregir el contraste del indicador de foco medido en la Semana 12 (ASII-04)` del PR #408. El color se sigue distinguiendo del azul de los controles (`#1d4ed8`) y mantiene la identidad visual ámbar del anillo de foco.

| Indicador de foco | Fondo | Razón | Mínimo | Criterio | Resultado |
|---|---|---:|---:|---|---|
| `#b45309` | `#f6f7fb` | 4,69:1 | 3,0:1 | 1.4.11 | Cumple |
| `#b45309` | `#ffffff` | 5,02:1 | 3,0:1 | 1.4.11 | Cumple |

## 3. Contraste medido (WCAG 1.4.3 y 1.4.11)

| Grupo de usos | Razón medida | Mínimo | Resultado |
|---|---:|---:|---|
| Texto del cuerpo y de las tarjetas (16 px) | 13,32:1 y 14,26:1 | 4,5:1 | Cumple |
| Texto de ayuda (14 px) y `trace_id` (13 px) | 5,93:1 | 4,5:1 | Cumple |
| Texto de ayuda y del simulador sobre la banda ámbar | 5,33:1 | 4,5:1 | Cumple |
| Botón principal y franja seleccionada | 6,70:1 | 4,5:1 | Cumple |
| Mensaje de error y mensaje de éxito | 6,57:1 y 7,13:1 | 4,5:1 | Cumple |
| Título del encabezado sobre la barra oscura | 14,26:1 | 3,0:1 | Cumple |
| Bordes de campo, de franja y de la acción principal | 5,93:1 a 6,26:1 | 3,0:1 | Cumple |
| Indicador de foco (corregido) | 4,69:1 y 5,02:1 | 3,0:1 | Cumple |

Medidos **16 pares de color: 16 cumplen y 0 no cumplen**. El guion imprime la tabla completa par por par.

## 4. Verificación de roles, estados, errores, accesibilidad y reflow

| Bloque | Comprobaciones | Resultado | Qué demuestra |
|---|---:|---|---|
| Roles y permisos (R1–R8) | 8 | 8 cumplen | El administrador ve el alta, la asociación y la licencia; la recepcionista no ve ni el alta ni la licencia; el médico ve «Mi disponibilidad»; el acceso sin permiso responde 403 |
| Estados (E1–E4) | 4 | 4 cumplen | Vacío, médico sin horario visible, alta confirmada y el aviso «Elegir no reserva» |
| Errores (X1–X6) | 6 | 6 cumplen | 409 de licencia, 409 de principal única, conflicto de agenda con `trace_id`, alta sin respuesta, consulta sin respuesta y modo sin conexión |
| Accesibilidad (A1–A6) | 6 | 6 cumplen | `radiogroup` con nombre, `role="alert"`, `role="status"`, foco de 3 px, contorno en la franja y objetivos de 44 px |
| Tamaño de texto y reflow (T1–T3) | 3 | 3 cumplen | Sin desborde horizontal al 200 % del texto, a 640 px y a 320 px, con la acción principal visible |

**27 comprobaciones, 27 cumplen.** El guion imprime la tabla completa con el valor observado de cada una.

## 5. Qué no se midió y queda pendiente

- **Lector de pantalla real (NVDA o VoiceOver):** la Semana 9 recorrió los wireframes con NVDA, pero la medición de esta semana es sobre el DOM y los atributos ARIA del prototipo, no sobre un lector de pantalla instalado. Queda para la revisión técnica formal de la Semana 13.
- **Contraste con la vista real del hospital:** la paleta del prototipo es la del módulo, no la del tema institucional definitivo; los valores se vuelven a medir cuando exista el tema final.
- **Pruebas de integración con el servidor real:** el prototipo simula las respuestas del contrato; el guion verifica la interfaz, no la conexión con `/api/v1`.
