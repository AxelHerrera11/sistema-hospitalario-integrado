# Contrato API REST — Signos vitales y alertas v1

## Convenciones

- Prefijo `/api/v1`. JSON UTF-8. Fechas `YYYY-MM-DD HH:MM:SS` en la zona del hospital (propuesta: ISO 8601 con zona cuando se integre con `timestamptz`).
- **Identidad:** el prototipo usa `X-User-ID`. En el SHI será el JWT con `X-Tenant-ID` (ASII-01); el contrato de errores no cambia.
- **Permisos** `modulo.accion` de dos partes, validados por RBAC (ASII-02): `signos_vitales.registrar`, `signos_vitales.ver`, `signos_vitales.corregir`.
- **Errores:** `{ "code": "...", "trace_id": "<uuid v4>" }`. Todo error y toda respuesta exitosa llevan `trace_id` en el cuerpo y `X-Trace-Id` en el encabezado.
- **Datos que no viajan:** el cuerpo de error y la bitácora de acceso no incluyen valores clínicos ni datos del paciente.

## Endpoints y permisos

| Método | Ruta | Permiso RBAC | Éxito | Estado |
|---|---|---|---|---|
| `POST` | `/api/v1/care-episodes/{care_episode_id}/vital-signs` | `signos_vitales.registrar` | `201` | implementado (Semana 5) |
| `GET` | `/api/v1/vital-signs/{vital_sign_id}` | `signos_vitales.ver` | `200` | implementado (Semana 5) |
| `GET` | `/api/v1/care-episodes/{care_episode_id}/vital-signs` | `signos_vitales.ver` | `200` | propuesto |
| `POST` | `/api/v1/vital-signs/{vital_sign_id}/corrections` | `signos_vitales.corregir` | `201` | propuesto |

| Rol | `registrar` | `ver` | `corregir` |
|---|---|---|---|
| `medico` | X | X | X |
| `enfermeria` |  | X |  |
| `admin_hospital` |  |  |  |

Supuesto: enfermería no registra porque el trigger `enforce_vital_sign_integrity` de la migración `000401` exige que el autor tenga un `doctor_profile` con asignación activa. Si el equipo decide que enfermería registre, hay que cambiar ese trigger con Javier (ver [frontera](./frontera-microservicio.md#decisiones-pendientes)).

## Campos

### `POST /api/v1/care-episodes/{care_episode_id}/vital-signs`

| Campo | Tipo | Regla |
|---|---|---|
| `measured_at` | string | obligatorio, `YYYY-MM-DD HH:MM:SS` válido |
| `temperature`, `heart_rate`, `respiratory_rate`, `systolic_bp`, `diastolic_bp`, `oxygen_saturation`, `weight`, `height`, `glucose` | number | al menos uno; dentro de los límites fisiológicos de `VitalSigns::LIMITS`; sistólica ≥ diastólica |

Respuesta `201`:

```json
{ "vital_sign_id": "…", "code": "ALERT_GENERATED", "severity": "critical", "findings": { "heart_rate": "critical", "oxygen_saturation": "warning" }, "trace_id": "…" }
```

`code` es `OK`, `ALERT_GENERATED` o `THRESHOLDS_NOT_APPLICABLE` (paciente menor de 18 años: se guarda sin evaluar).

### `GET /api/v1/vital-signs/{vital_sign_id}`

Devuelve la medición (`care_episode_id`, `measured_at`, los signos medidos). Es dato clínico: además del permiso exige asignación activa en el episodio (`403 NOT_TREATING_PHYSICIAN`).

## Códigos de error

| HTTP | `code` | Cuándo |
|---|---|---|
| `401` | `UNAUTHENTICATED` | Sin actor autenticado o token inválido |
| `403` | `FORBIDDEN` | El actor no tiene el permiso de la ruta; el cuerpo incluye `permission` |
| `403` | `NOT_TREATING_PHYSICIAN` | Sin asignación activa en el episodio, o el episodio no existe (no se revela cuál) |
| `404` | `ROUTE_NOT_FOUND` / `VITAL_SIGN_NOT_FOUND` | Ruta inexistente / medición inexistente |
| `409` | `EPISODE_CLOSED` | El episodio no está abierto |
| `422` | `PAYLOAD_INVALID` | JSON malformado o campo con tipo incorrecto |
| `422` | `VITAL_SIGNS_INVALID` / `VITAL_SIGN_IMPLAUSIBLE` / `MEASURED_AT_INVALID` | Sin signos o signo no reconocido / valor fuera de rango fisiológico / fecha mal formada |
| `500` | `VITAL_SIGN_PERSISTENCE_ERROR` | La base no está disponible; no se guardó nada ni se notificó |
| `503` | `AUTHORIZATION_UNAVAILABLE` | RBAC caído: falla cerrada, no se atiende la petición |

## Evento de salida (propuesto)

`signo_vital.alerta_generada`, publicado por outbox en la misma transacción que la medición; lo consume Alertas críticas (ASII-21).

```json
{ "event_id": "<uuid v4>", "type": "signo_vital.alerta_generada", "occurred_at": "…", "vital_sign_id": "…", "care_episode_id": "…", "severity": "critical", "findings": ["heart_rate", "oxygen_saturation"], "trace_id": "…" }
```

Sin valores medidos ni datos del paciente: quien necesite el detalle consulta `GET /vital-signs/{id}` con su propio permiso. Entrega al menos una vez; el consumidor descarta repetidos por `event_id`.
