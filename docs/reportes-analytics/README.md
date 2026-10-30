# Reportes y analytics básicos (ASII-23) — Manual del módulo

**Issue:** #152 · **Estudiante:** `Yamilet1235` (María Yamilet Lindo Pablo).

El modulo entregado calcula el indicador **IND-RA-01** (tasa de cancelacion
de hospitalizaciones, fórmula **TCI-1.0**) con datos reales de la base
HOSPITAL, expuesto por el API del cuerpo del sistema y navegable en la
pantalla Vue "Reportes".

## Qué se entrega por semana (S1–S18)

| Semana | Requisito | Archivo/evidencia | PR | Estado |
|---|---|---|---|---|
| 1 | Definición del indicador y VCs | `semana-01/` (narrativa + 3 SVG/puml) | (base previa) | COMPLETO |
| 2 | SOLID/LSP | `semana-02/README.md` + `tests/example-lsp.php` | #1233 | COMPLETO |
| 3 | Arquitectura 4 capas | `micro-his/reportes-analytics/**` + `tests/run.php` (11 OK) | #1237, #1239 | COMPLETO |
| 4 | MVC y Repository | `semana-04/README.md` (mapeo por archivo) | #1241 | COMPLETO |
| 5 | API del micro-módulo | `semana-05/README.md` + endpoint | #1245 | COMPLETO |
| 6 | Primer parcial (guion) | `semana-06/README.md` | #1283 | COMPLETO |
| 7 | Componentes y protocolos | `semana-07/README.md` | #1283 | COMPLETO |
| 8 | UX de la pantalla | `semana-08/README.md` + `ReportsPage.vue` | #1248, #1283 | COMPLETO |
| 9 | Accesibilidad | `semana-09/README.md` + `reportes.a11y.test.mjs` (11 OK) | #1283 | COMPLETO |
| 10 | Movilidad | `semana-10/README.md` (viewport global + inputs nativos) | #1283 | COMPLETO |
| 11 | Prototipo navegable | `semana-11/README.md` (checklist integrado) | #1283 | COMPLETO |
| 12 | Segundo parcial (guion) | `semana-12/README.md` | #1283 | COMPLETO |
| 13 | Revisión formal preparada | `semana-13/checklist-revision-tecnica.md` | #1285 | COMPLETO |
| 14 | SQA | `semana-14/README.md` (política + rastreo de defectos) | #1285 | COMPLETO |
| 15 | Matriz de pruebas | `semana-15/matriz-pruebas.md` (R1–R20) | #1285 | COMPLETO |
| 16 | Integración con datos reales | `IndicatorHospitalIntegrationTest` + `semana-16/README.md` | #1269, #1283 | COMPLETO |
| 17 | Seguridad y despliegue | `semana-17/README.md` (controles probados + instalación) | #1269, PR S17+S18 | COMPLETO |
| 18 | Demo final | `semana-18/README.md` (8 pasos con resultado esperado) | #1288* | COMPLETO* |

Toda promoción develop->main fue <= 400 líneas: #1234, #1238, #1240, #1242, #1246, #1254, #1271, #1279 y las de esta serie de evidencias.

## Prueba manual completa (receta exacta)

**URL de la aplicación:** `http://127.0.0.1:8000/login` (con `npm run dev`
corriendo en otra terminal; el SPA se sirve por Blade de Laravel).

**1. Bases (una vez):** PostgreSQL 15+ local; en `.env` apuntar
`CENTRAL_DB_*` y `HOSPITAL_DB_*` a bases propias (p. ej. `his_central` /
`his_hospital`; puertos `5432`), luego:

```
composer migrate:central && composer migrate:framework && composer migrate:hospital
psql -d his_hospital -c "CREATE EXTENSION IF NOT EXISTS btree_gist;"
```

**2. Hospital activo en CENTRAL (tinker):**

```php
$net = DB::connection('central')->table('networks')->insertGetId(['name'=>'red-demo','status'=>'active','timezone'=>'America/Guatemala']);
DB::connection('central')->table('hospitals')->insert(['code'=>'HOSP-DEMO','name'=>'Hospital Demo','status'=>'active','timezone'=>'America/Guatemala','network_id'=>$net]);
php artisan tinker // copiar el hospital_id generado:
// DB::connection('central')->table('hospitals')->first()->hospital_id  => UUID_TENANT
```

**3. Usuario ficticio + contraseña local:**

```
php artisan auth:provision-first-superadmin
# referencias: operador "demo-local"; UUID_TENANT; correo de su preferencia
# p.ej. yamilet.demo@hospital.local; la contraseña la teclea ud. (>= 12
# caracteres con mayuscula, minuscula, numero y simbolo) y queda hasheada
# en auth_credentials de CENTRAL (Argon2id/bcrypt del proyecto).
```

**4. Sembrar episodios en HOSPITAL (psql):**

```sql
INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
VALUES ('00000000-0000-4000-8000-000000000001', 'DEMO-PACIENTE', 'Paciente', 'Demo', '1990-01-01', 'other');
INSERT INTO care_episodes (local_patient_id, type, status, started_at, ended_at, reason, service_code) VALUES
 ((SELECT local_patient_id FROM local_patients WHERE code='DEMO-PACIENTE'), 'inpatient','closed','2026-01-02 20:00:00-06','2026-01-04 20:00:00-06','demo reportes','HOSP-UCI'),
 ((SELECT local_patient_id FROM local_patients WHERE code='DEMO-PACIENTE'), 'inpatient','closed','2026-01-05 20:00:00-06','2026-01-07 20:00:00-06','demo reportes','HOSP-UCI'),
 ((SELECT local_patient_id FROM local_patients WHERE code='DEMO-PACIENTE'), 'inpatient','cancelled','2026-01-06 20:00:00-06','2026-01-06 21:00:00-06','demo reportes','HOSP-UCI'),
 ((SELECT local_patient_id FROM local_patients WHERE code='DEMO-PACIENTE'), 'inpatient','closed','2026-01-09 20:00:00-06','2026-01-11 20:00:00-06','demo reportes','HOSP-PED');
```

**5. Levantar:** `php artisan serve` + `npm run dev` (o `npm run build` para
usar solo `php artisan serve`).

**6. Filtros a introducir y resultado esperado:**

| Filtros | Resultado esperado |
|---|---|
| Desde `2026-01-01`, Hasta `2026-02-01`, Sala vacía | `25.00 %` (1 cancelado / 4 finalizadas) + trazabilidad |
| Desde `2026-01-01`, Hasta `2026-02-01`, Sala `HOSP-UCI` | `33.33 %` (1 / 3; queda fuera HOSP-PED) |
| Hasta `2026-01-01` (invertido) | "Revisa el periodo indicado." (422) |
| Desde `2019-01-01`, Hasta `2019-02-01` | `N/A` + leyenda `SIN_DATOS_EN_PERIODO` |

El rol `superadmin` (creado en el paso 3) ya incluye `reportes.ver` en
`config/rbac.php`; no se requieren permisos adicionales.

## Baseline técnico (Semana 1)

`care_episodes` es la fuente única: episodios `type='inpatient'` y
`status ∈ {closed, cancelled}`; periodo medio abierto `[desde, hasta)` sobre
`ended_at` con offset explícito (`-06:00`); filtro `service_code` opcional;
denominador cero -> estado publicado; `cancelled > finalized` -> 503
(lectura incoherente); como limitation documentada, la conexión HOSPITAL es
una sola por despliegue.
