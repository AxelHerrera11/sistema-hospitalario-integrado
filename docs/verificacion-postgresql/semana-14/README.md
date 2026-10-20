# Semana 14 — Confiabilidad y aseguramiento de calidad

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 14 — Plan SQA de la base de datos
- **Entregable / evidencia:** Plan SQA de BD con evidencia de una restauración y un rollback ejecutados.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Definir el plan de aseguramiento de calidad de la base de datos: respaldo,
restauración, rollback de migraciones, aislamiento entre hospitales y métricas. El
plan se acompaña de **evidencia ejecutada**: un respaldo, una restauración completa y
un rollback con su re-aplicación.

## 2. Entregables

- [plan-sqa-bd.md](./plan-sqa-bd.md): respaldo, restauración, rollback, aislamiento,
  métricas y puerta de calidad previa a integrar un PR de base de datos.
- [evidencia-restauracion-rollback.md](./evidencia-restauracion-rollback.md): salidas
  reales del ciclo completo sobre PostgreSQL 16.

## 3. Evidencia ejecutada en esta semana

| Paso | Resultado |
|---|---|
| Respaldo con `pg_dump -Fc` | Archivo de 1,5 MiB generado |
| Restauración en `his_hospital_restore` | 17 tablas, 50 002 estancias y `btree_gist` presente |
| Rollback de las 6 migraciones HOSPITAL | Sin errores; queda solo la tabla de migraciones |
| Re-aplicación (`up`) | 17 tablas reconstruidas y `btree_gist` restablecida |
| Suite de integridad tras reconstruir | OK (53 tests, 679 assertions) |

## 4. Reglas que deja fijadas el plan

1. **Respaldar antes de revertir.** El orden es respaldo → rollback → verificación, y
   si algo falla, restaurar.
2. **Un respaldo no verificado no es un respaldo.** La restauración de prueba es parte
   del procedimiento semanal.
3. **Ninguna dependencia implícita entre migraciones.** Tras el rollback no queda
   ninguna extensión; cada migración la deja установada si la necesita.
4. **Puerta de calidad objetiva** antes de integrar: prueba en verde, `down`
   probado, estilo, sintaxis, suite completa, numeración acordada y entidades
   registradas.

## 5. Validación ejecutada

```bash
# comando: podman exec bd-hospital pg_dump -U postgres -d his_hospital_test -Fc -f /tmp/hospital.dump
# resultado: respaldo de 1,5 MiB

# comando: podman exec bd-hospital pg_restore -U postgres -d his_hospital_restore --no-owner /tmp/hospital.dump
# resultado: restauracion sin errores; 17 tablas, 50 002 estancias y btree_gist presente

# comando: php artisan migrate:rollback --database=hospital --path=database/migrations/hospital --realpath --force
# resultado: 5 migraciones revertidas en orden inverso; queda 1 tabla

# comando: php artisan migrate --database=hospital --path=database/migrations/hospital --realpath --force
# resultado: 6 migraciones aplicadas; 17 tablas y btree_gist restablecida

# comando: php vendor/bin/phpunit tests/Database
# resultado: OK (53 tests, 679 assertions) despues de reconstruir el esquema
```

## 6. Riesgos y pendientes

- El respaldo continuo, el cifrado en reposo y la rotación dependen de la plataforma
  de cada hospital: es un acuerdo pendiente con el equipo, no una decisión de este
  módulo.
- La rotación y el cifrado no se probaron: el entorno de prueba no tiene
  almacenamiento de producción.
- El aislamiento se verificó con la suite del repositorio; la regresión manual sobre
  dos hospitales es tarea de otro módulo del plan.
