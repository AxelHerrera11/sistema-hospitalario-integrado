# Dependencias del módulo — Micro-HIS Signos vitales

## Regla de dependencia entre capas

| Capa | Depende de | No depende de |
|---|---|---|
| Presentation (`VitalSignHttpHandler`) | `RecordVitalSigns`, objetos de valor de Domain | PDO, SQL, umbrales |
| Application (`RecordVitalSigns`, `EpisodeContext` y puertos) | Domain y sus propios puertos | PDO, HTTP, canal de notificación |
| Domain (`VitalSigns`, reglas, `ThresholdEvaluator`, `Assessment`, `Severity`) | Solo PHP | Application, Persistence, Presentation |
| Persistence (`PdoVitalSignStore`, `QueuedAlertNotifier`) | Puertos de Application, Domain, `PDO` | Presentation |

Las flechas apuntan hacia adentro (Presentation → Application → Domain ← Persistence). Persistence depende de Application porque implementa sus interfaces.

## Matriz caso de uso → dependencias

| Paso del flujo | Componente | Requerimientos |
|---|---|---|
| Captura y plausibilidad | `VitalSigns` (Domain) | RF-SV-01, RF-SV-02 |
| Acceso y episodio abierto | `CareEpisodeReader` | RF-SV-01 |
| Evaluación de umbrales | `ThresholdEvaluator` + `VitalSignRule` | RF-SV-03 a RF-SV-05 |
| Perfil no aplicable | `RecordVitalSigns::ADULT_AGE` | RF-SV-06 |
| Registro, auditoría y alerta | `VitalSignRepository`, `AlertNotifier` | RF-SV-07 |

## Persistencia y SQL

- Los valores siempre van como parámetros de `prepare()` y `execute([...])`.
- Los nombres de columna del `INSERT` salen de `VitalSigns::LIMITS`, una lista blanca validada en el dominio, nunca de la entrada.
- `PDO::ERRMODE_EXCEPTION` está activo. Un `PDOException` dentro de la transacción hace `rollBack()` y se traduce a `500 VITAL_SIGN_PERSISTENCE_ERROR` sin notificar.
- `database/schema.sql` es un subconjunto de la migración hospital `000401` adaptado a SQLite. Los `CHECK` de plausibilidad se replican en `VitalSigns`.

## Dependencias externas y supuestos

| Dependencia | Estado | Tratamiento en el prototipo |
|---|---|---|
| Autenticación JWT, RBAC y tenant (ASII-01, 02) | Fuera de alcance | `X-User-ID` identifica al médico. |
| `care_episodes` y `care_assignments` (Admisión) | Otro módulo | Solo lectura. La fecha de nacimiento se copia en `care_episodes` para calcular la edad. |
| Perfil de umbrales `adulto-2026.1` | Ilustrativo, sin validación clínica | Bandas fijas en las reglas; un paciente menor de 18 años recibe `THRESHOLDS_NOT_APPLICABLE`. |
| Canal de notificación (acuerdo C3) | Por acordar | `QueuedAlertNotifier` en memoria. |
| `critical_alert` (slice H7) y `local_access_audits` (slice H8) | Sin acuerdo o sin migración en `develop` | No se usa `critical_alert`; tabla mínima de auditoría en `schema.sql`. |

## Límites de la Semana 3

- No hay reconocimiento de alertas ni corrección de mediciones (`entered_in_error`).
- La SpO₂ de 0 % se rechaza; el `CHECK` de la migración la acepta. Se documenta como diferencia del prototipo.
- No se integra con el router de Laravel ni con PostgreSQL real. MVC con Repository y adaptadores InMemory se completan en la Semana 4.
