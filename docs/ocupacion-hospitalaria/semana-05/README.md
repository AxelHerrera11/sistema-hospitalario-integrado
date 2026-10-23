# Dashboard de ocupación hospitalaria - Semana 5

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Tema:** cliente-servidor, contrato API REST, evaluación de frontera de microservicio y
  migración razonada.

## Plan de issue, rama, worktree y Pull Request

| Elemento | Valor |
|---|---|
| Plan de issue | Issue [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36) del módulo ASII-09, con la lista de actividades semanales de Odoo. Las entregas de esta semana se registran con `Refs #36`. |
| Ramas de la semana | `feature/asii-09-semana-05-dashboard-javifa2324` (semana 5) y `feature/asii-09-semana-16-integracion-laravel-javifa2324` (integración del endpoint en el HIS). |
| Worktrees | `/var/home/javier/shi-asii-09-semana-05-dashboard-ocupacion` y `/var/home/javier/shi-asii-09-semana-16-ocupacion`, creados desde `origin/develop`. |
| Pull Requests | Contrato de integración: PR #342. Endpoint integrado en Laravel: PR #574. Pantalla Vue: PR #575. |
| Destino | `develop` |

## Índice

1. Introducción y evolución
2. Contrato y arquitectura
3. Decisión de frontera y migración
4. Evidencia, conclusión y fuentes

## Introducción

Semana 5 continúa el mismo Micro-HIS PHP 8.2+ de ocupación. El caso de uso se ofrece como
`GET /api/v1/occupancy`: un cliente HIS solicita una fotografía por hospital y fecha de corte,
mientras el servidor conserva la regla autoritativa basada en `bed_stays`.

## Evolución cliente-servidor

El diseño de esta semana quedó materializado en el HIS. La entrada HTTP del módulo se implementó
en Laravel, no dentro del Micro-HIS:

- Ruta `GET /api/v1/occupancy` con la cadena de middleware `tenant` + `jwt.auth` +
  `rbac:ocupacion.ver` (`routes/api.php`).
- `app/Http/Controllers/Api/V1/OccupancyController.php` resuelve el corte, invoca el caso de uso
  y responde `200`, `400`, `401`, `403`, `405` o `503` sin traza cuando `APP_DEBUG=false`.
- `app/Providers/OcupacionServiceProvider.php` cablea el Micro-HIS con el adaptador PDO de la
  conexión `hospital` y traduce un fallo de persistencia a `PersistenceFailure`.
- El Micro-HIS conserva Domain, Application y Persistence: `BedSnapshotReader` como puerto,
  `PdoBedSnapshotReader` como adaptador y `OccupancyCalculator` como regla autoritativa. La
  conexión se resuelve de forma perezosa para que la caída de la base devuelva `503` y no `500`.

La autenticación no usa el token estático del prototipo: usa el JWT del grupo y el tenant se
resuelve con la cabecera `X-Tenant-ID` que el middleware `tenant` valida contra la base HOSPITAL
autorizada.

- [Contrato API de la semana](./contrato-api.md)
- [Frontera y migración](./frontera-microservicio.md)
- [Contenedores cliente-servidor](./fuentes/01-cliente-servidor.mmd)
- [Secuencia](./fuentes/02-secuencia-consulta.mmd)
- [Migración](./fuentes/03-migracion.mmd)
- [Declaración de IA](./DECLARACION_IA.md)

## Evidencia

Salida real del 21 de octubre de 2026 sobre `develop` en `75bd69ce`:

```bash
$ php micro-his/ocupacion-hospitalaria/tests/run.php
OK: 12 verificaciones aprobadas

$ php artisan test tests/Feature/Ocupacion tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
Tests:    22 passed (125 assertions)
Duration: 2.12s
```

Las pruebas del contrato HTTP cubren credenciales, aislamiento por hospital, fecha inválida,
contrato exitoso, dominio, Repository, error de persistencia y límites arquitectónicos. PostgreSQL
sigue siendo opcional en el Micro-HIS mediante `MICROHIS_PG_DSN`.

## Conclusión

La API crea una separación cliente-servidor sin duplicar reglas. No se recomienda extraer ASII-09
como microservicio todavía: su volumen y SLO no están medidos y depende directamente de datos
propiedad de ASII-06/07/08. Primero se estabiliza el contrato y se mide; solo después se considera
una réplica de lectura propia.

## Bibliografía

- Fielding, R. T. (2000). *Architectural Styles and the Design of Network-based Software Architectures*.
- RFC 9110, *HTTP Semantics* (2022).
- OpenTelemetry, *Observability Primer* (consulta: 2026-10-14).
