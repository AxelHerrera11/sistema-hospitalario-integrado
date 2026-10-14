# Dashboard de ocupación hospitalaria - Semana 3

## Ficha

- **Estudiante/módulo:** Javier Alexander Fajardo López (`Javifa2324`) - ASII-09
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Tarea/base:** Micro-HIS PHP 8.2+ por capas y PDO preparado; continúa [Semana 2](../semana-02/README.md).

## Resultado

El prototipo ejecutable está en [`micro-his/ocupacion-hospitalaria`](../../../micro-his/ocupacion-hospitalaria). Consulta `wards`, `beds` y `bed_stays` en una sola sentencia para producir la fotografía atómica definida en Semana 2.

| Capa | Responsabilidad | Dependencias permitidas |
|---|---|---|
| Presentation | Traducir el caso de uso a una respuesta y fallar con `503`. | Application |
| Application | Orquestar la consulta mediante `BedSnapshotReader`. | Domain |
| Domain | Clasificar camas, detectar inconsistencias y calcular agregados. | Ninguna capa |
| Persistence | Implementar el puerto con PDO y sentencias preparadas. | Application, Domain |

[Vista de componentes editable](./fuentes/01-componentes.mmd)

## Ejecución

```bash
php micro-his/ocupacion-hospitalaria/tests/run.php
MICROHIS_PG_DSN='pgsql:host=127.0.0.1;dbname=his_hospital' \
  php -S 127.0.0.1:8083 -t micro-his/ocupacion-hospitalaria/public
```

## Evidencia

El runner aprobó 12 verificaciones: camino feliz global/por sala, clasificación autoritativa por estancia, discrepancias, conteo único, tasa y distribución, capacidad no aplicable, falla segura y límites arquitectónicos. Persistence usa `prepare()` y no `query()` ni `exec()`.

Todos los PHP pasaron `php -l`; Mermaid y `git diff --check` pasaron. El adaptador se verificó con PostgreSQL 16 temporal: 5 camas, 2 salas, tasa 50 % y 2 inconsistencias.

## Alcance

Esta semana delega autenticación y selección del hospital al HIS principal; no define filtros, contrato HTTP definitivo ni MVC/Repository. Semana 4 agregará MVC y adaptadores Repository. `pdo_pgsql` se cargó localmente desde el RPM oficial para la prueba, sin alterar `/etc`.

**Estado:** entrega validada localmente, sin rama publicada ni Pull Request.
