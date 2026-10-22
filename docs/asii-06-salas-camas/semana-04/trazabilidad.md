# Trazabilidad — Semana 4

La tabla enlaza los requisitos de
[Semana 2](../semana-02/rf-rnf-criterios.md) y los casos de uso de
[Semana 1](../semana-01/narrativa-alcance-trazabilidad.md) con los componentes
reales de Semana 3 y su evidencia. Las referencias `UC-SC-*` corresponden a
los identificadores del diagrama de casos de uso.

| Requisito | Caso de uso previo | Componente | Evidencia |
|---|---|---|---|
| RF-01: crear ward con `code`, `name`, `location` y estado inicial `active`. | UC-SC-01 Crear sala/ward; UC-SC-03 Validar datos. | `Ward::create()`, `CatalogService::createWard()`, `WardRepository::insertWard()`, `PdoWardRepository::insertWard()`. | `micro-his/salas-camas/tests/run.php`: alta de ward con respuesta 201. La migración define columnas y default. |
| RF-02: rechazar `code` de ward duplicado. | UC-SC-01; validación de datos. | `PdoWardRepository` y restricción `wards_code_unique`. | Migración `2026_08_11_000300`; `SchedulingAndBedIntegrityTest::test_ward_and_bed_catalogs_enforce_codes_and_status_but_not_occupancy()`. El test de micro-HIS usa fake y no verifica esa restricción. |
| RF-03: crear cama para ward existente, conservando FK y estado `available`. | UC-SC-02 Crear cama; UC-SC-04 Verificar sala; UC-SC-05 Validar estado; UC-SC-06 Asociar cama. | `CatalogService::createBed()`, `WardRepository::findByCode()`, `Bed::create()`, `BedRepository::insertBed()`, `PdoBedRepository::insertBed()`. | `micro-his/salas-camas/tests/run.php`: alta de cama y estado por defecto; migración define `ward_id` requerido, FK y default. |
| RF-04: rechazar ward inexistente y código repetido dentro del ward. | UC-SC-04 Verificar sala; UC-SC-06 Asociar cama. | `CatalogService::createBed()` produce `WARD_NOT_FOUND`; FK y `beds_ward_code_unique` respaldan integridad. | Suite standalone comprueba 404 para ward inexistente; `SchedulingAndBedIntegrityTest` comprueba FK/unicidad con PostgreSQL. |
| RF-05: permitir solo `active`/`inactive` para ward. | UC-SC-05 Validar estado operativo. | `Ward::__construct()` y `DomainRuleViolation`. | `Ward` restringe el conjunto; evidencia declarativa de `wards_status_check` en migración. La suite standalone no incluye un caso inválido de estado de ward. |
| RF-06: permitir solo `available`/`occupied`/`maintenance`/`inactive` para cama. | UC-SC-05; UC-SC-02 Crear cama. | `Bed::__construct()` y `DomainRuleViolation`; `CatalogController` lo traduce a 422. | Suite standalone verifica que `broken` sea rechazado; migración define `beds_status_check`; test de integridad PostgreSQL valida estado inválido. |
| RNF-01: integridad relacional, unicidad y dominios. | UC-SC-04, UC-SC-05, UC-SC-06. | `PdoWardRepository`, `PdoBedRepository`, restricciones del esquema. | `SchedulingAndBedIntegrityTest`; restricciones de migración `wards_code_unique`, `beds_ward_code_unique`, FK y CHECKs. |
| RNF-02: no dejar un registro parcial cuando falla el almacenamiento. | UC-SC-01 y UC-SC-02. | `PersistenceFailure`, `CatalogController::respond()`. | `tests/run.php` verifica la traducción de fallo fake; no comprueba una transacción PostgreSQL del flujo. |
| RNF-03: separar entrada, coordinación y persistencia. | UC-SC-01 a UC-SC-06. | Domain, `CatalogService`, contratos de repositorio, adapters PDO y `CatalogController`. | Arquitectura implementada en `micro-his/salas-camas/src/`; PHP lint y suite standalone. |
| RNF-04: poder sustituir persistencia por contrato. | UC-SC-01 a UC-SC-06. | `WardRepository`, `BedRepository`, `CatalogService` y `FakeCatalogRepository`. | La suite inyecta fake. No existe adapter InMemory de producción ni test de paridad PostgreSQL del micro-HIS. |

## Trazabilidad de límites

`rooms` no es requisito, entidad ni tabla de ASII-06: el vínculo del catálogo
es `beds.ward_id → wards.ward_id`. `bed_stays` aparece en el esquema y en las
pruebas de integridad de otros flujos, pero esta entrega no implementa
admisión, ocupación, traslado o alta.

## Validaciones de Semana 4

- `php micro-his/salas-camas/tests/run.php` — 3 escenarios PASS: camino feliz,
  estado de cama inválido y error de persistencia fake.
- `php -l` sobre los archivos PHP de `micro-his/salas-camas/` y la migración
  `2026_08_11_000300_create_scheduling_and_bed_tables.php` — sin errores de
  sintaxis.
- La prueba PostgreSQL `SchedulingAndBedIntegrityTest` no se ejecutó: su
  `setUp()` ejecuta `migrate:fresh` sobre la conexión hospitalaria compartida.
