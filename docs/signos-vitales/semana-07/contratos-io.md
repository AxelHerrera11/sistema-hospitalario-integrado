# Contratos de entrada y salida

## HTTP (backend ↔ frontend y consumidores)

Contrato base en la [Semana 5](../semana-05/contrato-api.md) y [OpenAPI](../semana-05/openapi.yaml). La refactorización solo agrega algo **aditivo** en la entrada: cada signo admite un número (unidad canónica) o un objeto `{value, unit}`.

### `POST /api/v1/care-episodes/{care_episode_id}/vital-signs`

Entrada:

```json
{ "measured_at": "2026-10-14 08:30:00", "heart_rate": 138, "glucose": { "value": 2.9, "unit": "mmol/L" } }
```

| Campo | Tipo | Regla |
|---|---|---|
| `measured_at` | string | obligatorio, `YYYY-MM-DD HH:MM:SS` |
| signo (nueve posibles) | number \| `{value: number, unit: string}` | al menos uno; plausible tras convertir a la unidad canónica |
| `unit` | string | unidad aceptada para ese signo; otra → `422 UNIT_NOT_SUPPORTED` |

Unidades aceptadas: temperatura `°C` (canónica) y `°F`; glucosa `mg/dL` (canónica) y `mmol/L`; los demás signos solo su unidad canónica (`lpm`, `rpm`, `%`, `mmHg`, `kg`, `cm`).

Salida `201` (los valores se expresan en la unidad canónica):

```json
{ "vital_sign_id": "…", "code": "ALERT_GENERATED", "severity": "critical", "threshold_profile_version": "adulto-2026.1",
  "findings": { "glucose": "critical", "heart_rate": "critical" }, "trace_id": "…" }
```

`threshold_profile_version` es un campo **nuevo** que se obtiene del `ThresholdProfile` aplicado; la [narrativa de la Semana 1](../semana-01/narrativa-alcance-trazabilidad.md) ya lo pedía para la alerta. Con `THRESHOLDS_NOT_APPLICABLE` el campo va `null`.

Errores (`{code, trace_id}`): `401 UNAUTHENTICATED`, `403 FORBIDDEN` o `NOT_TREATING_PHYSICIAN`, `409 EPISODE_CLOSED`, `422 PAYLOAD_INVALID`, `VITAL_SIGNS_INVALID`, `VITAL_SIGN_IMPLAUSIBLE`, `MEASURED_AT_INVALID` o `UNIT_NOT_SUPPORTED`, `500 VITAL_SIGN_PERSISTENCE_ERROR`, `503 AUTHORIZATION_UNAVAILABLE`.

### `GET /api/v1/vital-signs/{vital_sign_id}`

Sin cambios respecto a la Semana 5: `200` con `vital_sign_id`, `care_episode_id`, `measured_at` y `values` en la unidad canónica.

## Puertos de la aplicación (PHP)

| Puerto | Entrada | Salida | Garantía |
|---|---|---|---|
| `VitalSignRepository::record` | episodio, usuario, `VitalSigns`, `measuredAt`, `?Assessment` | id (UUID v4) | Medición y auditoría todo o nada; `RuntimeException` si la persistencia no está disponible |
| `VitalSignRepository::find` | id | `?StoredVitalSign` | Los mismos valores guardados |
| `CareEpisodeReader::contextFor` | usuario, episodio | `?EpisodeContext` (`open`, `patientAge`) | `null` si el usuario no es tratante activo |
| `PermissionChecker::allows` | actor, `modulo.accion` | bool | `RuntimeException` si RBAC no responde |
| `AlertNotifier::notify` | id de la medición, `Assessment` | — | Se llama solo después del `COMMIT` |

Contratos **nuevos** del dominio:

| Componente | Entrada | Salida | Errores |
|---|---|---|---|
| `Measurement::canonical()` | signo, valor, `?unit` | valor en la unidad canónica | `VitalSignViolation('UNIT_NOT_SUPPORTED', 422)` |
| `ProfileCatalog::forAge(int)` | edad | `?ThresholdProfile` | ninguno; `null` = sin perfil aplicable |
| `ThresholdProfile::evaluate(VitalSigns)` | medición ya canónica y plausible | `Assessment` | no lanza ante un signo ausente (LSP) |

## Frontend

### Store Pinia `signosVitales`

| Elemento | Tipo | Descripción |
|---|---|---|
| `submitting` | boolean | `true` mientras el `POST` está en curso |
| `result` | objeto \| null | El `201`: `vital_sign_id`, `code`, `severity`, `findings`, `threshold_profile_version` |
| `error` | `{code, trace_id}` \| null | Último error del contrato |
| `record(episodeId, measurements, measuredAt)` | acción | Llama a la API, nunca reintenta, deja `result` o `error` |

### Componentes

| Componente | Props | Eventos |
|---|---|---|
| `MeasurementField` | `sign`, `units` (lista), `modelValue: {value, unit}` | `update:modelValue` |
| `VitalSignsForm` | `careEpisodeId`, `disabled` | `submit(measurements, measuredAt)` |
| `AlertResult` | `result` | — |
| `SeverityBadge` | `severity` (`none` \| `warning` \| `critical`), `evaluated: boolean` | — |
| `FindingsList` | `findings: Record<string, 'warning' \| 'critical'>` | — |

`SeverityBadge` con `evaluated = false` pinta «no evaluado» (caso `THRESHOLDS_NOT_APPLICABLE`).
