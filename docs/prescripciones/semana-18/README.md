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
No se cambiaron documentos ni archivos de otros módulos; config/rbac.php permanece intacto.
La revisión de duplicados remotos fue confirmada por María: ningún PR abierto toca Prescripciones.
María autorizó commit, push y PR hacia develop; ningún permiso pendiente se activa.

## Verificación

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
