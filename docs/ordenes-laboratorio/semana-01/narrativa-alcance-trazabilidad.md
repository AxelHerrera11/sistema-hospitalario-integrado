# Narrativa de alcance y trazabilidad — Órdenes de laboratorio desde EMR

**Semana 1 · Flujo:** emisión de orden desde el expediente y seguimiento de su estado.

## 1. Problema

En el expediente electrónico el médico decide solicitar estudios, pero esa decisión suele quedar en
texto libre o en una pantalla separada. Sin una orden formal, el laboratorio no sabe qué practicar,
el médico no puede seguir el avance y el sistema no puede auditar quién pidió qué ni cuándo. El
módulo convierte esa decisión en un objeto clínico con identidad, estado y rastro.

## 2. Límites del proceso

```text
Expediente          Órdenes de laboratorio (este módulo)              Otros módulos
──────────          ─────────────────────────────────────            ─────────────
Paciente      ─┐
Episodio      ─┼──▶  Emitir → ISSUED → COLLECTED → IN_PROCESS
Nota clínica  ─┘         │        → RESULTED → VALIDATED
                         └────────→ CANCELLED
                                    │
Catálogo de pruebas ────────────────┘ (referencia lógica por lab_test_id)
Muestras / resultados ──────────────┘ (transiciones y estados compartidos, aún por acordar)
```

El módulo **referencia** entidades de otros dueños; no las crea ni las modifica. En esta fase el
expediente y el catálogo se representan detrás de un puerto con adaptador en memoria, tal como exige
el plan de integración del grupo.

## 3. Supuestos explícitos

1. Una orden pertenece a **un paciente, un episodio y una base HOSPITAL** (aislamiento por tenant).
2. El episodio debe estar **abierto** y corresponder al paciente; la nota clínica es opcional.
3. Una orden tiene **al menos un ítem** y no repite la misma prueba.
4. Cada prueba debe estar **activa y vigente** en el catálogo del hospital.
5. Una `Idempotency-Key` repetida en el mismo hospital devuelve la orden ya creada; nunca duplica.
6. Los estados son un catálogo cerrado y las transiciones se validan en el dominio.
7. Una orden `VALIDATED` es terminal; una `CANCELLED` también. Cancelar exige motivo y actor.
8. Los errores usan códigos estables y no exponen `SQLSTATE`, rutas ni datos clínicos.

## 4. Actores

- **Médico del expediente:** conoce al paciente y decide las pruebas; emite y puede cancelar.
- **Enfermera:** consulta la orden para preparar al paciente; no emite ni cambia estados clínicos.
- **Personal de laboratorio:** registra la toma y el procesamiento; avanza estados permitidos.
- **Sistema:** valida tenant, permisos, coherencia, idempotencia, transiciones y auditoría.

## 5. Flujo narrado

1. El médico abre el expediente de un paciente con un episodio abierto.
2. Elige una o varias pruebas del catálogo y, si lo desea, deja contexto clínico y una nota asociada.
3. La pantalla envía la orden con un `Idempotency-Key` para que un doble clic no genere dos órdenes.
4. El servidor valida token, hospital y permiso `laboratorio.ordenes.crear`.
5. La aplicación consulta el expediente: paciente–episodio–nota coherentes y episodio abierto.
6. Consulta el catálogo: todas las pruebas existen, están activas y vigentes.
7. El dominio construye la orden `ISSUED`, con sus ítems y el evento inicial.
8. El repositorio guarda orden, ítems e historial en una sola transacción.
9. La respuesta devuelve la orden, su estado y un `correlation_id` para rastrear el caso.
10. El laboratorio avanza el estado; cada transición deja un evento con actor y fecha.
11. El médico consulta la orden y ve el mismo historial que el laboratorio.
12. Si la orden se cancela antes de validarse, se guarda el motivo y se conserva todo el rastro.

## 6. Excepciones del flujo

| Situación | Resultado | Qué no ocurre |
|---|---|---|
| Sin token o sin permiso | 401 / 403 | No se consulta el expediente |
| Sin `X-Tenant-ID` o de otro hospital | 400 / 403 | No se lee ni escribe en otra base |
| Cero ítems o prueba repetida | 422 `ORDER_ITEMS_INVALID` | No se crea la orden |
| Episodio cerrado o de otro paciente | 422 `CARE_EPISODE_NOT_OPEN` | No se crea la orden |
| Prueba inexistente o no vigente | 422 `LAB_TEST_NOT_ACTIVE` | No se crea la orden |
| Misma `Idempotency-Key` | 200 con la orden existente | No se duplica |
| Transición no permitida | 409 `INVALID_STATUS_TRANSITION` | No cambia el estado |
| Cancelar una orden validada | 409 `ORDER_ALREADY_VALIDATED` | No se altera el historial |
| Falla la base de datos | 503 `PERSISTENCE_FAILURE` | Rollback completo, sin datos a medias |

## 7. Trazabilidad

| Decisión de diseño | Caso de uso | Nodo de actividad | Mensaje de secuencia |
|---|---|---|---|
| La orden nace en el expediente | CU-OL-01 | B, C | `POST /lab-orders` |
| Coherencia paciente–episodio–nota | CU-OL-01 | F | `EMR.episodioYNota` |
| Vigencia del catálogo | CU-OL-01 | H | `EMR.pruebasActivas` |
| Idempotencia por hospital | CU-OL-01 | J, K | `REP.buscarPorIdempotencia` |
| Atomicidad de orden e historial | CU-OL-01 | M, N | `REP.guardar` (transacción) |
| Seguimiento del estado | CU-OL-02, CU-OL-03 | Lectura / transición | `GET` y `PATCH /lab-orders/{id}` |
| Cancelación conservando el rastro | CU-OL-04 | Decisión de cancelación | `POST /lab-orders/{id}/cancel` |
| Consulta acotada al tenant | CU-OL-05 | Filtro por paciente/episodio | `GET /lab-orders?patient=…` |

## 8. Fuera de alcance y dependencias abiertas

- No se crean tablas de otros módulos ni migraciones Laravel en esta entrega.
- Los estados compartidos con muestras y resultados deben ratificarse con sus dueños.
- El formato de `X-Correlation-ID` frente a `X-Request-ID` sigue siendo un acuerdo del grupo.
- La implementación real del catálogo y del expediente llega por sus módulos dueños; aquí se aísla
  detrás de un puerto para no bloquear el avance.

Estas dependencias están registradas en [`../PENDIENTES-INTEGRACION.md`](../PENDIENTES-INTEGRACION.md)
y no se resuelven inventando contratos ajenos.
