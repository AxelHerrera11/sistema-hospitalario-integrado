# ASII-03 Pacientes — Semana 14: SQA e integración inicial

## Objetivo

Iniciar la integración HTTP real de Pacientes y dejar controles SQA compactos
para dar seguimiento a confiabilidad, seguridad y riesgos.

## Slice implementado

- `GET /api/v1/patients/{code}` reutiliza `PatientService` y
  `PdoPatientRepository`, y responde `patient_id` desde el ID local.
- Ruta protegida por `tenant`, `jwt.auth` y `rbac:pacientes.ver`.
- El acceso PDO es diferido y usa la conexión Laravel `hospital` configurada.

## Alcance y evidencia

La prueba HTTP cubre consulta autorizada/no encontrada, identidad ausente,
permiso denegado, tenant distinto y error de persistencia sin detalles internos.
Usa dobles para tenant/JWT y RBAC (con la autorización/matriz reales) y SQLite
con PDO; no es una prueba contra PostgreSQL ni autenticación JWT real.

Validaciones reportadas: 4 pruebas HTTP, 21 aserciones, sin fallos; suite PHP
de Pacientes PASS; ruta API presente; `php -l` y `git diff --check` PASS.
Las pruebas HTTP emitieron warnings por ausencia de `.env` local.

## Limitaciones

`hospital` es una conexión fija configurada, no se resuelve dinámicamente por
tenant. PostgreSQL real, middleware de tenant/JWT real y aislamiento de datos
entre hospitales quedan pendientes. POST/PATCH y frontend Pacientes no forman
parte de este slice.

## Siguiente gate de calidad

Antes de ampliar operaciones, validar con infraestructura de integración:

- PostgreSQL hospital y migración `local_patients`;
- tenant y JWT reales, con intento de acceso cruzado;
- RBAC denegando y permitiendo con roles configurados;
- restricciones únicas y manejo de conflictos;
- respuestas de error alineadas al contrato OpenAPI.
