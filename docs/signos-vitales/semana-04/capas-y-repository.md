# Capas, responsabilidades y contrato del Repository

## Responsabilidades por capa

| Capa | Clases | Responsabilidad | No hace |
|---|---|---|---|
| Entrada | `public/index.php`, `app.php` | Arma la `Request` desde HTTP, elige el adaptador (PDO o InMemory) y emite la respuesta. `signos_vitales_router()` es la raíz de composición. | Reglas, SQL |
| Presentation (MVC) | `Router`, `VitalSignController`, `Request`, `JsonView` | Ruta → acción. La `Request` rechaza con `422` los campos con tipo incorrecto; el controlador llama al caso de uso y traduce la excepción a estado HTTP; la vista serializa a JSON. | SQL, umbrales, plausibilidad |
| Application (modelo) | `RecordVitalSigns`, `EpisodeContext`, `StoredVitalSign` y los puertos `VitalSignRepository`, `CareEpisodeReader`, `AlertNotifier` | Orquesta el flujo: fecha válida, acceso y episodio, evaluación, guardado y notificación después del `COMMIT`. | HTTP, PDO |
| Domain | `VitalSigns`, `ThresholdEvaluator`, `VitalSignRule` y reglas, `Assessment`, `Severity`, `VitalSignViolation` | Plausibilidad fisiológica y umbrales. `VitalSignViolation` trae el estado HTTP sugerido. | Todo lo externo |
| Persistence | `PdoVitalSignRepository`, `PdoCareEpisodeReader`, `InMemoryVitalSignRepository`, `InMemoryCareEpisodeReader`, `QueuedAlertNotifier` | Implementan los puertos. PDO usa sentencias preparadas y una transacción. | Reglas, HTTP |

El controlador no decide reglas. No contiene SQL, no conoce `PDO` y no evalúa umbrales: `run.php` lo comprueba leyendo el código fuente del controlador y del router.

## Qué cambió respecto a la Semana 3

| Semana 3 | Semana 4 | Motivo |
|---|---|---|
| `VitalSignHttpHandler` (parseo, validación de tipos y respuesta en una clase) | `Router` + `VitalSignController` + `Request` + `JsonView` | Una responsabilidad por clase; la vista y la `Request` sirven a otros controladores. |
| `RecordVitalSigns::execute(..., VitalSigns $vitals, ...)` | `execute(..., array $measurements, ...)` | El controlador ya no construye objetos de dominio: pasa los valores y el caso de uso crea `VitalSigns`. |
| `PdoVitalSignStore` implementaba dos puertos | `PdoVitalSignRepository` y `PdoCareEpisodeReader` | Los datos de episodios son de Admisión (solo lectura); el Repository es de este módulo. Cada adaptador implementa un solo puerto. |
| Solo adaptadores PDO | Se agregan `InMemoryVitalSignRepository` e `InMemoryCareEpisodeReader` | Pruebas sin base de datos y MVC ejecutable sin PostgreSQL. |
| `VitalSignRepository::record()` | Se agrega `find()` y el objeto `StoredVitalSign` | Un Repository debe poder devolver lo que guardó; también permite probar el contrato. |

## Objetos reutilizables

- `JsonView` y `Request` no conocen el módulo de signos vitales: sirven a cualquier controlador del micro-HIS (el módulo de Médicos tiene los mismos).
- `VitalSigns`, `ThresholdEvaluator` y `VitalSignRule` no tienen dependencias; otro módulo (por ejemplo, Notas SOAP) podría reutilizarlos para mostrar el estado de los signos.
- `InMemoryCareEpisodeReader` sirve como doble de Admisión para los módulos que lean episodios mientras no exista la integración real.
- `QueuedAlertNotifier` es el puerto de salida de alertas; cuando se acuerde el canal (C3) se sustituye sin tocar el caso de uso.

## Contrato `VitalSignRepository` (Application)

```php
interface VitalSignRepository
{
    public function record(string $episodeId, string $userId, VitalSigns $vitals, string $measuredAt, ?Assessment $assessment): string;
    public function find(string $vitalSignId): ?StoredVitalSign;
}
```

| Método | Garantía que debe cumplir cada adaptador |
|---|---|
| `record(...)` | Guarda la medición y su auditoría (`registrado` y, según corresponda, `alerta_generada` o `sin_evaluacion`) todo o nada. Si la persistencia falla lanza `RuntimeException` y no queda nada guardado. Devuelve el id. |
| `find(id)` | Devuelve el `StoredVitalSign` con los mismos valores guardados, o `null` si no existe. |

**Por qué en Application:** el caso de uso define lo que necesita guardar; Persistence depende de ese límite (inversión de dependencias) y el dominio ni la presentación saben si hay PDO o memoria detrás. `CareEpisodeReader` queda como puerto aparte porque esos datos pertenecen a Admisión y solo se leen.

**Cómo se demuestra que los adaptadores son intercambiables:**

- `tests/repository.php` ejecuta la misma batería de contrato contra `PdoVitalSignRepository` (SQLite en memoria) y `InMemoryVitalSignRepository`, a través de la interfaz.
- `tests/run.php` ejecuta el flujo completo por el `Router` con PDO y repite los casos clave con InMemory.

## Traducción a HTTP en el controlador

| Ruta | Resultado | Estado | Cuerpo |
|---|---|---|---|
| `POST /episodes/{id}/vital-signs` | Medición guardada | `201` | `vital_sign_id`, `code` (`OK`, `ALERT_GENERATED` o `THRESHOLDS_NOT_APPLICABLE`), `severity`, `findings` |
| Cualquiera | `VitalSignViolation` | `403`, `409` o `422` | `code` |
| Cualquiera | Falla del repositorio (`RuntimeException`) | `500` | `code: VITAL_SIGN_PERSISTENCE_ERROR` |
| Otra ruta | — | `404` | `code: ROUTE_NOT_FOUND` |
| Cuerpo que no es JSON de objeto | — | `422` | `code: PAYLOAD_INVALID` |
