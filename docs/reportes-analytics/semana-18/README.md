# ASII-23 Semana 18 — Demo final: plan de cierre

**Issue:** `Refs #152`. Plan del demo final del módulo, pasos exactos y
resultado esperado de cada uno.

## Preparación (una vez)

1. Migraciones y cuenta: pasos 1-5 de `semana-17/README.md`.
2. Sembrar datos de demo en la base HOSPITAL local (expediente sintético
   evidente + 4 episodios inpatient: 3 `closed` y 1 `cancelled` con
   `service_code = 'HOSP-UCI'`; el bloque SQL completo está en el manual
   del módulo, `docs/reportes-analytics/README.md`).

## Pasos del demo y resultado esperado

| # | Acción | Resultado esperado |
|---|---|---|
| 1 | Abrir `http://127.0.0.1:8000/login` | Formulario con campo "ID de tenant (X-Tenant-ID)" (el UUID del hospital). |
| 2 | Login de la cuenta ficticia | Redirección al SPA con menú lateral; "Reportes" visible por el permiso. |
| 3 | Abrir `/reportes` | Título; "Desde/Hasta" y sala opcional; botón "Calcular". |
| 4 | Desde `2026-01-01`, Hasta `2026-02-01`, sala vacía | `25.00 %` + leyenda "Cancelados sobre hospitalizaciones finalizadas (TCI-1.0)" + trazabilidad `IND-RA-01 (TCI-1.0) · calculado <instante>`. |
| 5 | Misma ventana con sala `HOSP-UCI`  | `33.33 %` (el episodio `HOSP-PED` del sembrado queda fuera). |
| 6 | Hasta `2026-01-01` (periodo invertido) | Mensaje "Revisa el periodo indicado." (422 en el API). |
| 7 | Desde/Hasta sin episodios (p. ej. 2019) | `N/A` con leyenda del estado `SIN_DATOS_EN_PERIODO` (nunca un 0 % inventado). |
| 8 | `DELETE` manual al endpoint (curl) | 405 (método no permitido) — opcional, vía terminal. |

## Cierre del módulo

- Todo integrado en `develop` y promovido a `main` (PRs ≤ 400 líneas).
- Sin revisiones del usuario registrado hasta su confirmación manual.
- Cualquier hallazgo del usuario en el demo se registra como issue de la
  tarea y se corrige con el mismo ciclo: revisión → pruebas → merge a
  develop → promoción a main.
