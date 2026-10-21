# Riesgos y controles SQA ASII-29

| Riesgo | Impacto | Control | Responsable |
|---|---|---|---|
| Sesion vencida deja pantalla usable | Alto | Invalidar contexto y volver a login | Auth |
| Tenant incorrecto muestra datos | Alto | Validacion de headers y 403 | API |
| Reintento duplica accion | Alto | `X-Request-Id` e idempotencia | Integracion |
| Red intermitente pierde contexto | Medio | Estado offline y reintento | UX |
| Cache expone dato clinico | Alto | No guardar payload clinico | Seguridad |
| Simulacion se confunde con NativePHP real | Medio | Evidencia separada | QA |

Cada riesgo se cierra con una prueba observable. Fallos de plataforma quedan
como restriccion documentada hasta contar con una ejecucion real.
