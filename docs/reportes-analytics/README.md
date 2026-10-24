# Reportes y analytics básicos (ASII-23) — Manual del módulo

**Issue:** #152 · **Estudiante:** `Yamilet1235` (María Yamilet Lindo Pablo).

El modulo entregado calcula el indicador **IND-RA-01** (tasa de cancelacion
de hospitalizaciones, fórmula **TCI-1.0**) con datos reales de la base
HOSPITAL, expuesto por el API del cuerpo del sistema y navegable en la
pantalla Vue "Reportes".

## Qué se entrega por semana

| Semana | Entregable | PR | Evidencia de ejecución |
|---|---|---|---|
| 2 | Ejemplo LSP ejecutable (antes/después jerarquía) + README | #1233 | `php micro-his/reportes-analytics/tests/example-lsp.php` -> 7 comprobaciones |
| 3 | Micro-HIS vanilla 4 capas + `run.php` 11 escenarios | #1237, #1239 | `php micro-his/reportes-analytics/tests/run.php` -> 11 OK |
| 4 | Evidencia MVC/Repository mapeada al módulo | #1241 | `run.php` + `git log` por capa |
| 5/16 | Endpoint Laravel `POST /api/v1/reports/indicators/IND-RA-01/calculate` con `rbac:reportes.ver` | #1245, #1269 | suite completa 361 tests OK; `tests/Feature/Reportes` 14 OK |
| 8 | Pantalla Vue "Reportes" (filtros periodo + sala, estados del contrato) | #1248 | `node resources/js/modules/reportes/reportes.test.mjs` -> 15 aserciones; navegación/permisos OK |
| 16/17 | Integración real HOSPITAL PostgreSQL (55433) + sin filtración de datos | #1269, #1271 (promoción) | `IndicatorHospitalIntegrationTest` OK (rollback limpio) |

Cada PR a develop ≤ 400 líneas (objetivo 350) se promovió a `main` con
promociones ≤ 400 (#1234, #1238, #1240, #1242, #1246, #1254, #1271).

## Cómo ejecutar el demo (5 pasos)

1. **Preparar la base HOSPITAL**: con PostgreSQL 15+ corriendo, crear
   `his_hospital_test` en el puerto 55433 (`HOSPITAL_DB_*` en `phpunit.xml`)
   y cargar el esquema (`php artisan migrate --database=hospital`); los
   episodios del demo se siembran con el INSERT de la sección de abajo.
2. **Levantar las pantallas**: en dos terminales del proyecto,
   `php artisan serve` (API en 8000) y `npm run dev` (Vite).
2. **Credenciales**: iniciar sesión con un usuario del hospital cuyo rol
   tenga el permiso `reportes.ver` (rol sembrado; ver `docs/rbac`).
3. **Navegación**: menú lateral -> "Reportes" (o ruta `/reportes`). El
   guard de la ruta y el backend exigen el mismo permiso.
4. **Calcular**: elegir periodo (`Desde` menor que `Hasta`) y, opcionalmente,
   un código de sala (`service_code`); pulsar "Calcular".
5. **Resultados esperados**:
   - Con datos: porcentaje con dos decimales (p. ej. `25.00 %`) + leyenda
     "Cancelados sobre hospitalizaciones finalizadas (TCI-1.0)" y
     trazabilidad `IND-RA-01 (TCI-1.0) · calculado <instante>`.
   - Sin episodios en el periodo: `N/A` con la leyenda
     `SIN_DATOS_EN_PERIODO` — nunca un 0 % inventado (regla dominio 0 = sin
     datos; cero real se muestra solo cuando hay episodios).
   - Periodo invertido: mensaje "Revisa el periodo indicado" (422 del API);
   - Base caída: "La base de episodios no está disponible" (503), sin cifra
     previa.

## Baseline técnico (Semana 1)

`care_episodes` es la fuente única: episodios `type='inpatient'` y
`status ∈ {closed, cancelled}`; periodo medio abierto `[desde, hasta)` sobre
`ended_at` con offset explícito (`-06:00`); filtro `service_code` opcional;
denominador cero -> estado publicado; `cancelled > finalized` -> 503
(lectura incoherente); como limitation documentada, la conexión HOSPITAL es
una sola por despliegue.
