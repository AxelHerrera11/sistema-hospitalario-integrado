# Plan SQA — ASII-06, Semana 14

## Objetivo y alcance

Prevenir y detectar defectos del catálogo, con evidencia reproducible sobre
creación, consulta, estados, integridad, seguridad y trazabilidad. Este PR
entrega el plan; no implementa CRUD, nuevas reglas clínicas ni aislamiento definitivo.
La aceptación del plan documental no equivale a la aceptación funcional del módulo.

## Requisitos y criterios de aceptación

Fuente normativa del módulo: [RF/RNF de Semana 2](../semana-02/rf-rnf-criterios.md),
[trazabilidad de Semana 4](../semana-04/trazabilidad.md) e issue #11.
Los identificadores siguientes pertenecen a esas fuentes; M y R son del plan SQA.

| Requisito existente | Criterio de aceptación y evidencia a exigir | Métrica |
|---|---|---|
| RF-01, RF-03 | Alta válida conserva identificador, relación y defaults `active`/`available`; micro-HIS y futura prueba HTTP de creación. | M-01, M-02, M-03 |
| RF-02, RF-04 | Código ward único; cama única por `(ward_id, code)`; ward inexistente rechazado sin persistir. Prueba PostgreSQL negativa pendiente. | M-02 |
| RF-05, RF-06 | Solo estados ward `active/inactive` y cama `available/occupied/maintenance/inactive`; inválido rechazado sin cambio. | M-03 |
| RNF-01 | FK, unicidad y CHECKs respetados en persistencia real; suite de integridad a ejecutar en Semana 15. | M-02, M-03 |
| RNF-02 | Fallo de alta no deja registro parcial; el fallo fake actual no acredita atomicidad real. | M-01, M-05 |
| RNF-03 | Entrada, dominio, servicio y persistencia separados; revisar dependencias antes de cualquier corrección. | M-06 |
| RNF-04 | Repositorios sustituibles con comportamiento equivalente; pendiente comparar fake/PDO con mismos casos. | M-01, M-06 |

Seguridad tenant/JWT/RBAC procede del contrato integrado y alcance mínimo de
#11; no se inventa un RNF adicional. Aceptar consultas exige denegar acceso sin
`camas.ver`, identidad inválida y cruce entre hospitales; M-04 mide estos casos.

## Arquitectura y brecha de creación

Flujo de lectura: Vue `WardCatalogPage` → Axios → rutas de
`SalasCamasServiceProvider` → `WardCatalogController` → `CatalogService` →
contratos `WardRepository`/`BedRepository` → adaptadores PDO → conexión `hospital`.
Tenant y JWT preceden a `rbac:camas.ver` y `EnsureHospitalDatabaseTenant`.
Referencias de diseño: [arquitectura](../semana-04/arquitectura.md) y
[prototipo](../semana-11/README.md).

GET `/api/v1/wards` retorna `ward_id, code, name, location, status`;
GET `/api/v1/wards/{ward}/beds` retorna `bed_id, ward_id, code, type, status`.
No requieren payload; usan Bearer y `X-Tenant-ID`. Colección vacía: 200;
ward ausente: 404; autenticación/autorización: 401/403; persistencia: 503.
Header mal formado: 400; hospital inexistente: 404. El guard temporal da 403
para hospital distinto y 503 sin asignación; no selecciona dinámicamente una base.

`CatalogService::createWard/createBed` y el controlador standalone admiten
creación (201, 422 por dominio, 404 ward ausente, 500 fallo de persistencia).
No hay POST Laravel ni formulario de alta del catálogo. Antes de publicarlos,
exigir validación de entrada, permiso de escritura acordado (no inventarlo),
protección tenant, unicidad/FK, fallos sin escritura parcial y pruebas negativas.
La UI de consulta no permite editar estados. No se infieren transiciones,
ocupación ni aforo desde el enum; coordinar esos contratos sin alterar otros módulos.

## Controles de calidad

Existente = inspeccionado en código; pendiente = propuesto, aún sin acreditación.
Una prueba existente no implica ejecución exitosa en este entorno.

| Control | Tipo | Aplicación y evidencia | Riesgos / métricas |
|---|---|---|---|
| C-01 | Preventivo | Tenant/JWT, `camas.ver` y guard temporal existentes; exigir revisión ante cambios de rutas. | R-01, R-02, R-07 / M-04 |
| C-02 | Preventivo | FK, UNIQUE y CHECKs de migración `2026_08_11_000300`; validación Domain y servicio existentes. No ejecutar migraciones aquí. | R-03, R-04, R-06 / M-02, M-03 |
| C-03 | Preventivo | Revisión por capas y contrato de alta/capacidad antes de habilitar UI o escritura HTTP; pendiente. | R-05, R-06, R-09 / M-06 |
| C-04 | Detectivo | Ejecutar build, Node, endpoint, aislamiento, PDO y standalone; guardar comando, SHA y bloqueos. | R-01, R-03, R-07, R-08, R-09 / M-01, M-04, M-05 |
| C-05 | Detectivo | Casos negativos de duplicados, FK, estados y rollback en base de test; ampliar cobertura en Semana 15. | R-03, R-04, R-06, R-08 / M-02, M-03, M-05 |
| C-06 | Detectivo | Revisión de errores sanitizados por código HTTP/request ID; monitoreo periódico propuesto, sin tokens ni datos clínicos. | R-02, R-08, R-09 / M-05, M-07 |
| C-07 | Detectivo | Recorrido autenticado, teclado y estados Vue con identidad autorizada; pendiente. | R-09, R-10 / M-06, M-07 |
| C-08 | Detectivo | Revisar trazabilidad y defectos con responsable, estado, evidencia y cierre independiente. | R-01 a R-10 / M-06, M-07 |

## Medición, responsables y decisión

[Métricas](./metricas.md) define denominadores y umbrales; no excluir errores
ambientales para aparentar cumplimiento. Registrar N/A cuando no hay muestra.
Conservar por ejecución fecha, SHA, entorno de test, salida resumida, numerador,
denominador y evidencia; no mezclar históricos con resultados nuevos.

| Rol | Responsable | Acción |
|---|---|---|
| Autoría y seguimiento documental | Madelin Cerón | Mantener plan, mediciones, riesgos y calendario. |
| Revisión técnica | Pendiente de asignación | Contrastar fuentes, arquitectura y suficiencia de controles. |
| Corrección documental | Madelin Cerón | Corregir sus documentos y registrar observaciones. |
| Corrección técnica | Pendiente de asignación | Atender defectos con autorización en una entrega técnica separada. |
| Verificación independiente | Pendiente de asignación | Reproducir evidencia y verificar cierre sin autoaprobación. |
| Aprobación del PR | Registrada en GitHub (`.github/BRANCH_PROTECTION.md`) | Aceptación documental del plan; no equivale a la aceptación funcional del módulo. |

Madelin no asume aprobaciones ni asigna trabajo a otros integrantes. Los roles
pendientes deben confirmarse antes de un cierre. Ante umbral incumplido,
registrar defecto/bloqueo, priorizar según matriz y repetir la medición tras
la corrección. No cerrar riesgo alto por documentación o por falta de entorno.
El plan se acepta documentalmente con 5–7 métricas completas, matriz con controles,
responsables, calendario, fuentes y resultados honestos, tras revisión independiente.
El cierre funcional requiere además evidencia pendiente y decisión autorizada.
