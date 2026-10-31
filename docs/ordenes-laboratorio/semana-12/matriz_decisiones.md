# Matriz decisión → RF/RNF → evidencia

**Semana 12 · Órdenes de laboratorio desde EMR**
**Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09).

## Cómo leer la matriz

- **Verificada:** existe una prueba ejecutada o una captura reproducible que sostiene la decisión.
- **Verificada con límite:** la evidencia existe, pero depende de un supuesto declarado.
- **Diferida:** la decisión se analiza, pero no se implementa en esta entrega.

## Matriz

| # | Decisión | RF/RNF | Evidencia | Estado |
|---|---|---|---|---|
| D1 | La orden nace `ISSUED` y solo avanza por transiciones explícitas; `VALIDATED` y `CANCELLED` son finales | RF-OL-06, RF-OL-07, RF-OL-10 | `src/Domain/LabOrder.php`; `tests/run.php`: transición inválida y cancelación; `tests/mvc.php`: 409 | Verificada |
| D2 | Idempotencia con `UNIQUE (tenant_id, idempotency_key)`; el replay devuelve 200 con la orden existente | RF-OL-04 | `IssueLabOrderOutcome`; `tests/api.php`: replay 200 y misma orden; `openapi.yaml`: 200/201 | Verificada |
| D3 | Todo acceso filtra por `tenant_id`; un tenant ajeno recibe 404 sin revelar existencia | RF-OL-09 | `PdoLabOrderRepository`; `tests/run.php` y `tests/api.php`: aislamiento entre hospitales | Verificada |
| D4 | Permisos `laboratorio.ordenes.crear/ver/actualizar/cancelar` antes del caso de uso | RF-OL-12 | `ApiKernel::requiredPermission()`; `tests/api.php`: 403 sin permiso | Verificada con límite: la cabecera `X-Permissions` la confía el gateway |
| D5 | Historial append-only: cada transición agrega un evento y nunca se actualiza ni borra | RF-OL-10, RNF-OL-05 | `lab_order_events`; `tests/run.php`: secuencia de eventos; `tests/mvc.php`: consulta del historial | Verificada |
| D6 | Los ítems guardan instantánea de código y nombre; no dependen de cambios futuros del catálogo | RF-OL-05, RF-OL-03 | `LabOrderItem`; `lab_order_items`; `tests/run.php`: orden con pruebas | Verificada |
| D7 | Errores estables `problem+json` con código y `request_id`, sin filtrar SQL ni datos clínicos | RF-OL-11, RNF-OL-04 | `LabOrderPresenter`; `tests/api.php`: 400/401/403/409/422; `openapi.yaml` | Verificada |
| D8 | Repositorio con adaptadores InMemory, SQLite y PostgreSQL; la transacción cubre orden + ítems + evento | RNF-OL-01, RNF-OL-02, RNF-OL-06 | `tests/run.php`: 28 casos × 3 adaptadores; PostgreSQL 16.15 verificado | Verificada |
| D9 | El cliente reintenta solo lecturas; las escrituras no se reintentan automáticamente | RNF-OL-07 | `tests/client.php`: 6 casos; timeout y servidor caído | Verificada |
| D10 | Flujo UX lineal con contexto de solo lectura y confirmación antes de emitir | RNF-OL-08 | Prototipo: `desktop-lista.jpg` → `desktop-emision.jpg` → `desktop-confirmacion.jpg` → `desktop-detalle.jpg` | Verificada visualmente |
| D11 | El error crítico muestra mensaje, código, HTTP y `request_id`; el foco va al panel y hay salidas | RF-OL-11, RNF-OL-08 | `desktop-error.jpg`, `movil-error.jpg`; ruta `?vista=detalle&orden=ORD-1041&accion=cancelar` | Verificada visualmente |
| D12 | A 390 px se usa una columna, acciones a ancho completo y sin scroll horizontal | RNF-OL-08 | `movil-lista.jpg`, `movil-emision.jpg`, `movil-detalle.jpg`, `movil-error.jpg` | Verificada visualmente |
| D13 | No se implementa cancelación masiva; se difiere con semántica de éxito parcial, permiso y pruebas | RF-OL-07, RF-OL-10, RF-OL-12, RNF-OL-05, RNF-OL-08 | [`respuesta_cambio.md`](./respuesta_cambio.md) | Diferida |

## Decisiones que no se presentan como terminadas

| Tema | Por qué no está cerrado | Qué falta |
|---|---|---|
| Roles reales | El prototipo fija «Dra. Méndez · Médico» | Integración con autenticación y token; pruebas de rol |
| Permisos | La API confía en `X-Permissions` | Derivar permisos del token en el gateway |
| Accesibilidad | Revisión heurística | Auditoría con lector de pantalla y usuarios |
| Cancelación masiva | Cambio diferido | Contrato, permiso, UX de selección, pruebas de lote |
| Estados de muestras/resultados | Módulos ajenos | Acuerdo de contrato con sus dueños |

## Cobertura resumida

| Evidencia | Casos | Qué cubre |
|---|---:|---|
| `tests/run.php` | 28 × 3 adaptadores | Dominio, aplicación, persistencia, transiciones, tenant, idempotencia |
| `tests/mvc.php` | 12 | Controlador, presentación y errores HTTP |
| `tests/api.php` | 18 | Contrato, permisos, validación, conflicto e idempotencia |
| `tests/client.php` | 6 | Cliente HTTP, timeout y reintento de lecturas |
| Capturas | 9 | Flujo feliz y error crítico en escritorio y móvil |
| OpenAPI | 4 rutas de órdenes | Contrato de emisión, consulta, listado, avance y cancelación |
