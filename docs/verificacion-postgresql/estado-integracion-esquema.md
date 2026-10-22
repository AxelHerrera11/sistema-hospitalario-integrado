# Estado de integración del esquema federado en `develop`

Verificación del módulo **Verificación de Base de Datos PostgreSQL** (ASII-09) sobre el esquema
real, con las migraciones de `origin/develop` (`b87bca1`) aplicadas en PostgreSQL 16. Este
documento responde una pregunta que se repite en las revisiones: *¿qué tablas existen hoy y de
quién son las que faltan?*

## Cómo se verificó

```bash
# comando: php artisan migrate --database=central --path=database/migrations/central --realpath --force
# resultado: 000100 a 000400 DONE (15 tablas de dominio CENTRAL)

# comando: psql -d his_central_test -c "select table_name from information_schema.tables
#            where table_schema='public' and table_type='BASE TABLE' order by 1"
# resultado: 22 tablas = 15 de dominio CENTRAL + 7 de framework (cache, jobs, sessions, etc.)

# comando: psql -d his_hospital_test -c "select table_name from information_schema.tables
#            where table_schema='public' and table_type='BASE TABLE' order by 1"
# resultado: 17 tablas = 16 de dominio HOSPITAL + migrations

# comando: git ls-tree -r origin/develop --name-only database/migrations/hospital
# resultado: 000000 a 000401; ningún archivo de los slices H5, H6, H7 ni H8
```

## CENTRAL: 15 de 15, completo

| Slice | Tablas | Estado |
|---|---|---|
| C1 | `networks`, `hospitals`, `central_users`, `hospital_memberships` | `INTEGRADO` |
| C2 | `mpi_patients`, `mpi_identifiers`, `patient_hospital_links`, `identity_match_candidates` | `INTEGRADO` |
| C3 | `patient_consents`, `cross_hospital_access_grants`, `cross_access_audits`, `break_glass_reviews` | `INTEGRADO` |
| C4 | `sync_nodes`, `sync_checkpoints`, `central_inbox_messages` | `INTEGRADO` |

## HOSPITAL: 16 de 27, faltan 11

| Slice | Tablas de la especificación | Estado | Módulo dueño |
|---|---|---|---|
| H1 | `local_user_refs`, `doctor_profiles`, `specialties`, `doctor_specialties` | `INTEGRADO` | ASII-04 (médicos) |
| H2 | `local_patients`, `care_episodes`, `care_assignments` | `INTEGRADO` | ASII-07 (admisión) |
| H3 | `appointments`, `wards`, `beds`, `bed_stays` | `INTEGRADO` | ASII-05 a ASII-08 |
| H4 | `medical_records`, `clinical_notes`, `diagnoses`, `allergies`, `vital_signs` | `INTEGRADO` | ASII-10 a ASII-13 |
| H5 | `medications`, `prescriptions` | `SIN MIGRACIÓN` | ASII-14 (medicamentos), ASII-15 (prescripciones) |
| H6 | `lab_tests`, `lab_orders`, `lab_order_items`, `specimens` | `SIN MIGRACIÓN` | ASII-16 a ASII-20 (laboratorio) |
| H7 | `lab_result_versions`, `critical_alerts` | `SIN MIGRACIÓN` | ASII-19, ASII-20 (resultados), ASII-21 (alertas) |
| H8 | `local_access_audits`, `outbox_events`, `inbox_messages` | `SIN MIGRACIÓN` | ASII-22 (gobernanza), ASII-25 (auditoría) |

En total faltan **11 tablas** de las 27 que exige la especificación
(`openspec/changes/postgresql-federated-schema-rebuild/design.md`, 27 entidades HOSPITAL). Las
cuatro migraciones HOSPITAL que sí existen son `2026_08_11_000100`, `000200`, `000300` y `000401`.

## Caso concreto: el catálogo de pruebas de laboratorio (ASII-17)

- Lo que está integrado del módulo de Axel (ASII-17) es **documental**: el PR #43 (`docs(semana-1)`)
  y los PR #223 a #241, que son entregables de las semanas 10 a 14.
- Lo que **no** está integrado es el esquema: no existe `lab_tests` ni ninguna tabla del slice H6
  en `develop`, y no hay migración HOSPITAL para ese slice.
- El PR #337 de este módulo (contribución de emergencia con `lab_tests` y `critical_alerts`) sigue
  **cerrado** a solicitud del dueño del módulo de laboratorio. Este módulo no lo reabre: verifica
  y avisa, no crea tablas de otros módulos.

## Consecuencias

1. Cualquier módulo que dependa del slice H6, H7 o H8 tiene que trabajar contra un adaptador
   InMemory hasta que su dueño publique la migración, tal como ordena el plan transversal.
2. La tabla `doctor_schedules` (entidad 28 propuesta) no cambia esta cuenta: es una entidad
   adicional y su aprobación sigue pendiente (ver `doctor-schedules/acuerdos.md`).
3. La semana 9 (calidad de datos de prueba) y la semana 14 (respaldo, restauración y rollback)
   se validaron sobre el esquema real que existe hoy: 15 tablas CENTRAL y 16 HOSPITAL.

## Regla que aplica este módulo

Ninguna tabla de otro módulo se crea desde aquí. Cuando falta un slice, se comunica con evidencia
y con el dueño responsable, y se registra como `PENDIENTE` hasta que exista la migración
correspondiente.
