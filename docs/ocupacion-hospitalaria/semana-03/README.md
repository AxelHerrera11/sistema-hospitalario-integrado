# Dashboard de ocupación hospitalaria - Semana 3

- **Estudiante/módulo:** Javier Alexander Fajardo López (`Javifa2324`) - ASII-09
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Tarea/base:** Micro-HIS PHP 8.2+ por capas y PDO preparado; continúa [Semana 2](../semana-02/README.md).

## Resultado
El [prototipo ejecutable](../../../micro-his/ocupacion-hospitalaria) consulta `wards`, `beds` y `bed_stays` en una sola sentencia para producir la fotografía actual definida en Semana 2. Consulte la [vista de componentes editable](./fuentes/01-componentes.mmd).

| Capa | Responsabilidad | Dependencias permitidas |
|---|---|---|
| Presentation | Traducir el caso de uso a una respuesta y fallar con `503`. | Application |
| Application | Orquestar la consulta mediante `BedSnapshotReader`. | Domain |
| Domain | Clasificar camas, detectar inconsistencias y calcular agregados. | Ninguna capa |
| Persistence | Implementar el puerto con PDO y sentencias preparadas. | Application, Domain |

## Ejecución local
```bash
php micro-his/ocupacion-hospitalaria/tests/run.php
MICROHIS_PG_DSN='pgsql:host=127.0.0.1;dbname=his_hospital' \
  MICROHIS_PG_USER=postgres MICROHIS_PG_PASSWORD=postgres \
  php micro-his/ocupacion-hospitalaria/tests/postgres.php
```

## Evidencia
El runner aprobó 12 verificaciones de dominio, consolidación, falla segura y límites arquitectónicos. La prueba PostgreSQL reproducible crea tablas temporales y comprueba el adaptador PDO, incluidos `started_at = T`, `ended_at = T` y una estancia futura. Persistence usa `prepare()` y no `query()` ni `exec()`. Todos los PHP pasaron `php -l`; Mermaid y `git diff --check` pasaron.

## Alcance
El endpoint es exclusivamente un arnés local sin autenticación y no debe desplegarse. La integración futura delegará autenticación y selección del hospital al HIS principal. Esta semana consulta el inventario y estado operativo actuales; el corte parametrizado permite comprobar la regla temporal de `bed_stays`, pero no reconstruye estados históricos de cama. No define filtros, contrato HTTP definitivo ni MVC/Repository; Semana 4 agrega MVC y Repository. `pdo_pgsql` se cargó desde el RPM oficial sin alterar `/etc`.
