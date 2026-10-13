# Narrativa de alcance y trazabilidad — Alergias clínicas y alerta visual en expediente

## Propósito

Que cada alergia o intolerancia conocida del paciente quede **registrada una sola vez, verificada por un profesional y visible para cualquiera que abra el expediente**, con una alerta proporcional a su severidad. La misma información alimenta la verificación previa de las prescripciones (ASII-15), de modo que una alergia registrada en este módulo pueda evitar un error de medicación.

## Alcance funcional

**Incluido**

- Registrar una alergia del paciente en la base del hospital activo (`allergies`, `status = 'active'`):
  - Alérgeno por código (`allergen_code`) o por nombre (`allergen_name`); al menos uno es obligatorio.
  - Tipo (`allergy`, `intolerance`, `unknown`), severidad (`mild`, `moderate`, `severe`, `life_threatening`, `unknown`), reacción opcional y fecha de inicio no futura.
  - El usuario que registra queda como verificador (`verified_by_user_ref_id`).
- Rechazar el registro si ya existe una alergia **activa equivalente** (mismo paciente, tipo y alérgeno normalizado).
- Mostrar en el expediente una **alerta visual** con las alergias activas, ordenadas por severidad:
  - Rojo no descartable si hay alguna `severe` o `life_threatening`.
  - Ámbar si solo hay leves, moderadas, desconocidas o intolerancias.
  - Indicador neutro «Sin alergias registradas» si no hay ninguna.
  - Gris «Alergias no disponibles» si la consulta falla (falla segura).
- Resolver (`resolved`: el paciente ya no la presenta) o inactivar (`inactive`: se registró por error o se descartó) una alergia con fecha y motivo, sin borrarla.
- Exponer la consulta de alergias activas a Prescripciones (UC-ALG-09).
- Auditar el registro, la consulta del expediente y los cambios de estado en `local_access_audits` (HOSPITAL).

**Fuera de alcance**

- Catálogo de alérgenos o de principios activos: el código lo define el *Catálogo de medicamentos* (ASII-14), que este módulo solo referencia.
- La decisión de bloquear o permitir una prescripción con conflicto: módulo *Prescripciones electrónicas* (ASII-15).
- Edición en sitio de una alergia activa (por ejemplo, cambiar su severidad): se modela como inactivar la anterior y registrar una nueva, para conservar la historia. Se revisa en la Semana 5.
- Registro de «sin alergias conocidas» (NKA) como dato clínico afirmativo: el esquema actual no lo representa (ver R1).
- Pantalla general del expediente: módulo *Expediente médico electrónico base* (ASII-10); este módulo aporta el componente de alerta.

## Supuestos y restricciones

- Las alergias pertenecen al **paciente local** (`local_patient_id`), no a un episodio: se muestran en cualquier atención del paciente en ese hospital.
- **Relación de cuidado:** el médico debe tener un `care_assignment` activo en algún episodio abierto del paciente. Para enfermería, el esquema no guarda asignaciones, así que se valida solo por permiso y tenant (ver R2).
- Solo el **médico tratante** resuelve o inactiva una alergia (permiso `alergias.resolver`).
- El esquema ya garantiza: alérgeno presente (`allergies_allergen_check`), valores válidos de tipo, severidad y estado, coherencia entre estado y `resolved_at` (`allergies_status_date_check`), unicidad de la alergia activa equivalente y que **no se borren** alergias (`allergies_retention`).
- **Reglas de la aplicación** que la base no garantiza: fecha de inicio y de resolución no futuras, motivo obligatorio para resolver o inactivar, paciente no fallecido para registrar y relación de cuidado.
- **Falla segura:** nunca se interpreta un error de lectura como «el paciente no tiene alergias». El expediente muestra «Alergias no disponibles» y Prescripciones recibe `503`.
- **Auditoría en la misma transacción:** el registro y el cambio de estado se escriben junto con `local_access_audits` y `outbox_events` en la conexión `hospital`. Si la auditoría falla, se hace `ROLLBACK`. La lectura del expediente también se audita, pero si esa auditoría falla **no se bloquea la consulta clínica**: se registra en el log de la aplicación y se reintenta.
- La auditoría guarda metadatos (usuario, paciente, alergia, severidad, acción y motivo), no el expediente completo.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-ALG-01 | Registrar alergia | Partición A: «Médico o enfermería abre el expediente…», «Capturar alérgeno…» → «BEGIN conexión hospital: INSERT allergies…» → «COMMIT · 201 Alergia registrada» | 1–2, 4, 7–8, 19–20, 27–30 |
| UC-ALG-02 | Validar alérgeno, tipo y severidad | A: «¿Alérgeno presente, tipo y severidad del catálogo, reacción no vacía y fecha de inicio no futura?» | 5–6 |
| UC-ALG-03 | Rechazar alergia duplicada o inválida | A: «422 Errores por campo», «¿Ya existe una alergia activa equivalente?» → «409 ALLERGY_DUPLICATED», «Índice único violado (registro concurrente)» | 6, 15–18, 23–24 |
| UC-ALG-04 | Consultar expediente del paciente | Partición B: «Usuario abre el expediente…», «¿El paciente existe en el tenant?» | 31–32, 34–38 |
| UC-ALG-05 | Emitir alerta visual de alergias | B: «Consultar alergias activas», «¿Consulta de alergias exitosa?» → banner gris, «¿Hay alergias activas?», «¿Alguna severe o life_threatening?» → banner rojo, ámbar o indicador neutro | 39–42, 46–50 |
| UC-ALG-06 | Resolver o inactivar alergia | Partición C completa: «¿La alergia existe… y está active?», «¿Motivo escrito y fecha…?» → «UPDATE allergies…» → «COMMIT · 200» | 51–52, 54–58, 61–62, 66–68 |
| UC-ALG-07 | Validar acceso y relación de cuidado | A: «¿JWT, tenant y permiso alergias.registrar?», «¿Tiene relación de cuidado?». B: «¿… alergias.ver?». C: «¿… alergias.resolver?», «¿Es médico con care_assignment activa…?». D: «¿Token de servicio…?» | 2–4, 11–14, 32–34, 52–54, 59–60 |
| UC-ALG-08 | Registrar auditoría (`local_access_audits`, HOSPITAL) | A: «INSERT local_access_audits + outbox_events», «¿INSERT y auditoría correctos?» → ROLLBACK o COMMIT. B: «Registrar la lectura…», «¿Auditoría de lectura registrada?». C: «+ auditoría con motivo», «¿Auditoría registrada?» | 21–22, 25–26, 43–45, 63–65 |
| UC-ALG-09 | Verificar alergias activas (Prescripciones) | Partición D completa: «¿Token de servicio, tenant y permiso alergias.ver?», «¿Paciente existe y la consulta responde?» → 404, 503 o 200 | Reutiliza la consulta de los mensajes 36–42; el contrato servicio a servicio se detalla en la Semana 5 |

## Flujo y resultados

### A · Registrar alergia

1. **Acceso (UC-ALG-07):** JWT, tenant y permiso `alergias.registrar`; sin ellos, `401` o `403` con la traza de RBAC.
2. **Datos (UC-ALG-02):** alérgeno por código o nombre, tipo y severidad válidos, reacción no vacía si se envía y fecha de inicio no futura; si no, `422` con errores por campo.
3. **Paciente:** si no existe en el tenant, `404`; si está fallecido, `409 PATIENT_INACTIVE`.
4. **Relación de cuidado (UC-ALG-07):** si no la tiene, `403`.
5. **Duplicado (UC-ALG-03):** si ya hay una alergia activa equivalente, `409 ALLERGY_DUPLICATED` y se muestra la existente.
6. **Persistencia y auditoría (UC-ALG-08):** en una transacción `hospital`: `INSERT allergies`, `INSERT local_access_audits` y `INSERT outbox_events`. Si otro usuario registró la misma alergia al mismo tiempo, el índice único lo detecta y se responde `409`. Si la auditoría falla, `ROLLBACK` y `500`.
7. **Resultado:** alergia `active` (`201`) y la alerta del expediente se actualiza.

### B · Consultar expediente con alerta visual

1. **Acceso:** permiso `alergias.ver`; sin él, `401` o `403` y el expediente no se muestra.
2. **Paciente:** si no existe en el tenant, `404`.
3. **Consulta (UC-ALG-05):** alergias activas ordenadas por severidad. Si falla, `503 ALLERGIES_UNAVAILABLE` y banner gris persistente «Alergias no disponibles — verifique antes de prescribir».
4. **Alerta visual:**
   - Alguna `severe` o `life_threatening`: banner rojo no descartable con alérgeno, reacción y severidad.
   - Solo leves, moderadas, desconocidas o intolerancias: banner ámbar.
   - Ninguna: indicador neutro «Sin alergias registradas» (no «sin alergias conocidas», ver R1).
5. **Auditoría de lectura (UC-ALG-08):** si falla, log de aplicación y reintento; la consulta no se bloquea.
6. **Resultado:** `200` con `highest_severity` y la lista de alergias.

### C · Resolver o inactivar alergia

1. **Acceso:** permiso `alergias.resolver`; sin él, `401` o `403`.
2. **Estado:** `404` si no existe; `409 ALLERGY_NOT_ACTIVE` si ya está resuelta o inactiva.
3. **Médico tratante:** si no lo es, `403`.
4. **Datos:** motivo obligatorio y fecha de resolución no futura y no anterior a la de inicio; si no, `422`.
5. **Persistencia y auditoría:** `UPDATE allergies` (`status`, `resolved_at`) y auditoría con el motivo en la misma transacción. Si la auditoría falla, `ROLLBACK` y `500`.
6. **Resultado:** `200`; la alergia deja de aparecer en la alerta visual, pero sigue en el historial.

### D · Verificar alergias activas para Prescripciones

1. **Acceso:** token de servicio, tenant y permiso `alergias.ver`; sin ellos, `401` o `403`.
2. **Paciente:** si no existe, `404`.
3. **Disponibilidad:** si la base no responde, `503 ALLERGIES_UNAVAILABLE`; Prescripciones no guarda ni firma.
4. **Resultado:** `200` con código, nombre, tipo y severidad de cada alergia activa. Una lista vacía significa «sin alergias registradas», no «sin alergias conocidas».

## Riesgos y pendientes detectados en el esquema

| # | Hallazgo | Impacto | Propuesta |
|---|---|---|---|
| R1 | `allergies` no puede representar «sin alergias conocidas» (NKA): no hay un registro afirmativo de que se preguntó y el paciente negó alergias. | El expediente no distingue «se evaluó y no tiene» de «nunca se evaluó». Es un riesgo clínico para la prescripción. | Mostrar «Sin alergias registradas» (no «sin alergias conocidas»). Proponer en la Semana 5 un registro de evaluación (por ejemplo `allergy_assessed_at` en el paciente o un tipo `no_known_allergy`), acordado con el responsable del esquema. |
| R2 | `care_assignments` solo relaciona **médicos** con episodios; no existe asignación de enfermería. | La relación de cuidado de enfermería no se puede verificar en la base. | Validar enfermería por permiso y tenant, y dejarlo documentado. Coordinar con *RBAC* (ASII-02) y *Médicos* (ASII-04) si se necesita una asignación por servicio. |
| R3 | `allergies` no tiene columna para el **motivo** de resolución o inactivación. | El motivo solo quedaría en la auditoría. | Guardar el motivo en `local_access_audits` (Semana 1) y evaluar una columna `status_reason` en la Semana 5. |
| R4 | No hay catálogo de alérgenos entre las 27 entidades del esquema HOSPITAL. | Prescripciones compara por código; si el código del alérgeno y el del principio activo no coinciden, el conflicto no se detecta. | Usar el mismo código de principio activo que el *Catálogo de medicamentos* (ASII-14) y validarlo detrás de una interfaz; acordarlo con ASII-14 y ASII-15. |
| R5 | `local_access_audits` (slice H8) aún no tiene migración (tarea 3.7). | Sin ella no se garantiza «registro y auditoría en la misma transacción». | Reutilizar la propuesta de columnas del Anexo A de Notas SOAP (ASII-11) y validarla con el grupo antes de implementar. |

## Dependencias con otros módulos

- **Depende de:**
  - *Pacientes* (ASII-03): `local_patients` (ya existe).
  - *Médicos* (ASII-04) y *Expediente* (ASII-10): `care_assignments` y episodios para la relación de cuidado.
  - *RBAC* (ASII-02): permisos `alergias.*`.
  - *Catálogo de medicamentos* (ASII-14): código común de principio activo.
  - Esquema HOSPITAL: `local_access_audits` y `outbox_events` (slice H8).
- **Dependen de este módulo:**
  - *Expediente médico* (ASII-10): muestra la alerta visual.
  - *Prescripciones* (ASII-15): consume las alergias activas mediante su interfaz `AllergyChecker`.
  - Reportes y analytics (ASII-23).
