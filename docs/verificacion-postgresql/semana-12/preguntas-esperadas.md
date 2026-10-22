# Preguntas esperadas y respuestas (Semana 12)

Preparation for the second evaluation: the questions most likely to be asked about the
work of weeks 7 to 11, with the answer backed by the published evidence.

## 1. ¿Qué pasa si dos módulos crean la misma tabla?

La segunda migración falla por colisión de nombre y el PR se devuelve. La regla del
módulo de BD es que ninguna tabla nueva entra sin avisar a Javier, que lleva la lista
de entidades y responsable por tabla
(`semana-03/asignacion-entidades.md`). El aviso es del mismo día.

## 2. ¿Por qué el esquema no tiene "ON DELETE CASCADE"?

Para que un registro clínico no desaparezca por una cascada silenciosa. La retención
se aplica con triggers explícitos (`medical_records_retention`,
`clinical_notes_retention`, `allergies_retention`, `vital_signs_retention`,
`care_assignments_retention`) que lanzan excepción. Un borrado se decide de forma
explícita, nunca como efecto colateral de otro.

## 3. ¿Qué garantiza un índice único parcial?

Que no se repitan hechos clínicos equivalentes **activos al mismo tiempo**. Por
ejemplo, `allergies_active_equivalent_unique` impide dos alergias iguales del mismo
paciente, pero permite conservar la alergia ya resuelta. La unicidad total habría
impuesto borrar historia.

## 4. ¿Por qué el módulo de SOAP puede trabajar con SQLite?

Porque un doble en memoria o SQLite sirve para probar reglas de dominio sin base de
datos. No sirve para el adaptador real: el esquema federado usa UUID, `timestamptz`, FK
locales, triggers de inmutabilidad y unicidad de versión. Por eso la verificación de BD
exige que el adaptador real se pruebe contra PostgreSQL 16 antes de la entrega, para
que la diferencia entre el doble y el esquema real no aparezca en producción.

## 5. ¿El índice propuesto mejora siempre?

No. La medición muestra que el índice `(bed_id, started_at, ended_at)` no se usa en la
forma original de la consulta y que, sin índice de fecha, la consulta preagregada es
**peor** que la original (5,496 ms frente a 4,764 ms). El índice y la forma de la
consulta son un conjunto; por eso se publican las dos cosas juntas.

## 6. ¿Qué pasa si la base de datos no responde?

La respuesta es `503` con mensaje de indisponibilidad, nunca ceros ni listas vacías:
el usuario debe distinguir "no hay datos" de "no pude consultar". El catálogo de la
semana 8 lo fija como regla y el plan SQA de la semana 14 lo verifica.

## 7. ¿Por qué no se aplicaron las dos migraciones propuestas?

La numeración de migraciones es un recurso compartido: los slices H5, H6 y H7 ya
tienen números reservados. Aplicar una migración con un número ya usado rompe el
orden de ejecución. Las dos migraciones quedan como propuesta con su reversión y sus
pruebas, y se aplican cuando el grupo acuerde el número.

## 8. ¿Qué pasa con las entidades que todavía no tienen migración?

Se declaran como pendientes, no se dibujan como implementadas y no se verifican. La
semana 7 deja tres hallazgos con responsable: slice H8, entidades de laboratorio y
alertas, y `doctor_schedules`. Ninguna tabla nueva avanza sin aviso.
