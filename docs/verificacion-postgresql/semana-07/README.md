# Semana 7 — Diseño de componentes y refactorización

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 7 — Auditoría de migraciones
- **Entregable / evidencia:** Informe de auditoría por migración con hallazgos y migración correctiva propuesta.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Auditar las migraciones que están integradas en `origin/develop`: claves primarias,
foráneas, `NOT NULL`, `UNIQUE`, `CHECK` y acciones `ON DELETE`. La auditoría se hizo
**contra las bases reales**, no leyendo solo el código: se aplicaron las migraciones
sobre dos PostgreSQL 16 de prueba y se consultó el catálogo del sistema.

Incluye además el registro formal de la solicitud de `doctor_schedules` y la
propuesta de migración correctiva.

## 2. Entregables

- [Informe de auditoría por migración](./informe-auditoria-migraciones.md): método,
  resumen por migración, siete hallazgos con severidad y reglas para migraciones nuevas.
- [Migración correctiva propuesta](./migracion-correctiva-propuesta.sql): límites de
  longitud para texto libre, con su reversión. **No se aplica** en este PR porque el
  número de archivo debe acordarse con el grupo.
- [Registro de coordinación de `doctor_schedules`](./registro-coordinacion-doctor-schedules.md):
  validación de la definición literal contra el esquema real, contrato publicado y
  acuerdos con responsable y estado.

## 3. Hallazgos en resumen

| Id | Severidad | Hallazgo | Estado |
|---|---|---|---|
| H-01 | Bloqueante | Falta `doctor_schedules` (posible entidad 28) | `PENDIENTE DE APROBACIÓN` |
| H-02 | Mayor | Slice H8 sin migraciones (auditoría local, outbox, inbox) | `PENDIENTE` |
| H-03 | Mayor | Laboratorio, alertas y medicamentos sin migración | `PENDIENTE` por módulo |
| H-04 | Menor | Texto libre sin longitud máxima | `PROPUESTA` |
| H-05 | Menor | `diagnoses.clinical_note_id` admite diagnóstico sin nota | `PENDIENTE DE DECISIÓN` |
| H-06 | Informativo | Columnas nullable justificadas una por una | Sin acción |
| H-07 | Informativo | `beds.status` es estado operativo, no fuente de ocupación | Sin acción |

## 4. Validación ejecutada

```bash
# comando: php artisan migrate --database=central --path=database/migrations/central --realpath --force
# resultado: 5 migraciones CENTRAL aplicadas sobre PostgreSQL 16

# comando: php artisan migrate --database=hospital --path=database/migrations/hospital --realpath --force
# resultado: 6 migraciones HOSPITAL aplicadas sobre PostgreSQL 16

# comando: SELECT confdeltype, count(*) FROM pg_constraint WHERE contype='f' GROUP BY 1
# resultado: 57 filas con 'r' (RESTRICT); ninguna FK en cascada

# comando: SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid='vital_signs'::regclass AND contype='c'
# resultado: 16 CHECK de dominio y rango en vital_signs

# comando: SELECT count(*) FROM pg_trigger WHERE NOT tgisinternal
# resultado: 15 triggers en HOSPITAL y 2 en CENTRAL

# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database
# resultado: OK (53 tests, 679 assertions)
```

## 5. Riesgos y pendientes

- H-01, H-02 y H-03 dependen de decisiones de otros módulos; se comunican el mismo día
  y cada uno tiene responsable identificado.
- La numeración de migraciones es un recurso compartido: el archivo de la migración
  correctiva y el de `doctor_schedules` deben acordarse antes de abrir su PR.
- La auditoría cubre el esquema actual; las migraciones que se creen esta semana se
  auditan cuando su PR se abra.
