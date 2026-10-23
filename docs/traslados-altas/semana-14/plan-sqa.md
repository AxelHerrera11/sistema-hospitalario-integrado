# Plan de aseguramiento de calidad (SQA) — ASII-08

Medición del 21/10/2026 sobre develop `b252e1e`. Las métricas M1–M4 salen de [`evidencia/metricas.php`](./evidencia/metricas.php) ([salida](./evidencia/salida-metricas.txt)); M5 y M6, de suites existentes; M7 es un objetivo cuyo valor actual se midió.

## Métricas

| ID | Métrica | Fuente | Umbral | Medición real | Acción si no se cumple |
|---|---|---|---|---|---|
| M1 | Verificaciones automáticas sin base en verde | `tests/run.php` | 100 % | **95/95** | No integrar; corregir en el mismo PR |
| M2 | Tamaño de cada PR (adiciones + eliminaciones) | Merges de ASII-08 en `develop` | ≤ 400 | **25 PR; máximo 393; ninguno supera 400** | Partir el PR por commits coherentes |
| M3 | RF-TA-01..10 citados por al menos una prueba | `tests/*` | 10/10 | **10/10** | Agregar la prueba antes de cerrar el RF |
| M4 | Dependencias prohibidas entre capas | Reglas de `tests/arquitectura.php` | 0 | **0** | Mover el código a la capa correcta |
| M5 | Sondas de usabilidad y accesibilidad cumplidas | `semana-09/evidencia/sondas-ux.mjs` | ≥ 9/10 | **9/10** (Semana 10) | Agregar el hallazgo al backlog con prioridad |
| M6 | Verificaciones contra PostgreSQL en verde | `tests/postgres.php` | 100 % | **20/20** | No integrar cambios de persistencia |
| M7 | Pruebas de concurrencia con conexiones independientes | Por crear (Semana 15) | ≥ 2 | **0** (objetivo, sin cumplir) | Crearlas antes de la integración de la Semana 16 |

## Riesgos

| Riesgo | Probabilidad | Impacto | Control |
|---|---|---|---|
| Dos traslados simultáneos de la misma estancia tienen éxito | Media | Alto | P3 y D4 |
| Un tiempo agotado se informa como éxito o como «sin cambios» | Baja | Alto | D3 (probado: `movil.mjs` E2) |
| Integración con RBAC y tenant sin permisos definidos | Alta | Alto | P4 |
| Un PR demasiado grande impide revisarlo | Media | Medio | P1 |
| Un nombre en `docs/` rompe `PublishedContractsTest` | Media | Medio | D2 |

## Controles

| ID | Tipo | Control | Responsable | Calendario |
|---|---|---|---|---|
| P1 | Preventivo | El ciclo de integración rechaza un PR de más de 400 líneas o con menos de 3 commits | Autora | En cada PR |
| P2 | Preventivo | Validación de la solicitud antes de conectar con HOSPITAL | Autora | Vigente desde la Semana 4 |
| P3 | Preventivo | `FOR UPDATE` sobre el episodio, la estancia y la cama; índice de estancia abierta única | Autora | Vigente; verificación en la Semana 15 |
| P4 | Preventivo | Acordar los permisos de traslado, alta y consulta con RBAC antes de integrar | Autora con ASII-02 (`Luis890D`) | Antes de la Semana 16 |
| D1 | Detectivo | `run.php` y `node --test` dentro del ciclo de integración | Autora | En cada PR |
| D2 | Detectivo | Búsqueda de nombres con forma de permiso y de enlaces rotos en `docs/` | Autora | En cada PR de documentación |
| D3 | Detectivo | Recorridos de la interfaz y escenarios móviles en Chrome | Autora | Semanas 10–12 y antes de la 17 |
| D4 | Detectivo | Pruebas de concurrencia con dos procesos PHP contra PostgreSQL | Autora | Semana 15 |

## Objetivos frente a mediciones

- **Medido:** M1–M6, con valores obtenidos por ejecución el 21/10/2026.
- **Objetivo sin cumplir:** M7, con valor actual 0.
- **No medido:** la latencia de la API y la satisfacción de los usuarios; no se declaran valores.
