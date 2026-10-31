# Plan de pruebas — ASII-06

Objetivo: contrastar RF/RNF de [Semana 2](../semana-02/rf-rnf-criterios.md) con
dominio, caminos del servicio, contrato HTTP y restricciones reales del catálogo.
Los requisitos de seguridad/consulta proceden de #11 y del contrato integrado;
no se les inventa un identificador RF/RNF de creación.

## Entorno, procedimientos y clasificación

Ejecutar desde la raíz con dependencias instaladas y phpunit.xml: PHP 8.2.12,
PHPUnit 11.5.55. Datos sintéticos; nunca datos clínicos ni migraciones destructivas.
P-U = `php vendor/phpunit/phpunit/phpunit tests/Unit/SalasCamas/CatalogRulesTest.php`.
P-H = `php artisan test tests/Feature/SalasCamas/WardCatalogEndpointTest.php`.
P-I = `php artisan test tests/Feature/SalasCamas/CatalogConstraintsTest.php`.
Cada fila es un caso; U y CB son conjuntos separados aunque comparten archivo.
Los cuatro CN reutilizan métodos existentes, no pruebas nuevas duplicadas.
Los restantes casos de esas suites son regresión adicional, sin reclasificarlos.

| ID / tipo | Objetivo y requisito | Precondición / entrada | Resultado esperado | Procedimiento / método | Resultado obtenido / estado |
|---|---|---|---|---|---|
| U01 / unitaria | Normalización/default de ward; RF-01, RF-05 | Sin DB; code/name/location con espacios | Campos recortados, active, id null | P-U / `test_u01_ward_normalizes_input_and_defaults_to_active` | Coincide; PASS |
| U02 / unitaria | Asociación y estados de cama; RF-03, RF-06 | Sin DB; ward-1, código/tipo con espacios; cuatro estados válidos | Relación conservada, default available y cuatro valores aceptados | P-U / `test_u02_bed_preserves_relation_and_all_valid_states` | Coincide; PASS; los cuatro valores no cuentan como cuatro unitarias |
| U03 / unitaria | Estado ward válido/inválido; RF-05 | Ward con datos completos; inactive y broken | inactive aceptado; WARD_STATUS_INVALID para broken | P-U / `test_u03_ward_accepts_inactive_and_rejects_unknown_state` | Coincide; PASS |
| CB01 / caja blanca | Corte temprano del servicio; RF-04, RNF-03 | Mock findById('missing') retorna null | CatalogOperationFailure WARD_NOT_FOUND; nunca listByWardId | P-U / `test_cb01_missing_ward_short_circuits_bed_repository` | Excepción y ausencia de llamada verificadas; PASS |
| CB02 / caja blanca | Traducción de regla de dominio; RF-06, RNF-03 | Mock ward existente; createBed(' W-1 ', status broken) | Búsqueda W-1; DomainRuleViolation traducida a BED_STATUS_INVALID; nunca insertBed | P-U / `test_cb02_invalid_bed_is_translated_without_insert` | Camino catch y cero inserciones verificados; PASS |
| CN01 / caja negra HTTP | Consulta autorizada; #11, contrato GET | Fixture reader con camas.ver, 2 wards; GET /api/v1/wards | 200, data con ward_id/code/name/location/status, meta.count=2 y request_id | P-H / `test_route_is_registered_and_returns_only_catalog_fields` | Coincide; PASS; se usa su resultado HTTP, no sus aserciones internas para el mínimo |
| CN02 / caja negra HTTP | Camas de ward correcta; #11, relación de RF-03 | Fixture con 2 camas en ward solicitada y 1 en otra; GET beds | 200, solo 2 camas, ward_id correcto y campos bed_id/ward_id/code/type/status | P-H / `test_ward_beds_route_returns_only_beds_of_the_requested_ward` | Coincide; PASS |
| CN03 / caja negra HTTP | Denegar sin permiso; #11/seguridad | other-user autenticado sin camas.ver; GET /api/v1/wards | 403, code RBAC_DENIED | P-H / `test_request_without_permission_is_denied` | Coincide; PASS |
| CN04 / caja negra HTTP | Recurso inexistente; #11/contrato | UUID de ward ausente y not-a-uuid; GET beds | 404, error.code WARD_NOT_FOUND | P-H / `test_missing_or_invalid_ward_identifier_returns_not_found` | Coincide en ambas entradas; PASS, un solo caso |
| I01 / integración | FK ward/bed; RF-04, RNF-01 | P-I, ward/bed válidos en transacción; INSERT cama con UUID ward inexistente | SQLSTATE 23503; catálogo válido preservado | P-I / dataset `I01 foreign key` | Coincide; PASS |
| I02 / integración | Ward único; RF-02, RNF-01 | P-I, ward existente; INSERT mismo code | SQLSTATE 23505; un ward original | P-I / dataset `I02 duplicate ward` | Coincide; PASS |
| I03 / integración | Cama única por ward; RF-04, RNF-01 | P-I, cama B-1 existente; INSERT B-1 en misma ward | SQLSTATE 23505; una cama original | P-I / dataset `I03 duplicate bed` | Coincide; PASS |
| I04 / integración | CHECK ward; RF-05, RNF-01 | P-I, ward active; UPDATE status broken | SQLSTATE 23514; original active | P-I / dataset `I04 ward state` | Coincide; PASS |
| I05 / integración | CHECK cama; RF-06, RNF-01 | P-I, cama available; UPDATE status broken | SQLSTATE 23514; original available | P-I / dataset `I05 bed state` | Coincide; PASS |

En CB01 se observa `CatalogService::listBedsForWard`: condición findById === null,
throw y ausencia de consulta a camas. En CB02 se observa `createBed`: ward encontrada,
Bed::create rechaza estado, catch convierte error y preserva causa sin insertar.
Se verifican ramas significativas; no se afirma cobertura porcentual de líneas.

P-H sustituye tenant/JWT mediante TestIdentity y usa repositorio fake, manteniendo
RBAC real. No acredita login real. El aislamiento existente usa identidad CENTRAL
SQLite aislada y esquemas PostgreSQL temporales con JWT real de test; no servidores
hospitalarios independientes. P-I exige testing, driver pgsql, DB his_hospital_test
y puerto 55433; genera UUIDs, usa savepoint, confirma SQLSTATE y datos originales,
revierte todo en finally y verifica ausencia del ward al terminar.

## Casos pendientes, sin PASS

| ID | Objetivo / requisito | Precondición, entrada y procedimiento futuro | Esperado / resultado y estado |
|---|---|---|---|
| P01 | Alta HTTP/UI; RF-01 a RF-06 | Contrato de escritura y permiso autorizado implementados; enviar altas válidas/duplicadas/estados inválidos por interfaz publicada | Alta/rechazo según contrato por definir; no ejecutable hoy, POST/formulario ausentes |
| P02 | Atomicidad real multirregistro; RNF-02 | Operación transaccional de negocio definida; inyectar fallo tras primera escritura | Ningún registro parcial; pendiente, rollback del harness no acredita esta propiedad |
| P03 | Capacidad | Regla y campo de capacidad aprobados; probar límite y exceso | Decisión según contrato funcional; no ejecutable, no hay aforo máximo modelado |
| P04 | Equivalencia de adaptadores; RNF-04 | Misma matriz de aceptación en fake y PDO | Comportamiento equivalente; pendiente, ejecuciones separadas no prueban paridad completa |

Salida: mínimos académicos automatizados aprobados, regresiones ejecutadas y
brechas registradas; aceptación final requiere revisión independiente pendiente.
