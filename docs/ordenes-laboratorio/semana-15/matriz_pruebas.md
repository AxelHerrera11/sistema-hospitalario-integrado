# Matriz RF/RNF → prueba — Semana 15

**Ejecución:** 22-oct-2026 · `php tests/semana15.php` → 24/24 · `php tests/run.php` → 84/84.

## 1. Casos

| ID | Tipo | Requisito | Escenario | Entrada o condición | Oráculo | Evidencia | Estado |
|---|---|---|---|---|---|---|---|
| U1 | Unitaria | RF-OL-01, RF-OL-10 | Emisión válida | Orden con `test-glu` y `test-hb` | `ISSUED`, 2 ítems, 1 evento | `semana15.txt` U1 | PASS |
| U2 | Unitaria | RF-OL-01 | Prueba repetida | Dos ítems con `test-glu` | `ORDER_ITEMS_INVALID` | U2 | PASS |
| U3 | Unitaria | RF-OL-06 | Salto de estado | `ISSUED → VALIDATED` | `INVALID_STATUS_TRANSITION` | U3 | PASS |
| U4 | Unitaria | RF-OL-07, RF-OL-10 | Cancelación con motivo | Motivo «Estudio duplicado» | `CANCELLED`; evento con actor y motivo | U4 | PASS |
| U5 | Unitaria | RF-OL-06, RF-OL-07 | Cancelar una orden validada | Cuatro avances hasta `VALIDATED` | `ORDER_ALREADY_VALIDATED` | U5 | PASS |
| U6 | Unitaria | RF-OL-07 | Motivo vacío | `"   "` | `CANCELLATION_REASON_REQUIRED` | U6 | PASS |
| U7 | Unitaria | RF-OL-02 | Episodio cerrado | `care_episode_id = epi-closed` | `CARE_EPISODE_NOT_OPEN` | U7 | PASS |
| U8 | Unitaria | RF-OL-03 | Prueba inactiva o inexistente | `test-unknown` | `LAB_TEST_NOT_ACTIVE` | U8 | PASS |
| U9 | Unitaria | RF-OL-05 | Consulta de la orden | `FindLabOrder` por ID | 2 ítems y 1 evento | U9 | PASS |
| U10 | Unitaria | RF-OL-08 | Listado por estado | Filtro `status=ISSUED` | Solo órdenes `ISSUED` | U10 | PASS |
| W1 | Caja blanca | RF-OL-04, RNF-OL-01 | Rama de idempotencia | Misma clave dos veces | Misma orden; no duplica | W1 | PASS |
| W2 | Caja blanca | RNF-OL-01, RNF-OL-02 | Rama de fallo y rollback | `failNextCreate = true` | `PERSISTENCE_FAILURE`; conteo sin cambios | W2 | PASS |
| W3 | Caja blanca | RF-OL-07 | Condiciones de longitud | 4, 5, 250 y 251 caracteres | 4 y 251 inválidos; 5 y 250 válidos | W3 | PASS |
| B1 | Caja negra | RF-OL-01 | Emisión por API | `POST /api/v1/lab-orders` válido | `201` con ID | B1 | PASS |
| B2 | Caja negra | RF-OL-04 | Replay idempotente | Misma `Idempotency-Key` | `200` con la misma orden | B2 | PASS |
| B3 | Caja negra | RF-OL-12 | Permiso de creación ausente | Solo `laboratorio.ordenes.ver` | `403 FORBIDDEN` | B3 | PASS |
| B4 | Caja negra | RF-OL-12 | Permiso de lectura ausente | Solo `laboratorio.ordenes.crear` | `403` | B4 | PASS |
| B5 | Caja negra | RF-OL-09 | Aislamiento por tenant | Tenant B consulta orden de A | `404` | B5 | PASS |
| B6 | Caja negra | RF-OL-07 | Límites del motivo en API | 4, 251 y 5 caracteres | `422`, `422`, `200 CANCELLED` | B6 | PASS |
| B7 | Caja negra | RNF-OL-09 | Paginación acotada | `per_page` 1, 50, 0 y 51 | `200`, `200`, `422`, `422` | B7 | PASS |
| B8 | Caja negra | RF-OL-04, RF-OL-11 | Idempotency-Key ausente | `POST` sin la clave | `422 IDEMPOTENCY_KEY_REQUIRED` | B8 | PASS |
| B9 | Caja negra | RF-OL-11 | Ruta y método desconocidos | `/api/v9/...` y `DELETE` | `404` en ambos | B9 | PASS |
| B10 | Caja negra | RF-OL-11 | Tenant ausente | `GET` sin cabeceras | `400` `problem+json` con `request_id` | B10 | PASS |
| B11 | Caja negra | RNF-OL-02 | Falla de persistencia en API | `failNextCreate` durante `POST` | `503 PERSISTENCE_FAILURE` sin traza | B11 | PASS |

## 2. Cobertura por requisito

| Requisito | Casos | Observación |
|---|---|---|
| RF-OL-01 | U1, U2, B1 | Emisión y validación de ítems |
| RF-OL-02 | U7 | Coherencia del episodio |
| RF-OL-03 | U8 | Prueba activa/vigente |
| RF-OL-04 | W1, B2, B8 | Idempotencia y clave obligatoria |
| RF-OL-05 | U9 | Consulta con ítems e historial |
| RF-OL-06 | U3, U5 | Transiciones válidas y orden final |
| RF-OL-07 | U4, U5, U6, W3, B6 | Cancelación, motivo y límites |
| RF-OL-08 | U10 | Listado por estado |
| RF-OL-09 | B5 | Aislamiento entre hospitales |
| RF-OL-10 | U1, U4 | Evento inicial y evento de cancelación |
| RF-OL-11 | B8, B9, B10, B11 | Errores estables y sin traza |
| RF-OL-12 | B3, B4 | Permisos por operación |
| RNF-OL-01 | W1, W2 | No duplicación y rollback |
| RNF-OL-02 | W2, B11 | Falla segura de persistencia |
| RNF-OL-03 | — | Medición de rendimiento de la semana 5 |
| RNF-OL-04 | — | Revisión de datos clínicos en respuesta/log |
| RNF-OL-05 | U1, U4, U9 | Evidencia indirecta de historial de solo inserción |
| RNF-OL-06 | `run.php` | InMemory, SQLite y PostgreSQL 16.15 |
| RNF-OL-07 | Toda la batería | Sin red ni servicios externos en `semana15.php` |
| RNF-OL-08 | — | UX y accesibilidad de semanas 8–11 |
| RNF-OL-09 | B7 | Límite 1–50 |
| RNF-OL-10 | — | Revisión arquitectónica de la semana 7 |

## 3. Técnicas de caja negra

- **Clases de equivalencia:** motivos válidos/inválidos, claves idempotentes presentes/ausentes,
  permisos presentes/ausentes y tenant propio/ajeno.
- **Valores límite:** 4/5/250/251 para `reason`; 0/1/50/51 para `per_page`.
- **Prueba de error:** sin tenant, sin usuario, sin permiso, sin clave, ruta y método desconocidos.
- **Prueba de falla:** repositorio forzado a fallar para verificar `503` y ausencia de traza.

## 4. Brechas declaradas

No se automatizan en esta semana RNF-OL-03 (rendimiento), RNF-OL-04 (inspección de datos clínicos),
RNF-OL-05 (verificación directa de `UPDATE`/`DELETE` en base), RNF-OL-08 (UX) ni RNF-OL-10
(arquitectura). Se enlazan con las evidencias de las semanas 5, 7 y 8–11; no se declaran cubiertos por
esta batería.
