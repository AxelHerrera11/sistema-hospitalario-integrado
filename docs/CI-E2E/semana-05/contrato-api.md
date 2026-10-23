# Contrato API — `/api/v1/qa/runs`

> **Estado:** [Implementado] en `micro-his/qa-calidad-versiones` (servidor de demo y CLI de CI). [Propuesto] su montaje en Laravel: requiere que María apruebe los permisos de abajo. Especificación OpenAPI: [openapi.yaml](openapi.yaml).

## Convenciones

- Identidad resuelta por el servidor (JWT + `TenantMiddleware` en Laravel; `X-Demo-Session` en la demo). Nunca se acepta `tenant_id` desde el cuerpo.
- `X-Tenant-ID` obligatorio y debe coincidir con el tenant de la sesión.
- `X-Request-ID` opcional; se devuelve igual si tiene formato seguro, si no se genera `req-…`.
- Respuestas `Cache-Control: no-store, private`. Cuerpo máximo 64 KiB.
- Errores con el formato de RBAC: `{"code": "...", "trace_id": "..."}`; 409 y 422 agregan `reason` y `message`.

## Endpoints

| Método | Ruta | Permiso (propuesto) | Éxito | Descripción |
|---|---|---|---|---|
| GET | `/api/v1/qa/runs?limit=1..50` | `qa.ejecuciones.ver` | 200 `{data: Run[]}` | Ejecuciones del tenant, más recientes primero |
| POST | `/api/v1/qa/runs` | `qa.ejecuciones.registrar` | 201 `{data: Run}` | Planificar (RF-01) |
| GET | `/api/v1/qa/runs/{id}` | `qa.ejecuciones.ver` | 200 `{data: Run}` | Detalle con la última decisión del gate |
| POST | `/api/v1/qa/runs/{id}/transitions` | `qa.ejecuciones.registrar` | 200 `{data: Run}` | `{"transition": "start" \| "complete" \| "abort", "reason"?}` |
| POST | `/api/v1/qa/runs/{id}/results` | `qa.ejecuciones.registrar` | 201 `{data: Run}` | Resultado de una suite con evidencias |
| POST | `/api/v1/qa/runs/{id}/gate-decisions` | `qa.gates.evaluar` | 201 `{data: Decision}` | Evaluar el gate con la política vigente |

No hay `PUT`, `PATCH` ni `DELETE`: un resultado o una decisión es evidencia y no se edita ni se borra (BN17 → 404).

## Esquemas

```json
// POST /api/v1/qa/runs
{ "release_version": "1.4.0-rc.1", "commit_sha": "<40 hex>", "environment": "ci",
  "suites": [ { "name": "phpunit-unit", "kind": "unit", "required": true },
              { "name": "e2e-auth-rbac", "kind": "e2e", "required": true } ] }

// POST /api/v1/qa/runs/{id}/results
{ "suite": "phpunit-unit", "total": 107, "failed": 0, "skipped": 0, "duration_ms": 37313,
  "evidence": [ { "kind": "junit", "path": "phpunit/unit.xml", "sha256": "<64 hex>", "bytes": 2370 } ] }

// Run (respuesta)
{ "id": "uuid", "tenant_id": "...", "release_version": "...", "commit_sha": "...", "environment": "ci",
  "status": "planned|running|completed|aborted", "created_by": "...", "created_at": "ISO-8601",
  "finished_at": null, "abort_reason": null,
  "suites": [ { "name": "...", "kind": "...", "required": true,
                "result": null | { "total": 0, "passed": 0, "failed": 0, "skipped": 0, "duration_ms": 0, "evidence": [] } } ],
  "gate": null | Decision }

// Decision
{ "run_id": "uuid", "verdict": "approved|rejected|blocked", "policy_version": "qg-0.1-propuesta",
  "evaluated_by": "...", "evaluated_at": "ISO-8601",
  "findings": [ { "rule": "SUITE_MISSING", "severity": "blocker|rejection|warning", "suite": "e2e-auth-rbac", "detail": "..." } ] }
```

Datos obligatorios y validaciones: SemVer; SHA-1 de 40 hex minúsculas; ambiente `local|ci|staging`; 1–30 suites con nombre `[a-z0-9][a-z0-9._-]{1,62}` único y `kind` del enum `syntax|lint|static_analysis|build|unit|integration|contract|e2e`; `required` booleano (texto `"false"` → 422); al menos una unit y una e2e obligatorias; enteros ≥ 0 con `failed + skipped ≤ total`; hasta 20 evidencias con `kind` `junit|log|screenshot|summary`, ruta relativa sin `..`, SHA-256 y `bytes ≥ 1`.

## Códigos de estado

| Código | `code` | Cuándo |
|---|---|---|
| 400 | `TENANT_REQUIRED`, `INVALID_JSON` | Falta `X-Tenant-ID`; cuerpo no es JSON |
| 401 | `AUTH_REQUIRED` | Sin identidad |
| 403 | `TENANT_MISMATCH`, `RBAC_DENIED` | Tenant distinto al de la sesión; sin permiso (la denegación se audita) |
| 404 | `QA_RUN_NOT_FOUND`, `ROUTE_NOT_FOUND` | Ejecución inexistente o de otro tenant; ruta o método no soportado |
| 409 | `QA_RUN_STATE_CONFLICT` | Transición inválida o resultado duplicado |
| 413 | `PAYLOAD_TOO_LARGE` | Cuerpo > 64 KiB |
| 422 | `QA_VALIDATION_FAILED` | Regla de entrada (`reason` dice cuál) |
| 503 | `RBAC_UNAVAILABLE`, `QA_STORAGE_UNAVAILABLE`, `TENANT_CONTEXT_UNAVAILABLE` | Dependencia caída: falla cerrado, nada se guarda |
| 500 | `INTERNAL_ERROR` | Error no previsto; la causa solo va al reporte interno |

## Permisos (propuesta pendiente)

Ningún permiso `qa.*` existe hoy en `config/rbac.php`; no se agregaron. Propuesta para revisar con María:

| Permiso propuesto | Roles sugeridos | Justificación |
|---|---|---|
| `qa.ejecuciones.ver` | `superadmin`, `admin_hospital` | Consultar ejecuciones y decisiones |
| `qa.ejecuciones.registrar` | `superadmin` + cuenta de servicio de CI | Planificar, transicionar y registrar resultados |
| `qa.gates.evaluar` | `superadmin` + cuenta de servicio de CI | Emitir el veredicto de una versión |

> **Comentario para María:** «Hola María. El módulo QA/CI (fila 25) expone `/api/v1/qa/runs` solo en su micro-HIS por ahora. Para montarlo en Laravel con tu middleware necesitaría `qa.ejecuciones.ver`, `qa.ejecuciones.registrar` y `qa.gates.evaluar` (tabla en `docs/CI-E2E/semana-05/contrato-api.md`). No los agregué al catálogo. Dos preguntas: ¿preferís un prefijo distinto de `qa`? y ¿cómo modelamos la cuenta de servicio de CI, que no es un rol del catálogo? Mientras tanto uso un `AccessGate` estático y el contrato de rutas del CI ya verifica que toda ruta use permisos de tu catálogo. — Cindy»

## Comportamiento ante indisponibilidad

RBAC caído → 503 `RBAC_UNAVAILABLE` (BN22, E2E-QA-07). Almacén caído → 503 `QA_STORAGE_UNAVAILABLE` con *rollback* (P14, BN23). Gobernanza caída → eventos de seguridad (`qa.acceso.denegado`, `qa.gate.evaluado`) abortan con 503; los informativos se reportan y no detienen (A15, A16). En CI, cualquier error de configuración del gate termina con exit 3 y el check queda rojo.
