# Notas SOAP y diagnósticos — Semana 12: segunda evaluación parcial

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** presentación breve, recorrido navegable, matriz decisión → RF/RNF → evidencia y respuesta a un cambio solicitado.

## Ficha de la asignación

- **Semana:** Semana 12 — Segunda evaluación parcial
- **Tarea individual:** Defienda las decisiones de componentes, UX, accesibilidad y movilidad de Notas SOAP y diagnósticos. Demuestre con el prototipo cómo «registro de nota SOAP y asociación de diagnóstico clínico» trata roles, estados, errores y riesgos asociados con notas SOAP, diagnósticos, autoría clínica, versionado y expediente.
- **Entregable / evidencia:** presentación breve, recorrido navegable, matriz decisión → RF/RNF → evidencia y respuesta documentada a un cambio solicitado.
- **Issue:** Refs #22
- **Alcance:** evaluación y defensa. El prototipo es el de la [Semana 11](../semana-11/README.md) con **una pantalla nueva**: la corrección de una nota firmada, que es la respuesta práctica al cambio.

## Tesis

**La nota SOAP es un documento clínico con autoría, versión y firma, y la interfaz tiene que decirlo en cada pantalla.** De ahí salen cuatro decisiones, una por semana de diseño:

1. **UX (S8):** el paciente, el estado y la autoría van siempre a la vista; las acciones dependen del rol y del estado de la nota.
2. **Accesibilidad (S9):** los errores se anuncian, el color nunca es la única señal y nada se deshabilita sin explicar.
3. **Movilidad (S10):** un campo por pantalla, hoja inferior para confirmar y ningún texto clínico en el dispositivo.
4. **Prototipo (S11–12):** se demuestra el camino feliz, un error crítico (`NOTE_SIGNED`) y la salida correcta: una versión nueva con motivo.

## Recorrido navegable

Abrir [`prototipo.html`](./prototipo.html) en el navegador (sin instalación). Cada paso tiene una URL de atajo con datos de ejemplo; el recorrido completo dura unos cuatro minutos.

| # | Qué se demuestra | Cómo llegar | Captura (desktop · móvil) |
|---|---|---|---|
| 1 | Episodio sin nota; el rol decide la acción | `prototipo.html#/episodio` | [desktop](../semana-11/capturas/desktop-01-episodio-vacio.png) · [móvil](../semana-11/capturas/movil-01-episodio-vacio.png) |
| 2 | Editor: cuatro secciones, validación al pulsar | «Nueva nota» → «Revisar y registrar» vacío | [desktop](../semana-11/capturas/desktop-02-editor.png) · [móvil](../semana-11/capturas/movil-02-editor.png) |
| 3 | Confirmación con paciente completo | `?demo#/confirmar` | [desktop](../semana-11/capturas/desktop-03-confirmar.png) · [móvil](../semana-11/capturas/movil-03-confirmar.png) |
| 4 | Diagnóstico: tipo en español, «Principal» con razón | `?demo#/diagnostico` | [desktop](../semana-11/capturas/desktop-04-diagnostico.png) · [móvil](../semana-11/capturas/movil-04-diagnostico.png) |
| 5 | **Error crítico:** nota firmada mientras se asociaba | casilla «Simular error crítico» → «Asociar» | [desktop](../semana-11/capturas/desktop-06-error-nota-firmada.png) · [móvil](../semana-11/capturas/movil-06-error-nota-firmada.png) |
| 6 | **Salida (nueva):** versión corregida con motivo | «Ver versiones» → «Crear versión corregida» | [desktop](./capturas/desktop-09-corregir.png) · [móvil](./capturas/movil-09-corregir.png) |
| 7 | Versión 2 vigente y versión 1 reemplazada | `?demo=v2#/versiones` | [desktop](./capturas/desktop-10-versiones-v2.png) · [móvil](./capturas/movil-10-versiones-v2.png) |
| 8 | Mismo dato, rol enfermería (solo lectura) | `?demo=dx&rol=enfermeria#/episodio` | [desktop](../semana-11/capturas/desktop-08-enfermeria.png) · [móvil](../semana-11/capturas/movil-08-enfermeria.png) |

## Matriz, cambio y presentación

- [Matriz decisión → RF/RNF → evidencia](./matriz.md): 14 decisiones, con el requisito que defienden y dónde se ven.
- [Respuesta al cambio solicitado](./cambio-solicitado.md): «corregir la errata editando la nota firmada».
- [Presentación breve y preguntas preparadas](./presentacion.md).

## Qué aporta el prototipo de esta semana

Respecto a la Semana 11 se añadió `#/corregir`: pide un motivo obligatorio (error junto al campo, `role="alert"` y foco en él), crea la **versión 2 vigente** y deja la versión 1 «reemplazada por la versión 2». El botón solo existe para el médico y mientras no haya versión 2. Se probó en Chrome: sin motivo, el foco queda en el campo y aparece el aviso; con motivo, la lista muestra las dos versiones.

## Riesgos que sigo defendiendo como abiertos

| # | Riesgo | Dónde queda |
|---|---|---|
| 1 | El prototipo no cubre `403 NOT_TREATING_PHYSICIAN` ni «catálogo caído»: están solo en los wireframes de la Semana 8 | [matriz](./matriz.md), filas 5 y 9 |
| 2 | Sin lector de pantalla ni axe | Regresión de ASII-28 |
| 3 | Borrador en servidor (`clinical_note_drafts`) | Pendiente de Javier (ASII-09) |
| 4 | La unicidad de versión por expediente (R-06) impide la cadena de correcciones | Petición de la Semana 6 a Javier |
| 5 | No existe un RNF de accesibilidad en la Semana 2 | Se propone `RNF-SOAP-07` en la [matriz](./matriz.md) |

## Archivos

- [Prototipo navegable](./prototipo.html) y [capturas nuevas](./capturas/)
- [Matriz](./matriz.md) · [Cambio solicitado](./cambio-solicitado.md) · [Presentación](./presentacion.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)

> Los enlaces a las Semanas 2, 8, 9, 10 y 11 resuelven cuando los PR #65, #427, #441, #525 y #528 estén integrados en `develop`; esta rama solo añade la evaluación de la Semana 12.
