# Verificación de Base de Datos PostgreSQL

Módulo transversal del Sistema Hospitalario Integrado 2026. Javier es responsable de
**verificar el esquema, las migraciones y las tablas nuevas**, de llevar la lista de
tablas y de acordar con cada dueño de módulo quién escribe su migración.

Este directorio reúne la evidencia de las 15 semanas, un PR por semana.

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Correo académico:** jfajardo13@miumg.edu.gt
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Issue relacionado:** `Refs #36`
- **Fecha de corte de integración del grupo:** jueves 22 de octubre de 2026, 12:00

## Semanas

| Semana | Entregable | Evidencia | Carpeta |
|---:|---|---|---|
| 1 | Modelo de clases UML y correspondencia clase → tabla | Diagrama de clases + tabla de correspondencia | [`semana-01/`](./semana-01/) |
| 2 | Requisitos de datos: integridad, unicidad, obligatoriedad, trazabilidad y retención | RF/RNF de datos con criterio de aceptación | [`semana-02/`](./semana-02/) |
| 3 | Arquitectura de datos federada | Vista de despliegue y de datos | [`semana-03/`](./semana-03/) |
| 4 | Acceso a persistencia y patrón repositorio | Diagrama de capas e interfaz repositorio | [`semana-04/`](./semana-04/) |
| 5 | Matriz endpoint → tablas → operación | Matriz con transacciones señaladas | [`semana-05/`](./semana-05/) |
| 6 | Primera evaluación parcial | Presentación y defensa | [`semana-06/`](./semana-06/) |
| 7 | Auditoría de migraciones | Informe por migración y migración correctiva propuesta | [`semana-07/`](./semana-07/) |
| 8 | Errores de integridad vistos por el usuario | Catálogo violación → mensaje → HTTP | [`semana-08/`](./semana-08/) |
| 9 | Calidad de los datos de prueba | Checklist de calidad y seeders propuestos | [`semana-09/`](./semana-09/) |
| 10 | Paginación y sincronización para clientes móviles | Propuesta con ejemplos de consulta | [`semana-10/`](./semana-10/) |
| 11 | Planes de ejecución e índices | EXPLAIN antes/después y índices propuestos | [`semana-11/`](./semana-11/) |
| 12 | Segunda evaluación parcial | Presentación y defensa | [`semana-12/`](./semana-12/) |
| 13 | Revisión técnica formal | Acta de revisión con severidad y responsables | [`semana-13/`](./semana-13/) |
| 14 | Aseguramiento de calidad de la base | Plan SQA con rollback y restauración ejecutados | [`semana-14/`](./semana-14/) |
| 15 | Pruebas de restricciones y casos límite | Pruebas en `tests/Database` y casos de caja negra | [`semana-15/`](./semana-15/) |

## Tabla-resumen de las 15 semanas

| Semana | Entregable | Archivo de evidencia | Estado | Pruebas | PR |
|---:|---|---|---|---|---|
| 1 | Modelo de clases UML y correspondencia clase → tabla | `semana-01/01-modelo-clases.svg`, `semana-01/02-clase-tabla.md` | Verificado | SVG válido; suite `tests/Database` en verde | [#360](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/360) |
| 2 | RF/RNF de datos con criterio de aceptación | `semana-02/rf-rnf-datos.md`, `semana-02/matriz-verificacion.md` | Verificado | 10 RF y 6 RNF trazados a restricciones reales | [#361](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/361) |
| 3 | Arquitectura de datos federada | `semana-03/01-arquitectura-federada.svg`, `semana-03/asignacion-entidades.md` | Verificado | 15 tablas CENTRAL y 15 HOSPITAL inventariadas | [#362](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/362) |
| 4 | Capas de persistencia e interfaz de repositorio | `semana-04/01-capas-persistencia.svg`, `semana-04/consultas-principales.sql` | Verificado | Consultas contra tablas verificadas | [#363](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/363) |
| 5 | Matriz endpoint → tablas → operación | `semana-05/matriz-endpoint-tablas.md`, `semana-05/transacciones.md` | Verificado | 21 endpoints mapeados | [#369](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/369) |
| 6 | Primera evaluación parcial | `semana-06/presentacion.md`, `semana-06/autoevaluacion.md` | Verificado | Caso práctico sobre el esquema real | [#370](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/370) |
| 7 | Auditoría de migraciones | `semana-07/informe-auditoria-migraciones.md` | Verificado | 57 FK con RESTRICT e índice; 17 triggers | [#373](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/373) |
| 8 | Catálogo de errores de integridad | `semana-08/catalogo-violaciones.md` | Verificado | SQLSTATE medidos en ejecución | [#375](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/375) |
| 9 | Calidad de datos de prueba | `semana-09/checklist-calidad-datos.md`, `semana-09/seeder-propuesto.sql` | Verificado | 10 criterios medidos; seeder ejecutado | [#380](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/380) |
| 10 | Paginación móvil y sincronización | `semana-10/consultas-paginacion.sql`, `semana-10/metricas-medidas.md` | Verificado | Página de 25 camas: 2,9 KiB y 0,130 ms | [#387](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/387) |
| 11 | Planes de ejecución e índices | `semana-11/planes-ejecucion.md`, `semana-11/indices-propuestos.sql` | Verificado | 5 variantes: de 4,764 ms a 0,241 ms | [#388](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/388) |
| 12 | Segunda evaluación parcial | `semana-12/presentacion.md`, `semana-12/preguntas-esperadas.md` | Verificado | Caso práctico con el módulo de SOAP | [#389](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/389) |
| 13 | Revisión técnica formal | `semana-13/README.md`, `semana-13/checklist-revision.md` | Verificado | Revisión del PR #376 con 10 hallazgos | [#390](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/390) |
| 14 | Plan SQA de la base | `semana-14/plan-sqa-bd.md`, `semana-14/evidencia-restauracion-rollback.md` | Verificado | Respaldo, restauración y rollback ejecutados | [#403](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/403) |
| 15 | Pruebas de caja blanca y caja negra | `tests/Database/Hospital/OccupancySnapshotIntegrityTest.php`, `semana-15/casos-caja-negra.md` | Verificado | OK (6 tests, 59 assertions); suite completa OK (66 tests, 790 assertions) | [#410](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/410) |

**Entrega técnica independiente (excepción documentada):** tabla `doctor_schedules`
con migración reversible y 13 pruebas, [#412](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/412).

## Principles and rules of this module

1. Nada se afirma sin evidencia ejecutada en el repositorio o en una base de prueba.
2. Toda migración nueva se verifica contra las reglas de la semana 7 antes de entrar a `develop`.
3. Los acuerdos con otros módulos se registran como `PENDIENTE` o `ACORDADO`, con responsable.
4. No se usan datos reales de pacientes, credenciales ni secretos en ejemplos.
5. Cada semana es un PR independiente, con más de 3 commits y menos de 400 líneas.
