# RF/RNF y criterios de aceptación — Alertas críticas y notificaciones internas

Derivados de los casos de uso de la [Semana 1](../semana-01/README.md).

## Requerimientos funcionales

| Código | UC | Requerimiento | Criterio de aceptación |
|---|---|---|---|
| RF-ALR-01 | UC-ALR-01 | Detectar el valor crítico con los umbrales de la versión de la prueba. | Glucosa 32 mg/dL con crítico bajo 40 → valor crítico `low`; 95 mg/dL o prueba sin umbrales → sin alerta. |
| RF-ALR-02 | UC-ALR-02 | Una sola alerta por resultado. | El mismo evento dos veces deja exactamente una alerta. |
| RF-ALR-03 | UC-ALR-02 | Destinatario inicial según el episodio. | Médico con `care_assignment` activa (nivel 0); sin médico, jefatura (nivel 1). |
| RF-ALR-04 | UC-ALR-03 | Notificar por la bandeja y reintentar si falla. | Si la entrega falla se reintenta y el plazo sigue corriendo. |
| RF-ALR-05 | UC-ALR-04 | Acusar con lectura de verificación. | Queda `acknowledged` y se detiene el escalamiento; sin lectura `422 READ_BACK_REQUIRED`; ya acusada `409`. |
| RF-ALR-06 | UC-ALR-07 | Exigir la acción clínica para cerrar. | Queda `resolved`; sin acción `422`. |
| RF-ALR-07 | UC-ALR-05 | Escalar las vencidas sin acuse. | A los 15 min sube a jefatura y luego a dirección médica; al agotar el nivel 2 se pide una llamada registrada. |
| RF-ALR-08 | UC-ALR-06 | Consultar la bandeja. | Con `alertas.ver` se ven las alertas abiertas ordenadas por vencimiento. |
| RF-ALR-09 | UC-ALR-09 | Auditar cada paso. | Cada acción deja su evento en `local_access_audits`. |

## Requerimientos no funcionales

| Código | Categoría | Criterio medible |
|---|---|---|
| RNF-ALR-01 | Oportunidad clínica | El destinatario ve la alerta en ≤ 60 s (p95) desde la validación. |
| RNF-ALR-02 | Confiabilidad | Con el consumidor caído y restaurado, el 100 % de los eventos pendientes generan su alerta y ninguna se duplica. |
| RNF-ALR-03 | Escalamiento puntual | Se escala ≤ 2 min después de `due_at`; dos ejecuciones no escalan la misma alerta. |
| RNF-ALR-04 | Seguridad | El 100 % de los acuses sin permiso, de otro tenant o sin relación de cuidado reciben `401`/`403`. |
| RNF-ALR-05 | Mantenibilidad (SRP) | Cambiar el canal o el plazo no modifica `CriticalValueDetector` ni `CriticalAlertRegistrar` ([demo](./evidencia/demo-srp.php)). |

## Escenario de aceptación (SRP)

```gherkin
Dado que TI decide enviar las notificaciones por outbox_events en lugar de la bandeja
Cuando se reemplaza InboxNotifier por OutboxNotifier
Entonces la misma glucosa de 32 mg/dL genera la misma alerta para la Dra. López
Y CriticalValueDetector y CriticalAlertRegistrar no se modifican
```
