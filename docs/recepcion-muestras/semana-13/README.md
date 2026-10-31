# Semana 13 — Revisión técnica

Checklist ejecutado por ReceptionContractTest: error 404 estable, respuesta pública sin claves internas ni referencias clínicas, rechazo de actor local revocado y permiso independiente para etiquetas. La API valida límites que coinciden con PostgreSQL. Las semanas se presentan como una pila de PR: cada base es la rama del PR anterior.


## Checklist de revisión técnica formal (plan, semana 13)

Autora: Mar-03. Revisión e integración: Yamilet (acordado en la coordinación de migraciones de S05). Revisión solicitada en el orden #841 → #692 → … → #839.

| # | Punto de revisión | Evidencia | Estado |
|---|---|---|---|
| 1 | Controlador sin SQL ni reglas de negocio de dominio | `ReceptionController`, `Application/ReceiveSpecimen` | Cumple |
| 2 | Cada ruta protegida por `tenant`, `jwt.auth` y `rbac:muestras.*` | `ReceptionServiceProvider` | Cumple |
| 3 | Respuesta pública sin `request_hash`, actor ni referencias clínicas internas | `ReceptionContractTest` | Cumple |
| 4 | Actor local revocado o no sincronizado recibe 403 | `ReceptionContractTest`, `ReceptionApiTest` | Cumple |
| 5 | Idempotencia y conflicto de clave | `ReceiveSpecimenTest`, `ReceptionApiTest` | Cumple |
| 6 | Atomicidad muestra + outbox y exclusión concurrente | `ReceptionIntegrityTest` | Cumple |
| 7 | Restricciones de base (UUID, FK, CHECK) | `SpecimenIntegrityTest` | Cumple |
| 8 | Contrato OpenAPI coherente con rutas | `semana-05/openapi.yaml` | Revisado manualmente |
| 9 | Rangos clínicos validados con personal de laboratorio | SD-REC-01 | **Pendiente** |
| 10 | `muestras.rechazar` sin endpoint asociado | RBAC | Observación abierta |
| 11 | `pending-orders` devuelve filas de la proyección completas | `ReceptionController::pending` | Observación abierta: valorar recorte de campos |
| 12 | Integración con el contrato real de órdenes (ASII-16) | `OrderProjection` | Pendiente de contrato |
