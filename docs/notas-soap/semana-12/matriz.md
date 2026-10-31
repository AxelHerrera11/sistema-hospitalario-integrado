# Matriz decisión → RF/RNF → evidencia (Semana 12)

Cada decisión de UX, accesibilidad o movilidad se liga al requisito que protege ([RF/RNF de la Semana 2](../semana-02/rf-rnf-criterios.md)) y a la evidencia que otra persona puede repetir. **Estado:** *Prototipo* = se ve y se prueba en `prototipo.html`; *Diseño* = está en los wireframes de la [Semana 8](../semana-08/wireframes.md) y no en el prototipo.

| # | Decisión | RF / RNF | Evidencia verificable | Estado |
|---|---|---|---|---|
| 1 | Editor por pasos en móvil; validación al pulsar, sin botón deshabilitado (B-06) | RF-SOAP-01, 02 | `#/editor/0`: «Revisar y registrar» vacío muestra el error y enfoca el primer campo | Prototipo |
| 2 | Contador a 4000, ámbar `#92400e` con texto «Quedan N» (B-10) | RF-SOAP-02 | `maxlength="4000"` y contador desde 3600; contraste 7.09:1 ([S9](../semana-09/README.md)) | Prototipo |
| 3 | Confirmación que repite nombre, expediente y fecha de nacimiento (B-01) | RF-SOAP-01 · RNF-SOAP-03 | `#/confirmar`: el diálogo contiene los tres datos | Prototipo |
| 4 | Acciones según rol: enfermería sin controles de edición, ni deshabilitados | RF-SOAP-03 · RNF-SOAP-01 | Rol «Enfermería» en `#/episodio`: no hay botón de edición en el DOM | Prototipo (la UI refleja; el servidor decide) |
| 5 | Sin asignación: «No atiende este episodio» y sin texto clínico | RF-SOAP-03 · RNF-SOAP-01 | Wireframe 4 y escenario 2 de [S10](../semana-10/escenarios.md) | Diseño |
| 6 | Diagnóstico solo tras registrar la nota (B-02) | RF-SOAP-04 | `#/exito` es el único camino a `#/diagnostico`; el editor no tiene búsqueda | Prototipo |
| 7 | Un grupo de radios en español; «Secundario» por defecto (B-05) | RF-SOAP-04 | `#/diagnostico`: un `fieldset` con tres radios; valores internos `primary`, `secondary`, `differential` | Prototipo |
| 8 | «Principal» deshabilitado con su razón si ya existe uno (B-09) | RF-SOAP-05 | Asociar un principal y elegir otro código: «Ya hay un diagnóstico principal», enlazado con `aria-describedby` | Prototipo |
| 9 | Catálogo caído: búsqueda bloqueada, nunca «sin diagnósticos» | RF-SOAP-06 · RNF-SOAP-03 | Wireframe 2 y flujo 1 de [S8](../semana-08/README.md); falta en el prototipo | Diseño |
| 10 | Error `NOTE_SIGNED` con `role="alert"`; el diagnóstico no se reintenta solo | RF-SOAP-07 | Casilla «Simular error crítico»; el título recibe el foco | Prototipo |
| 11 | Nota firmada sin edición; la corrección es una versión nueva con motivo obligatorio | RF-SOAP-07 · RNF-SOAP-02 | `#/corregir`: sin motivo no se crea; con motivo, v2 vigente y v1 «reemplazada» | Prototipo (backend propuesto) |
| 12 | Autoría con nombre y rol, sin tratamiento de género (B-12) | RF-SOAP-03 | Cada pantalla: «Autoría: López, A. · Médico tratante» | Prototipo |
| 13 | El texto clínico vive solo en memoria | RNF-SOAP-05 | `grep -cE "localStorage\|sessionStorage\|indexedDB" prototipo.html` devuelve `0` | Prototipo |
| 14 | `trace_id` en cada error, sin detalle interno de la excepción | RNF-SOAP-05, 01 | Panel de `#/error`: código `NOTE_SIGNED` y `trace_id` a 14 px | Prototipo |

## Requisito que falta: accesibilidad

La Semana 2 define RNF-SOAP-01 a 06 y ninguno cubre accesibilidad ni uso móvil, aunque las decisiones 1, 2 y 7 dependen de ella. Se propone **RNF-SOAP-07 (Accesibilidad y movilidad):** *cada pantalla del flujo cumple WCAG 2.2 AA y se usa sin desplazamiento horizontal en 320 px.* Criterio: axe-core sin violaciones *serious* o *critical* y recorrido completo con teclado a 320, 360 y 430 px. Es una propuesta: no modifica los documentos de la Semana 2.
