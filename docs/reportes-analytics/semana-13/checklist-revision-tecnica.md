# ASII-23 Semana 13 — Revisión técnica formal preparada (reportes)

**Issue:** `Refs #152`. Preparada en el formato de revisión del cuerpo
(`revision-tecnica-formal.md` del módulo tenants) y lista para rendir,
con hallazgos reales ya cerrados en PRs.

## Lista de verificación

| ID | Categoría | Pregunta de revisión | Respuesta verificable |
|---|---|---|---|
| ARG-01 | Argumento | ¿La regla es una sola fuente? | Fórmula `IND-RA-01`/`TCI-1.0` definida en S1 y usada por use case único. |
| ARQ-01 | Arquitectura | ¿Reglas separadas de persistencia y HTTP? | 4 capas; SQL solo en `PdoEpisodeSummaryReader`; HTTP solo en controlador/Router. |
| ERR-01 | Errores | ¿La persistencia cae con 503 y sin cifras? | Catch `RuntimeException` → `REPORTES_UNAVAILABLE`; probado con base inexistente. |
| COH-01 | Coherencia | ¿Cancelados ≤ finalizadas? | Validación `lectura_incoherente_del_periodo`; prueba en `run.php` (503). |
| PER-01 | Periodo | ¿Medio abierto con zona explícita? | `Period::create` con offset obligatorio y `desde<hasta`; 422 `periodo_invalido`. |
| DEN-01 | Denominador | ¿0 finales → estado publicado? | `SIN_DATOS_EN_PERIODO` sin porcentaje; el 0 % válido se muestra con datos (prueba de cero). |
| PRV-01 | Privacidad | ¿Sin datos personales? | Consulta `count + SUM` y cuerpo sin `patient/paciente/care_episode/mpi`; test de no filtración en base real. |
| AUT-01 | Autorización | ¿RBAC del servidor? | `rbac:reportes.ver` de ruta + test 403/TENANT_MISMATCH; el SPA no decide. |
| ANT-01 | Antipatrón | ¿SQL dinámico o inyección? | Params preparados `?` para todos los valores; sin concatenación. |
| INY-02 | Integración | ¿Base caída no es 500? | Resolución del caso de uso dentro del `try` (fix de #1269). |
| TEST-01 | Pruebas | ¿Se pueden ejecutar con un comando? | `run.php` (11), phpunit Feature (14), a11y node (11). |
| DOC-01 | Documentación | ¿Semana→evidencia→PR trazable? | Manual del módulo (`docs/reportes-analytics/README.md`). |

## Hallazgos resueltos durante la construcción (registro del proceso)

- TypeError por firma de `Exception` con code string → contrato con mensaje
  como código (PR Semana 3).
- Router mal requerido en `app.php` (ruta `../src`) → corregido antes del PR.
- Escenario de datos con `open` contado → el dominio excluye `open`
  explícitamente y las pruebas cambian a 25.00 %/33.33 %.
- GET a ruta POST no redirige a 405 si el fallback web la atrapa → la
  prueba usa DELETE (405 garantizado por la colección).
- El test de integración HOSPITAL atribuía los episodios con
  `SELECT ... LIMIT 1` de `local_patients`: con la base migrada desde cero
  la filas no existían y la not-null de `care_episodes` reventaba. La
  prueba sembró su expediente sintético propio dentro de la transacción
  con rollback (2026-10-26, 14/14 OK en entorno aislado reproducible).

## Pendientes aceptados y justificados

- Auditoría manual con lector de pantalla en dispositivo real (S9 basa la
  garantía programática; queda como acción presencial del estudiante).
- Conexión HOSPITAL única por despliegue: limitation documentada en S1.
