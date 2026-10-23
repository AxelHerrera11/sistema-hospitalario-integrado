# Gestion de citas - Semana 3

**Estudiante:** Lis Ivette Rosales Colindrez ([`Lis671`](https://github.com/Lis671)) | **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)

**Proceso:** solicitud, validacion de disponibilidad, confirmacion o cancelacion de cita.

**Enfoque:** Micro-HIS en PHP 8.2+ vanilla, sin framework, capas Presentation / Application / Domain / Persistence.

## Entregables

- [RF/RNF y criterios de aceptacion](./rf-rnf-criterios.md)
- [Evidencia verificable](./evidencia-verificable.md)
- Fuentes PlantUML: [capas](./fuentes/01-capas.puml), [contexto C4](./fuentes/02-contexto-c4.puml) (SVG renderizado al lado)
- Micro-HIS ejecutable en [`micro-his/`](./micro-his/bootstrap.php)

## Vista de capas

| Capa | Carpeta | Responsabilidad | Que NO sabe |
|---|---|---|---|
| **Presentation** | `micro-his/bin/`, `micro-his/public/` | Traducir CLI/HTTP a un DTO de entrada y presentar el resultado o error. | No conoce reglas de negocio ni SQL. |
| **Application** | `micro-his/src/Application/` | Orquestar: validar con el Dominio, pedir datos a Persistence, ordenar la persistencia. | No valida por si misma; no sabe si los datos vienen de PDO o de un doble. |
| **Domain** | `micro-his/src/Domain/` | Entidad `Cita`, excepciones y el puerto `CitaRepository`. | No importa PDO, `$_POST` ni librerias externas. |
| **Persistence** | `micro-his/src/Persistence/` | Implementa el puerto con PDO preparado y con dobles en memoria. | No decide reglas; ya fueron validadas en el Dominio. |

La Inversion de Dependencias (puerto en el Dominio, adaptador en Persistence) permite
probar `AgendarCita` completo con dobles, sin base de datos, y traducir cualquier
`PDOException` a `ErrorPersistenciaException` antes de salir de Persistence.

## Regeneracion

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-03/fuentes/*.puml -o ..
php docs/asii-05-citas/semana-03/micro-his/tests/ejecutar_pruebas.php
```

Este entregable corresponde a la Semana 3 del modulo; el PR apunta a `develop` con `Refs #207`.
