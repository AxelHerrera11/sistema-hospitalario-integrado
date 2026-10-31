# Contrato API del expediente

Fuente única del contrato: [`micro-his/expediente-medico/openapi.yaml`](../../../micro-his/expediente-medico/openapi.yaml)
(OpenAPI 3.0.3). Esta página lo resume y lo compara con lo que existe en cada entrada.

## Recurso

`/patients/{patientCode}/medical-record` — sub-recurso del paciente, igual que `patients/{code}` del módulo
Pacientes. `patientCode` sigue `^[A-Za-z0-9][A-Za-z0-9_-]{0,39}$`.

| Operación | Micro-HIS (`public/index.php`) | Laravel `/api/v1` (`app/ExpedienteMedico`) | Permiso |
|---|---|---|---|
| `GET` consulta longitudinal | Implementada | Implementada, probada y activa (`php artisan route:list --path=api/v1/patients`) | `expediente.ver` (catálogo real) + vínculo asistencial |
| `POST` apertura idempotente | Implementada (403 con la matriz real) | **No publicada**: su permiso no existe en el catálogo | Propuesta para RBAC (S-02) |

## Cabeceras

| Cabecera | Micro-HIS | Laravel |
|---|---|---|
| Identidad | `X-User-ID` (PROVISIONAL, la envía el cliente) | `Authorization: Bearer <JWT>` → usuario CENTRAL → `local_user_refs` (servidor) |
| Tenant | `X-Tenant-ID` validado por formato y por membresía RBAC | `X-Tenant-ID` validado por el middleware `tenant` (`TENANT_MISMATCH`) |
| Correlación | `X-Correlation-ID` opcional → `correlation_id` del evento | `X-Request-Id` → `X-Correlation-ID` |

## Respuestas

- **200 consulta:** `record`, `patient` (`code`, `display_name`, `birth_date`, `sex`, `status`), `summary`
  (`episodes`, `open_episodes`) y `episodes[]` ordenados del más reciente al más antiguo. No incluye `contact`,
  `global_id` ni identificadores de otros módulos (minimización, RNF-EXP-01).
- **201/200 apertura:** datos del expediente + `created`. Nunca datos del paciente.
- **Errores:** `{ "code": "…", "trace_id"?: "…" }`. Lista completa en el README del micro-HIS. En Laravel, los
  401/403 de los middlewares compartidos usan sus propios códigos (`RBAC_DENIED`, `TENANT_MISMATCH`).
- **Cabeceras de seguridad:** `Cache-Control: no-store` y `X-Content-Type-Options: nosniff`.

## Ejemplos reales (prototipo)

```bash
curl -s -H "X-User-ID: 11111111-1111-4111-8111-111111111111" -H "X-Tenant-ID: hospital-demo" http://127.0.0.1:8096/patients/P-0001/medical-record
curl -s -H "X-User-ID: 33333333-3333-4333-8333-333333333333" -H "X-Tenant-ID: hospital-demo" http://127.0.0.1:8096/patients/P-0001/medical-record
```

Salidas guardadas en [`evidencias/curl-contrato.txt`](evidencias/curl-contrato.txt).

## Pendiente de acuerdo

El formato común de errores y paginación del grupo (responsable: Lis) no está publicado. Cuando exista, el contrato
se ajustará en un PR propio; no se inventa aquí.
