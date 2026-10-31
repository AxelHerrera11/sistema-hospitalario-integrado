# Validación de integración

Fuentes: [RF/RNF](../semana-02/rf-rnf-criterios.md),
[arquitectura](../semana-04/arquitectura.md), provider, controlador y vista actuales.
RF-01/03 respaldan los fixtures creados mediante servicio; RF-04 y RNF-01 la
relación ward/bed; RF-05/06 los estados. Seguridad/consulta proceden de #11 y
contratos integrados, sin inventar nuevos identificadores de requisitos.

| Flujo / escenario | Evidencia ejecutada | Resultado y límite |
|---|---|---|
| Login → tenant/JWT/RBAC → controlador → servicio → PDO → wards | CatalogAuthenticatedIntegrationTest, 1 test/28 aserciones | PASS: login, listado con ward sintética y campos/defaults; sin fake de catálogo |
| Selección lógica de ward → consulta beds | Integración autenticada y WardCatalogFlowTest | PASS por capas; cama de ward correcta, relación y available. No es un solo recorrido de navegador |
| Catálogo vacío | WardCatalogEndpointTest y prueba frontend de reintento | PASS en fixture/doble; no se vacía una tabla real para producir este escenario |
| Ward sin camas / ward inexistente | Integración autenticada sobre PostgreSQL real | PASS: 200 data=[] y count=0; 404 error.code WARD_NOT_FOUND |
| Sin autenticación / sin camas.ver | Integración autenticada y suites endpoint/aislamiento | PASS: 401 TOKEN_MISSING con JWT real; 403 RBAC_DENIED en suites existentes |
| Identidad permitida / conexión de otro hospital | Integración autenticada y CatalogHospitalIsolationTest | PASS: 200 hospital asignado y 403 HOSPITAL_DATABASE_TENANT_MISMATCH; contención temporal |
| Error API / reintento | WardCatalogFlowTest y endpoint | PASS: 503 legible, segundo intento limpia error; fake verifica respuesta segura de persistencia |
| Carga / selección / respuesta obsoleta | WardCatalogFlowTest, 4 tests | PASS: flags de carga, vacío, selección tardía no reemplaza camas; sin DOM |
| Ward 404 y acceso 403 en frontend | WardCatalogFlowTest | PASS: 404 no reintenta camas; 403 vacía selección/datos |
| Navegación protegida | WardCatalogNavigationTest, 2 tests | PASS: permiso de ruta/menú; no prueba guard ejecutado en navegador |
| Integridad y estados en PostgreSQL | Pruebas I01–I05 de #1365 ejecutadas temporalmente | PASS: FK, dos UNIQUE y dos CHECK; originales y rollback verificados |
| Responsive básico | CSS inspeccionado: cortes 700/380 px, tarjetas reutilizables | Pendiente de navegador; build no demuestra ausencia de desbordamiento |

## Contrato vigente

Solo GET `/api/v1/wards` y GET `/api/v1/wards/{ward}/beds`, sin cuerpo,
Bearer y `X-Tenant-ID`, permiso `camas.ver`. Ambas mantienen tenant/JWT/RBAC
y EnsureHospitalDatabaseTenant. Ward: ward_id, code, name, location, status;
cama: bed_id, ward_id, code, type, status. Respuesta `data` y `meta` (count,
request_id; beds además ward_id). Vacío válido: 200; ward inválida/ausente: 404.
Persistencia: 503 con error.code WARD_CATALOG_UNAVAILABLE/BED_CATALOG_UNAVAILABLE;
RBAC: 403 code RBAC_DENIED. Guard: error string y 403 por desacuerdo o 503 sin
asignación. No se inventan endpoints de alta ni permisos de escritura.

El nuevo test exige testing, host 127.0.0.1, his_hospital_test:55433 y adaptadores
PDO, inserta fixtures por servicio en transacción y revierte en finally; comprueba
que no queden sus wards. Las identidades solo viven en SQLite en memoria.
No se afirma equivalencia con producción, atomicidad de negocio multirregistro
ni selección dinámica de base de datos por hospital.
