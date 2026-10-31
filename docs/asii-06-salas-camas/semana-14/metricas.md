# Métricas SQA

Siete métricas propuestas. Responsable de recolectar y documentar **cada una**:
Madelin Cerón. Corrección técnica y verificación independiente: Pendiente de
asignación. Fecha de línea base: 28/10/2026; comandos/resultados en [README](./README.md).
Los umbrales son metas del plan, no requisitos históricos ni SLO de producción.

| ID / nombre y objetivo | Fórmula y unidad | Umbral propuesto | Fuente y frecuencia | Acción de control |
|---|---|---|---|---|
| M-01 Pruebas aprobadas: detectar regresión | 100 × casos aprobados / casos previstos en suites Node y PHPUnit seleccionadas; errores cuentan como no aprobados. | 100%; cero bloqueos antes del cierre funcional. | Salidas de Node y tres suites PHPUnit del README; en cada PR y tras corrección. | C-04: registrar fallos/bloqueos, corregir o habilitar entorno autorizado y repetir; no excluirlos del denominador. |
| M-02 Integridad ward/bed: impedir relaciones inválidas | Número de violaciones aceptadas en casos de FK, código ward duplicado y `(ward_id, code)` duplicado. | 0 aceptaciones indebidas; ejecutar los 3 casos negativos. | Restricciones de migración hospitalaria y futura suite negativa PostgreSQL; cada cambio de persistencia y Semana 15. | C-02/C-05: rechazar cierre si falta caso; corregir validación/restricción en PR técnico y repetir con rollback. |
| M-03 Estados: rechazar valores fuera de dominio | 100 × casos de estado correctos / casos ejecutados de matriz de dominios; registrar también cobertura ejecutados/previstos. | 100% correctos y cobertura completa: 2 ward + 4 cama válidos y 1 inválido por entidad (8 casos). | Domain Ward/Bed, CHECKs y pruebas parametrizadas pendientes; cada cambio de estados y Semana 15. | C-02/C-05: incorporar casos faltantes y verificar rechazo sin escritura; no inferir transiciones clínicas. |
| M-04 Acceso: impedir lecturas no autorizadas | Número de respuestas que exponen catálogo en escenarios negativos / escenarios negativos ejecutados. | 0 exposiciones y todos los escenarios previstos ejecutados. | Endpoint y aislamiento: sin JWT, sin permiso, header inválido, hospital inexistente, cruce A/B y guard sin asignar; por PR. | C-01/C-04: detener aceptación ante exposición, priorizar alta y reproducir en test; no omitir guard/RBAC. |
| M-05 Contrato API: respuestas confiables | 100 × escenarios con HTTP/payload seguro esperado / escenarios de contrato ejecutados. | 100% y cobertura de 200 lista/vacío, 404 ward ausente, 503 persistencia, 401/403, 400 tenant inválido. | WardCatalogEndpointTest y futura prueba de fallo real de almacenamiento; por PR y revisión semanal de errores sanitizados. | C-04/C-06: registrar código, request ID anonimizado y causa; corregir contrato y repetir; no equiparar con disponibilidad productiva. |
| M-06 Trazabilidad: ningún requisito sin evidencia | 100 × RF/RNF con vínculo a criterio, componente y evidencia identificada / 10 requisitos (6 RF + 4 RNF). | 100% de vínculos; estado ejecutado/pendiente obligatorio. | Tabla del plan, trazabilidad Semana 4 y resultados; cada revisión documental. | C-03/C-08: completar enlaces o marcar brecha; una referencia a prueba pendiente no equivale a requisito aprobado. |
| M-07 Defectos abiertos: priorizar cierre verificable | Conteo de defectos abiertos por severidad alta/media/baja en el registro de hallazgos, separado de riesgos potenciales. | 0 altos para cierre funcional; todo defecto con responsable/rol pendiente, estado y evidencia. | Hallazgos de #1073 y nuevos defectos confirmados; diario hasta entrega y dos revisiones en Semana 15. | C-06/C-08: escalar altos, separar contención de solución y exigir verificación independiente del cierre. |

## Línea base realmente observada

| Métrica | Resultado y límite |
|---|---|
| M-01 | 15/19 = 78.95%: Node 2 + endpoint 13 aprobados; aislamiento 3 + integración 1 con error ambiental. Build y cuatro mensajes standalone se registran aparte, sin inflar casos de PHPUnit. |
| M-02 | No medida: PostgreSQL bloqueado; el test PDO positivo no cubriría por sí solo los tres negativos. |
| M-03 | Suite standalone rechaza `broken` en cama y comprueba default; matriz completa de 8 casos no ejecutada, sin porcentaje de conformidad. |
| M-04 | Endpoint 13/13 aprobado, pero no se extrajo aquí un denominador de escenarios negativos; aislamiento 3 errores. Valor agregado no medido; umbral aún no acreditado. |
| M-05 | Suite endpoint 13/13 aprobada (54 aserciones); no se levantó un registro de escenarios por código ni disponibilidad real. Métrica contractual completa pendiente. |
| M-06 | Tabla del plan identifica 10/10 requisitos con criterio, componente/evidencia mediante referencia a Semana 4: 100% de vínculos documentales; no 100% de implementación o pruebas aprobadas. |
| M-07 | En #1073 se registra un defecto alto confirmado (REV-01), abierto con contención temporal, y tres brechas REV-02/03/04. No se cuentan los diez riesgos como diez defectos ni se inventa una serie histórica. |

Capacidad: reconciliar total de camas por ward con la suma por estado en una
misma muestra transaccional (control C-05 propuesto, R-05); no hay aforo máximo
modelado. Un total consistente no prueba camas asignables ni ausencia de estancias.
