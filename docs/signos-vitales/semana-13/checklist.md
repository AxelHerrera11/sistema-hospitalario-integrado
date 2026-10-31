# Checklist de revisión técnica — Signos vitales y alertas (Semana 13)

Quince ítems para «captura de signos vitales, evaluación de umbrales y generación de alerta». Cada revisor marca **Cumple**, **No cumple** o **No aplica** (con motivo) y, si no cumple, abre un hallazgo con el [formato](./formato-hallazgos.md). Las rutas son relativas a `micro-his/signos-vitales/`. **Sev.:** C crítico · M mayor · m menor.

## Reglas de dominio

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-01 | Lo imposible se rechaza antes de evaluar: rangos fisiológicos y sistólica ≥ diastólica | `src/Domain/VitalSigns.php` | Un signo desconocido da `VITAL_SIGNS_INVALID`; fuera de rango, `VITAL_SIGN_IMPLAUSIBLE` (422); cada caso tiene su prueba y los límites coinciden con los `CHECK` de la migración `000401` | M |
| C-02 | Las reglas de umbral son sustituibles y un signo ausente nunca oculta la alerta de otro | `VitalSignRule`, `RangeRule`, `ThresholdEvaluator` | `evaluate()` es `final`; las seis reglas pasan la misma prueba de contrato; el evaluador no usa `instanceof` | C |
| C-03 | Sin perfil aplicable se guarda y se avisa; nunca se omite la evaluación en silencio | `Application/RecordVitalSigns.php` | Paciente menor de 18 años: `201 THRESHOLDS_NOT_APPLICABLE`, auditado y sin alerta | M |
| C-04 | La fecha de medición es válida y no es futura | `RecordVitalSigns::execute` | Formato `AAAA-MM-DD HH:MM:SS` válido y no posterior a la hora del servidor (la base exige `recorded_at >= measured_at`); hay prueba de la fecha futura | M |

## Contrato API

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-05 | Rutas, verbos y permisos coinciden con el OpenAPI | `Presentation/ApiKernel.php`, `docs/signos-vitales/semana-05/openapi.yaml` | `POST /care-episodes/{id}/vital-signs` y `GET /vital-signs/{id}`, su permiso y su código de éxito son idénticos en código y contrato | M |
| C-06 | Cada `code` tiene un estado HTTP único y documentado; ningún error interno se presenta como otro | `VitalSignViolation`, `VitalSignController::respond` | Ningún `code` aparece con dos estados; un fallo posterior al `COMMIT` no se informa como `VITAL_SIGN_PERSISTENCE_ERROR` | C |
| C-07 | Toda respuesta lleva `trace_id` y ninguna filtra detalle interno | `ApiKernel::handle`, `JsonView` | El cuerpo y `X-Trace-Id` coinciden; ninguna respuesta contiene el mensaje de una excepción ni valores clínicos | M |

## Permisos y tenant

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-08 | Autorización en dos niveles: permiso de la ruta y médico tratante; no se revela si el recurso existe | `ApiKernel`, `GetVitalSign` | Permiso sin asignación activa da `403 NOT_TREATING_PHYSICIAN`; un id inexistente y uno ajeno no son distinguibles para quien no atiende | C |
| C-09 | Falla cerrada: sin identidad `401`, sin permiso `403`, RBAC caído `503`; la identidad es verificable | `ApiKernel::route`, `Request::actor` | Ante la duda se niega y no se toca la base; la identidad viene de un JWT firmado, no de una cabecera libre | C |
| C-10 | Aislamiento por tenant | `ApiKernel`, `Request`, repositorios | Toda lectura y escritura queda acotada al tenant del actor, o hay evidencia de que otra capa lo garantiza | C |

## Integridad, unidades y alertas

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-11 | Medición y auditoría en una transacción; la notificación solo después del `COMMIT` y sin afectar la respuesta de la medición | `PdoVitalSignRepository::record`, `RecordVitalSigns` | Si la auditoría falla, `ROLLBACK` y nada guardado; si el canal falla después del `COMMIT`, la medición sigue siendo `201` y la falla se registra | C |
| C-12 | Las reglas de integridad viven también en la base y el esquema coincide con el real | `database/schema.sql`, migraciones `000200` y `000401` | `recorded_by_user_ref_id`, `medical_record_id` y el estado inicial coinciden con `000401`, o las divergencias están declaradas y tienen dueño | M |
| C-13 | Las unidades son explícitas: ninguna medición se evalúa en una unidad que nadie declaró | `Request::measurements`, [contrato](../semana-05/contrato-api.md) | El contrato fija la unidad de cada signo y una unidad desconocida se rechaza; un valor en otra unidad no produce una alerta falsa | C |
| C-14 | La auditoría no guarda valores clínicos y es solo de anexado | `PdoVitalSignRepository::audit`, `local_access_audits` | `detail` solo con identificadores, severidad y nombres de signos; la tabla no permite `UPDATE` ni `DELETE` | M |

## Dependencias

| # | Qué se verifica | Dónde | Criterio de aprobación | Sev. |
|---|---|---|---|---|
| C-15 | Solo las dependencias necesarias, todas detrás de un puerto | `src/Application/*`, `app.php` | Application y Domain no importan PDO, HTTP ni clases de otros módulos; el doble en memoria pasa la misma batería (`repository.php`) | m |

## Puntos de atención declarados por el autor

Antes de la reunión, el autor declara lo que **ya sabe** que puede fallar, para que el tiempo de los revisores se use en lo desconocido. Se reprodujeron con [`evidencia/sondas.php`](./evidencia/sondas.php) (*ejecución*) o se leyeron en el código o la migración (*lectura*).

| # | Ítem | Observación | Cómo se comprobó |
|---|---|---|---|
| P-1 | C-10 | El código no contiene la palabra «tenant» (0 coincidencias en `src/`); RNF-SV-01 exige `X-Tenant-ID`. Puede resolverlo otra capa; la pregunta es cuál | Ejecución: sonda P-1 |
| P-2 | C-06, C-11 | Si el canal de notificación falla después del `COMMIT`, el controlador responde `500 VITAL_SIGN_PERSISTENCE_ERROR` aunque la medición **ya quedó guardada y auditada**. El médico reintenta y duplica la medición y la alerta | Ejecución: sonda P-2 (2 eventos de auditoría con respuesta 500) |
| P-3 | C-04 | Una `measured_at` futura (2099) o de 1900 se acepta con `201`; la base rechazaría la futura con `recorded_at >= measured_at` y el usuario vería un `500` | Ejecución: sonda P-3 |
| P-4 | C-08 | `GetVitalSign` busca la medición **antes** de autorizar el episodio: `404` si no existe y `403` si existe y no se atiende; permite enumerar ids | Ejecución: sonda P-4 |
| P-5 | C-09 | La identidad es la cabecera `X-User-ID`, sin firma; cualquier cliente puede suplantarla. Riesgo ya declarado en la Semana 6; RBAC es un doble | Ejecución: sonda P-5 |
| P-6 | C-12 | `schema.sql` usa `recorded_by`, no envía `medical_record_id` (NOT NULL en `000401`) y deja el estado `validated` por defecto (real: `draft`). Ya está declarado como esquema simplificado (#132) | Ejecución: sonda P-6 |
| P-7 | C-13 | No hay forma de declarar la unidad: glucosa 5,5 (valor normal en mmol/L) se evalúa como mg/dL y da una alerta crítica falsa. `Measurement`/`Unit` están diseñados (Semana 7) pero no implementados | Ejecución: sonda P-7 |
| P-8 | C-01 | La FC 138,5 se acepta (la columna `heart_rate` es `integer`) y la SpO2 0 se rechaza (la base lo admite) | Ejecución: sonda P-8 |
