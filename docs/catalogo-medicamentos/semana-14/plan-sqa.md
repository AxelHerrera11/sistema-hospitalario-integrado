# Plan de aseguramiento de calidad (SQA) - ASII-14

Atributos: calidad funcional, confiabilidad, seguridad y trazabilidad del catálogo. **Medido** = valor obtenido hoy con un comando; **Objetivo** = meta sin medición todavía.

## Métricas

| ID | Métrica | Umbral | Fuente | Valor actual | Estado | Acción si no se cumple |
|---|---|---|---|---|---|---|
| M1 | Suites del micro-HIS en verde y verificaciones aprobadas | 4/4 suites, 0 fallos | `evidencia/metricas.php` | 4/4; 101 aprobadas | **Medido** | Bloquear el PR hasta corregir |
| M2 | Errores de sintaxis en PHP | 0 | `metricas.php` (`php -l`) | 0 de 39 | **Medido** | Bloquear el PR |
| M3 | Divergencias observables entre adaptadores | 0 | `metricas.php` (casos no ASCII) | 2 de 3 | **Medido: no cumple** | Corregir AV3 en Semana 15 con prueba de contrato |
| M4 | Tamaño de cada PR (adiciones + eliminaciones) | ≤ 400 (objetivo 350) | `gh pr view --json additions,deletions` | máx. 270 en 15 PR | **Medido** | Dividir en partes coherentes |
| M5 | Comprobaciones de accesibilidad del prototipo | 24/24 | `semana-11/evidencia/verificar-prototipo.mjs` | 24/24 | **Medido** | Corregir antes de cerrar la semana |
| M6 | Operaciones del API v1 con permiso verificado por prueba HTTP | 4/4 | Pruebas de Laravel (Semana 16) | — | **Objetivo** (no hay rutas Laravel) | Ninguna ruta sin prueba de 401, 403 y 200 |
| M7 | Tiempo de respuesta p95 de la búsqueda | ≤ 300 ms | Logs con `trace_id` (Semana 17) | — | **Objetivo** (sin entorno desplegado) | Revisar el índice o la consulta |

## Valores vigentes (30/10/2026)

La tabla anterior conserva la línea base de la Semana 14. Estos son los valores actuales.

| ID | Valor | Estado |
|---|---|---|
| M1 | `run.php` 47 y `casos.php` 17, sin fallos | Medido |
| M3 | 0 divergencias en `metricas.php` | **Cumple**; AV3 corregido |
| M4 | Máximo 354 líneas en los PR del 30/10/2026 | Medido |
| M6 | 4 de 4 operaciones con permiso probado por HTTP, además del rechazo de otro hospital | **Medido**, `tests/Feature/MedicationCatalog` |
| M7 | p95 de 384 ms con 20 búsquedas, en el servidor de desarrollo de PHP sin opcache y con 1 registro | **No cumple en local**; no representa producción. Repetir con opcache y volumen real |

## Matriz de riesgos

| Riesgo | Probabilidad | Impacto | Nivel | Control |
|---|---|---|---|---|
| R1 Un éxito se informa sin confirmar la persistencia | Baja | Alto | Medio | P1, D1 |
| R2 Los adaptadores difieren en resultados (AV3) | **Ocurrido** | Medio | Alto | P2, D2 |
| R3 Escritura sin permiso al integrar en Laravel | Media | Alto | Alto | P3, D3 |
| R4 Acceso a datos de otro hospital | **Ocurrió**: lo halló la auditoría del 30/10/2026; corregido en #1561 | Alto | Medio | P3, D3 |
| R5 Un PR mayor de 400 o que mezcla módulos | Media | Medio | Medio | P4, D4 |
| R6 Documentación que presenta lo propuesto como hecho | Media | Medio | Medio | P5, D5 |

## Controles

| ID | Tipo | Control | Responsable | Frecuencia |
|---|---|---|---|---|
| P1 | Preventivo | El dominio y `rowCount` exigen confirmación; sin `catch` que trague `PersistenceFailure` | Autora | Cada cambio |
| P2 | Preventivo | Toda regla de búsqueda tiene un caso en `tests/repository.php` con datos ASCII y no ASCII | Autora | Cada cambio de Persistence |
| P3 | Preventivo | Rutas Laravel con middlewares `tenant`, `jwt.auth` y permiso; denegación por defecto | Autora con RBAC (ASII-02) | Semana 16 |
| P4 | Preventivo | Una rama por parte y medición del diff antes del PR | Autora | Cada PR |
| P5 | Preventivo | Columna "Existe / Propuesto" en cada README | Autora | Cada semana |
| D1 | Detectivo | Mutación manual: tragar el fallo debe hacer fallar las pruebas | Autora | Cada parte con código |
| D2 | Detectivo | `metricas.php` M3 = 0 | Autora | Antes de cada PR |
| D3 | Detectivo | Pruebas HTTP de 401, 403, `TENANT_MISMATCH` y 200 por operación | Autora; revisión del moderador | Semana 16–17 |
| D4 | Detectivo | `Validate ASII PR` y la aprobación requerida en `develop` | GitHub y revisor (@rortizs) | Cada PR |
| D5 | Detectivo | Revisión técnica formal (Semana 13) | Moderador y revisores (propuestos) | Una vez por entrega |

## Calendario

| Momento | Actividad |
|---|---|
| Semana 14 | Medir M1–M5 (hecho) y registrar el resultado de M3 |
| Semana 15 | Corregir AV3 y AV1; M3 = 0; matriz RF/RNF → prueba |
| Semana 16 | Rutas Laravel con permisos y tenant; medir M6 |
| Semana 17 | Matriz de amenazas, pruebas de permisos y datos sensibles; M7 si hay entorno |
