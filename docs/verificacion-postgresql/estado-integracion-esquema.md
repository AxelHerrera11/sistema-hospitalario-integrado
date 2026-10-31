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

## HOSPITAL: migraciones integradas en `develop`

| Slice | Tablas de la especificación | Estado | Módulo dueño |
|---|---|---|---|
| H1 | `local_user_refs`, `doctor_profiles`, `specialties`, `doctor_specialties` | `INTEGRADO` | ASII-04 (médicos) |
| H2 | `local_patients`, `care_episodes`, `care_assignments` | `INTEGRADO` | ASII-07 (admisión) |
| H3 | `appointments`, `wards`, `beds`, `bed_stays` | `INTEGRADO` | ASII-05 a ASII-08 |
| H4 | `medical_records`, `clinical_notes`, `diagnoses`, `allergies`, `vital_signs` | `INTEGRADO` | ASII-10 a ASII-13 |
| H5 | `medications`, `prescriptions` | `INTEGRADO` | ASII-14 (medicamentos), ASII-15 (prescripciones) |
| H6 | `lab_tests`, `lab_orders`, `lab_order_items`, `specimens` | `INTEGRADO` | ASII-16 a ASII-20 (laboratorio) |
| H7 | `critical_alerts` y resultados publicados por ASII-19/20 | `INTEGRADO/PARCIAL` | ASII-19, ASII-20 (resultados), ASII-21 (alertas) |
| H8 | `local_access_audits`, `outbox_events`, `inbox_messages` | `INTEGRADO` | ASII-22 (gobernanza), ASII-25 (auditoría) |

La afirmación anterior de que faltaban 11 tablas estaba desactualizada. `develop` contiene,
además de los slices base, las migraciones `000500`, `000600`, `000601`, `000660`, `000700`,
`000900`, `001000`, `2026_10_28_001000` y `2026_10_31_000001`. La cantidad efectiva debe
confirmarse ejecutando las migraciones contra PostgreSQL; la lista de archivos por sí sola no
es evidencia de que una base concreta esté actualizada.

## Caso concreto: el catálogo de pruebas de laboratorio (ASII-17)

- El catálogo ASII-17 tiene la migración `2026_08_11_000600_create_lab_tests_table.php`.
- Las órdenes ASII-16 tienen migración propia `2026_10_31_000001_create_lab_orders_tables.php`.
- La recepción ASII-18 tiene `2026_08_11_000601_create_specimen_reception_tables.php`.
- La presencia de estas migraciones no sustituye la corrida real en PostgreSQL; esa corrida es
  la evidencia que se solicita en las semanas 17 y 18.

## Consecuencias

1. Cualquier módulo que dependa de un slice aún no migrado en el entorno debe declarar el
   bloqueo; no se debe asumir que la ausencia de una corrida local significa que el archivo no
   existe en `develop`.
2. La tabla `doctor_schedules` (entidad 28 propuesta) no cambia esta cuenta: es una entidad
   adicional y su aprobación sigue pendiente (ver `doctor-schedules/acuerdos.md`).
3. La semana 9 (calidad de datos de prueba) y la semana 14 (respaldo, restauración y rollback)
   se validaron sobre el esquema real que existe hoy: 15 tablas CENTRAL y 16 HOSPITAL.

## Regla que aplica este módulo

Ninguna tabla de otro módulo se crea desde aquí. Cuando falta un slice, se comunica con evidencia
y con el dueño responsable, y se registra como `PENDIENTE` hasta que exista la migración
correspondiente.
