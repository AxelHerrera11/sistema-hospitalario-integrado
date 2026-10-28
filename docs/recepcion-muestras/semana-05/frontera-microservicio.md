# Semana 5 — Contrato API y frontera de microservicio

Complementa la persistencia de este PR con lo que pide la matriz del [README](../../../README.md) para la semana 5: contrato de API y evaluación razonada de una frontera de microservicio. Contrato ejecutable: [openapi.yaml](openapi.yaml). Diagrama: [fuentes/frontera.mmd](fuentes/frontera.mmd).

## Estado: qué existe y qué es propuesta

| Aspecto | Implementado | Propuesta (no construida) |
|---|---|---|
| API | `POST /lab/specimens`, `GET /lab/specimens/pending-orders`, `GET /lab/specimens/{code}`, `GET /lab/specimens/{code}/label` | Evento hacia un publicador institucional |
| Datos | `specimens` y `reception_order_items` en la base HOSPITAL; eventos en `outbox_events` en la misma transacción | Base propia del servicio |
| Órdenes | Proyección local alimentada por `reception:import-order` | Consumo del contrato de ASII-16 cuando exista |

## Contrato, permisos y errores

| Endpoint | Permiso | Respuestas |
|---|---|---|
| POST /lab/specimens | `muestras.recibir` | 201, 401, 403, 404 ORDER_NOT_FOUND, 409 REQUEST_CONFLICT/ORDER_CONFLICT, 422, 503 |
| GET /lab/specimens/pending-orders | `muestras.recibir` | 200, 401, 403, 422 |
| GET /lab/specimens/{code} | `muestras.ver` | 200, 401, 403, 404 |
| GET /lab/specimens/{code}/label | `muestras.etiquetar` | 200, 401, 403, 404 |

Seguridad: JWT, `X-Tenant-ID` que debe coincidir con el hospital configurado y actor clínico activo en `local_user_refs`. Un rechazo por regla clínica (volumen, temperatura, hemólisis, identificación discordante…) es un resultado de negocio: HTTP 201 con `status: rejected` y `reason`.

## Evaluación de la frontera

La frontera candidata sería «recepción de muestras» como servicio con su propia base y API.

| Criterio | Hallazgo | Hacia microservicio |
|---|---|---|
| Propiedad de datos | Solo escribe `specimens`, su proyección de órdenes y `outbox_events`; las FK apuntan a pacientes y episodios locales del hospital | Obligaría a duplicar o consultar por red pacientes y episodios |
| Consistencia | Muestra y evento se confirman en una transacción local; una orden aceptada no se reabre (`FOR UPDATE`) | Pasaría a consistencia eventual y requeriría saga o reconciliación |
| Idempotencia | `request_key` + hash del contenido; hasta 3 reintentos ante colisión de código | Se mantiene, pero ahora a través de red |
| Resiliencia | El cliente conserva la clave y reintenta; el servidor responde 503 reintentable | Añadiría timeouts, circuit breaker y colas |
| Observabilidad | Hoy: `report($error)` en fallos de persistencia | Haría falta traza distribuida, métricas y correlación |
| Escala | No hay medición de carga que justifique escalar este módulo por separado | Sin evidencia medible |

**Decisión:** permanecer como módulo dentro del monolito Laravel, con puertos (`OrderGateway`, `ReceptionRepository`) que permiten extraerlo después. No se divide el sistema sin una justificación medible, como pide la matriz. Disparadores de reevaluación: carga de recepción que degrade otros módulos, equipo propietario independiente, o un contrato de órdenes remoto ya publicado.

## Migración razonada (si se justificara)

1. Mantener la API actual y el outbox como contrato de eventos.
2. Sustituir `OrderProjection` por un adaptador del contrato de ASII-16.
3. Publicar `outbox_events` mediante el publicador institucional y reconciliar.
4. Solo entonces separar el esquema `specimens` a una base propia, con réplica de las referencias de paciente.

## Límites

La disponibilidad de un servicio externo de órdenes no está acreditada: no existe todavía. Los rangos numéricos siguen siendo supuestos SD-REC-01. Observabilidad y resiliencia de red están descritas como diseño, no implementadas.
