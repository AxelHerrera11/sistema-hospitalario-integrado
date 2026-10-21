# ASII-03 Pacientes — Semana 13: revisión técnica formal

## Objetivo y alcance

Revisar implementación, contrato, privacidad, permisos, persistencia y riesgos
de integración usando solo el contenido presente en el `develop` actualizado.
Es una revisión documental/técnica; no se implementaron cambios funcionales.

## Material revisado

Se inspeccionaron capas Domain, Application, Presentation y Persistence del
módulo; prueba `micro-his/pacientes/tests/run.php`; OpenAPI; migraciones y
pruebas de esquema relacionadas con `local_patients`; documentos de Pacientes;
`config/rbac.php`, `routes/api.php`, middleware Tenant/JWT, RBAC y frontend.

Están disponibles Semanas 1–5, 7 y 8. No aparecen Semanas 6, 9, 10, 11 ni 12.
Tampoco existe `resources/js/modules/patients/` ni una ruta Vue/API de
Pacientes en esta revisión.

## Participantes y método

Revisión realizada por el asistente como apoyo técnico, contrastando código,
contrato, esquema, pruebas y documentos. Áreas responsables para seguimiento:
Pacientes (dominio/adaptador), integración Laravel/API, identidad/RBAC y
persistencia/DB. No se atribuyen aprobaciones de esas áreas.

El [plan de revisión](./plan-revision.md) delimita entradas y salida; el
[checklist](./checklist.md) contiene 14 criterios; los
[hallazgos](./hallazgos.md) registran 7 diferencias verificables.

## Resumen de resultados

- **Alta: 2.** No hay ruta/adapter HTTP de Pacientes; tampoco aplicación
  efectiva de permisos a endpoints del módulo. No hay endpoint Pacientes
  expuesto por `routes/api.php` en el estado revisado; son bloqueos previos a
  integración, no vulnerabilidades activas observadas.
- **Media: 4.** Discrepancia de IDs, conflicto/errores no alineados al contrato,
  validación UUID de `global_id` incompleta y falta de prueba directa del
  repositorio PDO contra PostgreSQL.
- **Baja: 1.** `UPDATE` no verifica filas afectadas antes de devolver éxito.

El módulo tiene lógica de aplicación y dominio, Repository y adaptadores.
`PdoPatientRepository` usa consultas preparadas y la migración PostgreSQL
define constraints. Las pruebas del módulo usan InMemory; existen pruebas
PostgreSQL de integridad/esquema relacionadas, pero no prueban el repositorio
PDO de Pacientes.

## Estado, riesgos y pendientes

- **Implementado:** casos PHP, validaciones de fecha/sexo/estado, unicidad en
  el esquema, persistencia PDO parametrizada, presenter interno y pruebas
  ejecutables del módulo con InMemory.
- **Documentado:** OpenAPI, UX Semana 8, permisos por operación y secuencia
  futura de integración.
- **Pendiente:** API Controller/Adapter Laravel, rutas y middleware aplicados,
  DI de PDO por hospital, traducción de `id` a `patient_id`, errores Problem
  JSON, pruebas de contrato y PDO/PostgreSQL.
- **Frontend:** solo base general de Vue; no hay pantalla ni prototipo
  Pacientes. No procede revisión visual/responsive.
- `global_id` es UUID lógico al MPI, sin FK cross-database ni sincronización
  demostrada. No confundirlo con `local_patient_id`.

Primer paso recomendado: acordar con las áreas de Integración API, Tenant/
Auth/RBAC y Persistencia una frontera HTTP con conexión hospitalaria y DTO/
errores, incluyendo el mapeo de IDs; después implementar y probar un endpoint
vertical protegido antes de conectar Vue. Detalles en [hallazgos](./hallazgos.md).

## Validaciones

Se ejecutaron `php micro-his/pacientes/tests/run.php` y
`node --test resources/js/router/permissions.test.mjs`. Se verificó
`git diff --check origin/develop...HEAD`; el diff de rama es vacío porque
`HEAD` coincide con `origin/develop`. Los cuatro documentos nuevos se contaron
por separado. No se modificaron código, migraciones, otros módulos, secretos
ni datos reales.
