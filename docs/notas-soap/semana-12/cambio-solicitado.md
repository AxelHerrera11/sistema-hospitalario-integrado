# Respuesta al cambio solicitado (Semana 12)

## El pedido

> «El médico encuentra una errata en una nota ya firmada. Queremos que la **edite directamente**, sin crear una versión nueva, para no perder tiempo en consulta. En el teléfono, que sea de dos toques.»

Es un cambio plausible preparado para la defensa: pide ahorrar pasos en la corrección y toca nota, diagnóstico, autoría, versionado y expediente a la vez.

## Decisión

**No se acepta la edición directa; sí se acepta el objetivo (corregir rápido).** La respuesta es acortar el camino de la *versión nueva*, no eliminarla.

| Razón | Evidencia |
|---|---|
| Editar una nota firmada destruye la evidencia de lo que el médico vio y decidió | RF-SOAP-07: una nota firmada no acepta cambios; trigger `clinical_notes_signed_immutable` (Semana 6) |
| El diagnóstico cuelga de la nota: cambiarla sin versión deja diagnósticos que no corresponden a ningún texto firmado | RNF-SOAP-02 (integridad), trigger `diagnoses_note_episode_author_trigger` |
| La autoría se volvería ambigua: no se sabría quién cambió qué | Semana 6: «la autoría clínica es una cadena, no un campo» |
| Tampoco hay forma de que la UI lo permita: el servidor responde `409 NOTE_SIGNED` | Contrato de la Semana 5; el error está en el [prototipo](./prototipo.html) |

## Qué se propone en su lugar

Una corrección de **tres pasos** con el texto de la versión 1 precargado: *Versiones → Crear versión corregida → motivo → Crear versión 2*. Ya existe en el prototipo (`#/corregir`) con el motivo obligatorio y la versión 1 marcada como «reemplazada por la versión 2». Lo que falta para cumplir «dos toques» es **precargar** el texto de la versión 1 en el editor y reducir el motivo a una frase corta; está propuesto, no implementado.

## Impacto por área

| Área | Cambio | Esfuerzo |
|---|---|---|
| **UX** | El botón «Crear versión corregida» sale también en la lectura de la nota firmada, no solo en Versiones | S |
| **Componentes** | `SoapNoteEditorPage` se reutiliza en modo «corregir»: mismas cuatro secciones, texto precargado y campo de motivo. No hay componente nuevo | M |
| **Accesibilidad** | Motivo con etiqueta y error en `role="alert"`; el foco entra al primer campo; el aviso «La versión 1 no se modifica» va antes del formulario | S |
| **Móvil** | La corrección es una pantalla por pasos como el alta; barra inferior con «Cancelar» y «Crear versión 2» | S |
| **Contrato (S5)** | Se usa el endpoint propuesto de corrección, que exige `motivo` y devuelve la versión nueva. Es aditivo, sigue en `/api/v1` | M |
| **Base de datos** | `supersedes_id` y `version_no` ya existen en el modelo. **No hay entidad nueva.** Depende de la unicidad por cadena (R-06), ya pedida a Javier | — |
| **Expediente** | La versión 2 se ancla al mismo expediente y episodio; el paciente sigue en la barra fija | — |

## Qué no cambia

La inmutabilidad de la versión firmada, la falla segura del catálogo, el control en dos niveles (permiso y médico tratante), el texto clínico solo en memoria y el `trace_id` en cada error.

## Criterios verificables

- Con la nota firmada, ningún camino de la UI muestra campos editables sobre la versión 1.
- «Crear versión 2» sin motivo no crea nada, muestra el error y deja el foco en el campo.
- Con motivo, la lista muestra «Versión 2 · vigente» y «Versión 1 · reemplazada por la versión 2».
- Un rol sin `notas_soap.corregir` (enfermería) no ve el botón.
- Cuando exista el endpoint, una segunda corrección sobre la versión 1 responde `409`, no crea una rama.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Dos médicos corrigen a la vez | Actualización condicionada en el servidor: la segunda recibe `409` y la UI lo muestra como el error de la Semana 11 |
| El motivo se vuelve un trámite («.») | Longitud mínima y revisión en auditoría; no se puede saltar |
| La versión nueva se confunde con la anterior | Cinta «Versión 2 de 2 · vigente» y enlace entre versiones (B-08) |
