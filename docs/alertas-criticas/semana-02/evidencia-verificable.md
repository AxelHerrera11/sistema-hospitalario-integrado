# Evidencia verificable — Alertas críticas y notificaciones internas

`php docs/alertas-criticas/semana-02/evidencia/demo-srp.php` (PHP 8.4.21) termina con código 0 y **12 PASS**. La salida completa está pegada en *Validación ejecutada* del PR.

| Bloque de la demo | Qué comprueba |
|---|---|
| Comportamiento equivalente (6) | Tratante nivel 0, jefatura sin médico, valor normal sin alerta, idempotencia, escalamiento a los 16 min y la misma auditoría en ambos diseños |
| Cambio de TI (2) | Con `OutboxNotifier` la alerta se detecta y registra igual; la notificación sale por el outbox |
| Cambio de calidad (2) | Con 10 min escala a los 11 min; con 15 min todavía no |
| Dependencias por reflexión (2) | Detector y resolvedor sin dependencias; el registrador no depende del canal |

**Conclusión:** cambiar el canal o el plazo no tocó `CriticalValueDetector` ni `CriticalAlertRegistrar`.
