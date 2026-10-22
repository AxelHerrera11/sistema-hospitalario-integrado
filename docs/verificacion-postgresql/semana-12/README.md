# Semana 12 — Segunda evaluación parcial

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 12 — Segunda evaluación parcial (defensa de las semanas 7 a 11)
- **Entregable / evidencia:** Presentación con auditoría de migraciones, manejo de errores, calidad de datos e índices.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Checkpoint del segundo parcial: defender el trabajo de las semanas 7 a 11 con el
material ya publicado y con la evidencia ejecutada en cada semana.

## 2. Presentación

[presentacion.md](./presentacion.md) contiene el guion de defensa en siete
diapositivas: auditoría de migraciones, manejo de errores, calidad de datos, índices y
planes de ejecución, paginación móvil, caso práctico de integración con el módulo de
SOAP y cierre con pendientes.

## 3. Evidencia de las semanas 7 a 11

| Semana | Entregable | Archivo |
|---:|---|---|
| 7 | Auditoría de migraciones | `semana-07/informe-auditoria-migraciones.md` |
| 7 | Registro de `doctor_schedules` | `semana-07/registro-coordinacion-doctor-schedules.md` |
| 8 | Catálogo de violaciones y HTTP | `semana-08/catalogo-violaciones.md` |
| 9 | Checklist de calidad de datos | `semana-09/checklist-calidad-datos.md` |
| 9 | Seeder propuesto | `semana-09/seeder-propuesto.sql` |
| 10 | Paginación y sincronización | `semana-10/consultas-paginacion.sql` |
| 10 | Métricas medidas | `semana-10/metricas-medidas.md` |
| 11 | Planes de ejecución | `semana-11/planes-ejecucion.md` |
| 11 | Índices propuestos | `semana-11/indices-propuestos.sql` |

## 4. Validación ejecutada

```bash
# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database
# resultado: OK (53 tests, 679 assertions) — el esquema que se defiende sigue verde

# comando: suite de mediciones de las semanas 9, 10 y 11 sobre PostgreSQL 16
# resultado: seeder aplicado sin errores; pagina de 25 camas con 2 999 bytes; planes de 4,764 ms a 0,241 ms
```

## 5. Riesgos y pendientes

- El caso práctico del módulo de SOAP se apoya en la revisión formal que se publica en
  la semana 13.
- Las mediciones usan volumen sintético: hay que repetirlas con el seeder común.
- El slice H8 y `doctor_schedules` siguen pendientes de decisión de otros módulos.
