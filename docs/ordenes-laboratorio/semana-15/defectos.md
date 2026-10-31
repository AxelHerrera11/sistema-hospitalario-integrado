# Defectos registrados — Semana 15

**Fecha de cierre de la revisión:** 22-oct-2026  
**Origen:** hallazgos H-01 a H-04 de la semana 13, confirmados o ajustados con la ejecución de la
semana 15.

## 1. Resumen

| ID | Hallazgo | Severidad | Estado | Evidencia |
|---|---|---|---|---|
| H-01 | Motivo de cancelación fuera del rango 5–250 | Mayor | **Corregido y probado** | U6, W3, B6; `run.txt` |
| H-02 | OpenAPI omitía respuestas 400/403/503 | Mayor | **Corregido y verificado** | `contrato.txt` 6/6 |
| H-03 | La API confía en `X-Permissions` | Mayor condicional | **Abierto / mitigado en gateway** | B3, B4; revisión de `ApiKernel` |
| H-04 | Replay con otra carga devuelve la orden previa | Mayor | **Abierto / aceptado** | B2 y revisión de `IssueLabOrder` |

## 2. H-01 — Longitud del motivo de cancelación

| Campo | Contenido |
|---|---|
| Artefacto | `src/Domain/LabOrder.php`; `src/Application/CancelLabOrder.php`; `openapi.yaml` |
| Detección | Semana 13, inspección inicial |
| Severidad | Mayor |
| Descripción | El contrato exige `reason` de 5 a 250 caracteres, pero el dominio solo rechazaba `null` o cadena vacía. Un motivo de 4 o 251 caracteres se aceptaba y quedaba en el historial. |
| Corrección | En `LabOrder::transitionTo()` se normaliza el motivo y, para `CANCELLED`, se valida longitud con `mb_strlen` (respaldo `strlen`). Se agregó `CANCELLATION_REASON_INVALID`. |
| Pruebas | U6 (vacío), W3 (4/5/250/251), B6 (API 4/251/5) y tres casos de regresión en `tests/run.php` para cada adaptador. |
| Resultado | 4 y 251 → `422 CANCELLATION_REASON_INVALID`; 5 y 250 → cancelación válida. Sin regresiones en las 84 verificaciones de `run.php`. |
| Estado | **Corregido y probado**. |
| Riesgo residual | El límite se cuenta en caracteres Unicode, no en bytes; es la decisión documentada. |

## 3. H-02 — Deriva del contrato OpenAPI

| Campo | Contenido |
|---|---|
| Artefacto | `docs/ordenes-laboratorio/semana-05/openapi.yaml` |
| Detección | Semana 13, inspección inicial |
| Severidad | Mayor |
| Descripción | Las operaciones de lectura no declaraban respuestas que el kernel sí produce: `400` por tenant ausente, `403` por permisos y `503` por persistencia. `PATCH` y cancelación tampoco declaraban `400`/`503`. |
| Corrección | Se agregaron `400`, `403` y `503` a `GET` de lista/detalle; `400` y `503` a `PATCH` y cancelación. Se mantuvieron `200/201/401/404/409/422` según la operación. |
| Verificación | `verificar_contrato.py` compara ruta, método y conjunto de códigos, además de `minLength: 5` y `maxLength: 250` de `reason`. Resultado: 6/6. |
| Resultado | Contrato consistente con las respuestas del kernel. |
| Estado | **Corregido y verificado**. |
| Riesgo residual | La verificación es un script manual, no una prueba de contrato integrada al pipeline. |

## 4. H-03 — Confianza en la cabecera `X-Permissions`

| Campo | Contenido |
|---|---|
| Artefacto | `src/Presentation/ApiKernel.php`; documentación de la semana 5 |
| Detección | Semana 13, inspección inicial |
| Severidad | Mayor, condicionada al despliegue |
| Descripción | El kernel toma los permisos de `X-Permissions` sin validar su origen. Un cliente con acceso directo a la API podría declarar permisos que no posee. |
| Comportamiento actual | B3 y B4 demuestran que, si la cabecera no incluye el permiso, la API responde 403. No demuestran que la cabecera sea auténtica. |
| Mitigación | El gateway debe derivar los permisos del token y sobrescribir `X-Permissions`; la API no debe exponerse directamente. Está documentado como requisito de despliegue, no implementado en este micro-HIS. |
| Estado | **Abierto / mitigado en gateway**. |
| Condición de cierre | Prueba de integración con gateway que descarte una cabecera falsificada y derive permisos del token. |
| Responsable propuesto | Integración/seguridad de la plataforma. |

## 5. H-04 — Replay idempotente con carga distinta

| Campo | Contenido |
|---|---|
| Artefacto | `src/Application/IssueLabOrder.php` |
| Detección | Semana 13, inspección inicial |
| Severidad | Mayor |
| Descripción | `executeOutcome()` busca por `tenant_id + idempotency_key` y devuelve la orden existente antes de comparar paciente, episodio, nota o pruebas. Una misma clave con otro payload recupera silenciosamente la orden anterior. |
| Comportamiento actual | B2 comprueba el caso correcto: misma clave y mismo payload → 200 con la misma orden. No existe prueba de clave repetida con payload distinto. |
| Impacto | Un error del cliente puede ocultar que la orden enviada no es la que se creó. |
| Corrección propuesta | Guardar una huella canónica del payload y devolver `409 IDEMPOTENCY_KEY_REUSED` cuando la clave exista con otra huella. Requiere migración de datos y prueba de compatibilidad. |
| Estado | **Abierto / aceptado** para esta entrega; no se implementó para no alterar el contrato sin decisión de integración. |
| Condición de cierre | Huella persistida, `409` documentado en OpenAPI y pruebas de mismo payload, payload distinto y concurrencia. |

## 6. Defectos descartados o no reproducidos

No se registraron defectos de los módulos de expediente o catálogo: son dobles de prueba y no se
probaron sus implementaciones reales. Tampoco se registra como defecto la ausencia de `pdo_pgsql` en
el PHP del host; es una condición del entorno y se resolvió ejecutando PostgreSQL en el contenedor
`localhost/mod09-php-pgsql`.
