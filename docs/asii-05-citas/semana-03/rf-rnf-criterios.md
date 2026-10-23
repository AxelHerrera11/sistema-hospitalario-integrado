# RF/RNF y criterios de aceptacion - Semana 3

**Modulo:** `ASII-05` - Gestion de citas | **Issue:** `#207`

## Requisitos funcionales

| ID | Requisito |
|---|---|
| RF-01 | Registrar una cita validando que no se solape con otra del mismo paciente o doctor. |
| RF-02 | Rechazar una cita con rango de fecha incoherente (`desde` posterior a `hasta`). |
| RF-03 | Confirmar una cita en estado `scheduled`. |
| RF-04 | Cancelar una cita en estado `scheduled` o `confirmed`. |
| RF-05 | Exponer el caso de uso por CLI (Presentation) sin duplicar reglas de negocio. |

## Requisitos no funcionales

| ID | Requisito |
|---|---|
| RNF-01 | `Domain` no importa PDO, HTTP ni librerias externas. |
| RNF-02 | Configuracion (DSN) fuera del codigo, via `.env` no versionado. |
| RNF-03 | Acceso a datos solo con sentencias PDO preparadas. |
| RNF-04 | `PDOException` se traduce a `ErrorPersistenciaException` antes de salir de Persistence. |
| RNF-05 | Ejecucion con `php` puro (CLI 8.2+), sin Composer ni framework. |

## Criterios de aceptacion

- **AC-01 (anti-solape):** una cita solapada se rechaza con `409 appointments_overlap` antes de persistir.
- **AC-02 (rango invalido):** `desde` posterior a `hasta` lanza `RangoFechaInvalidoException`.
- **AC-03 (confirmar):** `scheduled` pasa a `confirmed`; `cancelled` no puede confirmarse.
- **AC-04 (cancelar):** `scheduled`/`confirmed` pasan a `cancelled`.
- **AC-05 (error de persistencia):** un doble que falla propaga `ErrorPersistenciaException` sin dejar registro.
- **AC-06 (separacion de capas):** ningun archivo de `src/Domain/` contiene `PDO`, `$_GET`, `$_POST` ni `header(`.

## Trazabilidad

| Criterio | Evidencia (`micro-his/tests/ejecutar_pruebas.php`) |
|---|---|
| AC-01 | aplicacion: rechaza solape sin persistir |
| AC-02 | dominio: rechaza rango de fecha invertido |
| AC-03 | dominio: confirma solo si esta scheduled |
| AC-04 | dominio: cancela y detecta solape |
| AC-05 | aplicacion: error de persistencia se propaga |
| AC-06 | Inspeccion directa de `src/Domain/` |
