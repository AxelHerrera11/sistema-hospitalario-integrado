# Escenarios móviles — Signos vitales y alertas (Semana 10)

Dos escenarios de uso real en móvil. Cada uno fija las **decisiones de contenido** (qué se muestra primero y qué se pliega) y las **decisiones de error** (qué pasa cuando algo falla y qué debe hacer la persona).

## Escenario 1 — Paciente inestable, una mano, buena conexión

**Contexto.** Una médica tratante, de pie junto a la cama, con el móvil en una mano (390 × 844). Mide FC 138 y saturación 94 %. Quiere registrar rápido y saber si el equipo fue avisado.

| Paso | Pantalla | Qué hace | Decisión de contenido |
|---|---|---|---|
| 1 | Episodio | Pulsa «Nueva medición» (barra inferior, a la altura del pulgar) | La barra de paciente confirma a quién mide |
| 2 | M1 paso 1 | Escribe 138 y 94 con teclado numérico | Solo 4 signos principales; glucosa y temperatura quedan en otro paso |
| 3 | M1 paso 3 | Revisa la fecha (por defecto «ahora», visible y editable) | Peso y talla opcionales, plegados |
| 4 | M2 | Confirma | Paciente completo antes de los valores; sin glucosa, no hay selector de unidad que confundir |
| 5 | M3 | Ve «ALERTA CRÍTICA» | Severidad en la primera línea; FC y SpO2 por gravedad; «Se notificó a los médicos tratantes» |

**Decisiones de contenido**

- La alerta ocupa la parte alta de la pantalla: es lo primero que se ve sin desplazarse en 320 px.
- Los valores no aparecen en la notificación que reciben los otros médicos; la persona que registra sí los ve.
- «Ver los demás signos» queda plegado: reduce la carga cognitiva cuando lo urgente es otra cosa.

**Variante de error: valor imposible.** La médica escribe 1380 por error en FC.

| Qué | Decisión |
|---|---|
| Mensaje | `VITAL_SIGN_IMPLAUSIBLE`: «El valor no es posible para este signo. Revise el valor y la unidad.» (sin repetir el número) |
| Dónde | Junto al campo y en un resumen arriba; el foco va al campo; el teclado numérico sigue abierto |
| Qué se conserva | Todo lo escrito; solo se corrige el campo |
| Qué no pasa | No se guarda nada y no se notifica a nadie |

## Escenario 2 — Pasillo con señal débil, glucosa en mmol/L y la conexión cae al enviar

**Contexto.** Un médico tratante en un pasillo con poca señal mide glucosa 2,9 mmol/L (52,2 mg/dL, valor crítico). Elige `mmol/L`, confirma y pulsa «Registrar»; la conexión se corta.

| Paso | Pantalla | Qué ve | Decisión |
|---|---|---|---|
| 1 | M1 paso 2 | Glucosa 2,9 con `mmol/L` elegido | La unidad se elige a la vista; si hubiera dejado `mg/dL`, aparecería el aviso «2,9 es raro en mg/dL» |
| 2 | M2 | «2,9 mmol/L → 52,2 mg/dL» | La conversión se muestra antes de enviar |
| 3 | M4 | «NO SABEMOS SI SE GUARDÓ» | Dice qué se sabe y qué no, y qué hacer |
| 4 | Mediciones | «Revisar mediciones» no encuentra la de las 08:30 | Se habilita «Registrar de nuevo» (ya verificado) |
| 5 | M3 | Registra de nuevo y ve «ALERTA CRÍTICA» | Resultado normal |

**Decisiones de error**

| Decisión | Por qué |
|---|---|
| El mensaje empieza por lo que no se sabe y por el riesgo clínico: «No se confirmó que se haya notificado a los médicos tratantes. Si hay un valor crítico, avise usted directamente.» | Con un valor crítico, la prioridad es que alguien actúe, no que el sistema funcione. |
| **No se reintenta solo** | El `POST` no es idempotente: reintentarlo podría duplicar la medición y la alerta. |
| La única acción es «Revisar mediciones»; «Registrar de nuevo» solo aparece si la revisión no encuentra la medición | Convierte la incertidumbre en un dato antes de repetir. |
| Si la revisión **sí** la encuentra | Se muestra su resultado y no se ofrece reintentar; se pasa a M3. |
| Si la revisión tampoco responde | «Sin conexión. Anote los valores y avise al equipo»; lo escrito se conserva en memoria. |
| Se muestra el `trace_id` y «Copiar referencia» | Soporte puede buscarlo en la bitácora. |
| No se guarda nada en `localStorage` | Es dato clínico (RNF); el costo es que cerrar la pestaña lo pierde. |

**Mejora propuesta.** Con la cabecera `Idempotency-Key` (Semana 5), el cliente reenviaría con la misma clave y el servidor descartaría el duplicado, sin la revisión manual. Requiere cambio en el servidor y se deja como propuesta.

## Resumen de decisiones

| Tema | Decisión |
|---|---|
| Carga cognitiva | Tres pasos cortos, severidad primero, lo secundario plegado. |
| Unidades | Control segmentado sin valor por defecto; conversión visible antes de enviar. |
| Alertas | Texto y forma, nunca solo color; no desaparecen solas. |
| Conexión limitada | Decir lo que no se sabe, no reintentar solo, verificar antes de repetir. |
| Roles | Enfermería sin botones de captura; ni siquiera deshabilitados. |
