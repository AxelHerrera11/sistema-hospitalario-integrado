# Semana 16 — Médicos tratantes del expediente (parte 4)

La consulta del expediente exige un vínculo asistencial vigente (`care_assignments`), pero ningún módulo de la
aplicación lo creaba: Médicos registra al profesional y Admisión abre el episodio, y la asignación médico ↔ episodio
solo se leía. Esta parte la agrega para que el flujo «apertura y consulta autorizada del expediente longitudinal»
funcione de punta a punta sin cargar datos a mano.

## Contrato

| Método | Ruta | Permiso | Respuesta |
|---|---|---|---|
| GET | `/api/v1/patients/{code}/care-assignments` | `expediente.asignar` | episodios abiertos (sin motivo clínico), médicos tratantes vigentes y médicos asignables |
| POST | `/api/v1/patients/{code}/care-assignments` | `expediente.asignar` | 201 con `care_assignment_id`; body `care_episode_id`, `doctor_profile_id`, `specialty_id`, `reason` |
| DELETE | `/api/v1/patients/{code}/care-assignments/{id}` | `expediente.asignar` | 200 `status: closed`; la fila se conserva como evidencia |

`expediente.asignar` se concede a superadmin, admin_hospital y jefatura_medica: quienes organizan la atención, no
quienes la reciben. Ninguno de esos roles obtiene por esto la consulta clínica.

| Código | Estado | Causa |
|---|---|---|
| `CODIGO_PACIENTE_INVALIDO` | 422 | código con formato inválido |
| `PACIENTE_NO_ENCONTRADO` | 404 | el paciente no existe en este hospital |
| `EPISODIO_NO_DISPONIBLE` | 422 | el episodio no es del paciente o no está abierto |
| `MEDICO_NO_ELEGIBLE` | 422 | perfil o especialidad inactivos, o el médico no tiene esa especialidad vigente |
| `ASIGNACION_DUPLICADA` | 409 | el médico ya atiende ese episodio |
| `ASIGNACION_NO_ENCONTRADA` | 404 | la asignación no está activa o es de otro paciente |
| `ACTOR_SIN_REFERENCIA_LOCAL` | 403 | el actor no tiene referencia local en el hospital |
| `AUDITORIA_NO_DISPONIBLE` | 503 | la bitácora no guardó el evento; el cambio se revierte |

## Reglas

- Mismas condiciones que el trigger `enforce_care_assignment_integrity` de la base HOSPITAL (especialidad del médico
  activa y vigente), comprobadas antes de escribir para responder con un código claro.
- `valid_from` usa el reloj de la base, el mismo con el que `PdoCareRelationship` comprueba el vínculo.
- Escritura y auditoría en una transacción: `expediente_medico.medico_asignado` y
  `expediente_medico.asignacion_finalizada` (criticidad Security). Si la bitácora falla, no queda asignación.
- `source = manual`, `scope = {"care":"attending"}`. Sin migraciones.

## Validación contra PostgreSQL (datos ficticios)

Finalizar la asignación del médico demo → el médico recibe 403 `SIN_VINCULO_ASISTENCIAL`; asignarlo de nuevo → 201 y
la consulta vuelve a 200; repetir → 409 `ASIGNACION_DUPLICADA`; ambos eventos quedan en `audit_log`.
