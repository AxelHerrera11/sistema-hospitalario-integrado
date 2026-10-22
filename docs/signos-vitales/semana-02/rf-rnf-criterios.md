# RF/RNF y criterios de aceptación — Signos vitales y alertas por valores anormales

Se derivan de los casos de uso y las particiones A, B y C de la [Semana 1](../semana-01/README.md). Cada RF indica el UC que lo origina. Los umbrales son los del perfil ilustrativo `adulto-2026.1` ([Anexo B](../semana-01/narrativa-alcance-trazabilidad.md#anexo-b--perfil-de-umbrales-de-referencia-adulto)), pendientes de validación clínica.

## Requerimientos funcionales

| Código | UC | Requerimiento funcional | Criterio de aceptación |
|---|---|---|---|
| RF-SV-01 | UC-SV-01, 06 | Registrar una medición con al menos un signo vital, solo por el médico tratante. | **Dado** un médico con `care_assignment` activo, **cuando** envía una medición válida, **entonces** se guarda `vital_signs` `validated` y se responde `201`. Sin signos: `422`; no tratante: `403`; episodio cerrado: `409`. |
| RF-SV-02 | UC-SV-03 | Rechazar valores fisiológicamente imposibles antes de evaluar umbrales. | **Dado** una temperatura de 50 °C, **cuando** se registra, **entonces** `422 VITAL_SIGN_IMPLAUSIBLE` y no se guarda nada. |
| RF-SV-03 | UC-SV-02 | Evaluar cada signo presente contra el perfil vigente en `measured_at`; un signo ausente no se evalúa ni bloquea a los demás. | **Dado** una medición con solo FC 95, **cuando** se evalúa, **entonces** el resultado es `warning` por FC, sin error por la glucosa ausente. |
| RF-SV-04 | UC-SV-04 | Generar una alerta con la severidad máxima de los hallazgos. | **Dado** FC 138 y SpO₂ 94 %, **cuando** se evalúa, **entonces** se responde `201` con una alerta `critical` y dos hallazgos (FC `critical`, SpO₂ `warning`). |
| RF-SV-05 | UC-SV-02 | Los signos sin umbral (peso y talla) nunca generan alerta ni error. | **Dado** una medición con solo peso, **cuando** se evalúa, **entonces** `201` sin alerta. |
| RF-SV-06 | UC-SV-02 | Sin perfil aplicable (por ejemplo, pediatría), guardar y avisar. | **Dado** un paciente de 9 años, **cuando** se registra, **entonces** `201` con `THRESHOLDS_NOT_APPLICABLE` y la auditoría `signo_vital.sin_evaluacion`. |
| RF-SV-07 | UC-SV-05, 07 | Auditar la alerta en la misma transacción y notificar después del `COMMIT`. | **Dado** una alerta generada, **cuando** se confirma, **entonces** existe `signo_vital.alerta_generada` en `local_access_audits` y se encola la notificación; si la auditoría falla, `ROLLBACK` y `500`. |

## Requerimientos no funcionales

| Código | Categoría | Requerimiento no funcional | Criterio de aceptación (medible) |
|---|---|---|---|
| RNF-SV-01 | Seguridad | JWT, tenant y permisos `signos_vitales.registrar` y `signos_vitales.ver`. | El 100 % de las peticiones sin permiso o con `X-Tenant-ID` ajeno reciben `401`/`403`. |
| RNF-SV-02 | Seguridad del paciente | Ninguna regla puede ocultar la alerta de otro signo. | En la prueba de contrato, 0 reglas lanzan excepción ante un signo ausente; FC 138 siempre da `critical`. |
| RNF-SV-03 | Consistencia | Ningún valor anormal sin traza. | Con la auditoría forzada a fallar, 0 signos anormales guardados. |
| RNF-SV-04 | Rendimiento | La evaluación no retrasa el registro. | `POST /vital-signs` ≤ 300 ms (p95); la notificación es asíncrona. |
| RNF-SV-05 | Mantenibilidad (LSP) | Toda regla es sustituible por el tipo base `VitalSignRule`. | Las cinco reglas de la [demo](./evidencia/demo-lsp.php) pasan la misma prueba de contrato y el evaluador no usa `instanceof`. |

## Escenarios de aceptación

```gherkin
Escenario: Alerta crítica con varios hallazgos
  Dado que el médico tratante registra FC 138 y SpO2 94 % de un adulto
  Cuando el sistema evalúa con el perfil adulto-2026.1
  Entonces responde 201 con una alerta critical y dos hallazgos
  Y existe el evento signo_vital.alerta_generada

Escenario: Signo ausente no bloquea la evaluación
  Dado que la medición tiene FC 95 y no tiene glucosa
  Cuando el sistema evalúa los umbrales
  Entonces la alerta es warning por FC y no hay error por la glucosa

Escenario: Solo peso
  Dado que la medición solo tiene peso 70 kg
  Cuando el sistema evalúa los umbrales
  Entonces responde 201 sin alerta

Escenario: Regla nueva sustituible (LSP)
  Dado que se agrega RespiratoryRateRule como subtipo de RangeRule
  Cuando se registra FR 26
  Entonces la alerta es critical sin modificar ThresholdEvaluator
```
