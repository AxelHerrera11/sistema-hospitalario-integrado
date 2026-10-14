# Semana 7 — Diseño de componentes backend/frontend

**Módulo:** ASII-29 — Investigación y prototipo NativePHP

## Componentes

- Frontend móvil/escritorio futuro: bandeja offline, detalle de acción, estado de conectividad y reintento.
- Presentation: controlador del prototipo y contrato de acciones.
- Application: preparación y sincronización por lote.
- Domain: acción local, conectividad y estados de sincronización.
- Persistence: Repository local InMemory/PDO.
- Integration: SyncGateway y TelemetryRecorder como puertos.

El diagrama de componentes distingue la simulación actual de la futura integración NativePHP.

## Contratos de entrada y salida

| Componente | Entrada | Salida |
|---|---|---|
| `PrototypeController` | `actorId`, `tenantId`, acción y valor | `ActionResponse` con estado y mensaje |
| `PrepareLocalAction` | acción, unidad, motivo y conectividad | `LocalHisAction` validada o error de dominio |
| `SyncLocalActions` | lista de acciones pendientes | resultado por acción: sincronizada, en cola o fallida |
| `SyncGateway` | acción e `idempotencyKey` | confirmación HIS o error recuperable |
| `TelemetryRecorder` | tenant, acción, estado y error | evento de observabilidad sin datos clínicos innecesarios |

## Propuesta de refactorización

1. Extraer un componente de idempotencia para correlacionar cada acción con el envío remoto.
2. Persistir reintentos y motivos en una cola durable PDO.
3. Mantener la interfaz de gateway independiente de HTTP, NativePHP y servicios productivos.
4. Compartir en frontend un componente de estado offline con acciones reintentar/cancelar.
5. Agregar correlación y métricas por tenant, acción y resultado del gateway.

## Refactor concreto: sincronización acoplada

### Antes

`PrototypeController` valida datos, decide si hay conexión, persiste directamente, llama al gateway y construye el mensaje de UI. Esto acopla UI, reglas, almacenamiento y transporte NativePHP.

### Después

`PrototypeController` solo recibe la acción y delega a `PrepareLocalAction` y `SyncLocalActions`. El dominio decide estados; `LocalActionRepository`, `SyncGateway` y `TelemetryRecorder` son puertos intercambiables con adaptadores InMemory/PDO, NativePHP o HTTP.

### Justificación

El cambio reduce acoplamiento y permite probar la viabilidad NativePHP con dobles sin cambiar las reglas del HIS. También aísla reintentos, idempotencia y riesgos de plataforma sin ampliar la propiedad de datos del módulo.

## Criterios de aceptación

- No se crean acciones con tenant o actor vacío.
- Un mismo identificador no cruza tenants.
- Un rechazo puede reintentarse y conserva el motivo.
- Una excepción de transporte queda registrada y no detiene acciones independientes.
- La UI diferencia pendiente, fallida y sincronizada.

