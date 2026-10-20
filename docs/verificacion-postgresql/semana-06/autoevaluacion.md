# Autoevaluación de la primera evaluación parcial (Semana 6)

Criterios con la evidencia que los respalda. La evaluación final depende de la
revisión humana, no de esta autoevaluación.

| Criterio | Evidencia | Estado |
|---|---|---|
| Modelo UML de las entidades centrales | `semana-01/01-modelo-clases.svg` con fuente editable | Cumplido |
| Correspondencia clase → tabla con restricciones | `semana-01/02-clase-tabla.md` | Cumplido |
| RF/RNF de datos con criterio de aceptación | `semana-02/rf-rnf-datos.md` (10 RF, 6 RNF) | Cumplido |
| Traza requisito → restricción → prueba | `semana-02/matriz-verificacion.md` | Cumplido |
| Vista de despliegue y de datos federada | `semana-03/01-arquitectura-federada.svg` | Cumplido |
| Asignación de entidades a cada base | `semana-03/asignacion-entidades.md` | Cumplido |
| Diagrama de capas de persistencia | `semana-04/01-capas-persistencia.svg` | Cumplido |
| Interfaz de repositorio con consultas | `semana-04/consultas-principales.sql` | Cumplido |
| Matriz endpoint → tablas → operación | `semana-05/matriz-endpoint-tablas.md` | Cumplido |
| Transacciones justificadas por restricciones | `semana-05/transacciones.md` | Cumplido |
| Caso práctico aplicado al esquema real | `semana-06/presentacion.md`, diapositiva 7 | Cumplido |
| Evidencia reproducible | Suite `tests/Database` ejecutada contra PostgreSQL 16 | Cumplido |

## Límites declarados

1. Once entidades HOSPITAL todavía no tienen migración: su modelo es propuesta.
2. El modelo de la semana 1 se basó en `origin/develop` en `af6c1d3`; las
   migraciones que entren después obliga a regenerar la tabla de correspondencia.
3. La presentación es el guion de defensa; la exposición es humana y no se sustituye
   con este documento.
