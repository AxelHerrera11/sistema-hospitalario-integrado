# ASII-03 Pacientes — Semana 15

## Objetivo y alcance

Completar el slice HTTP con registro y edición, reutilizando el micro-HIS,
y aportar pruebas unitarias, caja blanca y caja negra. GET existente se conserva.

## Operaciones

- `GET /api/v1/patients/{code}` — `pacientes.ver`.
- `POST /api/v1/patients` — `pacientes.registrar`.
- `PATCH /api/v1/patients/{code}` — `pacientes.editar`.

Las tres rutas conservan `tenant → jwt.auth → rbac`. La API valida payloads,
traduce el ID de dominio a `patient_id` y responde 404/409/422/500 sin detalles
PDO. Inserción/edición reutilizan `PatientService` y `PdoPatientRepository`.

## Evidencia y límites

Resultado ejecutado: 5 pruebas unitarias (10 aserciones) y 10 pruebas HTTP
(47 aserciones), suites PASS; HTTP presenta warnings por `.env` ausente.
Las pruebas HTTP usan dobles para tenant/JWT y SQLite/PDO, no PostgreSQL real.
La conexión `hospital` continúa fija; aislamiento dinámico por tenant, JWT real
y PostgreSQL quedan pendientes. No se afirma sincronización MPI.

Detalle por requisito y caso en [matriz-pruebas.md](./matriz-pruebas.md).
