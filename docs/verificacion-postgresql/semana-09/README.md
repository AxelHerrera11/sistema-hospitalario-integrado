# Semana 9 — Evaluación del diseño, usabilidad y accesibilidad

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 9 — Calidad de los datos de prueba (seeders)
- **Entregable / evidencia:** Checklist de calidad de datos con resultados ([checklist-calidad-datos.md](./checklist-calidad-datos.md)) y seeder propuesto ([seeder-propuesto.sql](./seeder-propuesto.sql)).
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Evaluar la calidad de los datos de prueba que usa el equipo para validar pantallas,
reportes y pruebas: completitud, consistencia y realismo. En este módulo el foco son
los datos de **base de datos**: si el seeder es inconsistente con los triggers, las
pruebas de los demás módulos fallan por motivos que no tienen que ver con su código.

## 2. Seeder propuesto

[seeder-propuesto.sql](./seeder-propuesto.sql) crea un juego mínimo y coherente:

| Entidad | Volumen | Cobertura buscada |
|---|---:|---|
| `local_user_refs` | 1 | Usuario local con rol, referencia lógica a CENTRAL |
| `wards` | 2 | Dos salas en estado activo |
| `beds` | 3 | Cama disponible, cama en mantenimiento y cama de otra sala |
| `local_patients` | 3 | Paciente activo con contacto, paciente activo sin contacto y paciente fallecido |
| `care_episodes` | 3 | Episodios `open`, `closed` y `cancelled` |
| `bed_stays` | 2 | Estancia abierta y estancia cerrada dentro del periodo de su episodio |

Los datos son **ficticios**: nombres genéricos, correo del dominio reservado
`example.test` y UUID deterministas. No hay datos reales de pacientes, credenciales
ni secretos.

### 2.1 Corrección aplicada durante la elaboración

La primera versión usaba `now() - interval 'N days'` en sentencias separadas. Al
ejecutarla, el trigger `enforce_bed_stay_integrity` rechazó la estancia cerrada con
`bed_stays_episode_period_trigger`: el instante de cierre del episodio y el de la
estancia se calcularon en momentos distintos, y la estancia quedaba con `ended_at`
posterior al cierre del episodio.

**Corrección:** todas las marcas de tiempo son fijas (`TIMESTAMPTZ '2026-10-05
08:00:00+00'`). Con eso el seeder es **determinista** y se puede repetir sin depender
del instante de ejecución. Este hallazgo es el argumento central de la semana: un
seeder no determinista produce pruebasflakes en los demás módulos.

## 3. Reglas de calidad que debe seguir el seeder común

1. **Determinista:** marcas de tiempo y UUID fijos; el resultado no depende del
   momento de ejecución.
2. **Sin datos reales:** nombres genéricos y correos del dominio reservado
   `example.test`.
3. **Coherente con el esquema:** cumple triggers, exclusiones y checks; si no, el
   error viene del seeder y no del módulo que prueba.
4. **Cobertura de estados:** cada dominio con enumeración tiene al menos un registro
   por valor, para que las pantallas y los reportes tengan qué mostrar.
5. **Volumen mínimo:** dos o tres registros por entidad son suficientes para validar
   reglas y barata de mantener; los volúmenes grandes se generan por prueba.
6. **Reversible:** el seeder puede aplicarse sobre una base limpia y volver a correr
   sin duplicar (`ON CONFLICT DO NOTHING`).
7. **Verificable:** cada criterio del checklist tiene una consulta que lo mide; los
   resultados están en [checklist-calidad-datos.md](./checklist-calidad-datos.md).

## 4. Validación ejecutada

```bash
# comando: podman exec -i bd-hospital psql -U postgres -d his_hospital_test < docs/verificacion-postgresql/semana-09/seeder-propuesto.sql
# resultado: INSERT 0 1 (usuario), INSERT 0 2 (salas), INSERT 0 3 (camas), INSERT 0 3 (pacientes), INSERT 0 3 (episodios), INSERT 0 1 e INSERT 0 1 (estancias) sin errores

# comando: primera version del seeder con now() - interval 'N days'
# resultado: ERROR bed_stays_episode_period_trigger; se corrigio con marcas de tiempo fijas

# comando: consultas de calidad Q1 a Q10 sobre la base con el seeder aplicado
# resultado: 0 camas sin sala, 0 duplicados, 0 estancias fuera de periodo, 0 episodios abiertos sin estancia, 0 contactos fuera del dominio de prueba

# comando: ocupacion en el corte 2026-10-19 12:00 UTC
# resultado: U1-01 ocupada (true), U1-02 en mantenimiento (false), UC1-01 libre (false)
```

## 5. Riesgos y pendientes

- El seeder común del equipo sigue sin definirse (acuerdo pendiente entre el módulo
  de auth y el de pacientes): esta propuesta es la contribución del módulo de BD.
- Las tablas de laboratorio, alertas, medicamentos y prescripciones todavía no tienen
  migración, así que sus datos de prueba no se pueden preparar.
- La lista de estados por dominio debe crecer cuando entren las tablas pendientes.
