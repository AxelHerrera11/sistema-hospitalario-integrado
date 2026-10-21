# Semana 4 — MVC, casos de uso y persistencia

## 1. Mapa MVC aplicado

| Rol MVC | Clase real | Qué hace | Qué **no** hace |
|---|---|---|---|
| Controlador (Laravel) | `app/GobernanzaAuditoria/AuditEventController.php` (37 líneas) | Lee el guard `api`, el atributo `tenant`, dos cabeceras y la query, y devuelve un `JsonResponse`. | No valida filtros, no consulta la base, no decide permisos. |
| Controlador (núcleo) | `src/Presentation/AuditHttpHandler.php` | Comprueba identidad y tenant y traduce excepciones a 401/400/403/422/503/500. | No contiene SQL ni reglas de filtrado. |
| Vista (JSON) | `src/Presentation/AuditEventPresenter.php` | Arma la forma pública del evento y de la página. | No expone hashes ni datos internos. |
| Modelo (dominio) | `src/Domain/*` | Entidad, reglas y contratos. | No conoce HTTP ni PDO. |
| Casos de uso | `RecordAuditEvent`, `SearchAuditEvents` | Coordinan la autorización, la validación, la lectura, el registro y la política de fallo. | No construyen SQL. |

## 2. Interfaces de repositorio

| Interfaz | Método | Contrato |
|---|---|---|
| `Domain\AuditLogger` | `append(AuditEvent): void` | Solo agrega eventos. Si falla, lanza `AuditStorageFailure` (nunca descarta en silencio). Participa en la transacción abierta del consumidor. |
| `Domain\AuditEventReader` | `search(string $tenantId, AuditFilter): AuditPage` | El tenant es un argumento separado y obligatorio. Orden `occurred_at DESC, event_id DESC`. Si falla, lanza `AuditStorageFailure`. |

No existen `update()` ni `delete()` (TC-W-04).

## 3. Adaptadores

| Adaptador | Almacén | Notas |
|---|---|---|
| `Persistence\InMemoryAuditLog` | Arreglo en memoria | Desarrollo, pruebas y demostración. Rechaza `event_id` duplicados. |
| `Persistence\PdoAuditLog` | `audit_log` + `audit_chain_state` (PostgreSQL; SQLite en pruebas) | SQL preparado; cadena SHA-256 compatible con Tenants (TC-R-01…03); `FOR UPDATE` solo en pgsql. Mensajes de error genéricos. |
| `Persistence\AuditRowMapper` | — | Traduce filas ↔ `AuditEvent`. Lee filas de otros productores (`logRaw`) sin inventar el resultado. |
| `Persistence\AuditChainHasher` | — | Una sola implementación del algoritmo de cadena dentro del módulo. |

**No se inventaron tablas ni se ejecutaron migraciones compartidas.** El adaptador PDO usa las columnas de las migraciones de `audit_log` de la rama de Tenants. Las pruebas cargan [`database/audit_log.sqlite.sql`](../../../micro-his/gobernanza-auditoria/database/audit_log.sqlite.sql), que reproduce esas columnas y el trigger append-only en una base SQLite en memoria.

## 4. Validación de entradas

`AuditFilterParser` es el único punto de entrada de los filtros. Acepta una lista blanca de 9 parámetros, rechaza tipos que no sean texto, aplica los formatos de dominio y valida fechas de forma estricta (rechaza `2026-02-30`). También valida que `desde` sea anterior a `hasta` y los límites de paginación. Los errores se acumulan y se devuelven todos juntos, ordenados por parámetro.

## 5. Manejo explícito de errores

| Excepción | Capa que la lanza | Traducción HTTP | Registro |
|---|---|---|---|
| `InvalidAuditEvent` | Domain | — (error del consumidor) | `audit_event_invalid` |
| `InvalidAuditFilter` | Application | 422 `AUDIT_FILTER_INVALID` | — |
| `AccessDenied` | Application | 403 `RBAC_DENIED` / 503 `RBAC_UNAVAILABLE` | La registra RBAC (`DenialRecorder`). |
| `AuditStorageFailure` | Persistence | 503 `AUDIT_UNAVAILABLE` | `audit_record_failed` / `audit_query_unavailable` |
| `\Throwable` inesperado | — | 500 `INTERNAL_ERROR` | `audit_query_internal_error` (solo la clase de la excepción) |

## 6. Separación entre SQL y reglas de negocio

- El SQL solo aparece en `src/Persistence/PdoAuditLog.php`. TC-A-03 comprueba que Presentation no contiene `SELECT|INSERT|UPDATE|DELETE`, y TC-W-10 que el adaptador no usa `query()`/`exec()`.
- Las reglas viven en Domain (validación del evento y saneamiento) y en Application (política de fallo, orden autorizar → validar → leer → auditar).

## 7. Sustitución del adaptador sin cambiar los casos de uso

`tests/contract.php` ejecuta los mismos 11 escenarios con `InMemoryAuditLog` y con `PdoAuditLog`; `tests/blackbox.php` ejecuta los mismos 15 escenarios HTTP con ambos. Los casos de uso son las mismas instancias de clase: solo cambia el objeto que se inyecta.

```php
foreach (['InMemory' => fn () => new InMemoryAuditLog(), 'PDO' => fn () => pdoStore()] as $name => $factory) {
    $store = $factory();
    $rec = new RecordAuditEvent($store, $clock, $reporter);   // mismo caso de uso
    // ... mismos escenarios y mismas aserciones
}
```
