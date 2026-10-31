# Semana 13 — Registro de hallazgos

**Severidad:** Alta (bloquea un requisito M o expone datos), Media (degrada la seguridad, la integridad o la experiencia), Baja (mejora o riesgo acotado).
**Estado:** Corregido (con verificación), Pendiente (con responsable), Aceptado (riesgo documentado) o Externo (pertenece a otro módulo).

| Id | Sev. | Archivo:línea | Problema | Impacto | Recomendación | Estado |
|---|---|---|---|---|---|---|
| H-GOB-01 | Baja | `micro-his/gobernanza-auditoria/src/Persistence/PdoAuditLog.php:35` | El constructor cambia `PDO::ATTR_ERRMODE` de la conexión **compartida** que recibe. | Si otro componente esperaba el modo silencioso, cambia su comportamiento. Laravel ya usa `ERRMODE_EXCEPTION`, por lo que hoy el impacto es nulo. | Exigir el modo excepción como precondición (verificarlo y fallar) en lugar de imponerlo. | Aceptado |
| H-GOB-02 | Media | `src/Persistence/PdoAuditLog.php:86` | `SELECT head_hash … WHERE chain_id = 1 FOR UPDATE` serializa **todas** las escrituras de la red en una sola fila. | Con muchas escrituras concurrentes, las transacciones esperan el bloqueo y aumenta la latencia de las operaciones que auditan. | Cadena por hospital (`chain_id` = hospital) o sellado asíncrono por lotes. La decisión la toman Javier y Tenants (propuesta 001 §4.6). | Pendiente (Javier) |
| H-GOB-03 | Baja | `database/audit_log.sqlite.sql` (columna `occurred_at TEXT`) | En el esquema SQLite de prueba, la fecha es texto. | El orden es correcto con el formato del módulo, pero filas con otro formato podrían ordenarse mal. **Solo afecta las pruebas.** | Mantener un formato UTC fijo en los datos de prueba (ya se hace). | Aceptado |
| H-GOB-04 | Media | `src/Application/RecordAuditEvent.php:67` | Un evento inválido (por ejemplo, una denegación de RBAC con `user_id` vacío) lanzaba `InvalidAuditEvent` sin reportar nada, y `Authorizer` silencia la excepción. | La denegación seguía en pie, pero **no quedaba ningún rastro** del fallo de auditoría. | Reportar `audit_event_invalid` antes de relanzar. | **Corregido.** Se verificó que TC-U-21 y TC-I-07 **fallan sin el cambio** y pasan con él. |
| H-GOB-05 | Baja | `src/Domain/AuditFilter.php:17` | Paginación por `OFFSET` hasta la página 10 000 (desplazamiento de 1 000 000). | Las páginas profundas son costosas en PostgreSQL. | Paginación por cursor (`occurred_at`, `event_id`) en una versión 2 del contrato. | Pendiente |
| H-GOB-06 | Baja | `app/GobernanzaAuditoria/GobernanzaAuditoriaServiceProvider.php:43,61` | Doble autorización: el middleware `rbac:` y `RbacAccessGate` consultan los roles dos veces por petición. | Duplica la consulta a CENTRAL. Una denegación en el segundo paso la registraría dos veces solo si los roles cambian entre ambas llamadas. | Mantenerla (defensa en profundidad: el caso de uso es seguro fuera de Laravel) o inyectar una puerta de «ya autorizado» solo en Laravel. | Aceptado |
| H-GOB-07 | Alta | `origin/develop:app/Http/Middleware/TenantMiddleware.php:5` | El middleware `tenant` usa `App\Models\Tenant`, que **no existe** en develop (`app/Models` está vacío). | Ninguna ruta `/api/v1` con `tenant` funciona (el README del proyecto lo confirma: HTTP 500), incluida `/audit-events`. | Crear los modelos o adaptar el middleware al esquema federado (`hospitals`). | Externo (Usuarios/Tenants) |
| H-GOB-08 | Baja | `src/Domain/ContextSanitizer.php:23` | La lista de términos clínicos es conservadora: se verificó que elimina `medicamento_id` y `nota_count`. | Se pierde contexto legítimo, aunque queda indicado en `redacted_keys`. | Mantenerla (es preferible eliminar de más) y, si hace falta, agregar una lista blanca por módulo. | Aceptado |
| H-GOB-09 | Baja | `src/Domain/AuditEvent.php:103` (`rehydrate`; corrección en la línea 121) | Las filas de otros productores se devuelven sin revalidar el formato de `resource_type`, `resource_id` ni `trace_id`. | Un valor anómalo guardado por otro escritor se mostraría tal cual. Va escapado en JSON y el prototipo usa `textContent`, así que no hay XSS. | Aplicar los mismos patrones al leer y, si no cumplen, mostrar `null` y anotarlo en `redacted_keys`. | **Corregido.** Verificado con TC-W-15. |
| H-GOB-10 | Media | `micro-his/gobernanza-auditoria/public/prototipo/app.js:119` | Con 401 o 403 el prototipo mostraba el formulario de filtros (hallazgo F-02 de la Semana 9). | Sugería al usuario sin permiso que podía filtrar, lo que contradice W5 y RV-01. | Ocultar `.filters` con 401 y 403, y dar todo el ancho al mensaje. | **Corregido.** Verificado en el navegador: `display` de `.filters` = `none` para médico y sin sesión, `block` para admin ([s13-01](../evidencias/s13-01-acceso-denegado-sin-filtros.jpg)). |
| H-GOB-11 | Media | `origin/develop:app/Providers/RbacServiceProvider.php:21` y `config/rbac.php` | RBAC enlaza `DenialRecorder` → `LogDenials` (solo log), y `auditoria.ver` no está en el catálogo. | Las denegaciones no llegan a la bitácora y ninguna consulta real sería autorizada. | Enlazar `RbacDenialRecorder` y agregar `auditoria.ver` en `pending`, y luego en `roles`. | Externo (RBAC, María) |
| H-GOB-12 | Baja | `src/Application/AuditFilterParser.php:43` | Los nombres de parámetros desconocidos se devuelven en `errors` (truncados a 40 caracteres). | Refleja una entrada del cliente en la respuesta. No es explotable en JSON, pero es contenido no confiable. | Sanear el nombre (`[^a-z0-9_]` → `_`) antes de devolverlo. | **Corregido.** Verificado con TC-U-22 (`<script>x` → `_script_x`). |

## Resumen

| Estado | Alta | Media | Baja | Total |
|---|---|---|---|---|
| Corregido | 0 | 2 | 2 | 4 |
| Pendiente | 0 | 1 | 1 | 2 |
| Aceptado | 0 | 0 | 4 | 4 |
| Externo | 1 | 1 | 0 | 2 |
| **Total** | 1 | 4 | 7 | 12 |

## Verificación específica pedida

| Aspecto | Conclusión | Evidencia |
|---|---|---|
| Permisos | El código usa el mecanismo real; el permiso está pendiente de aprobación. | Puntos 1, 2 y 14; TC-I-04…06 |
| Aislamiento | Sin hallazgos en el módulo. | Puntos 3 y 4; TC-C-01, TC-N-02 y TC-N-07…09 |
| Errores | H-GOB-04 corregido. | Punto 8 |
| Persistencia | Riesgo de concurrencia (H-GOB-02); falta probar con PostgreSQL. | Puntos 11 y 12 |

## Seguimiento de hallazgos externos (2026-10-31)

La tabla y el resumen anteriores son el registro de la revisión de la Semana 13 y no se reescriben. Estado actual de
los dos hallazgos externos, verificado sobre `develop`:

| Id | Estado actual | Verificación |
|---|---|---|
| H-GOB-07 | **Resuelto por Usuarios/Tenants.** El middleware `tenant` ya no depende de `App\Models\Tenant`: resuelve el hospital con el modelo federado `Hospital`. | Con la aplicación y PostgreSQL locales, `GET /api/v1/audit-events` responde 200 con los eventos del hospital de la sesión; un token de otro hospital recibe 403 `TENANT_MISMATCH` (`GovernanceHttpTest::test_real_token_cannot_select_foreign_hospital_in_header_or_query`). |
| H-GOB-11 | **Resuelto por RBAC y Tenants.** `auditoria.ver` está activo en `config/rbac.php` y `DenialRecorder` se enlaza con `AuditDenials`, que escribe en `audit_log`. | `GovernanceHttpTest::test_medico_is_denied_before_filters_and_denial_is_persisted_once`: 403 `RBAC_DENIED` y una sola fila `rbac.access_denied` con el mismo `trace_id`. |

Con esto quedan **0 hallazgos externos abiertos**. Siguen pendientes H-GOB-02 (bloqueo único de la cadena, decisión de
Javier y Tenants) y H-GOB-05 (paginación por cursor en una versión 2 del contrato).
