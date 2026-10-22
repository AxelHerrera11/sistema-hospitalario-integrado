# Gestion de citas medicas y agenda - Semana 4

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)

## Entregables

- [Diagrama MVC + Repository](./01-mvc-repository.svg) | [fuente](./fuentes/01-mvc-repository.puml)
- [Integracion con repositorio de datos compartido](./02-repositorio-compartido.svg) | [fuente](./fuentes/02-repositorio-compartido.puml)
- Codigo ejecutable [Micro-HIS MVC + Repository](./micro-his/)
- Requisitos, criterios de aceptacion y evidencia: en este documento.

## Capas, responsabilidades y objetos reutilizables

La entrada se organiza con MVC y el acceso a datos con el patron Repository. El controlador
no tiene SQL ni reglas de negocio. La interfaz `CitaRepository` vive en el Dominio y la
Infraestructura provee dos adaptadores intercambiables.

| Capa | Responsabilidad | Artefactos |
|---|---|---|
| Presentation (MVC) | Router y controlador: HTTP al caso de uso | `public/index.php`, `Presentation/CitaController.php` |
| Application | Caso de uso con la regla de anti-solape | `Application/AgendarCita.php` |
| Domain | Entidad `Cita` e interfaz `CitaRepository` | `src/Domain/` |
| Infrastructure | Adaptadores `CitaRepositoryPDO` y `CitaRepositoryInMemory` | `src/Infrastructure/` |

El modulo se integraria a un repositorio de datos compartido del HIS implementando la misma
interfaz `CitaRepository` sobre el esquema comun (tenant + paciente), sin cambiar Dominio ni
controlador (ver `02-repositorio-compartido.puml`).

## Requisitos, criterios de aceptacion y evidencia

- **RF-01**: agendar una cita por la entrada MVC (HTTP).
- **RF-02**: rechazar citas con rango de fecha invertido.
- **RF-03**: rechazar citas que se solapan para el mismo paciente o doctor.
- **RF-04**: persistir y recuperar citas mediante la interfaz `CitaRepository`.
- **RF-05**: intercambiar el adaptador (PDO o InMemory) sin cambiar controlador ni dominio.
- **RNF-01**: PHP 8.2+ sin framework ni Composer; controlador sin SQL ni reglas.
- **RNF-02**: interfaz Repository en el Dominio; PDO con sentencias preparadas.
- **AC-01**: el controlador responde `201` en el camino feliz.
- **AC-02**: un solape responde `409 appointments_overlap`.
- **AC-03**: un rango invertido responde `409 cita_rango_invalido`.
- **AC-04**: `CitaRepositoryPDO` y `CitaRepositoryInMemory` cumplen el mismo contrato.
- **AC-05**: cambiar de adaptador no requiere tocar el controlador ni el caso de uso.
- **AC-06**: `src/Presentation/` y `src/Application/` no contienen SQL.

Evidencia (desde `micro-his/`): `php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php`
(3 pruebas, 0 fallidas); y con `php -S localhost:8080 -t public`, la entrada MVC responde
`201` en el camino feliz y `409` en el solape o el rango invertido.
## Regeneracion

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-04/fuentes/*.puml -o ..
```

Este entregable corresponde a la Semana 4 del modulo; el PR apuntara a `develop` con `Refs #207`.
