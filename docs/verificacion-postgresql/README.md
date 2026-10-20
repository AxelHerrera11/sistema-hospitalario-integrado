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

## Principles and rules of this module

1. Nada se afirma sin evidencia ejecutada en el repositorio o en una base de prueba.
2. Toda migración nueva se verifica contra las reglas de la semana 7 antes de entrar a `develop`.
3. Los acuerdos con otros módulos se registran como `PENDIENTE` o `ACORDADO`, con responsable.
4. No se usan datos reales de pacientes, credenciales ni secretos en ejemplos.
5. Cada semana es un PR independiente, con más de 3 commits y menos de 400 líneas.
