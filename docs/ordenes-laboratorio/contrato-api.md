# Contrato API de órdenes de laboratorio

Estado: contrato implementado parcialmente en la Semana 16; la integración queda
sujeta a las pruebas de PostgreSQL, RBAC y tenant descritas en el PR.

Autoría del módulo: `ASII-16 — Órdenes de laboratorio` (equipo de laboratorio).
Este contrato se preparó en el marco del plan transversal de integración del
18 de octubre de 2026 para acordar la frontera entre órdenes, catálogo,
recepción y resultados antes de escribir código.

## Crear una orden

`POST /api/v1/lab-orders`

| Elemento | Contrato |
|---|---|
| Autenticación | `Authorization: Bearer <token>` validado por el middleware de auth del grupo |
| Hospital | `X-Tenant-ID`; selecciona una única base HOSPITAL autorizada |
| Idempotencia | `Idempotency-Key` obligatoria y única por hospital; reutilizarla con otro cuerpo devuelve `409` |
| Trazabilidad | `X-Correlation-ID` opcional; el servidor genera un UUID si falta |
| Permiso | `laboratorio.ordenes.crear` (aún no existe en el RBAC de `develop`) |

Solicitud:

```json
{
  "local_patient_id": "uuid",
  "care_episode_id": "uuid",
  "clinical_note_id": "uuid",
  "priority": "ROUTINE",
  "clinical_context": "Control metabólico",
  "items": [
    {"lab_test_id": "uuid"},
    {"lab_test_id": "uuid"}
  ]
}
```

`clinical_note_id` y `clinical_context` son opcionales. `priority` admite
`ROUTINE`, `URGENT` o `STAT` y usa `ROUTINE` por defecto. El usuario que ordena
se obtiene del token y se guarda mediante su `local_user_ref`.

Reglas atómicas:

- La orden contiene al menos un ítem y no repite `lab_test_id`.
- Paciente, episodio, nota clínica, usuario y pruebas pertenecen a la misma base HOSPITAL.
- El episodio corresponde al paciente y está abierto para recibir órdenes.
- Cada `lab_test_id` identifica una versión activa y vigente en el momento de crear la orden.
- La orden y todos sus ítems se insertan en una transacción con estado inicial `REQUESTED`.
- Si cualquier ítem falla, no se conserva la orden ni ningún ítem.
- Repetir la misma clave con la misma solicitud devuelve el resultado original;
  reutilizarla con otro contenido devuelve `409`.

Respuesta `201`:

```json
{
  "data": {
    "lab_order_id": "uuid",
    "status": "REQUESTED",
    "priority": "ROUTINE",
    "ordered_at": "2026-10-20T10:00:00-06:00",
    "items": [
      {"lab_order_item_id": "uuid", "lab_test_id": "uuid", "status": "REQUESTED"}
    ]
  },
  "meta": {"correlation_id": "uuid"}
}
```

## Consultar órdenes

`GET /api/v1/lab-orders?patient_id={uuid}&status={status}&from={ISO-8601}&to={ISO-8601}&page=1&per_page=25`

Permiso: `laboratorio.ordenes.ver`. Los filtros son opcionales; `per_page`
admite de 1 a 100. Solo devuelve órdenes de la base HOSPITAL seleccionada.

La respuesta incluye la página solicitada y el total calculado antes de aplicar
el ordenamiento:

```json
{
  "data": {
    "lab_orders": [],
    "pagination": {"page": 1, "per_page": 25, "total": 0, "last_page": 1}
  },
  "meta": {"request_id": "req-...", "correlation_id": "uuid"}
}
```

`from` y `to` aceptan una fecha `YYYY-MM-DD` (el límite `to` es exclusivo al
inicio del día siguiente) o una fecha ISO-8601 con hora y zona horaria.

`GET /api/v1/lab-orders/{lab_order_id}`

Permiso: `laboratorio.ordenes.ver`. Incluye los ítems y la versión de catálogo
usada al ordenar; no sustituye los endpoints propietarios de muestras o resultados.

## Cancelar una orden

`POST /api/v1/lab-orders/{lab_order_id}/cancellation`

Permiso: `laboratorio.ordenes.cancelar`. Requiere `Idempotency-Key` y un cuerpo
`{"reason":"texto no vacío"}`. Solo una orden `REQUESTED` sin muestra recibida
puede pasar a `CANCELLED`; la cancelación conserva orden e ítems como evidencia.

## Errores

Todos devuelven `error.code` y `meta.request_id`; las respuestas exitosas incluyen también `meta.correlation_id`
cuando la solicitud alcanza el handler.

| HTTP | Código | Condición |
|---:|---|---|
| 400 | `INVALID_REQUEST` | JSON, encabezado o filtro malformado |
| 401 | `UNAUTHENTICATED` | Token ausente, vencido o inválido |
| 403 | `FORBIDDEN` | Falta el permiso o el hospital no está autorizado |
| 404 | `RESOURCE_NOT_FOUND` | Paciente, episodio, nota, prueba u orden no existe en HOSPITAL |
| 409 | `IDEMPOTENCY_CONFLICT` | La clave ya se usó con otro contenido |
| 409 | `ORDER_STATE_CONFLICT` | Estado incompatible o muestra ya recibida |
| 422 | `EMPTY_ORDER` | No se envió ninguna prueba |
| 422 | `DUPLICATE_LAB_TEST` | Una prueba aparece más de una vez |
| 422 | `INACTIVE_LAB_TEST` | La versión de catálogo no está activa o vigente |
| 422 | `PATIENT_EPISODE_MISMATCH` | El episodio no corresponde al paciente |
| 400 | `IDEMPOTENCY_KEY_REQUIRED` | Falta el encabezado `Idempotency-Key` |
| 422 | `EPISODE_NOT_OPEN` | El episodio no está abierto |
| 503 | `LAB_ORDER_UNAVAILABLE` | Dependencia o base HOSPITAL no disponible |

## Propiedad y dependencias

| Entidad | Tabla en base HOSPITAL | Propietario |
|---|---|---|
| `lab_order` | `lab_orders` | ASII-16 Órdenes de laboratorio |
| `lab_order_item` | `lab_order_items` | ASII-16 Órdenes de laboratorio |
| `lab_test` | `lab_tests` | ASII-17 Catálogo de pruebas de laboratorio |
| `specimen` | `specimens` | Recepción de muestras (María de los Ángeles) |
| `lab_result_version` | `lab_result_versions` | Ingreso y validación de resultados |

- Los ítems conservan el UUID exacto de la versión de catálogo ordenada.
- Auth/RBAC deben resolver JWT, hospital, usuario local y permisos antes del caso de uso.
- La persistencia H6 debe usar UUID, `timestamptz`, FK locales indexadas y `ON DELETE RESTRICT`.
- Al crear o cancelar se registra auditoría y se publica un evento mínimo por
  outbox cuando H8 esté disponible; no se incluye información clínica completa
  en el evento.

## Pendientes acordados con el equipo

1. Confirmar los nombres de los permisos `laboratorio.ordenes.*` en el RBAC común.
2. Publicar la migración H6 (`lab_orders`, `lab_order_items`) y su relación con
   `lab_tests` del módulo de catálogo.
3. Confirmar el formato de `X-Correlation-ID` frente a `X-Request-ID` del contrato
   de ocupación y unificarlo en el contrato común de errores.
4. Definir los estados compartidos entre órdenes, muestras y resultados.
5. Confirmar el nombre final del endpoint (`/api/v1/lab-orders`) frente al
   estilo `/api/v1/lab/...` de recepción de muestras.
