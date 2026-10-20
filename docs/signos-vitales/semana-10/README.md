# Signos vitales y alertas por valores anormales — Semana 10: diseño para movilidad

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales · **Entrega:** propuesta responsive, reglas de breakpoint y dos escenarios móviles.

## Ficha de la asignación

- **Semana:** Semana 10 — Diseño para movilidad
- **Tarea individual:** Adapte «captura de signos vitales, evaluación de umbrales y generación de alerta» a móvil (320–430 px): jerarquice los datos y acciones de signos vitales, unidades, umbrales, valores anormales y alertas clínicas, reduzca carga cognitiva y defina navegación, tablas/formularios, confirmaciones y recuperación ante conexión limitada.
- **Entregable / evidencia:** propuesta responsive de 3–5 pantallas, reglas de breakpoint y dos escenarios móviles con decisiones de contenido y error.
- **Issue:** Refs #23
- **Alcance:** diseño. **No se implementa código**: parte de los wireframes de la [Semana 8](../semana-08/wireframes.md) y aplica las correcciones P0 y P1 del [backlog de la Semana 9](../semana-09/backlog.md). El prototipo navegable es de la Semana 11.

## Quién usa el móvil y para qué

Quien captura junto a la cama, de pie, muchas veces con una sola mano y señal irregular. Su tarea es corta (anotar 3 a 6 valores), urgente (puede haber una alerta crítica) y debe poder interrumpirse sin perder lo escrito. De ahí salen tres principios:

1. **Lo urgente primero.** En un resultado, la severidad va antes que cualquier valor; en un error, lo que el médico debe hacer va antes que el código.
2. **Una decisión por pantalla.** La captura va por pasos cortos; cada paso muestra un grupo de signos relacionados.
3. **Nunca fingir.** Sin conexión no se muestra «registrado» ni «sin alertas»: se dice qué se sabe y qué no.

## Reglas de breakpoint

| Ancho | Nombre | Cambios |
|---|---|---|
| 320–359 px | Compacto | Una columna. Etiquetas encima del campo. El selector de unidad pasa a otra línea. Sin texto secundario fuera de la ayuda plegable. |
| 360–429 px | Móvil | Una columna. Valor y unidad en la misma línea (unidad como botones segmentados). |
| 430–767 px | Móvil grande y tablet vertical | Una columna con ancho máximo de 560 px centrado. Dos signos por fila solo si caben ≥ 160 px por campo. |
| ≥ 768 px | Escritorio | Los tres grupos de signos en una sola página, dos columnas; la confirmación es un diálogo centrado. |

Reglas comunes:

- **Objetivos táctiles** de 44 × 44 px como mínimo (B-11 de la Semana 9) y 8 px entre ellos.
- **Texto de campo** de 16 px o más (evita el zoom automático de iOS). Las etiquetas, 14 px mínimo.
- **Teclado numérico** con `inputmode="decimal"` y entrada con coma o punto (B-02).
- **Barra de paciente fija** arriba (nombre, edad, episodio) y **barra de acción fija** abajo, con la acción principal a la derecha y `safe-area-inset-bottom`.
- **Sin desplazamiento horizontal** a 320 px ni con zoom al 400 %. Ninguna información depende del hover.
- **Orientación:** funciona en vertical y horizontal; no se bloquea.

## Jerarquía de datos y acciones

| Nivel | Qué es | Dónde |
|---|---|---|
| 1 | Paciente y episodio correctos | Barra fija superior, siempre visible |
| 2 | Severidad global del resultado («Crítica», «Advertencia», «Sin valores fuera de umbral», «No evaluado») | Primera línea del resultado, con ícono y texto |
| 3 | Signos fuera de umbral, ordenados de mayor a menor severidad | Debajo de la severidad |
| 4 | Signos sin alerta y signos sin umbral (peso, talla) | Plegados detrás de «Ver los demás» |
| 5 | `code`, `trace_id`, perfil de umbrales | Letra de 14 px al final, con «Copiar referencia» |

La acción principal de cada pantalla es una sola y está en la barra inferior; las secundarias van como enlaces o dentro de «Más».

## Navegación

Una pila corta con retroceso predecible: **Episodio → Captura (3 pasos) → Confirmar → Resultado → Mediciones**.

- «Atrás» del sistema vuelve al paso anterior; en la confirmación, a la captura sin perder lo escrito.
- Tras un resultado, «Atrás» no vuelve a la captura: va al episodio, para no reenviar la medición.
- El rol decide qué se ve: enfermería no tiene «Nueva medición», no ve botones deshabilitados y llega a «Mediciones».

## Tablas y formularios

- **Tablas → tarjetas.** La lista de mediciones no es una tabla: cada medición es una tarjeta con la severidad arriba, la fecha y los signos fuera de umbral; el resto está plegado.
- **Formulario por pasos.** Paso 1: frecuencia cardiaca, saturación, frecuencia respiratoria y presión. Paso 2: glucosa y temperatura, con su unidad. Paso 3: peso, talla y fecha. Un indicador «Paso 2 de 3» y «Atrás» sin perder datos.
- **Unidad como control segmentado.** En glucosa y temperatura, dos botones visibles (`mg/dL` | `mmol/L`; `°C` | `°F`) sin valor por defecto: obliga a elegir y deja la unidad a la vista (B-01).
- **Validación junto al campo**, con resumen arriba y el foco en el primer error.

## Confirmaciones

La confirmación es una hoja a pantalla completa (no un cuadro pequeño): paciente completo, cada valor con su unidad y su conversión, y la advertencia «Si hay un valor fuera de umbral se notificará a los médicos tratantes». «Corregir» a la izquierda, «Registrar» a la derecha. El foco cae en «Corregir», no en «Registrar».

## Recuperación ante conexión limitada

| Situación | Qué hace la UI |
|---|---|
| Sin red antes de enviar | Banner «Sin conexión»; «Registrar» queda habilitado solo para pedir reintento; lo escrito se conserva en memoria. |
| La petición no responde | «No sabemos si se guardó. No se confirmó que se notificara a los tratantes: si hay un valor crítico, avise usted directamente. No repita el envío.» + «Revisar mediciones». |
| «Revisar mediciones» no encuentra la medición | Habilita «Registrar de nuevo», porque ya se verificó que no existe. |
| `500` / `503` | «No se registró nada» y reintento manual. |
| Conexión lenta | «Registrando…» con el botón deshabilitado; sin límite que provoque un reenvío automático. |

El `POST` no es idempotente: **no se reintenta solo**. La solución de fondo es la cabecera `Idempotency-Key` propuesta en la [Semana 5](../semana-05/frontera-microservicio.md): con ella el cliente podría reenviar sin duplicar. Hasta entonces, la verificación la hace la persona. Los valores no se guardan en `localStorage`; el riesgo de que el navegador descarte la pestaña en segundo plano se reduce con pasos cortos y se resolvería con un borrador en el servidor (pendiente de Javier).

## Qué correcciones de la Semana 9 incorpora

| Corrección | Dónde |
|---|---|
| B-01 unidad sin valor por defecto | Control segmentado del paso 2 |
| B-02 coma decimal | Teclado y entrada con coma o punto |
| B-03 paciente en la confirmación | Barra fija y hoja de confirmación |
| B-04 / B-05 anuncio y foco | Resultado con `role="alert"` y foco en el título |
| B-06 contraste | Tokens `#B91C1C`, `#B45309`, `#15803D`, `#4B5563` |
| B-08 pérdida de la medición | Pasos cortos y aviso al salir |
| B-11 reflujo y objetivos | Reglas de breakpoint |

## Archivos

- [Pantallas responsive](./pantallas.md): cinco pantallas a 360 px y su variante de escritorio.
- [Escenarios móviles](./escenarios.md): dos escenarios con decisiones de contenido y de error.
- [Declaración de uso de IA](./DECLARACION_IA.md)
