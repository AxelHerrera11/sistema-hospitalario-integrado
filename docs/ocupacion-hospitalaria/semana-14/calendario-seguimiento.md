# Calendario de seguimiento y acciones del plan SQA

Complementa al [catálogo de métricas](./metricas.md) y a la [matriz de riesgos](./matriz-riesgos.md).
Define quién mide cada métrica, con qué fuente, qué umbral aplica y qué acción se ejecuta cuando
el umbral no se cumple. La línea de base se fijó el 21 de octubre de 2026 sobre `develop` en
`75bd69ce`.

## Métricas principales y acción ante incumplimiento

| ID | Métrica | Umbral | Fuente | Responsable | Acción si no se cumple |
|---|---|---|---|---|---|
| M-01 | Camas clasificadas / camas leídas | 100 % | Pruebas de dominio del Micro-HIS | Autor del módulo | Bloquear el PR; se corrige la regla antes de revisar |
| M-02 | Global igual a suma de salas | 100 % | Prueba de agregación | Autor del módulo | Bloquear el PR; revisar la consolidación por sala |
| M-04 | Fallas simuladas que devuelven `503` sin métricas de ocupación | 100 % | Suite HTTP del endpoint | Autor del módulo | Bloquear el PR; un `500` o un cero falso es defecto bloqueante |
| M-05 | Payloads y logs revisados sin PII | 100 % | Esquema JSON y log de una solicitud | Revisor de seguridad | Retirar la evidencia y tratar como incidente |
| M-06 | Casos negativos de JWT, permiso y tenant | 100 % | Suite HTTP del endpoint | QA | No se libera el endpoint hasta cerrar el caso |
| M-13 | Pruebas obligatorias aprobadas / ejecutadas | 100 % | Salida de CI y entorno local declarado | CI | No se propone el PR para integración |
| M-14 | Hallazgos bloqueantes o mayores abiertos | 0 | Acta de revisión | Moderador de la revisión | El PR espera; se planifica nueva revisión |

Las métricas M-03, M-07, M-08, M-09, M-10, M-11, M-12, M-15 y M-16 son complementarias: se
reportan con el mismo formato, pero no bloquean la entrega documental de una semana; sí bloquean
la entrega funcional cuando su ausencia encubre un defecto (por ejemplo M-12 sin navegador se
reporta como «sin datos», nunca como «cumple»).

## Cadencia

| Momento | Qué se revisa | Quién | Evidencia |
|---|---|---|---|
| Antes de cada PR | Puerta de calidad: hallazgos abiertos, pruebas obligatorias, `git diff --check`, sintaxis, documentación | Autor | Cuerpo del PR |
| Al abrir cada PR | Recorrido de la checklist de la semana 13 | Moderador | Acta de revisión |
| Semanal | Métricas M-15 y M-16, riesgos de la matriz y hallazgos vencidos | Secretario de la revisión | Registro semanal |
| Por entrega | M-01, M-02, M-04 y M-13 con salida real | Autor + QA | Salida de comandos pegada en el PR |
| Antes de la entrega funcional | M-05, M-06 y M-12 | QA y seguridad | Informe de recorrido |
| Tras integrar en el entorno del equipo | M-08, M-09 y M-07 cuando haya volumen | Operación | Métricas del entorno |
| En la evaluación final | Consolidado de métricas, riesgos y hallazgos | Autor + moderador | Documento de evaluación final |

## Línea de base medida (21 de octubre de 2026)

| Evidencia | Estado medido | Comando |
|---|---|---|
| Suite Micro-HIS | `OK: 12 verificaciones aprobadas` | `php micro-his/ocupacion-hospitalaria/tests/run.php` |
| Contrato HTTP e integridad de ocupación | `Tests: 22 passed (125 assertions)` | `php artisan test tests/Feature/Ocupacion tests/Database/Hospital/OccupancySnapshotIntegrityTest.php` |
| Utilidades de la pantalla Vue | `OK: 14 aserciones de la pantalla de ocupación aprobadas` | `node --test resources/js/modules/ocupacion/ocupacion.test.mjs` |
| Semántica de ocupación en PostgreSQL 16 | 6 pruebas de integridad en verde | `php artisan test tests/Database/Hospital/OccupancySnapshotIntegrityTest.php` |
| Accesibilidad | Inspección documental: `role="status"` y `role="alert"` presentes; contraste y reflow **sin medir** | `grep -nE "aria-\|role=" resources/js/modules/ocupacion/pages/OcupancyPage.vue` |
| Volumen 10 000 camas (M-07) | **Sin datos**: no hay entorno con ese volumen | — |
| Tráfico real (M-08, M-09) | **Sin datos**: no hay despliegue productivo del módulo | — |

## Reglas de honestidad del seguimiento

- Una métrica sin medición se reporta como «sin datos», nunca como «cumple».
- No se ajustan umbrales para aparentar cumplimiento: un incumplimiento documenta tendencia,
  causa y acción.
- Los hallazgos que dependen de un tercero (ASII-06/07/08, ASII-02, el equipo de operación) se
  registran con su responsable y su fecha, aunque la evidencia todavía no exista.
- La evidencia se conserva asociada al SHA y al entorno, sin tokens, DSN con contraseña ni datos
  de pacientes.
