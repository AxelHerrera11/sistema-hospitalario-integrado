# Semana 5 — Contrato API de consulta de la bitácora

## 1. Convenciones existentes que se siguen

| Convención | Origen real | Aplicación |
|---|---|---|
| Prefijo `/api/v1` | `bootstrap/app.php` (`apiPrefix: 'api/v1'`) y `RbacServiceProvider` | `GET /api/v1/audit-events` |
| Middleware `tenant` → `jwt.auth` → `rbac:<permiso>` | `RbacServiceProvider::boot()` y el comentario de `RequirePermission` | El mismo orden en `GobernanzaAuditoriaServiceProvider::boot()`. |
| Formato de error `{code, trace_id}` y `Cache-Control: no-store, private` | `App\Rbac\RequirePermission::error()` | Lo usan todos los errores del módulo. |
| `X-Tenant-ID` comparado con `hash_equals` contra el tenant del servidor | `RequirePermission` | `AuditHttpHandler` lo repite (defensa en profundidad). |
| Permisos `modulo.accion` sin comodines | `config/rbac.php` y la regex de `RequirePermission` | `auditoria.ver` (propuesto). |

## 2. Endpoint

```
GET /api/v1/audit-events
Authorization: Bearer <JWT>
X-Tenant-ID: <hospital_id>
X-Request-ID: <opcional, ^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$>
```

No hay `POST`, `PUT`, `PATCH` ni `DELETE`: la bitácora no se modifica por HTTP. El registro lo hacen los módulos dentro del servidor, mediante `RecordAuditEvent`.

### Parámetros (query)

| Parámetro | Tipo / formato | Ejemplo | Validación |
|---|---|---|---|
| `usuario` | identificador | `aaaaaaaa-0000-4000-8000-000000000001` | `formato_invalido` |
| `modulo` | `^[a-z][a-z0-9_]{0,39}$` | `rbac` | `formato_invalido` |
| `accion` | `a.b` en minúsculas, hasta 80 caracteres | `access_denied`, `nota.firmada` | `formato_invalido` |
| `desde` | `AAAA-MM-DD` o `AAAA-MM-DDTHH:MM[:SS][Z\|±HH:MM]` | `2026-10-15` | `fecha_invalida` |
| `hasta` | Igual; **inclusivo** (día, minuto o segundo) | `2026-10-15` | `fecha_invalida`, `anterior_a_desde` |
| `resultado` | `success` · `failure` · `denied` | `denied` | `valor_no_permitido` |
| `correlacion` | `^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$` | `trace-1a2b3c4d5e6f` | `formato_invalido` |
| `pagina` | entero 1…10 000, por defecto 1 | `2` | `fuera_de_rango` |
| `por_pagina` | entero 1…100, por defecto 25 | `50` | `fuera_de_rango` |

- Una fecha sin zona se interpreta en la zona del servidor (`config('app.timezone')`; en la demostración, `America/Guatemala`).
- Cualquier otro parámetro da `parametro_no_permitido`.
- `tenant_id`, `hospital_id`, `tenant` o `hospital` solo se aceptan si son **idénticos** al tenant del servidor; si no, 403 `TENANT_MISMATCH`.

### Respuesta 200

```json
{
  "data": [{
    "event_id": "b1de6b5f-a7ca-44ff-8df6-6f65fcf600a3",
    "occurred_at": "2026-10-15T20:51:00.000Z",
    "actor_id": "aaaaaaaa-0000-4000-8000-000000000002",
    "tenant_id": "11111111-1111-4111-8111-111111111111",
    "module": "rbac", "action": "access_denied", "event": "rbac.access_denied",
    "resource": {"type": "permission", "id": "auditoria.ver"},
    "outcome": "denied", "occurred": false, "criticality": "security",
    "correlation_id": "trace-1a2b3c4d5e6f", "reason": "permiso_faltante",
    "context": {"permission": "auditoria.ver"}, "redacted_keys": []
  }],
  "meta": {"page": 1, "per_page": 25, "total": 1, "total_pages": 1},
  "trace_id": "req-proto-01"
}
```

Cabeceras: `Cache-Control: no-store, private` y `X-Request-ID: <trace_id>`.

### Errores

| HTTP | `code` | Cuándo | Lo emite |
|---|---|---|---|
| 401 | `AUTH_REQUIRED` | No hay usuario autenticado. | `RequirePermission` / `AuditHttpHandler` |
| 400 | `TENANT_REQUIRED` | Falta `X-Tenant-ID`. | Ídem |
| 403 | `TENANT_MISMATCH` | `X-Tenant-ID` o un parámetro de tenant distinto al del servidor. | Ídem |
| 403 | `RBAC_DENIED` | Falta `auditoria.ver` en el hospital actual. `trace_id` = el de la denegación registrada. | RBAC |
| 422 | `AUDIT_FILTER_INVALID` | Filtros inválidos. Incluye `errors: {param: código}`. | `AuditHttpHandler` |
| 503 | `RBAC_UNAVAILABLE` | RBAC no pudo resolver los roles o registrar la denegación. | RBAC / `AuditHttpHandler` |
| 503 | `AUDIT_UNAVAILABLE` | Falló la lectura de la bitácora o el registro de la consulta. | `AuditHttpHandler` |
| 503 | `TENANT_CONTEXT_UNAVAILABLE` | El servidor no resolvió el tenant. | `AuditHttpHandler` |
| 500 | `INTERNAL_ERROR` | Error inesperado (sin detalle). | `AuditHttpHandler` |

El 403 se responde **antes** de validar los filtros (TC-N-11): un usuario sin permiso no recibe nunca un 422.

## 3. Autenticación, autorización y permisos

- **Autenticación:** JWT (`tymon/jwt-auth`, guard `api`) mediante el middleware `jwt.auth`. El controlador toma el `user_id` de `$request->user('api')->getAuthIdentifier()`.
- **Tenant:** `TenantMiddleware` fija el atributo `tenant`; el controlador usa `$tenant->getKey()`.
- **Autorización:** el middleware real `rbac:auditoria.ver` (`App\Rbac\RequirePermission` → `MicroHis\Rbac\Application\Authorize`). Además, `SearchAuditEvents` vuelve a consultar `AccessGate` (`RbacAccessGate` sobre el mismo `Authorize`) para que el caso de uso sea seguro también fuera de Laravel. Ver H-GOB-06 en la Semana 13.

**Permiso propuesto** (no existe en `config/rbac.php`; hay que pedirlo a María):

| Permiso | Formato | Roles propuestos | Estado |
|---|---|---|---|
| `auditoria.ver` | `modulo.accion` | `superadmin` (el README: «gobernanza para super admin»). `admin_hospital` queda **por decidir**. | Propuesto. Hasta que se apruebe, toda consulta da 403. |

Cambio sugerido para RBAC (no aplicado):

```php
// config/rbac.php → 'pending'
'auditoria.ver' => ['superadmin'],
```

## 4. Aislamiento por tenant y prevención de filtraciones

1. El tenant es siempre el del servidor (AT-01…05). `AuditEventReader::search($tenantId, …)` y todo SQL lleva `hospital_id = :tenant`.
2. No existe un endpoint por id (`/audit-events/{id}`) que permita sondear eventos de otro hospital.
3. La respuesta no expone `previous_hash`, `event_hash` ni el contexto eliminado.
4. Los errores no distinguen si un usuario o un evento existen en otro tenant.
5. El servidor de la API no hace distinción entre un filtro sin coincidencias y un tenant ajeno: ambos devuelven `data: []` o 403, según corresponda.

## 5. Consultas parametrizadas

Todas las sentencias usan `PDO::prepare` y `bindValue`, incluido `LIMIT`/`OFFSET` con `PARAM_INT`. En el filtro de módulo se escapan `!`, `_` y `%` con `ESCAPE '!'`. Ver la [propuesta SQL §5](../propuestas-sql/001_audit_log_indices_consulta.md#5-consultas-que-generará-el-módulo-parametrizadas).

## 6. Fallos de persistencia

- **Lectura fallida:** 503 `AUDIT_UNAVAILABLE` y reporte `audit_query_unavailable`, con la causa como código.
- **Registro fallido de la consulta (evento `Security`):** 503, sin entregar datos (falla cerrada, TC-N-17).
- La bitácora nunca responde «0 resultados» cuando en realidad falló.

## 7. Observabilidad y correlación

- `trace_id` en todas las respuestas: el `X-Request-ID` del cliente si es seguro o `req-<16 hex>` generado.
- El evento `gobernanza.auditoria_consultada` guarda `correlation_id = trace_id` y los filtros usados.
- Las denegaciones conservan el `trace_id` de RBAC, que el usuario ve en el 403 y el auditor puede buscar con `correlacion=`.
- `FailureReporter` envía al log `gobernanza.<código>` con `tenant_id`, `correlation_id` y la causa.

## 8. Plan para sustituir InMemory por persistencia real

| Paso | Responsable | Cambio | Verificación |
|---|---|---|---|
| 1 | Cindy/Tenants y Javier | Fusionar las migraciones `audit_log` y `audit_chain_state`, o decidir `local_access_audits`. | `tests/Database` (Javier) |
| 2 | Javier | Aplicar la propuesta 001 (índices y restricción de `result`). | `EXPLAIN ANALYZE` |
| 3 | María (RBAC) | Agregar `auditoria.ver` y enlazar `DenialRecorder::class` → `RbacDenialRecorder` en `RbacServiceProvider`. | TC-I-01…07 con PostgreSQL |
| 4 | Equipo | Registrar `App\GobernanzaAuditoria\GobernanzaAuditoriaServiceProvider` en `bootstrap/providers.php`. | `php artisan route:list --path=api/v1/audit-events` |
| 5 | Gobernanza | Prueba de Laravel contra PostgreSQL de prueba (comando en la Semana 15). | `php artisan test` |

Los casos de uso, los controladores y las pruebas de contrato **no cambian**: solo cambia el enlace `AuditLogger`/`AuditEventReader` → `PdoAuditLog`, que ya está en el proveedor.
