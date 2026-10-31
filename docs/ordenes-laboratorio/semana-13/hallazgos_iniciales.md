# Hallazgos iniciales (candidatos)

**Estado:** inspección preliminar, previa a la sesión formal. Ninguno se declara corregido en esta
semana.
**Actualización semana 15:** H-01 y H-02 fueron corregidos y verificados; H-03 y H-04 siguen abiertos.
El detalle está en `../semana-15/defectos.md`.
**Regla:** si la sesión no confirma la evidencia, el hallazgo se descarta o se marca «No verificado».

## H-01 — Motivo de cancelación fuera del rango 5–250

| Campo | Contenido |
|---|---|
| Artefacto | `src/Application/CancelLabOrder.php`; `src/Domain/LabOrder.php`; `openapi.yaml` |
| Evidencia | OpenAPI exige `minLength: 5` y `maxLength: 250`; el dominio solo rechaza `null`/vacío |
| Esperado | Rechazo estable para 4 y 251 caracteres, con prueba de límites |
| Observado | El caso de uso acepta cualquier cadena no vacía; la longitud solo se valida en el contrato y en el diálogo del prototipo |
| Impacto | Auditoría débil y deriva contrato–código |
| Severidad provisional | Mayor |
| Recomendación | Validar longitud en dominio/aplicación y agregar pruebas 4/5/250/251 |

## H-02 — OpenAPI no declara todas las respuestas de lectura

| Campo | Contenido |
|---|---|
| Artefacto | `docs/ordenes-laboratorio/semana-05/openapi.yaml`; `src/Presentation/ApiKernel.php` |
| Evidencia | `GET /lab-orders` declara 200/401/422, pero el kernel también puede devolver 400 `TENANT_REQUIRED`, 403 y 503; `GET /lab-orders/{id}` declara 200/401/404 y omite 400/403/503 |
| Esperado | El contrato documenta todos los códigos que el cliente puede recibir |
| Observado | Respuestas reales del kernel ausentes en el contrato |
| Impacto | Clientes generados o integraciones pueden no manejar 403/503; pruebas de contrato incompletas |
| Severidad provisional | Mayor |
| Recomendación | Completar respuestas por operación y agregar una prueba de contrato que compare rutas y códigos |

## H-03 — Permisos confiados en la cabecera `X-Permissions`

| Campo | Contenido |
|---|---|
| Artefacto | `src/Presentation/ApiKernel.php`; documentación de la semana 5 |
| Evidencia | El kernel acepta la lista de permisos tal como llega en la cabecera |
| Esperado | En producción, los permisos se derivan del token o los inyecta el gateway y se sobrescribe la cabecera del cliente |
| Observado | Si la API se expone directamente, un cliente podría declarar permisos que no tiene |
| Impacto | Escalada de privilegios si falla el gateway o la red |
| Severidad provisional | Mayor (condicionada a despliegue) |
| Recomendación | Documentar el contrato del gateway, bloquear acceso directo y agregar prueba de integración con permisos derivados |

## H-04 — Replay con la misma clave y otro payload devuelve la orden anterior

| Campo | Contenido |
|---|---|
| Artefacto | `src/Application/IssueLabOrder.php` |
| Evidencia | `executeOutcome()` busca por `tenant_id + idempotency_key` y retorna la orden existente antes de comparar paciente, episodio o pruebas |
| Esperado | Misma clave + mismo payload → 200; misma clave + payload distinto → 409 de conflicto |
| Observado | Cualquier payload con la misma clave recupera la orden previa sin avisar |
| Impacto | Un error del cliente puede ocultar que la orden enviada no es la que se creó |
| Severidad provisional | Mayor |
| Recomendación | Guardar una huella canónica del payload y devolver `IDEMPOTENCY_KEY_REUSED` si no coincide |

## Resumen

| ID | Tema | Severidad provisional | Estado |
|---|---|---|---|
| H-01 | Longitud del motivo | Mayor | Corregido en semana 15 |
| H-02 | Deriva del contrato | Mayor | Corregido en semana 15 |
| H-03 | Confianza en `X-Permissions` | Mayor (condicionada) | Abierto |
| H-04 | Replay con payload distinto | Mayor | Abierto / aceptado |

La sesión formal debe confirmar o descartar cada uno, asignar responsable y fecha, y registrar la
decisión final en la minuta.
