# Alertas críticas y notificaciones internas — Semana 6: primera evaluación parcial

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-21 · **Entrega:** defensa teórica y caso práctico arquitectónico.

## Tesis arquitectónica

En el flujo «detección, envío, acuse y escalamiento de una alerta crítica», **ningún valor crítico queda sin una persona responsable que lo confirme**. Hay una sola alerta por resultado aunque el evento llegue repetido (idempotencia en la base), el acuse exige leer de vuelta paciente, prueba y valor, y solo el primero que acusa gana. Cada responsabilidad tiene una sola razón para cambiar (SRP): detectar, elegir destinatario, persistir, notificar y escalar son piezas distintas. Por eso el canal o el plazo cambian sin tocar la detección.

## Trazabilidad de punta a punta

```mermaid
flowchart LR
  V([Validación de resultados ASII-20]) --> UC1[UC-ALR-01 y 02<br/>Detectar y generar]
  M([Médica tratante]) --> UC4[UC-ALR-04 y 06<br/>Acusar y bandeja]
  UC1 --> RF1[RF-ALR-01..03<br/>umbral de la versión · una alerta por resultado]
  UC4 --> RF5[RF-ALR-05 y 08<br/>lectura de verificación · primero gana]
  RF1 --> DOM[Domain: CriticalAlert::detect]
  RF5 --> DOM2[Domain: CriticalAlert::acknowledge]
  DOM --> APP[HandleResultValidated]
  DOM2 --> APP2[AcknowledgeAlert · GetInbox]
  APP & APP2 --> REP{{AlertRepository<br/>PDO · InMemory}}
  APP --> EP1[POST /api/v1/internal/result-validated]
  APP2 --> EP2[GET /api/v1/critical-alerts y POST …/acknowledge]
```

| Semana | Qué aportó | Evidencia |
|---|---|---|
| 1 | Actores, UC-ALR-01..09, actividad, secuencia y Anexo A (`critical_alerts` propuesta) | `docs/alertas-criticas/semana-01/` (PR #196) |
| 2 | RF/RNF y SRP: una razón para cambiar por clase | `docs/alertas-criticas/semana-02/` (PR #198) |
| 3 | Micro-HIS por capas con idempotencia, acuse y pruebas | `micro-his/alertas-criticas/` (PR #202) |
| 4 | MVC (la bandeja como View) + Repository PDO e InMemory | Reentrega de la Semana 4 (diseño del PR #60) |
| 5 | API v1, cliente con publicación idempotente, OpenAPI y frontera | Reentrega de la Semana 5 (diseño del PR #64) |
