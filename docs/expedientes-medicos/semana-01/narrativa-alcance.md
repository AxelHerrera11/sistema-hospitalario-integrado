# Narrativa de alcance — apertura y consulta autorizada del expediente longitudinal

## Objetivo del proceso

Que el personal clínico autorizado de un hospital pueda **abrir una sola vez** el expediente de un paciente y
**consultar su historia longitudinal** (todos sus episodios clínicos en ese hospital, del más reciente al más
antiguo), sin que nadie sin permiso o sin relación asistencial vea datos clínicos, y dejando constancia de cada
acceso en la bitácora de auditoría.

## Dentro del alcance

- Apertura del expediente (`medical_records`): uno por paciente y por hospital, solo si el paciente está activo.
  La operación es idempotente: repetirla devuelve el mismo expediente.
- Consulta longitudinal: datos mínimos del paciente, datos del expediente, resumen y episodios (`care_episodes`).
- Acceso autorizado en dos pasos independientes: permiso RBAC `expediente.ver` en el hospital (tenant) y vínculo
  asistencial activo con el paciente (`care_assignments` + `doctor_profiles`).
- Auditoría de aperturas, consultas y denegaciones mediante el contrato de Gobernanza (`RecordAuditEvent`).
- Integridad: el expediente y sus episodios pertenecen al mismo paciente; estados y fechas coherentes; sin borrado.

## Fuera del alcance

- Crear o cerrar episodios (Admisión, Traslados y altas), escribir notas SOAP o diagnósticos (Notas SOAP),
  registrar alergias o signos vitales: el expediente solo los **reúne**; sus módulos los escriben.
- Cerrar un expediente (la tabla admite `closed`, pero ningún requisito del curso lo pide; queda como propuesta).
- Acceso de emergencia («romper el vidrio») y acceso entre hospitales (`cross_hospital_grants`): no tienen una
  política acordada en el grupo; no se inventan.
- Autenticación: la identidad la aporta el módulo Auth (JWT). En el micro-HIS se simula con `X-User-ID`.

## Actores

| Actor | Rol real (`config/rbac.php`) | Objetivo en el proceso | Resultado esperado |
|---|---|---|---|
| Médico tratante | `medico` | Consultar la historia del paciente que atiende; abrirle expediente si no tiene | 200 con datos / 201 |
| Enfermería | `enfermeria` | Consultar el expediente | Tiene `expediente.ver`, pero hoy no existe una tabla de asignación de enfermería: 403 `SIN_VINCULO_ASISTENCIAL` (supuesto S-01) |
| Recepción / Admisión | `recepcion` | Abrir el expediente al registrar la primera atención | 201 si se activa el permiso propuesto; hoy 403 |
| Administración del hospital | `admin_hospital` | — (no debe ver datos clínicos) | 403 `ACCESO_DENEGADO` |
| Sistema RBAC (María) | — | Decidir el permiso por hospital | `allowed / reason / trace_id` |
| Gobernanza y Auditoría | — | Guardar cada acceso | Evento append-only encadenado |
| Pacientes (Madelin) | — | Identificar al paciente por código | `Patient` o nada |

## Supuestos pendientes de validar

| Id | Supuesto | Con quién se valida |
|---|---|---|
| S-01 | La consulta exige vínculo asistencial además del permiso RBAC (misma condición que usan los triggers de notas clínicas). | María (RBAC) y grupo |
| S-02 | El permiso de apertura (acción «abrir» del módulo «expediente») se concede a `medico` y `recepcion`. No existe en el catálogo. | María |
| S-03 | Número de expediente = `EXP-` + código del paciente. La columna `record_number` solo exige texto único. | Javier (BD) |
| S-04 | Un paciente inexistente en la consulta responde igual que «sin vínculo» (403) para no revelar qué pacientes existen. | Grupo |
| S-05 | Una consulta exitosa sin evento de auditoría persistido no entrega datos (criticidad Security). | Gobernanza (Cindy) |
| S-06 | Un paciente fallecido o inactivo conserva su expediente consultable, pero no recibe uno nuevo. | Grupo |

## Trazabilidad entre los tres diagramas

| Caso de uso (01) | Pasos del diagrama de actividad (02) | Mensajes del diagrama de secuencia (03) | Código | Pruebas |
|---|---|---|---|---|
| UC-02 Consultar | C1…C7 | 1–29 | `ConsultLongitudinalRecord::execute` | C1–C7, N-01…N-07 |
| UC-01 Abrir | A1…A7 | (secuencia equivalente; ver Semana 5) | `OpenMedicalRecord::execute` | A1–A7, N-08…N-10 |
| UC-03 Verificar permiso | C1, A1 | 8–13 | `AccessGuard::requirePermission` → `RbacAccessAuthorizer` | C1, A1, N-03, N-04 |
| UC-04 Verificar vínculo | C3/C4 | 15–18 | `CareRelationship::hasActiveAssignment` | C3, C4, P-01 |
| UC-05 Auditar | denegaciones, C6, A5 | 11, 17, 22–24 | `GobernanzaClinicalAudit::record` | C6, A5, P-07, N-12 |
| UC-06 Ver motivo | resultado C7 | 29 | `vista.js` (`<details>` plegado) | captura `d-03-motivo-desplegado.png` (Semana 11) |
