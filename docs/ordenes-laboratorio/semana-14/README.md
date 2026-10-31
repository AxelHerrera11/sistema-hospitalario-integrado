# Órdenes de laboratorio desde EMR — Semana 14: plan SQA

**Módulo:** Órdenes de laboratorio desde EMR (ASII-16) · **Entrega académica:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09)
**Alcance:** calidad de «emisión de orden desde el expediente y seguimiento de su estado»,
confiabilidad, seguridad, trazabilidad y riesgos de órdenes, expediente, pruebas solicitadas y
estados.
**Artefactos:** [`metricas.md`](./metricas.md) · [`matriz_riesgos.md`](./matriz_riesgos.md)

## 1. Objetivo del plan

Definir cómo se mide y controla la calidad del módulo antes y después de cada entrega, con métricas
verificables, umbrales, responsables y acciones. El plan no declara que el sistema sea confiable: fija
qué evidencia haría falta para afirmarlo y qué se hace cuando un umbral se incumple.

## 2. Política de calidad

- **Integridad primero:** ninguna orden parcial, ningún evento perdido y ninguna transición inválida.
- **Seguridad por defecto:** tenant y permisos se verifican en cada operación; los errores no revelan
  datos clínicos ni detalles de persistencia.
- **Trazabilidad reconstruible:** toda orden se puede explicar desde su historial.
- **Contrato verificable:** el OpenAPI y la implementación no pueden divergir sin un hallazgo.
- **Calidad medible:** cada release se evalúa con las siete métricas y la matriz de riesgos.
- **No se inventa evidencia:** un ítem sin medición queda «No verificado».

## 3. Métricas (resumen)

| ID | Métrica | Umbral | Frecuencia | Responsable |
|---|---|---|---|---|
| M1 | Tasa de pruebas aprobadas | 100 % en la rama principal | Cada PR | Autor |
| M2 | Defectos escapados | 0 críticos; ≤10 % del total por iteración | Mensual | QA / moderador |
| M3 | Cobertura de transiciones permitidas y prohibidas | 100 % | Cada PR que toque dominio | Autor |
| M4 | Conformidad contrato–implementación | 100 % de operaciones y códigos | Cada cambio de contrato | Autor + QA |
| M5 | Incidentes de fuga entre tenants | 0 confirmados | Cada release y monitoreo | Seguridad |
| M6 | Completitud de trazabilidad | 100 % de eventos con campos requeridos | Semanal | QA |
| M7 | Latencia p95 de listado | <200 ms con 10 000 órdenes y filtro por paciente | Cada release | Autor / DBA |

La definición, fórmula, fuente y acción de cada métrica están en [`metricas.md`](./metricas.md).

## 4. Matriz de riesgos

[`matriz_riesgos.md`](./matriz_riesgos.md) contiene nueve riesgos con probabilidad, impacto, nivel,
control preventivo, control detectivo, responsable y plan de contingencia. Los riesgos principales:

- **R1:** permisos confiados al gateway → escalada de privilegios.
- **R2:** duplicado por reintento → dos órdenes para la misma solicitud.
- **R3:** escritura parcial → orden sin ítems o sin evento inicial.
- **R4:** transición inválida o cancelación de una orden validada.
- **R5:** fuga de datos entre hospitales.
- **R6:** deriva entre OpenAPI e implementación.
- **R7:** flujo inusable en móvil o con teclado.
- **R8:** dependencia o framework innecesario.
- **R9:** historial alterado o incompleto.

## 5. Controles preventivos y detectivos

| Control | Tipo | Riesgos que cubre | Evidencia | Responsable | Frecuencia |
|---|---|---|---|---|---|
| Invariantes en `LabOrder` | Preventivo | R3, R4, R9 | `tests/run.php` | Autor | Cada PR |
| `UNIQUE (tenant_id, idempotency_key)` | Preventivo | R2 | Esquema + prueba de replay | Autor | Cada PR |
| Transacción orden + ítems + evento | Preventivo | R3 | Prueba de fallo de persistencia | Autor | Cada release |
| Filtro por tenant en repositorio | Preventivo | R5 | Pruebas de aislamiento | Autor | Cada PR |
| Permisos antes del caso de uso | Preventivo | R1 | `ApiKernel` + 403 | Seguridad | Cada PR |
| Gateway que sobrescribe `X-Permissions` | Preventivo | R1 | Configuración + prueba de integración | Infraestructura | Cada despliegue |
| Checklist de revisión de 15 ítems | Preventivo | R3, R4, R6, R8 | Semana 13 | Moderador | Cada release |
| Pruebas de contrato | Detectivo | R6 | Comparación OpenAPI–kernel | QA | Cada cambio de contrato |
| Consulta de auditoría de eventos | Detectivo | R9 | SQL de campos faltantes | QA | Semanal |
| Monitoreo de 403/404/503 y latencia | Detectivo | R1, R5, R7 | Panel de métricas | Operación | Continuo |
| Revisión de logs y redacción | Detectivo | R5 | Muestreo de logs | Seguridad | Mensual |
| Prueba de carga de listado | Detectivo | R7 | Benchmark reproducible | Autor | Cada release |

## 6. Roles y responsabilidades

| Rol | Responsabilidad | Asignación |
|---|---|---|
| Autor / desarrollo | Implementa, ejecuta pruebas y corrige hallazgos | Javier Alexander Fajardo López |
| QA / moderador | Valida métricas, revisa evidencias y bloquea releases | Docente o par designado |
| Seguridad | Revisa permisos, tenant, secretos y logs | Par revisor |
| Infraestructura / DBA | Gateway, índices, respaldo y monitoreo | Rol simulado en el curso |
| Producto / negocio | Prioriza riesgos y acepta residuales | Docente |

En un trabajo individual, los roles se documentan y se validan con pares; el autor no aprueba solo
sus propios riesgos residuales.

## 7. Calendario de seguimiento

| Fecha | Hito | Métricas | Salida |
|---|---|---|---|
| 2026-10-26 | Línea base | M1, M3, M4, M6 | Informe inicial y riesgos abiertos |
| 2026-11-02 | Contrato y permisos | M4, M5, M1 | Hallazgos y plan de corrección |
| 2026-11-09 | Trazabilidad | M6, M2 | Auditoría de eventos y defectos |
| 2026-11-16 | Rendimiento | M7 | Benchmark y ajuste de índices |
| 2026-11-23 | Movilidad y accesibilidad | M1, M2 | Revisión de flujo y hallazgos |
| 2026-11-30 | Regresión | M1, M3, M4 | Resultado de la batería completa |
| 2026-12-07 | Revisión SQA | Todas | Actualización de umbrales y riesgos |
| 2026-12-14 | Cierre | Todas | Informe final y riesgos residuales |

## 8. Criterios de liberación

Un release se bloquea si:

1. M1 < 100 % o hay una prueba fallando.
2. M3 < 100 % en transiciones tocadas.
3. M4 < 100 % en operaciones modificadas.
4. M5 > 0 incidentes confirmados.
5. M6 < 100 % en los eventos del período.
6. M7 supera el umbral sin plan de acción aprobado.
7. Existe un riesgo bloqueante sin mitigación.

## 9. Gestión de defectos y escalamiento

| Severidad | Tiempo de respuesta | Escalamiento |
|---|---|---|
| Crítica / bloqueante | Inmediato; release detenido | Autor → QA → docente |
| Mayor | ≤2 días hábiles | Autor → QA |
| Menor | ≤5 días hábiles | Backlog |
| Observación | Próxima iteración | Backlog |

Cada defecto se registra con el formato de la semana 13 y se vincula a la métrica afectada.

## 10. Herramientas y evidencia

- `php tests/run.php`, `mvc.php`, `api.php`, `client.php`.
- `openapi.yaml` y prueba de contrato.
- `database/schema.sql` y consultas de auditoría.
- Prototipo y capturas de la semana 11 para usabilidad y movilidad.
- Panel de métricas (propuesto): tasa de aprobación, latencia p95, 403/404/503, eventos incompletos.
- Registro de riesgos y minutas de revisión.

## 11. Límites del plan

- No hay entorno de producción ni telemetría real; las métricas se calculan en el entorno académico.
- La prueba de carga es local y no representa la escala hospitalaria.
- La auditoría de accesibilidad es heurística.
- Los roles de seguridad, infraestructura y negocio son simulados.
- El plan no cubre muestras, resultados ni facturación.

## 12. Declaración de IA

[`DECLARACION_IA.md`](./DECLARACION_IA.md), con validación humana pendiente.
