# Semana 2 — Proceso y modelo de diseño; principios SOLID

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 2 — Requisitos de datos: integridad, unicidad, obligatoriedad, trazabilidad y retención
- **Entregable / evidencia:** Lista de RF/RNF de datos con criterio de aceptación por cada uno ([rf-rnf-datos.md](./rf-rnf-datos.md)) y su traza con el esquema ([matriz-verificacion.md](./matriz-verificacion.md)).
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Los requisitos de datos se derivan de lo que el esquema **ya garantiza** y de lo que
los módulos necesitan. Cada requisito declara:

- **RF/RNF**: identificador y enunciado.
- **Criterio de aceptación**: cómo se comprueba, con el nombre real de la restricción
  o del trigger que lo hace cumplir.
- **Estado**: si la restricción existe en `origin/develop`, si está pendiente o si es
  propuesta.

Las restricciones citadas se leyeron de las migraciones y se contrastaron ejecutando
las pruebas de integridad del repositorio.

## 3. Trazabilidad con los diagramas

| Requisito | Evidencia de la semana 1 | Prueba que lo cubre |
|---|---|---|
| RF-D-01, RF-D-02 | `01-modelo-clases.svg` (relaciones `Ward → Bed → BedStay`) | `SchedulingAndBedIntegrityTest` |
| RF-D-03 | `02-clase-tabla.md` (restricciones por tabla) | `CareIntegrityTest`, `MedicalRecordIntegrityTest` |
| RF-D-04, RF-D-05 | `02-clase-tabla.md` (`ClinicalNote`, `Diagnosis`) | `MedicalRecordIntegrityTest` |
| RF-D-06, RF-D-07 | `02-clase-tabla.md` (columnas y `CHECK`) | `ProfessionalIdentityIntegrityTest`, `CareIntegrityTest` |
| RNF-D-01 | `README.md` de la semana 1 (doble base) | `ConnectionIsolationTest` |
| RNF-D-03, RNF-D-04 | Triggers citados | Pruebas de rollback de los slices |

## 4. Validación ejecutada

```bash
# comando: grep -rn "CONSTRAINT\|CREATE TRIGGER" database/migrations/hospital/*.php
# resultado: 16 tablas HOSPITAL con CHECK de dominio, unicidades parciales, exclusiones GiST y 8 triggers

# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database
# resultado: OK (53 tests, 679 assertions) — cada requisito marcado "Implementado" tiene prueba que lo respalda
```

## 5. Riesgos y pendientes

- RF-D-09 y RF-D-10 dependen del slice H8 (`local_access_audits`, `outbox_events`,
  `inbox_messages`), que todavía no tiene migración: quedan `PENDIENTE`.
- RNF-D-02 se mide en la semana 11; hasta entonces no hay línea base de rendimiento.
- Ningún requisito de este documento se marca implementado sin la restricción o el
  trigger que lo respalda en el esquema.
