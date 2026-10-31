# Formato de hallazgos — revisión técnica formal (Semana 13)

Cada hallazgo se registra con la plantilla de abajo, uno por entrada, en el acta de la reunión. La reunión **clasifica** el hallazgo y asigna responsable; no se discute la solución.

## Plantilla

| Campo | Contenido |
|---|---|
| **ID** | `H-R-NN`, correlativo dentro de la revisión |
| **Ítem** | Número del checklist (`C-NN`) o «fuera del checklist» |
| **Ubicación** | `archivo:línea`, ruta del contrato o tabla; nunca «en general» |
| **Tipo** | Defecto · Omisión · Pregunta · Mejora |
| **Severidad** | Crítico · Mayor · Menor · Sugerencia |
| **Descripción** | Una o dos frases: qué está mal, no cómo arreglarlo |
| **Evidencia** | Cómo reproducirlo o qué línea lo demuestra; comando, entrada y resultado |
| **Impacto** | Qué le pasa al paciente, a la nota o al sistema si no se corrige |
| **Corrección propuesta** | Opcional, se acepta o no después de la reunión |
| **Responsable y fecha** | Quién corrige y para cuándo (obligatorio desde Mayor) |
| **Estado** | Abierto · En corrección · Verificado · Rechazado (con motivo) |
| **Reportado por** | Revisor que lo encontró |

## Severidad

| Nivel | Criterio | Plazo de corrección |
|---|---|---|
| **Crítico** | Puede guardar un dato clínico incorrecto, perder trazabilidad o exponer una nota a quien no debe; o rompe una regla de dominio | Antes de aprobar la revisión |
| **Mayor** | El comportamiento es incorrecto o contradice el contrato, pero con rodeo o sin daño clínico directo | En el PR de corrección, con responsable y fecha |
| **Menor** | Defecto de claridad, mensaje o cobertura de pruebas sin riesgo clínico | Cuando convenga, sin bloquear |
| **Sugerencia** | Mejora que no es un defecto | Opcional |

## Tipo

- **Defecto:** el artefacto hace algo distinto de lo especificado.
- **Omisión:** falta algo que el contrato o los RF/RNF exigen.
- **Pregunta:** el revisor no puede decidir si es defecto; se convierte en otro tipo o se cierra con la respuesta del autor.
- **Mejora:** funciona, pero se puede simplificar (por ejemplo, una dependencia innecesaria).

## Estados y cierre

`Abierto` → `En corrección` (PR vinculado, de menos de 400 líneas) → `Verificado` (el moderador repite la evidencia y ya no falla). Un hallazgo `Rechazado` exige motivo escrito y la conformidad del revisor que lo reportó. Un Crítico no se cierra por consenso: se cierra con una prueba que antes fallaba y ahora pasa.

## Ejemplo diligenciado

Es el punto de atención **P-1** del [checklist](./checklist.md#puntos-de-atención-declarados-por-el-autor), reproducido antes de la reunión.

| Campo | Contenido |
|---|---|
| **ID** | H-R-01 (ejemplo) |
| **Ítem** | C-03 |
| **Ubicación** | `micro-his/notas-soap/database/schema.sql`, trigger `diagnoses_note_episode_author`; migración `000400`, función `enforce_diagnosis_integrity` |
| **Tipo** | Omisión |
| **Severidad** | Crítico |
| **Descripción** | La base no impide insertar un diagnóstico en una nota firmada; solo la política de dominio lo hace. |
| **Evidencia** | Con el esquema cargado en SQLite: insertar una fila en `clinical_notes` con `status = 'signed'` y luego una en `diagnoses` con ese `clinical_note_id` termina bien; la nota firmada queda con 1 diagnóstico. |
| **Impacto** | Si otra persona firma entre la lectura de la nota y la escritura, el diagnóstico queda en una nota firmada sin pasar por una corrección: la evidencia clínica cambia sin versión. |
| **Corrección propuesta** | Rechazar en el trigger de `diagnoses` la inserción cuando la nota está `signed` (`diagnoses_note_signed_trigger`) y repetir la lectura dentro de la transacción. Es una migración de triggers, sin entidad nueva; se coordina con Javier (ASII-09). |
| **Responsable y fecha** | Por asignar en la reunión |
| **Estado** | Abierto |
| **Reportado por** | Autor (punto de atención P-1) |

## Acta

El secretario completa, por cada sesión: fecha, asistentes y roles, artefactos y commits revisados, tiempo de preparación de cada revisor, hallazgos por severidad, decisión (Aprobada, Aprobada con condiciones o Re-revisión) y firmas del moderador y los revisores.
