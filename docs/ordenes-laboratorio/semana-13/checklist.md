# Checklist de revisión técnica — 15 ítems

**Resultado:** `Cumple` · `No cumple` · `Parcial` · `No verificado`
Al inicio de la sesión todos los ítems están **pendientes**. No se marca `Cumple` sin evidencia.

| ID | Área | Ítem verificable | Evidencia esperada | Resultado |
|---|---|---|---|---|
| C-01 | Dominio | Una orden solo existe con paciente, episodio, al menos una prueba y actor | `LabOrder::issue()`; `tests/run.php`; validación de campos | Pendiente |
| C-02 | Dominio | Solo se avanza por transiciones permitidas; `VALIDATED` no se cancela y `CANCELLED` es final | `TRANSITIONS`; `tests/run.php`; `tests/mvc.php` 409 | Pendiente |
| C-03 | Dominio | Una prueba inactiva o inexistente se rechaza; el ítem conserva código y nombre | `IssueLabOrder`; `LabOrderItem`; `tests/run.php` | Pendiente |
| C-04 | Dominio | Paciente, episodio y nota deben pertenecer al mismo contexto abierto | `ExpedienteGateway`; `CARE_EPISODE_NOT_OPEN`; `CLINICAL_NOTE_MISMATCH` | Pendiente |
| C-05 | Idempotencia | La misma `Idempotency-Key` del tenant devuelve la orden existente sin duplicar filas | Restricción `UNIQUE (tenant_id, idempotency_key)`; `tests/api.php` replay 200 | Pendiente |
| C-06 | Integridad | Orden, ítems y evento inicial se escriben en una transacción; un fallo no deja orden parcial | `PdoLabOrderRepository`; prueba de fallo; `RNF-OL-01/02` | Pendiente |
| C-07 | Tenant | Toda lectura y escritura filtra por `tenant_id`; un tenant ajeno recibe 404 | Repositorio; `tests/run.php` y `tests/api.php` | Pendiente |
| C-08 | Permisos | Crear, ver, actualizar y cancelar exigen el permiso correspondiente | `ApiKernel::requiredPermission()`; 403 en `tests/api.php` | Pendiente |
| C-09 | Identidad | El actor del evento es el usuario autenticado, no un campo libre del cuerpo | `LabOrderController`; `X-User-Ref`; pruebas de evento | Pendiente |
| C-10 | Trazabilidad | Cada transición deja un evento con anterior, nuevo, actor, fecha, motivo y `correlation_id`; no se actualiza ni borra | `lab_order_events`; `tests/run.php`; `RNF-OL-05` | Pendiente |
| C-11 | Errores | Los errores usan `problem+json` con código estable y `request_id`; no filtran SQL ni datos clínicos | `ApiKernel`; `LabOrderPresenter`; `tests/api.php` | Pendiente |
| C-12 | Contrato | OpenAPI documenta rutas, permisos y todas las respuestas que la implementación puede emitir | `openapi.yaml`; contraste con `ApiKernel`; sin deriva | Pendiente |
| C-13 | Dependencias | Solo PHP 8.2+, PDO y extensiones necesarias; no hay framework ni librería injustificada | `composer.json`; imports; revisión de `src/` | Pendiente |
| C-14 | Pruebas | Las cuatro baterías pasan y cubren camino feliz, error, límites, tenant e idempotencia | `run.php`, `mvc.php`, `api.php`, `client.php`; resultados | Pendiente |
| C-15 | Operación | `/health` y `/health/ready` responden; no hay secretos en el repositorio; logs no exponen datos clínicos | `public/index.php`; búsqueda de secretos; revisión de logs | Pendiente |

## Regla de cierre por ítem

| Resultado | Qué exige |
|---|---|
| Cumple | Evidencia mostrada y criterio satisfecho |
| Parcial | Evidencia incompleta o límite declarado; genera hallazgo menor/mayor |
| No cumple | Criterio incumplido; genera hallazgo con severidad |
| No verificado | No se pudo comprobar en la sesión; no cuenta como aprobado |

## Cobertura del alcance

| Bloque | Ítems |
|---|---|
| Dominio y reglas | C-01, C-02, C-03, C-04 |
| Integridad e idempotencia | C-05, C-06 |
| Seguridad y tenant | C-07, C-08, C-09 |
| Trazabilidad y errores | C-10, C-11 |
| Contrato y dependencias | C-12, C-13 |
| Pruebas y operación | C-14, C-15 |
