# Bitácora de comunicación de la revisión (Semana 13)

El plan de integración exige que todo bloqueo se comunique el mismo día, identificando
qué falta y quién debe resolverlo. Esta bitácora registra la comunicación de la
revisión formal.

| Fecha | PR revisado | Canal | Mensaje | Enlace |
|---|---|---|---|---|
| 2026-10-20 | #376 — Notas SOAP: Repository con adaptadores PDO e InMemory | Comentario en el PR | Veredicto, cuatro condiciones y checklist | https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/376#issuecomment-6283627749 |

## Contenido comunicado

- Veredicto: aprobado con condiciones; nada bloquea el avance del módulo.
- R-01 (mayor): identificador de nota con 32 bits y prefijo no UUID.
- R-02 (mayor): `local_access_audits` del doble con un diseño distinto al previsto
  para la entidad `local_access_audit` del slice H8.
- R-03 (mayor): `icd10_codes` como tabla fuera de la especificación, a registrar.
- R-04 y R-05 (moderado): el doble no replica las restricciones del esquema federado.
- Recordatorio: ejecutar el adaptador real contra PostgreSQL 16 con las pruebas de
  integridad del repositorio antes de entregar.

## Decisiones que se comunican al grupo

| Decisión | Quién decide | Estado |
|---|---|---|
| ¿`icd10_codes` entra a la especificación de entidades? | Grupo | `PENDIENTE` |
| ¿Quién es el dueño de `local_access_audit` (slice H8)? | Grupo | `PENDIENTE` |
| ¿Qué módulo escribe las migraciones de H8 y de laboratorio? | Javier con cada dueño | `PENDIENTE` |
