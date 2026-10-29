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
- Codigo ejecutable [Micro-HIS MVC + Repository](./micro-his/). Regenerar SVG: `java -jar plantuml.jar -tsvg fuentes/*.puml -o ..`

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

- **RF-01** agendar por la entrada MVC (HTTP); **RF-02** rechazar rango invertido; **RF-03** rechazar solape de paciente o doctor;
  **RF-04** persistir mediante `CitaRepository`; **RF-05** cambiar de adaptador (PDO o InMemory) sin tocar controlador ni dominio.
- **RNF-01** PHP 8.2+ sin framework; controlador sin SQL ni reglas. **RNF-02** Repository en el Dominio; PDO preparado.
  **RNF-03** fechas ISO 8601 con zona (`DATE_ATOM`), normalizadas a UTC antes de comparar y guardar.
- **AC-01** camino feliz `201`. **AC-02** solape `409 appointments_overlap`, tambien entre zonas horarias distintas.
  **AC-03** rango invertido `409 cita_rango_invalido`, comparado en UTC. **AC-04** PDO e InMemory cumplen el mismo contrato.
  **AC-05** cambiar de adaptador no toca controlador ni caso de uso. **AC-06** `Presentation/` y `Application/` sin SQL.
  **AC-07** paciente o doctor vacio `422 cita_datos_invalidos`; fecha sin zona o imposible `422 cita_fecha_invalida`.

Evidencia (desde `micro-his/`): `php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php`
(4 pruebas, 0 fallidas; la cuarta reproduce los casos de la revision del PR #1182). Con `php -S localhost:8080 -t public`
la entrada MVC responde `201` en el camino feliz, `409` en solape o rango invertido y `422` con datos o fechas invalidos.

Este entregable corresponde a la Semana 4 del modulo; el PR apuntara a `develop` con `Refs #207`.
