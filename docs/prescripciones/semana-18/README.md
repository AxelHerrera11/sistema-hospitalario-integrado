# Prescripciones — semana 18

Responsable: María de los Ángeles López Fajardo (Mar-03), ASII-15.
Rama: feature/asii-15-prescripciones-pendientes-mar-03, desde origin/develop (68c20e16).

## Decisión y contrato

Se eligió A: mantener HTTP 422, code ALLERGY_CONFLICT, severity y
exception_allowed siempre false. El permiso prescripciones.excepcion_alergia
sigue pendiente; tramitar excepciones queda fuera de esta versión.
El dominio conserva su clasificación clínica, pero esa clasificación no habilita
una excepción HTTP. No se agregó endpoint ni se activó ningún permiso.
El OpenAPI de semana 05 y los casos U-03/U-04 de semana 15 reflejan la decisión;
las descripciones anteriores de otras semanas quedan como antecedentes históricos.
U-03/U-04 tienen pruebas HTTP ejecutables que comprueban severidad, false y ninguna escritura.

## Integración de pantalla

GET /api/v1/patients/{code} devuelve patient.patient_id, code, given_name y family_name.
patient.patient_id corresponde a local_patients.local_patient_id (UUID); ese es
el ID que exige PdoPrescriptionContext y se usa en /patients/{id}/prescriptions,
no global_id ni el código. Se muestra nombre y código del paciente resuelto.
Al editar el código se descarta la selección y se ignoran respuestas atrasadas.

El selector busca mediante GET /api/v1/medications?q=... (2–60 caracteres,
hasta 50 resultados según el contrato existente), con medicamentos.ver.
Normaliza medication_id de la API v1 como id del selector (con code y name).
Solo ofrece resultados con validity active. El backend vuelve a validar vigencia.
Se mantienen labels, alertas, estados de carga/error/vacío y foco visible.
ALLERGY_CONFLICT muestra severidad traducida y confirma que no se guardó.

## RBAC y alcance

Se excluye exactamente diagnosticos.png del escáner de permisos documentados.
En el PR original no se cambiaron archivos de otros módulos ni config/rbac.php.
La revisión de duplicados remotos fue confirmada por María: ningún PR abierto toca Prescripciones.
María autorizó commit, push y PR hacia develop; ningún permiso pendiente se activa.

## Verificación inicial (antes de iniciar PostgreSQL)

- composer install --no-interaction --prefer-dist: completado (108 instalaciones).
- php micro-his/prescripciones/tests/run.php: OK, 86 verificaciones aprobadas.
- node resources/js/modules/prescripciones/prescripciones.test.mjs: OK; búsqueda por código,
  uso de ID interno, error de paciente, filtro vigente y severidades.
- Script y template Vue: compilación correcta con @vue/compiler-sfc ya instalado en otro worktree.
- Feature Prescriptions: OK (14 tests, 62 assertions).
- Feature Rbac: Tests: 40, Assertions: 3795, Failures: 1 (Órdenes).
- Estas suites usan dobles; no acreditan integración PostgreSQL.
- PostgreSQL: conexión rechazada en 127.0.0.1:55431 y :55433 con las bases de phpunit.xml.
  No se ejecutaron migraciones ni se declara éxito contra PostgreSQL.
- PublishedContractsTest: 8 tests, 2157 assertions, 1 failure; tras corregir la imagen,
  falla por laboratorio.ordenes.actualizar en docs/ordenes-laboratorio/semana-01/README.md.
  Se deja visible; no se agregaron excepciones para ocultar permisos de Órdenes.
- Suites combinadas: 54 tests, 3857 assertions, 1 failure (el mismo de Órdenes).
- Pint --test sobre los tres PHP cambiados: passed.

## Seguimiento RBAC y ejecución local (30-oct-2026)

Por decisión de Mar-03, laboratorio.ordenes.actualizar se registra en catalog y
pending para tecnico_laboratorio, como propuesta ASII-16 sin activar. No cambia
roles ni se agrega el permiso a excepciones. La matriz documental tiene una fila
sin concesiones y la decisión fechada; RoleMatrixTest ya admite este estado.
El escáner RBAC excluye además los archivos prescripciones.test.mjs y muestras.yml.
La primera ejecución encontró el primer nombre como falso permiso: fallo de
nuestro control documental RBAC; se corrigió sin tocar otros módulos.

Validación local, no CI. PostgreSQL 16.15, bases aisladas de semana 16 existentes,
con btree_gist confirmado en HOSPITAL mediante psql. Las variables se fijaron
solo en el proceso de pruebas; no se editó .env:

```powershell
$env:CENTRAL_DB_HOST="127.0.0.1"; $env:CENTRAL_DB_PORT="55431"
$env:CENTRAL_DB_DATABASE="his_prescriptions_s16_central_test"
$env:CENTRAL_DB_USERNAME="postgres"; $env:CENTRAL_DB_PASSWORD="postgres"
$env:HOSPITAL_DB_HOST="127.0.0.1"; $env:HOSPITAL_DB_PORT="55433"
$env:HOSPITAL_DB_DATABASE="his_prescriptions_s16_hospital_test"
$env:HOSPITAL_DB_USERNAME="postgres"; $env:HOSPITAL_DB_PASSWORD="postgres"
```

Las salidas completas se guardan localmente en evidencia-local/*.log
(ignoradas por Git); se conserva también rbac-feature-inicial.log.
Feature Prescriptions usa dobles; CentralRolesTest de RBAC usa SQLite en memoria.
La evidencia PostgreSQL real corresponde a las pruebas de integridad.

Resultados finales de esta ejecución local (duración informada por PHPUnit):

| Prueba | Tests | Aserciones | Fallos/errores | Duración | Salida completa local |
|---|---:|---:|---:|---|---|
| PrescriptionIntegrityTest | 13 | 172 | 0/0 | 32.188 s | [log](evidencia-local/prescription-integrity.log) |
| Feature Prescriptions | 14 | 62 | 0/0 | 0.507 s | [log](evidencia-local/prescriptions-feature.log) |
| Feature Rbac (repetición final) | 40 | 4039 | 0/0 | 16.271 s | [log](evidencia-local/rbac-feature-final.log) |
| Grupo database-integrity | 184 | 2095 | 0/0 | 8 min 00.447 s | [log](evidencia-local/database-integrity.log) |
| MigrationLayoutTest | 3 | 18 | 0/0 | 0.330 s | [log](evidencia-local/migration-layout.log) |

Cada comando usa php vendor/bin/phpunit seguido de la ruta solicitada o
--group database-integrity. No se suman los resultados: el grupo también incluye
PrescriptionIntegrityTest y MigrationLayoutTest.
La primera ejecución RBAC tuvo 40 tests, 3841 aserciones y 1 fallo (2.488 s):
el falso permiso prescripciones.test.mjs. Al corregirlo se detectó por inspección
el mismo caso con muestras.yml; ambos nombres exactos se excluyeron del escáner.
Clasificación: nuestro control documental RBAC, corregido; no hubo fallos finales
de otros módulos ni del entorno. La ejecución inicial sin PostgreSQL queda arriba
como antecedente; estos resultados posteriores acreditan las bases aisladas.
Pint --test pasó para config/rbac.php y PublishedContractsTest.php.
Ambos contenedores estaban detenidos, se iniciaron únicamente con docker start y
se restauraron a detenidos con docker stop al terminar. No se borraron contenedores
ni volúmenes, ni se usaron bases de desarrollo. Sin commit, push o PR nuevos.
