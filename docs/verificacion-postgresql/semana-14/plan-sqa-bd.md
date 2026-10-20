# Plan SQA de la base de datos (Semana 14)

Plan de aseguramiento de calidad de PostgreSQL en el HIS: respaldo, restauración,
rollback, aislamiento entre hospitales y métricas. La evidencia de ejecución está en
[evidencia-restauracion-rollback.md](./evidencia-restauracion-rollback.md).

## 1. Alcance y responsables

| Ámbito | Responsable | Alcance |
|---|---|---|
| Esquema y migraciones | Javier (verificación de BD) | Reglas de migración, numeración, revisión de cada migración nueva |
| Respaldo y restauración | Javier con el equipo de plataforma | Periodicidad, verificación y rotación |
| Aplicación y contratos | Cada módulo owner | Pruebas feature y de dominio |
| CI | Cindy (QA) | Ejecución de la suite de integridad en cada PR |

## 2. Respaldo

| Aspecto | Decisión |
|---|---|
| Qué se respalda | Base CENTRAL y **una base HOSPITAL por hospital** |
| Herramienta | `pg_dump` en formato personalizado (`-Fc`) |
| Frecuencia | Diaria completa y continua (WAL) en producción |
| Retención | 7 diarios, 4 semanales, 12 mensuales |
| Ubicación | Almacenamiento fuera del hospital, cifrado en reposo |
| Verificación | Restauración semanal sobre una base descartable y comparación de conteos |

Reglas:

1. El respaldo se hace **antes** de cualquier operación de rollback.
2. Un respaldo no verificado no es un respaldo: la restauración de prueba es parte del
   procedimiento, no una excepción.
3. Los respaldos contienen datos clínicos: se tratan con el mismo control de acceso que
   la base y su acceso queda auditado.

## 3. Restauración

Procedimiento verificado en esta semana:

```text
pg_dump -Fc  ->  pg_restore  ->  base descartable  ->  conteos y extensiones  ->  fin
```

| Verificación | Resultado obtenido |
|---|---|
| Tablas restauradas | 17 (16 del esquema + migraciones) |
| Filas de `bed_stays` | 50 002 |
| Extensión `btree_gist` | Presente |
| Integridad referencial | Sin errores en la restauración |

Criterio de aceptación: la base restaurada pasa la suite de integridad antes de
entrar en servicio.

## 4. Rollback de migraciones

| Aspecto | Decisión |
|---|---|
| Cuándo | Ante una migración que rompe la integración, el mismo día |
| Cómo | `php artisan migrate:rollback --database=<base> --path=<ruta> --realpath` |
| Requisito | Cada migración declara `down` y hay una prueba que lo ejercita |
| Post-condición | La base vuelve al estado anterior **sin** tablas residuales |
| Verificación | Contar objetos antes y después; ejecutar la suite de integridad |

Evidencia: tras el rollback solo queda la tabla de migraciones y la re-aplicación
reconstruye las 17 tablas con `btree_gist`.

**Orden obligatorio:** respaldar → revertir → verificar → (si falla) restaurar.

## 5. Aislamiento entre hospitales

| Control | Cómo se verifica |
|---|---|
| Una base por hospital | `ConnectionIsolationTest` y configuración de conexiones separadas |
| `X-Tenant-ID` obligatorio | Middleware de tenant y prueba sin la cabecera |
| Sin FK entre bases | Auditoría de migraciones: toda FK es local |
| Sin datos clínicos en CENTRAL | Auditoría del esquema CENTRAL |
| Identificadores lógicos | Revisión de que no se filtran datos de un hospital a otro |

## 6. Métricas

| Métrica | Fuente | Objetivo | Frecuencia |
|---|---|---|---|
| Migraciones aplicadas con prueba | CI | 100 % | Por PR |
| Tiempo de rollback de un slice | `migrate:rollback` | < 5 min | Por incidente |
| Éxito de restauración de prueba | Procedimiento semanal | 100 % | Semanal |
| Consultas sin índice | `EXPLAIN` de las consultas frecuentes | 0 | Quincenal |
| Divergencia entre doble y esquema real | Revisión formal | 0 | Por PR con persistencia |
| Integridad referencial | Suite `tests/Database` | 100 % | Por PR y por día |
| Extensiones requeridas presentes | `pg_extension` | `btree_gist` y `pgcrypto` siempre | Por despliegue |

## 7. Puerta de calidad antes de integrar un PR de base de datos

1. La prueba de integridad se escribe antes que la migración y está en verde.
2. La migración declara `down` y el rollback está probado.
3. `vendor/bin/pint --test` y `php -l` pasan sobre los archivos tocados.
4. `git diff --check` sin salida.
5. La suite `tests/Database` completa pasa sobre PostgreSQL real.
6. La numeración del archivo está acordada con el grupo.
7. No hay entidades nuevas sin registrar en la lista de entidades.

## 8. Riesgos y pendientes

- El respaldo continuo (WAL) y el cifrado en reposo dependen de la plataforma del
  hospital: es un acuerdo pendiente, no una decisión de este módulo.
- La rotación y el cifrado no se probaron en esta semana: el entorno de prueba no
  tiene almacenamiento de producción.
- El aislamiento se verificó con la suite del repositorio; falta la regresión manual
  que el plan asigna a otro módulo.
