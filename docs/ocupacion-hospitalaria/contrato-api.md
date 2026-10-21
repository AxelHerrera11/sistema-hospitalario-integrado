# Contrato API de ocupación hospitalaria (ASII-09)

Estado: contrato de integración propuesto. El endpoint todavía no está integrado
en Laravel; las semanas 3 y 5 delivered un módulo PHP autonomous con adaptador
InMemory/PDO, y este documento fija el acuerdo de integración con el HIS.

Alcance: solo lectura de ocupación. El módulo no asigna, traslada ni libera camas.

## Consulta de ocupación

`GET /api/v1/occupancy?cutoff_at={ISO-8601}`

| Elemento | Contrato |
|---|---|
| Autenticación | `Authorization: Bearer <token>` validado por el middleware de auth del grupo |
| Hospital | `X-Tenant-ID`; debe identificar la base HOSPITAL autorizada por el token |
| Trazabilidad | `X-Request-ID` opcional, máximo 64 caracteres seguros; el servidor genera uno si falta |
| Permiso | `ocupacion.ver` (aún no existe en el RBAC de `develop`; debe crearse con el equipo) |
| Fecha | `cutoff_at` opcional en formato `YYYY-MM-DDThh:mm:ss±hh:mm`; si falta se usa la hora del servidor |
| Semántica | Consulta de solo lectura; no requiere idempotency key ni modifica camas o estancias |

Respuesta `200`:

```json
{
  "data": {
    "cutoff_at": "2026-10-20T10:00:00-06:00",
    "global": {
      "total_beds": 20,
      "occupied": 12,
      "available": 6,
      "maintenance": 1,
      "inactive": 0,
      "unverified_occupied": 1,
      "inconsistencies": 2,
      "occupancy_rate": 66.67
    },
    "wards": []
  },
  "meta": {"request_id": "req-123"}
}
```

Semántica de la fotografía:

- Una cama está ocupada en el corte `T` cuando existe un `bed_stay` con
  `started_at <= T` y `ended_at` nulo o posterior a `T`.
- `beds.status` es un estado operativo no autoritativo y no reemplaza el intervalo.
- Una discrepancia entre el intervalo activo y `beds.status` se devuelve como
  inconsistencia; no se corrige ni se oculta desde este endpoint.
- `available` solo cuenta camas no ocupadas y en estado operativo normal; no
  incluye camas en mantenimiento o inactivas.
- `occupancy_rate` se calcula sobre camas operativas (`total_beds - maintenance - inactive`).

## Dependencias de datos

| Entidad | Tabla en base HOSPITAL | Uso |
|---|---|---|
| `ward` | `wards` | Agrupación y filtro por sala |
| `bed` | `beds` | Inventario de camas y estado operativo |
| `bed_stay` | `bed_stays` | Intervalo de ocupación autoritativo |
| `care_episode` | `care_episodes` | Coherencia episodio-paciente en la ficha de ocupación |

Las entidades ya existen en `develop` mediante las migraciones HOSPITAL. Este
módulo no crea tablas nuevas.

## Errores

Todos devuelven `error.code`, un `error.message` seguro y `meta.request_id`
cuando la solicitud alcanza el handler.

| HTTP | Código | Condición |
|---:|---|---|
| 400 | `INVALID_CUTOFF` | Corte ausente de zona horaria, no escalar o con formato inválido |
| 401 | `UNAUTHENTICATED` | Token ausente, vencido o inválido |
| 403 | `FORBIDDEN` | Falta `ocupacion.ver` o el hospital no está autorizado |
| 404 | `NOT_FOUND` | Ruta o recurso desconocido |
| 405 | `METHOD_NOT_ALLOWED` | Método distinto de `GET` |
| 503 | `OCCUPANCY_UNAVAILABLE` | Base HOSPITAL no disponible; nunca se responde ocupación cero |

## Integración y operación

- Auth/RBAC deben validar JWT, hospital y `ocupacion.ver` antes de invocar el caso de uso.
- La fotografía se calcula con una lectura consistente dentro de una sola base HOSPITAL.
- Entre hospitales no existe transacción global; cada respuesta identifica su corte.
- Resiliencia del cliente: timeout de 2 s y como máximo un reintento con jitter
  ante fallo de conexión o `503`.
- El dashboard puede conservar la última fotografía marcada como desactualizada,
  nunca presentarla como actual.
- Observabilidad: log estructurado sin datos de pacientes con request ID,
  hospital interno, estado, duración y edad del corte; métricas de latencia p95,
  tasa 5xx e inconsistencias.
- Seguridad: TLS en tránsito, secretos fuera del repositorio, rate limit y
  lista permitida de hospitales.
