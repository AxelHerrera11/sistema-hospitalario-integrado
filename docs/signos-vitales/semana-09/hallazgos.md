# Hallazgos — evaluación de usabilidad y accesibilidad (Semana 9)

Trece hallazgos sobre el diseño de la [Semana 8](../semana-08/wireframes.md). Cada uno cita la **evidencia** (el texto o dibujo del wireframe), la heurística o criterio WCAG 2.2 incumplido y el **dominio afectado**. La corrección y su criterio verificable están en el [backlog](./backlog.md).

**Severidad:** Crítica · Alta · Media · Baja (definidas en el [README](./README.md#método)). WF1 a WF6 son los wireframes de la Semana 8.

## Resumen

| ID | Hallazgo | Pantalla | Criterio | Severidad | Dominio |
|---|---|---|---|---|---|
| H-01 | La unidad se asume en silencio y su cambio no avisa | WF1, WF2 | 3.3.4 · H5 | **Crítica** | Unidades, alertas |
| H-02 | La coma decimal no tiene regla de interpretación | WF1 | 3.3.1 · H5 · H2 | **Crítica** | Signos vitales, valores anormales |
| H-03 | El resultado de la alerta no se anuncia | WF3–WF5 | 4.1.3 · H1 | Alta | Alertas |
| H-04 | El foco tras enviar o cerrar no está definido | WF3–WF5 | 2.4.3 · 3.2.2 | Alta | Alertas |
| H-05 | La confirmación no muestra al paciente | WF2 | 3.3.4 · H5 | Alta | Signos vitales |
| H-06 | La severidad y los estados no tienen paleta con contraste | WF3–WF5 | 1.4.3 · 1.4.11 · 1.4.1 | Alta | Valores anormales, alertas |
| H-07 | Campos compuestos y unidades sin etiqueta programática | WF1 | 1.3.1 · 2.5.3 | Media | Unidades |
| H-08 | Se pierde la medición al caducar la sesión o salir | WF1, WF2 | 2.2.1 · H5 | Media | Signos vitales |
| H-09 | Fecha de medición: formato, idioma y valor por defecto | WF1 | 3.3.2 · H2 · H5 | Media | Signos vitales |
| H-10 | Carga sin estado accesible | WF6 | 4.1.3 · H1 | Media | Signos vitales |
| H-11 | Rejilla densa: reflujo y objetivos pequeños | WF1 | 1.4.10 · 2.5.8 · 1.4.4 | Media | Unidades |
| H-12 | Enfermería llega a un callejón sin salida | Flujo 2, WF6 | H9 · H7 | Media | Alertas, acceso |
| H-13 | «Dra. López» asume género a partir del nombre | WF6 | H2 | Baja | Autoría |

## Detalle

### H-01 · La unidad se asume en silencio y su cambio no avisa — Crítica

- **Evidencia:** WF1 dibuja `Glucosa [ 5,5 ] [ mmol/L ▾ ]` con una vista previa, pero la Semana 8 dice que «la unidad por defecto es la canónica». Quien escribe 5,5 de un glucómetro en mmol/L y no toca el selector envía 5,5 mg/dL. Con el código real (cambio práctico de la Semana 6) eso es una **alerta crítica falsa** a los médicos tratantes. Tampoco se define qué ocurre si el médico cambia la unidad *después* de escribir el valor: el número queda igual y se reinterpreta.
- **Incumple:** H5 (prevención de errores), WCAG 3.3.4 (datos que afectan al paciente deben poder revisarse), H1.
- **Impacto:** inventa una alerta crítica, o al revés, oculta una real si el valor mal interpretado cae en la banda normal. Es el error que el cambio de unidades quería evitar.

### H-02 · La coma decimal no tiene regla de interpretación — Crítica

- **Evidencia:** WF1 y WF2 muestran `5,5` con coma, el uso del español. El contrato de la API recibe un número JSON con punto. Nada define cómo se convierte el texto: un `parseFloat("5,5")` de JavaScript devuelve **5**, no 5,5.
- **Incumple:** WCAG 3.3.1, H5 y H2 (el sistema usa la convención del usuario).
- **Impacto:** 5,5 mmol/L queda en 5 y 138,5 en 138, en silencio. Un valor truncado cambia la banda de severidad. El error pasa la plausibilidad, así que nadie lo ve.

### H-03 · El resultado de la alerta no se anuncia — Alta

- **Evidencia:** WF3 y WF4 describen el panel de resultado y «la alerta crítica queda en pantalla», pero ninguna anotación define `role="alert"` o `role="status"`, ni anuncio de «Registrando…».
- **Incumple:** 4.1.3 (mensajes de estado), H1.
- **Impacto:** un médico con lector de pantalla o con la atención en otra parte de la pantalla no se entera de que se generó una alerta crítica.

### H-04 · El foco tras enviar o cerrar no está definido — Alta

- **Evidencia:** al pulsar «Registrar» el formulario es reemplazado por el resultado (WF3) sin que se indique dónde cae el foco. «Cerrar» y «Registrar otra» tampoco dicen a dónde lo devuelven. Tampoco se dice si `Esc` cierra el panel ni si la confirmación (WF2) se comporta como diálogo.
- **Incumple:** 2.4.3, 3.2.2, 2.1.2.
- **Impacto:** con teclado el foco queda en un botón que ya no existe; el médico vuelve a empezar y puede repetir el envío creyendo que falló.

### H-05 · La confirmación no muestra al paciente — Alta

- **Evidencia:** WF2 dice «Se guardará en el episodio EP-1 · Medido el 14/10/2026 08:30» y lista los valores, pero no repite el nombre del paciente que WF1 muestra en el encabezado («María Gómez (45 años)»).
- **Incumple:** H5, WCAG 3.3.4.
- **Impacto:** un código de episodio no identifica a una persona. Con varias pestañas, el médico confirma una medición que quizá corresponde a otro paciente, y la alerta se notifica a los tratantes equivocados.

### H-06 · La severidad y los estados no tienen paleta con contraste — Alta

- **Evidencia:** la Semana 8 pide ■ Crítica, ▲ Advertencia, ✓ Sin valores fuera de umbral y ? No evaluado, pero no define colores. Con la paleta típica, [`contraste.php`](./evidencia/contraste.php) mide **6 de 16 combinaciones por debajo del mínimo**: «Advertencia» en ámbar 500 (2.15:1), verde 600 (3.30:1), «No evaluado» en gris 400 (2.54:1), borde de campo en gris 300 (1.47:1) y texto blanco sobre ámbar (2.15:1).
- **Incumple:** 1.4.3, 1.4.11, 1.4.1 (parcial: el ícono y el texto ayudan, pero «no evaluado» y «sin valores fuera de umbral» no se distinguen por forma).
- **Impacto:** una advertencia en ámbar claro es ilegible con luz de sala o con baja visión; confundir «no evaluado» con «normal» oculta que no hubo evaluación.

### H-07 · Campos compuestos y unidades sin etiqueta programática — Media

- **Evidencia:** WF1 pone `Presión arterial [ ] / [ ] mmHg` con una sola etiqueta para dos campos, y las unidades (`lpm`, `%`, `kg`) como texto suelto a la derecha.
- **Incumple:** 1.3.1, 3.3.2, 2.5.3 (el nombre accesible debe contener el texto visible).
- **Impacto:** un lector de pantalla dice «campo de edición» dos veces y no dice la unidad; el médico no sabe si escribe en mg/dL o mmol/L.

### H-08 · Se pierde la medición al caducar la sesión o salir — Media

- **Evidencia:** la Semana 8 prohíbe guardar la medición en `localStorage` (protección de datos) y no define qué pasa con el JWT vencido ni con «atrás» o cerrar la pestaña en WF1 y WF2.
- **Incumple:** 2.2.1, H5.
- **Impacto:** el médico vuelve a tipear los signos de un paciente inestable. El riesgo es que, con prisa, omita un signo.

### H-09 · Fecha de medición: formato, idioma y valor por defecto — Media

- **Evidencia:** WF1 muestra `[ 2026-10-14 08:30 ]` en formato ISO, mientras la confirmación (WF2) dice «14/10/2026 08:30». El contrato pide `AAAA-MM-DD HH:MM:SS` y no define zona horaria. Tampoco se dice si el valor por defecto es «ahora» ni si se puede confundir con la hora real de la medición.
- **Incumple:** 3.3.2, H2, H5.
- **Impacto:** una fecha mal interpretada ordena mal la evolución del paciente; un valor por defecto «ahora» registra con hora errónea una medición tomada antes.

### H-10 · Carga sin estado accesible — Media

- **Evidencia:** WF6 y los estados de la Semana 8 hablan de un «esqueleto gris sin valores» y de «Registrando…», sin `aria-busy` ni texto de estado.
- **Incumple:** 4.1.3, H1.
- **Impacto:** el lector de pantalla no sabe si la medición cargó o falló.

### H-11 · Rejilla densa: reflujo y objetivos pequeños — Media

- **Evidencia:** WF1 coloca cuatro filas de dos columnas con selectores `▾` y botones. A 320 px o con zoom al 400 % las columnas no caben; los selectores y «Cerrar» no declaran un tamaño mínimo.
- **Incumple:** 1.4.10, 2.5.8, 1.4.4.
- **Impacto:** quien captura en una tablet junto a la cama toca el selector de unidad equivocado.

### H-12 · Enfermería llega a un callejón sin salida — Media

- **Evidencia:** el README de la Semana 8 reconoce que la consulta de enfermería termina en `403 NOT_TREATING_PHYSICIAN` mientras no se asigne a enfermería. El mensaje «pida al médico tratante» no dice a quién ni cómo.
- **Incumple:** H9, H7.
- **Impacto:** quien más mide signos vitales en la práctica no puede ver el resultado ni la alerta y no sabe qué hacer.

### H-13 · «Dra. López» asume género a partir del nombre — Baja

- **Evidencia:** WF6 dice «Registrada por: Dra. López» y la Semana 8 usa «dra.lopez» como ejemplo, sin que el perfil del médico tenga un dato de tratamiento.
- **Incumple:** H2 (lenguaje del usuario, sin suposiciones).
- **Impacto:** puede dirigirse mal a una persona; es un asunto de exactitud más que de seguridad.
