# Métricas SQA — 7 indicadores

**Regla:** cada métrica tiene definición, umbral, fuente, responsable, frecuencia y acción. Un valor
sin fuente reproducible no cuenta.

| ID | Métrica | Fórmula / definición | Umbral | Fuente | Responsable | Frecuencia | Acción si falla |
|---|---|---|---|---|---|---|---|
| M1 | Tasa de pruebas aprobadas | casos aprobados / casos ejecutados × 100 | 100 % en la rama principal | `run.php`, `mvc.php`, `api.php`, `client.php` | Autor | Cada PR | Bloquear merge; corregir o justificar la prueba |
| M2 | Defectos escapados | defectos hallados después de la revisión / defectos totales × 100 | 0 críticos; ≤10 % por iteración | Registro de hallazgos y minutas | QA / moderador | Mensual | Análisis de causa raíz; reforzar checklist |
| M3 | Cobertura de transiciones | transiciones permitidas y prohibidas cubiertas / total de transiciones × 100 | 100 % | `tests/run.php`; matriz de estados | Autor | Cada PR que toque dominio | Agregar casos; no liberar |
| M4 | Conformidad de contrato | operaciones y códigos documentados que coinciden con la implementación / total × 100 | 100 % | `openapi.yaml` + prueba de contrato | Autor + QA | Cada cambio de contrato | Corregir contrato o código; abrir hallazgo |
| M5 | Incidentes de tenant | accesos cruzados confirmados entre hospitales | 0 | Pruebas de aislamiento; monitoreo 403/404/503 | Seguridad | Cada release y monitoreo continuo | Incidente; bloquear release; revisar consultas |
| M6 | Completitud de trazabilidad | eventos con actor, from/to, fecha, correlation_id y motivo (si cancelación) / total × 100 | 100 % | Consulta SQL sobre `lab_order_events` | QA | Semanal | Corregir transición; auditar el período |
| M7 | Latencia p95 de listado | percentil 95 de `GET /lab-orders` con 10 000 órdenes y filtro por paciente | <200 ms | Benchmark reproducible; `EXPLAIN` | Autor / DBA | Cada release | Revisar índice/consulta; plan de acción |

## Detalle por métrica

### M1 — Tasa de pruebas aprobadas

- **Qué mide:** estabilidad de la batería y confianza para integrar.
- **Exclusión:** una prueba marcada como omitida no cuenta como aprobada.
- **Evidencia:** salida completa de las cuatro baterías y fecha de ejecución.
- **Riesgo asociado:** R3, R4, R9.

### M2 — Defectos escapados

- **Qué mide:** eficacia de la revisión y de las pruebas antes de publicar.
- **Definición de defecto:** comportamiento que incumple un RF/RNF o un criterio de aceptación.
- **Umbral:** cualquier defecto crítico invalida la iteración; los mayores se analizan uno por uno.
- **Riesgo asociado:** R2, R4, R6.

### M3 — Cobertura de transiciones

- **Universo:** cada par `from → to` permitido y cada par prohibido relevante.
- **Criterio:** una transición está cubierta si existe una prueba que verifica el resultado y el
  evento generado.
- **Riesgo asociado:** R4, R9.

### M4 — Conformidad de contrato

- **Universo:** rutas, métodos, permisos, códigos de éxito y error, y esquemas del OpenAPI.
- **Criterio:** una operación cumple si el código devuelve exactamente los códigos documentados o si
  el contrato se actualiza en el mismo cambio.
- **Riesgo asociado:** R6.

### M5 — Incidentes de tenant

- **Definición:** cualquier lectura o escritura confirmada sobre una orden de otro hospital.
- **Cero tolerancia:** un incidente confirmado bloquea el release y exige revisión de todas las
  consultas.
- **Riesgo asociado:** R1, R5.

### M6 — Completitud de trazabilidad

- **Campos requeridos:** `from_status`, `to_status`, `actor`, `occurred_at`, `correlation_id`; en
  cancelación también `reason`.
- **Criterio:** el 100 % de los eventos del período cumple; un solo evento incompleto abre hallazgo.
- **Riesgo asociado:** R4, R9.

### M7 — Latencia p95 de listado

- **Escenario:** 10 000 órdenes del mismo tenant, filtro por paciente, `per_page=20`.
- **Medición:** al menos 30 repeticiones; se reporta p50, p95 y p99.
- **Criterio:** p95 <200 ms; si falla, se revisa el plan de ejecución y el índice
  `(tenant_id, patient_id)`.
- **Riesgo asociado:** R7 (adopción) y R3 (consulta lenta no es fallo de integridad, pero afecta el
  seguimiento).

## Revisión de umbrales

Los umbrales se revisan en la sesión SQA del 2026-12-07. Cambiarlos exige justificar la fuente, el
impacto en el release y la aprobación del moderador.
