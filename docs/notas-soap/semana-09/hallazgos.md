# Hallazgos — evaluación de usabilidad y accesibilidad (Semana 9)

Doce hallazgos sobre el diseño de la [Semana 8](../semana-08/wireframes.md). Cada uno cita la **evidencia** (el texto o dibujo del wireframe), la heurística o criterio WCAG 2.2 incumplido y el **dominio afectado**. La corrección y su criterio verificable están en el [backlog](./backlog.md). Método y checklist: [README](./README.md).

**Severidad:** Crítica · Alta · Media · Baja (definidas en el README).

## Resumen

| ID | Hallazgo | Pantalla | Criterio | Severidad | Dominio |
|---|---|---|---|---|---|
| H-07 | La confirmación no muestra al paciente | WF3 | 3.3.4 · H5 | **Crítica** | Expediente |
| H-01 | Diálogos sin gestión de foco | WF3, WF6 | 2.4.3 · 2.1.2 | Alta | Nota |
| H-02 | Selector de tipo duplicado y en inglés | WF2 | 4.1.2 · 3.1.2 · H2 | Alta | Diagnóstico |
| H-03 | «Continuar» deshabilitado impide ver el error | WF1 | 3.3.1 · 3.3.3 | Alta | Nota |
| H-06 | Cambios de estado sin anuncio | WF1–WF5 | 4.1.3 · H1 | Alta | Nota, diagnóstico |
| H-08 | Se pierde el borrador al salir o caducar la sesión | WF1 | 2.2.1 · H5 | Alta | Nota |
| H-09 | La nota firmada no ofrece camino de corrección | WF6 | H3 · H1 | Alta | Versionado |
| H-11 | El diagnóstico se busca antes de existir la nota | WF1 vs flujo 1 | H4 · H2 | Alta | Diagnóstico |
| H-12 | Se ofrece un segundo principal que se rechazará | WF2 | H5 | Media | Diagnóstico |
| H-04 | Contador en ámbar sin contraste ni alternativa | WF1 | 1.4.3 · 1.4.1 | Media | Nota |
| H-05 | Etiquetas implícitas y botones ambiguos | WF1, WF3 | 3.3.2 · 2.4.6 | Media | Nota |
| H-10 | Autoría con género supuesto y estado solo por color | WF4, WF6 | 1.4.1 · H2 | Media | Autoría |

## Detalle

### H-07 · La confirmación no muestra al paciente — Crítica

- **Evidencia:** el wireframe 3 dice «Se guardará en el episodio EP-1 como BORRADOR» y lista las cuatro secciones, pero no repite el nombre ni el expediente que el wireframe 1 muestra en el encabezado («María Gómez (exp-7734)»).
- **Incumple:** H5 (prevención de errores), WCAG 3.3.4 (datos que afectan al paciente deben poder revisarse antes de enviar).
- **Impacto:** un código de episodio no identifica a una persona. Con varias pestañas abiertas, el médico confirma una nota que quizá corresponde a otro paciente, y esa nota queda en el expediente equivocado. La Semana 8 evita el selector de paciente, pero no cierra el riesgo de la pestaña equivocada.

### H-01 · Diálogos sin gestión de foco — Alta

- **Evidencia:** la confirmación (WF3) y «Descartar borrador» (WF6) se dibujan como cuadros con dos botones. Ninguna anotación dice dónde cae el foco al abrir, a dónde vuelve al cerrar, si `Esc` cancela ni si el fondo queda inerte. En WF6 el orden «Cancelar … Descartar» deja el botón destructivo como último control.
- **Incumple:** 2.4.3 (orden del foco), 2.1.2 (sin trampas), 2.4.7 (foco visible), H3.
- **Impacto:** con teclado, el foco puede quedar detrás del diálogo; si cae por defecto en «Descartar», un `Enter` borra la nota sin guardar.

### H-02 · Selector de tipo duplicado y en inglés — Alta

- **Evidencia:** en WF2 cada resultado trae `[ primary | secondary | diff ]` y, debajo, otro grupo «( ) Principal (•) Secundario ( ) Diferencial». Los valores visibles están en inglés y abreviados (`diff`); el resto de la pantalla, en español.
- **Incumple:** 4.1.2 (rol y valor), 2.1.1, 3.1.2 (idioma), H2 (lenguaje del usuario), H4 (consistencia).
- **Impacto:** dos controles para el mismo dato; un lector de pantalla anuncia opciones distintas y el usuario no sabe cuál manda. Un diagnóstico mal tipado altera el diagnóstico principal de la nota.

### H-03 · «Continuar» deshabilitado impide ver el error — Alta

- **Evidencia:** WF1 dice que «Continuar» no se habilita hasta que las cuatro secciones tengan contenido. La Semana 8 dice además que «el foco vuelve al primer campo con problema».
- **Incumple:** 3.3.1 y 3.3.3: un botón deshabilitado no puede ser activado ni enfocado, así que nunca dispara la validación ni dice *cuál* sección falta.
- **Impacto:** el médico ve un botón gris sin explicación. Las dos reglas se contradicen: o el botón se deshabilita, o se valida al pulsar.

### H-06 · Cambios de estado sin anuncio — Alta

- **Evidencia:** «Registrando…», «Nota registrada (borrador)», «Diagnóstico asociado», el panel de error de WF5 y «Ningún código coincide» aparecen en pantalla; ninguna anotación define una región viva (`role="status"` / `role="alert"`). El `trace_id` va «en letra pequeña» y el código `[ DIAGNOSIS_DUPLICATED ]` aparece entre corchetes sin explicación.
- **Incumple:** 4.1.3 (mensajes de estado), H1, 1.4.4 (letra pequeña que no escala), H2.
- **Impacto:** con lector de pantalla no se entera de si la nota se guardó, lo que agrava el caso «no sabemos si se guardó» (POST no idempotente).

### H-08 · Se pierde el borrador al salir o caducar la sesión — Alta

- **Evidencia:** WF1 indica «(guardado automático: no, todavía)» y la regla «el texto vive en la memoria de la página». No hay aviso al salir, ni advertencia de caducidad de sesión.
- **Incumple:** 2.2.1 (límites de tiempo ajustables), H5, H3.
- **Impacto:** una recarga, un `Backspace` mal dirigido o una sesión vencida borran una nota de cuatro secciones escrita durante la consulta. Guardar en `localStorage` está prohibido (RNF-SOAP-05), así que la salida es el borrador en servidor o, como mínimo, un aviso.

### H-09 · La nota firmada no ofrece camino de corrección — Alta

- **Evidencia:** WF6 muestra la nota firmada con «[ Sin acciones de edición ]» y el texto «se crea una versión nueva con su motivo», pero sin botón ni enlace para hacerlo. Tampoco muestra si existe una versión posterior, ni distingue la vigente de las anteriores.
- **Incumple:** H3 (control del usuario), H1 (visibilidad), H7 (flexibilidad).
- **Impacto:** el médico encuentra un error en una nota firmada y no ve cómo corregirlo; el lector puede tomar una versión anterior por la vigente.

### H-11 · El diagnóstico se busca antes de existir la nota — Alta

- **Evidencia:** WF1 muestra `[ + Buscar diagnóstico ]` dentro del editor de una nota aún no registrada, y WF3 confirma «Diagnósticos (ninguno todavía)». El flujo 1 de la Semana 8 solo permite asociar tras el `201` de la nota, y el botón principal de WF1 se llama «Continuar» mientras WF3 y el flujo lo llaman «Registrar».
- **Incumple:** H4 (consistencia), H2.
- **Impacto:** el médico busca y elige un diagnóstico que el sistema no puede guardar todavía; o espera que se guarde junto con la nota.

### H-12 · Se ofrece un segundo principal que se rechazará — Media

- **Evidencia:** WF2 muestra la opción «Principal» siempre disponible, aunque `PRIMARY_DIAGNOSIS_EXISTS` (409) existe precisamente porque la nota ya puede tener uno.
- **Incumple:** H5 (mejor prevenir que informar).
- **Impacto:** el error se descubre tras enviar, cuando podía evitarse deshabilitando «Principal» con la razón visible.

### H-04 · Contador en ámbar sin contraste ni alternativa — Media

- **Evidencia:** WF1 pasa el contador a ámbar a los 3600 caracteres. Un ámbar típico (`#F59E0B`) sobre blanco da 2.15:1 y el estado solo se distingue por color. El esqueleto de carga en gris (`#9CA3AF`, 2.54:1) tampoco alcanza 3:1 para componentes gráficos si se usa como borde.
- **Incumple:** 1.4.3, 1.4.11, 1.4.1.
- **Impacto:** un médico con baja visión o daltonismo no percibe que se acerca al límite de 4000; la sección rechazada llega como `SOAP_SECTION_INVALID`.

### H-05 · Etiquetas implícitas y botones ambiguos — Media

- **Evidencia:** en WF1 las etiquetas «Subjetivo*» y la pista «¿Qué refiere el paciente?» comparten la misma línea que el campo, sin indicar qué significa `*`. En WF3 la acción se llama «Registrar nota» y el botón de WF1, «Continuar». Las filas de WF1–WF2 son de ancho fijo.
- **Incumple:** 1.3.1, 3.3.2, 2.5.3, 2.4.6, 1.4.10 (reflujo a 320 px).
- **Impacto:** quien usa lector de pantalla oye campos sin nombre ni obligatoriedad; en pantallas estrechas o con zoom 400 % las filas obligan a desplazarse en dos ejes.

### H-10 · Autoría con género supuesto y estado solo por color — Media

- **Evidencia:** WF4 y WF6 escriben «Autor: Dra. López», «Autora: Dra. López» y «Autor: usted» según la pantalla; el estado usa el punto «●» ante «Borrador» y «FIRMADA» sin otra señal que su color.
- **Incumple:** H2, 1.4.1; el género se deduce del nombre del autor, que el sistema no registra.
- **Impacto:** puede mostrar un tratamiento incorrecto en un documento clínico. La señal del estado debe llevar texto e ícono, no solo color.
