# Semana 6 — Primera evaluación parcial

**Módulo:** ASII-19 — Ingreso de resultados de laboratorio  
**Estudiante:** ORELLANA VÉLIZ, GERSON GIOVANNI

## Tesis arquitectónica

El flujo de captura y corrección controlada mantiene la trazabilidad desde los actores y UML hasta el dominio, las capas, Repository y el contrato HTTP. El resultado pendiente nunca se modifica sin validar tenant, unidad, estado y motivo de corrección.

## Integración de evidencias

| Decisión defendida | Evidencia | Criterio verificable |
|---|---|---|
| Separar reglas del transporte | src/Domain/PendingLabResult.php y ResultRuleValidator.php | Una entrada inválida no llega a Persistence |
| Aislar almacenamiento | PendingResultRepository y adaptadores InMemory/PDO | El caso de uso no contiene SQL |
| Conservar correcciones | historial de versión, valor anterior y motivo | La versión anterior puede auditarse |
| Proteger consistencia | pruebas de auditoría, persistencia y versión obsoleta | Un fallo deja el registro sin cambios |
| Exponer HTTP estable | public/index.php y controlador | El cuerpo y el código HTTP coinciden |

## Contrato de integración con otros módulos

ASII-19 consume el catálogo versionado de ASII-17 y publica el resultado de la
validación crítica para ASII-21. Esta integración respeta las convenciones
compartidas por el equipo:

- Las rutas se exponen bajo `/api/v1`.
- Las solicitudes exigen `X-Tenant-ID`; si falta se responde `400
  TENANT_REQUIRED` y si no corresponde al hospital se responde `403
  TENANT_MISMATCH`.
- `X-User-Ref` identifica al usuario que llega desde el middleware de
  autenticación. `X-Request-Id` se genera cuando no viene informado y se
  devuelve siempre en la respuesta.
- Los errores usan `application/problem+json` con `title`, `status`, `code`,
  `field` y `request_id`.
- Cada resultado conserva el `lab_test_id` exacto de la versión del catálogo;
  unidad, rango y umbral crítico se obtienen de esa versión y no de valores
  duplicados en ASII-19.

Cuando la validación determina un valor crítico, ASII-19 publica el evento
interno `POST /api/v1/internal/result-validated` con
`result_version_id`, `lab_test_id`, `patient_id`, `episode_id` y `value`.
El evento es idempotente por `result_version_id`: permite reintentos sin
duplicar alertas. ASII-21 es dueño de las alertas y sus estados; ASII-19 es
dueño del resultado y de su versión.

Este PR documenta el contrato y la decisión de integración. No crea las tablas
`lab_tests` ni `critical_alerts`, que pertenecen respectivamente a ASII-17 y
ASII-21.

## Cambio práctico defendido

Si el laboratorio corrige un resultado de 95 mg/dL a 105 mg/dL, se valida la unidad esperada, se exige motivo, se crea la versión 2 y se conserva la versión 1 para auditoría. Si falla la auditoría o la persistencia, la versión 1 permanece activa.

## Validación

    php micro-his/ingreso-resultados-lab/tests/run.php
    OK: camino feliz, regla de dominio y error de persistencia verificados.

## Decisiones pendientes

La persistencia productiva del historial requiere una tabla de versiones/correcciones en PostgreSQL; el prototipo deja explícito ese límite sin simular infraestructura productiva.

La implementación final del evento y la validación contra el catálogo deben
coordinarse con los PR de ASII-17 y ASII-21 antes de cerrar la integración.

