# Órdenes de laboratorio desde EMR — Semana 7: componentes y refactorización

**Módulo:** Órdenes de laboratorio desde EMR (ASII-16) · **Entrega académica:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09)
**Diagrama:** [`componentes.svg`](./componentes.svg) · **Fuente:** [`fuentes/componentes.mmd`](./fuentes/componentes.mmd)
**Refactor:** [`refactor.md`](./refactor.md)

## 1. Componentes backend

| Componente | Capa | Responsabilidad | Depende de |
|---|---|---|---|
| `ApiKernel` | Entrada | Tenant, permisos, `X-Request-ID`, problem+json | Controller |
| `LabOrderController` | Entrada | Rutas, despacho y elección JSON/HTML | Casos de uso, presentador, vista |
| `LabOrderDraftAssembler` | Aplicación | Convertir borrador + expediente + catálogo en un draft tipado | `ExpedienteGateway` |
| `EmitLabOrderHandler` | Aplicación | Idempotencia, guardado atómico y resultado 200/201 | Repositorio, dominio |
| `AdvanceLabOrder` / `CancelLabOrder` | Aplicación | Transiciones y motivo | Repositorio, dominio |
| `FindLabOrder` / `ListLabOrders` | Aplicación | Consulta y paginación acotada | Repositorio |
| `LabOrder` | Dominio | Invariantes, estados y eventos | Nada externo |
| `LabOrderRepository` | Puerto | Persistencia | — |
| `ExpedienteGateway` | Puerto | Contexto del expediente y pruebas activas | — |
| `PdoLabOrderRepository` | Persistencia | SQL preparado, transacciones, append-only | PostgreSQL/SQLite |
| `InMemoryLabOrderRepository` | Persistencia | Contrato para pruebas | — |

## 2. Componentes frontend

| Componente | Función | Estados que muestra |
|---|---|---|
| `OrderForm` | Captura paciente, episodio, nota y prioridad | vacío, validación, enviando, error |
| `TestSelector` | Busca y agrega pruebas activas | carga, sin resultados, seleccionada |
| `OrderTimeline` | Historial de estados con actor y fecha | `ISSUED` → `VALIDATED`, `CANCELLED` |
| `OrdersList` | Filtros por paciente/episodio/estado | vacío, carga, paginado |
| `ErrorPanel` | Traduce `problem+json` a acción | recuperable / no recuperable |

### Contratos de entrada/salida

| Componente | Entrada | Salida | Errores |
|---|---|---|---|
| `OrderForm` | paciente, episodio, nota, pruebas, prioridad | borrador validado en cliente | campos obligatorios, sin pruebas |
| `LabOrderDraftAssembler` | borrador + `ExpedienteGateway` | `LabOrderDraft` tipado | `CARE_EPISODE_NOT_OPEN`, `LAB_TEST_NOT_ACTIVE`, `ORDER_ITEMS_INVALID` |
| `EmitLabOrderHandler` | `LabOrderDraft`, actor, tenant, key | `IssueLabOrderOutcome` | `IDEMPOTENCY_KEY_REQUIRED`, `PERSISTENCE_FAILURE` |
| `AdvanceLabOrder` | tenant, id, nuevo estado, actor | `LabOrder` | `ORDER_NOT_FOUND`, `INVALID_STATUS_TRANSITION` |
| `CancelLabOrder` | tenant, id, actor, motivo | `LabOrder` | `ORDER_NOT_FOUND`, `ORDER_ALREADY_VALIDATED` |
| `FindLabOrder` / `ListLabOrders` | tenant, id o filtros | `LabOrder` / página | `ORDER_NOT_FOUND`, `PAGINATION_INVALID` |
| `OrderTimeline` | orden con eventos | estados y fechas | historial vacío (no debería ocurrir) |
| `ErrorPanel` | `problem+json` | mensaje accionable + `request_id` | — |

## 3. Punto de mayor acoplamiento

El mayor acoplamiento está en `IssueLabOrder`: conoce a la vez el **contrato de arrays** del
expediente, la forma del catálogo, la creación de ítems, la idempotencia, la carrera de persistencia y
la construcción del evento inicial. Se propone separarlo en:

1. `LabOrderDraftAssembler`: valida expediente y catálogo y devuelve un `LabOrderDraft` tipado.
2. `EmitLabOrderHandler`: solo idempotencia + persistencia + resultado.
3. `LabOrder`: conserva las invariantes y el evento inicial.

Detalle, código antes/después y criterios de no regresión en [`refactor.md`](./refactor.md).

## 4. Criterios de diseño

- **UI delgada:** no decide estados ni permisos; muestra lo que la API confirma.
- **Aplicación sin HTTP:** los casos de uso no conocen rutas, códigos HTTP ni HTML.
- **Dominio sin infraestructura:** `LabOrder` no importa PDO, framework ni request.
- **Persistencia sin reglas:** el repositorio no valida transiciones; solo guarda y recupera.
- **Contratos explícitos:** cada componente recibe y devuelve estructuras tipadas o arrays documentados.

## 5. Evidencia

| Verificación | Comando | Resultado |
|---|---|---|
| Contrato del repositorio | `php tests/run.php` | 25 × 3 adaptadores, OK |
| MVC y despacho | `php tests/mvc.php` | 12, OK |
| API y permisos | `php tests/api.php` | 18, OK |
| Cliente HTTP | `php tests/client.php` | 6, OK |
| SVG del diagrama | `mmdc -i fuentes/componentes.mmd -o componentes.svg` | Generado |

## 6. Declaración de IA

[`DECLARACION_IA.md`](./DECLARACION_IA.md), con validación humana pendiente.
