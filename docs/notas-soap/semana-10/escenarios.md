# Escenarios móviles — Notas SOAP y diagnósticos (Semana 10)

Dos situaciones reales de uso en móvil, con las decisiones de **contenido** (qué se muestra y qué no) y de **error** (qué se hace cuando falla). Usan las pantallas de [pantallas.md](./pantallas.md) y las reglas del [README](./README.md).

## Escenario 1 — Registrar la nota con señal inestable

**Actor:** médico tratante · **Dispositivo:** teléfono de 360 px, datos móviles con una barra · **Lugar:** pasillo, de pie.

**Situación:** terminó de atender a María Gómez (exp-7734, episodio EP-1) y registra la nota entre dos consultas. A mitad del envío se pierde la señal.

| Paso | Pantalla | Qué ve | Decisión |
|---|---|---|---|
| 1 | M2, pasos 1–4 | Un campo por pantalla; cabecera con paciente y fecha de nacimiento | El teclado ocupa media pantalla, por eso solo hay un campo y el contador queda sobre el teclado |
| 2 | M2, paso 3 | Banner «Sin conexión: lo escrito se conserva mientras no cierre la página» | El texto sigue en memoria; **no** se guarda en el dispositivo. «Siguiente» sigue funcionando porque no necesita red |
| 3 | M3 | Hoja de confirmación con paciente completo | Es la última comprobación de paciente equivocado (B-01) |
| 4 | M3 | Toca «Registrar nota»; el botón pasa a «Registrando…» y se deshabilita | Un doble toque no duplica la nota |
| 5 | M3 | A los 8 s: «Sigue enviando… no cierre la página» | No se reintenta el POST: no es idempotente |
| 6 | M3 | A los 20 s sin respuesta: «No sabemos si se guardó» y el botón «Comprobar» | Se prohíbe volver a pulsar «Registrar nota» hasta comprobar |
| 7 | M3 | Vuelve la señal; el banner dice «Conexión recuperada» (anunciado) y no actúa solo | El usuario decide cuándo comprobar |
| 8 | — | «Comprobar» hace un `GET` de las notas del episodio | Ver las dos salidas |

**Salida A — la nota llegó:** el `GET` devuelve la nota en borrador. La pantalla dice «La nota ya estaba registrada» con su `trace_id`, abre M1 y ofrece «Asociar diagnóstico». No se vuelve a enviar nada.

**Salida B — la nota no llegó:** el `GET` devuelve una lista vacía. La pantalla dice «No se registró nada» y habilita de nuevo «Registrar nota». El texto sigue en memoria, así que no se reescribe.

**Decisiones de contenido**

- La hoja de confirmación muestra el paciente completo y pliega las cuatro secciones: lo importante para decidir es *a quién* se registra.
- No se muestra el texto clínico en la notificación del sistema ni en el título de la pestaña.
- El `trace_id` aparece solo al terminar (éxito o error), en 14 px y con «Copiar referencia».

**Decisiones de error**

| Fallo | Mensaje | Acción |
|---|---|---|
| Sin red antes de enviar | «Sin conexión: no se registró nada» | «Registrar» no envía; el texto se conserva |
| `500 SOAP_PERSISTENCE_ERROR` | «No se registró nada; reintente» | Botón «Reintentar» habilitado (el servidor confirmó que no guardó) |
| `422 SOAP_SECTION_INVALID` | Error junto al campo | Vuelve al paso afectado con el foco en el campo |
| Se cierra la página por error | Aviso del navegador «Se perderá lo escrito» (B-04) | Solo se evita; la recuperación completa depende del borrador en servidor |
| Sesión caducada | «Su sesión venció. Inicie sesión; su texto se conserva en esta pantalla» | Reinicio de sesión en una ventana modal, sin recargar |

**Criterios verificables**

- Con «Offline» activo en el navegador, «Registrar nota» no envía y el texto sigue en la memoria.
- Con «Slow 3G», aparece el aviso a los 8 s y no se realiza un segundo `POST`.
- Con respuesta perdida, «Comprobar» emite un `GET` y nunca un `POST`.
- En 320 px no hay desplazamiento horizontal en M2 ni en M3.

## Escenario 2 — Enfermería consulta una nota corregida

**Actor:** enfermería con `notas_soap.ver` y asignación activa · **Dispositivo:** teléfono de 320 px (el más pequeño admitido), en la cabecera de la cama.

**Situación:** debe leer el plan de la nota antes de administrar el tratamiento. La nota fue firmada y luego corregida por el médico; además, el wifi de la sala se corta al abrir.

| Paso | Pantalla | Qué ve | Decisión |
|---|---|---|---|
| 1 | M1 | Esqueleto en gris, sin texto | No se muestra nada clínico mientras carga ni se reutiliza una copia guardada |
| 2 | M1 | Sin red: «No se pudo cargar. Reintentando…» | La lectura se reintenta sola (2, 4 y 8 s); no hay copia local del texto clínico |
| 3 | M1 | Vuelve la red; aparece la nota firmada | La cinta superior dice «Versión 1 · reemplazada por la versión 2» |
| 4 | M1 | Cinta con enlace «Ver la versión vigente» | Evita que se actúe sobre un plan obsoleto |
| 5 | M5 | Versión 2 vigente, motivo «error en la dosis» | Enfermería lee el motivo, pero no ve «Crear versión corregida» |
| 6 | M1 | Abre la versión 2 y desplaza hasta «Plan» | La sección Plan va abierta por defecto para su rol |

**Decisiones de contenido**

- Para enfermería, la sección **Plan** se abre primero; para el médico, la primera sección. La jerarquía sigue la tarea del rol.
- El estado «Firmada · Versión 2 · vigente» ocupa la cabecera. En 320 px el texto del chip pasa a dos líneas, nunca se recorta.
- No hay botón de edición, ni siquiera deshabilitado: el rol no tiene ese camino.
- Los diagnósticos se leen como tarjetas apiladas; el principal va primero y con la palabra «Principal».

**Decisiones de error**

| Fallo | Mensaje | Acción |
|---|---|---|
| Sin red al abrir | «No se pudo cargar. Reintentando…» | Reintento automático de la lectura; botón «Reintentar ahora» |
| `403 NOT_TREATING_PHYSICIAN` | «No atiende este episodio; pida al médico tratante» | Sin texto clínico en ningún momento |
| `404 NOTE_NOT_FOUND` | «La nota ya no está disponible» | Vuelve al episodio |
| Versión consultada ya no es la vigente | Cinta de «reemplazada» con enlace a la vigente | Nunca se esconde la versión anterior: se señala |

**Criterios verificables**

- Con la red cortada, M1 no muestra texto clínico previo ni de una consulta anterior.
- En 320 px el chip de estado se lee completo y no hay desplazamiento horizontal.
- Una versión reemplazada muestra siempre el enlace a la vigente (prueba de componente).
- Con el rol de enfermería, ningún control de edición existe en el DOM.
