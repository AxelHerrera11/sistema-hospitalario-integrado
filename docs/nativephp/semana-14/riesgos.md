# Riesgos y controles SQA ASII-29

## Metricas de seguimiento

| Metrica | Umbral | Fuente | Responsable | Accion |
|---|---|---|---|---|
| Escenarios UX cubiertos | 100% de estados definidos | Checklist S13 | UX | Completar flujo |
| Denegaciones correctas | 100% de 400/403 | Pruebas API | Seguridad | Abrir H-## |
| Reintentos sin duplicado | 100% | Logs con request id | Integracion | Revisar idempotencia |
| Datos clinicos en cache | 0 hallazgos | Inspeccion de almacenamiento | Seguridad | Bloquear cierre |
| Hallazgos criticos abiertos | 0 | Registro H-## | Moderador | No promover |
| Tiempo de recuperacion | <= 2 reintentos | Evidencia UX | UX | Ajustar mensaje |

## Calendario de seguimiento

| Momento | Actividad | Responsable |
|---|---|---|
| Inicio | Confirmar plataforma, permisos y tenant | Autor + QA |
| Durante | Medir escenarios y registrar H-## | Autor |
| Cierre | Revisar metricas y controles detectivos | Yamileth1235 |
| Antes de merge | Separar simulacion de ejecucion real | Lider del modulo |

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

Los controles preventivos bloquean payloads sin tenant, permisos o request id;
los detectivos comprueban 400/403, reintentos, cache y recuperacion de red.
