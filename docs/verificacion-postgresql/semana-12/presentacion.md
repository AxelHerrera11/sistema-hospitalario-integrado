# Presentación de la segunda evaluación parcial (Semana 12)

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 12 — Segunda evaluación parcial
- **Entregable / evidencia:** Presentación con auditoría de migraciones, manejo de errores, calidad de datos e índices.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## Diapositiva 1 — Auditoría de migraciones (Semana 7)

- 15 tablas CENTRAL y 15 HOSPITAL auditadas **contra el catálogo del sistema**, no
  leyendo el código.
- 57 claves foráneas: **todas** con `ON DELETE RESTRICT` y **todas** con índice.
- 5 exclusiones GiST que impiden solapamientos (agenda, especialidades, estancias).
- 17 triggers de integridad, inmutabilidad y retención.
- Siete hallazgos: dos bloqueantes o mayores (falta `doctor_schedules` y el slice H8),
  tres menores y dos informativos.
- Evidencia: `semana-07/informe-auditoria-migraciones.md`.

## Diapositiva 2 — Manejo de errores (Semana 8)

- La base lanza un **SQLSTATE**; la capa de aplicación lo traduce a un código de
  negocio estable y a un código HTTP.
- SQLSTATE medidos en ejecución: `23502`, `23503`, `23505`, `23514` y `23P01`.
- 21 filas de catálogo con mensaje accionable en español.
- El texto técnico de PostgreSQL nunca llega al usuario; el log guarda SQLSTATE,
  restricción y `request_id`, nunca datos clínicos.
- Evidencia: `semana-08/catalogo-violaciones.md`.

## Diapositiva 3 — Calidad de datos (Semana 9)

- Un seeder **determinista** (marcas de tiempo fijas), con datos ficticios y cobertura
  de estados.
- Diez criterios medidos sobre la base real: 0 camas sin sala, 0 duplicados, 0
  estancias fuera de periodo, 0 episodios abiertos sin estancia.
- El hallazgo de la semana: con `now()` en sentencias separadas, el trigger
  `bed_stays_episode_period_trigger` rechazó una estancia. Un seeder no determinista
  produce pruebas inestables en los demás módulos.
- Evidencia: `semana-09/checklist-calidad-datos.md`.

## Diapositiva 4 — Índices y planes de ejecución (Semana 11)

| Variante | Índice | Tiempo |
|---|---|---:|
| Original (`EXISTS` correlado) | — | 4,764 ms |
| Original | `(bed_id, started_at, ended_at)` | 4,180 ms (índice no usado) |
| Preagregado | `(started_at, ended_at)` | 1,523 ms |
| Preagregado | — | 5,496 ms (peor que la original) |
| Semi-join | `(started_at, ended_at)` | 0,241 ms |

- Conclusión: el índice y la forma de la consulta son un conjunto. Por separado no
  rinden.
- Evidencia: `semana-11/planes-ejecucion.md`.

## Diapositiva 5 — Paginación móvil (Semana 10)

- Página de 25 camas: **2,9 KiB** frente a ~120 KiB de la lista completa.
- Paginación por clave `(ward_code, bed_code)`, sin `OFFSET`: 0,130 ms por página.
- El corte se fija para toda la paginación; si cambiara entre páginas, la lista
  mezclaría dos instantes.
- Sincronización desde `sync_checkpoints` e `central_inbox_messages`, con máximo 200
  mensajes por vuelta.

## Diapositiva 6 — Caso práctico

**Pregunta:** el módulo de SOAP propone su propio esquema SQLite de prueba con
`clinical_notes`, `diagnoses` y `local_access_audits`. ¿Puede integrarse tal cual?

**Respuesta:**

1. **No como esquema final.** El esquema objetivo es PostgreSQL federado con UUID,
   `timestamptz`, FK locales, triggers de inmutabilidad y unicidad de versión. El
   SQLite del módulo es un doble de pruebas: sirve para verificar reglas de dominio,
   no para el adaptador real.
2. **El riesgo real está en la divergencia.** Si el doble tiene reglas que el esquema
   federado no tiene (por ejemplo, sin unicidad de versión de nota), las pruebas
   pasan en local y fallan al conectar el adaptador real.
3. **Qué exige el módulo de BD:** que el doble declare qué restricción del esquema
   federado sustituye, que el adaptador real se pruebe contra PostgreSQL 16 antes de
   la entrega, y que toda tabla nueva del doble (`icd10_codes`, `local_access_audits`)
   se registre como tabla fuera de la especificación y se apruebe o se elimine.
4. **Lo que sí está bien:** sentencias preparadas, transacción con reversión, auditoría
   dentro de la misma transacción y separación de dominio. Esa arquitectura es la
   correcta y se mantiene.

**Conclusión:** los dobles en memoria o SQLite son válidos para probar reglas de
dominio; el adaptador real debe probarse contra el esquema federado, y la verificación
de BD existe exactamente para que esa diferencia no llegue a producción.

## Diapositiva 7 — Cierre

- Entregado: auditoría, errores, calidad de datos, paginación e índices.
- Pendiente declarado: revisión formal de un PR del grupo (semana 13), plan SQA con
  respaldo y rollback ejecutados (semana 14) y pruebas de caja blanca y negra
  (semana 15).
- Acuerdos abiertos con otros módulos: numeración de migraciones, contrato común de
  errores, seeder compartido, propiedad de `doctor_schedules` y del slice H8.
