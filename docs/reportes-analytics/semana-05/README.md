# ASII-23 Semana 5 — API REST integrada al contenedor

**Issue:** `Refs #152` · Caso "Sistema hospitalario integrado": el módulo PHP
vanilla de la Semana 3 se consume desde la misma API protegida del sistema.

## Contrato publicado (código integrado)

| Elemento | Valor |
|---|---|
| Ruta | `POST /api/v1/reports/indicators/IND-RA-01/calculate` |
| Middleware | `tenant` + `jwt.auth` + `rbac:reportes.ver` |
| Cuerpo | `from`, `until` (ISO-8601 con zona), `service_code` opcional |
| 200 | `data{indicator,formula_version,percentage,state,computed_at}`, `meta.filters`, `Cache-Control: no-store, private` |
| 422 | `error.code = periodo_invalido` o `entrada_invalida`, sin porcentaje |
| 503 | `error.code = REPORTES_UNAVAILABLE` (base caída o lectura incoherente) |
| 401/403 | `AUTH_REQUIRED` / `RBAC_DENIED` / `TENANT_MISMATCH`; 405 método distinto |

## Archivos y PRs de evidencia

- `micro-his/reportes-analytics/bootstrap.php` — autoloader del módulo.
- `app/Providers/ReportesServiceProvider.php` — conexión perezosa HOSPITAL.
- `app/Http/Controllers/Api/V1/ReportesIndicatorController.php` — traduce
  HTTP; resuelve el caso de uso dentro del `try` para responder 503 no 500.
- `routes/api.php` — ruta permiso `reportes.ver` (mismo permiso del frontend).
- PRs #1245 (332 líneas) y #1269 (172 líneas), promovidos en #1246 y #1271.

## Pruebas que evidencian la integración

- `tests/Feature/Reportes/IndicatorEndpointTest.php` — 10 pruebas con dobles.
- `tests/Feature/Reportes/IndicatorHospitalIntegrationTest.php` — base real
  `his_hospital_test` (55433): cálculo 33.33 %, `SIN_DATOS_EN_PERIODO`, 503
  sin base, respuesta sin datos personales. Rol="superadmin" ya trae
  `reportes.ver` en `config/rbac.php`.

## Análisis cliente-servidor y plan

El análisis completo, con diagramas, está en [`cliente-servidor.md`](./cliente-servidor.md).
