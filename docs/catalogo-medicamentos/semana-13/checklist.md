# Checklist de revisión (14 ítems) y autoverificación

La columna "Autoverificación" es de la **autora**, no de los revisores. Los resultados vienen de sondas ejecutadas el 20/10/2026 sobre la rama de la Semana 13.

| # | Área | Pregunta | Dónde mirar | Autoverificación |
|---|---|---|---|---|
| C01 | Dominio | ¿Todo alta nace `active` y valida obligatorios, longitudes y formato del código? | `Domain/Medication.php`, `tests/domain.php` | Cumple (pruebas en verde) |
| C02 | Dominio | ¿Repetir la vigencia actual se rechaza y no hay borrado ni vencimiento de lotes? | `Medication::changeValidity`, `tests/architecture.php` | Cumple |
| C03 | Dominio | ¿Hay código sin uso o reglas duplicadas fuera del dominio? | `src/`, `Router.php` | **AV1:** `Medication::normalizeCode()` no tiene usos (0 referencias) |
| C04 | Integridad | ¿La unicidad del código se garantiza también ante carreras (restricción de base)? | `UNIQUE(code)`, `PdoMedicationCatalog::insert` | Cumple: UNIQUE → 409 |
| C05 | Integridad | ¿Ningún éxito se informa sin confirmación (filas afectadas)? | `rowCount`, `tests/pdo.php` | Cumple |
| C06 | Integridad | ¿Ambos adaptadores devuelven los mismos resultados para los mismos datos? | `tests/repository.php` | **AV3:** "ácido" encuentra "ÁCIDO ACETILSALICÍLICO" en InMemory (1) y no en SQLite (0). La suite solo usa datos ASCII |
| C07 | API | ¿Cada operación del OpenAPI tiene permiso, `X-Tenant-ID`, errores y `trace_id`? | `semana-05/openapi.yaml` | Cumple en el contrato; no implementado en Laravel |
| C08 | API | ¿Los códigos HTTP del micro-HIS coinciden con los del contrato? | `CatalogResponse`, `Router`, `contrato-api.md` | Cumple para 400, 404, 405, 409, 422 y 503; el micro-HIS no emite 401/403 |
| C09 | Permisos | ¿Cada operación exige su permiso y una ruta nueva nace denegada? | `config/rbac.php`, `Router` | **AV4:** el micro-HIS no aplica permisos (0 referencias); la escritura depende de permisos propuestos |
| C10 | Tenant | ¿El hospital sale del contexto autenticado y nunca del cuerpo? | `public/index.php` | AV4: sin tenant en el micro-HIS (una base = un hospital); pendiente para Semana 16 |
| C11 | Seguridad | ¿Todo valor externo va por sentencia preparada y los datos se muestran como texto? | `Persistence/`, `tests/pdo.php`, prototipo D11 | Cumple |
| C12 | Seguridad | ¿Los identificadores expuestos son no enumerables? | `PdoMedicationCatalog::insert` | **AV5:** ids secuenciales (1, 2, …); H5 usa UUID |
| C13 | Dependencias | ¿Las capas respetan Presentation → Application → Domain ← Persistence? | `tests/architecture.php` | Cumple |
| C14 | Dependencias | ¿La composición está en un solo lugar? | `app.php`, `bin/catalogo.php` | **AV2:** `bin/catalogo.php` no usa `app.php` y repite la composición |

## Hallazgos de la autoverificación (formato del plan)

| ID | Ítem | Archivo:línea | Descripción | Severidad | Decisión | Commit o prueba |
|---|---|---|---|---|---|---|
| AV1 | C03 | `src/Domain/Medication.php` (`normalizeCode`) | Método público sin usos | Menor | Eliminar en Semana 15 | Pendiente |
| AV2 | C14 | `bin/catalogo.php` | Composición duplicada respecto a `app.php` | Menor | Reutilizar `catalog_router()` | Pendiente |
| AV3 | C06 | `src/Persistence/PdoMedicationCatalog.php` (`search`) | `LOWER` de SQLite solo pliega ASCII; diverge de InMemory | Mayor | Corregir y cubrir con prueba en Semana 15 | Pendiente |
| AV4 | C09, C10 | micro-HIS completo | Sin permisos ni tenant | Mayor | Alcance de Semanas 16 y 17 (Laravel) | Pendiente |
| AV5 | C12 | `database/schema.sql` | Ids enumerables | Menor | Usar UUID al integrar con H5 | Pendiente |

Sondas ejecutadas: `grep` de usos y referencias sobre `src/`, `bin/`, `public/` y `app.php`, y un script PHP que inserta "ÁCIDO ACETILSALICÍLICO" en ambos adaptadores y busca "ácido".
