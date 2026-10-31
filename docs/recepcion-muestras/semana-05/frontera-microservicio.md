# Semana 5 — Contrato API y frontera de microservicio

Complementa la persistencia de este PR con lo que pide la matriz del [README](../../../README.md) para la semana 5: contrato de API y evaluación razonada de una frontera de microservicio. Contrato documentado para las entregas posteriores #693/#694: [openapi.yaml](openapi.yaml). Diagrama: [fuentes/frontera.mmd](fuentes/frontera.mmd).

## Estado: qué existe y qué es propuesta

Estado contrastado con develop adb128d3, después de #841 y #692. #1315 solo añade documentación.

| Aspecto | Integrado en develop de referencia | Entrega posterior o propuesta |
|---|---|---|
| Dominio | ReceiveSpecimen y puertos de Laravel (#841) | UI Vue en #695 |
| API | Sin endpoints de recepción en esta base | #693: POST /lab/specimens y GET de consulta, etiqueta y pending-orders; la consulta usa la proyección de #694 |
| Datos | specimens (#692); eventos en outbox_events de 000700 | #694: reception_order_items; base propia del servicio es propuesta |
| Órdenes | UnavailableOrders devuelve LAB_ORDER_UNAVAILABLE (503) | #694: OrderProjection y reception:import-order; consumidor remoto de ASII-16 pendiente |

## Contrato, permisos y errores de #693/#694 (pendientes de integración)

| Endpoint | Permiso | Respuestas |
|---|---|---|
| POST /lab/specimens | `muestras.recibir` | 201, 401, 403, 404 ORDER_NOT_FOUND, 409 REQUEST_CONFLICT/ORDER_CONFLICT, 422, 503 |
| GET /lab/specimens/pending-orders | `muestras.recibir` | 200, 401, 403, 422 |
| GET /lab/specimens/{code} | `muestras.ver` | 200, 401, 403, 404 |
| GET /lab/specimens/{code}/label | `muestras.etiquetar` | 200, 401, 403, 404 |

Seguridad: JWT, `X-Tenant-ID` que debe coincidir con el hospital configurado y actor clínico activo en `local_user_refs`. Un rechazo por regla clínica (volumen, temperatura, hemólisis, identificación discordante…) es un resultado de negocio: HTTP 201 con `status: rejected` y `reason`.

## Evaluación de la frontera de la pila completa

La tabla siguiente evalúa el diseño que completan #693–#695; los componentes posteriores no se atribuyen al develop de referencia.

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

1. Tras integrar #693/#694, mantener su API y el outbox como contrato de eventos.
2. Sustituir `OrderProjection` por un adaptador del contrato de ASII-16.
3. Publicar `outbox_events` mediante el publicador institucional y reconciliar.
4. Solo entonces separar el esquema `specimens` a una base propia, con réplica de las referencias de paciente.

## Límites

La disponibilidad de un servicio externo de órdenes no está acreditada: no existe todavía. Los rangos numéricos siguen siendo supuestos SD-REC-01. Observabilidad y resiliencia de red están descritas como diseño, no implementadas.
