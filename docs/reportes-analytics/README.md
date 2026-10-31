# Reportes y analytics básicos (ASII-23) — Manual del módulo

**Issue:** #152 · **Estudiante:** `Yamilet1235` (María Yamilet Lindo Pablo).

El modulo entregado calcula el indicador **IND-RA-01** (tasa de cancelacion
de hospitalizaciones, fórmula **TCI-1.0**) con datos reales de la base
HOSPITAL, expuesto por el API del cuerpo del sistema y navegable en la
pantalla Vue "Reportes".

## Qué se entrega por semana (S1–S18)

**Prueba manual de la autora (30/10/2026), en 8123, con semillas ficticias:** 25.00 % sin sala, 33.33 % con `HOSP-UCI`, periodo invertido con error y sin la cifra anterior, y periodo de 2019 con N/A. No cubre revisión total, pruebas móviles ni seguridad.

**Técnico** significa que el archivo y su contenido cumplen el cuadro de tareas. Eso no incluye revisión humana, exposición ni evaluación docente.

| Semana | Requisito | Evidencia | Estado | Falta |
|---|---|---|---|---|
| 1 | Diagramas UML y narrativa | `semana-01/` | Técnico | — |
| 2 | LSP, requisitos y antes/después | `semana-02/` | Técnico | — |
| 3 | Micro-HIS y vista C4 | `micro-his/reportes-analytics/`, `semana-03/` | Técnico | — |
| 4 | MVC, Repository, repositorio compartido | `semana-04/` | Técnico | — |
| 5 | Contrato, frontera y plan | `semana-05/` | Técnico | — |
| 6 | Primera evaluación | `semana-06/presentacion.pdf` | Preparada | Defensa y cambio real |
| 7 | Componentes, contratos, refactor | `semana-07/` | Técnico | — |
| 8 | Flujo y wireframes | `semana-08/` | Técnico | Prueba con usuarios, si se pide |
| 9 | Usabilidad y accesibilidad | `semana-09/` | Técnico | Lector de pantalla |
| 10 | Movilidad | `semana-10/` | Técnico | Teléfono físico, si se pide |
| 11 | Prototipo, mapa y capturas | `semana-11/` | Técnico | — |
| 12 | Segunda evaluación | `semana-12/presentacion.pdf` | Preparada | Exposición y cambio real |
| 13 | Plan de revisión formal | `semana-13/` | Preparada | Confirmar roles |
| 14 | Plan SQA | `semana-14/` | Técnico | Medir p95 con volumen real |
| 15 | Matriz, pruebas y defectos | `semana-15/` | Técnico | D10 abierto |
| 16 | Integración | `semana-16/` | Parcial | Entorno de servidor |
| 17 | Amenazas y despliegue | `semana-17/` | Parcial | Despliegue real; amenazas A10–A12 |
| 18 | Demostración y defensa | `semana-18/` | Preparada | Demostración y defensa |

Los PR de cada semana se consultan con `git log --first-parent origin/develop -- docs/reportes-analytics/semana-NN`.

## Prueba manual completa (receta exacta)

**URL de la aplicación:** `http://127.0.0.1:8000/login` (con `npm run dev`
corriendo en otra terminal; el SPA se sirve por Blade de Laravel).

**1. Bases (una vez):** PostgreSQL 15+ local; en `.env` apuntar
`CENTRAL_DB_*` y `HOSPITAL_DB_*` a bases propias (p. ej. `his_central` /
`his_hospital`) y definir `HOSPITAL_DB_TENANT_ID` con el UUID del hospital del
paso 2: sin esa variable el indicador responde 503. Luego:

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
| Hasta `2026-01-01` (invertido) | "Desde debe ser anterior a hasta." Sin llamada a la API y sin cifra, aunque antes hubiera un resultado |
| Desde `2019-01-01`, Hasta `2019-02-01` | `N/A` + leyenda `SIN_DATOS_EN_PERIODO` |

El rol `superadmin` (creado en el paso 3) ya incluye `reportes.ver` en
`config/rbac.php`; no se requieren permisos adicionales.

## Baseline técnico (Semana 1)

`care_episodes` es la fuente única: episodios `type='inpatient'` y
`status ∈ {closed, cancelled}`; periodo medio abierto `[desde, hasta)` sobre
`ended_at` con offset explícito (`-06:00`); filtro `service_code` opcional;
denominador cero -> estado publicado; `cancelled > finalized` -> 503
(lectura incoherente); como limitación documentada, la conexión HOSPITAL es
una sola por despliegue.
