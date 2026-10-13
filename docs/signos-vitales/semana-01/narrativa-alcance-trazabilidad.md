# Narrativa de alcance y trazabilidad — Signos vitales y alertas por valores anormales

## Propósito

Que el médico tratante registre los **signos vitales** del paciente durante el episodio de atención y que el sistema los **compare con umbrales clínicos** para **generar una alerta** cuando un valor sea anormal. La alerta se muestra al registrar, queda trazada en la auditoría y se **notifica** a los médicos tratantes del episodio. Así ningún valor anormal pasa desapercibido ni queda sin traza.

## Alcance funcional

**Incluido**

- Registrar signos vitales en un episodio de atención abierto (`vital_signs` con `status = 'validated'`): temperatura, frecuencia cardiaca, frecuencia respiratoria, presión arterial sistólica y diastólica, saturación de oxígeno, peso, talla y glucosa. Al menos un signo por registro.
- Rechazar mediciones **no plausibles** (fuera del rango fisiológico posible o con sistólica menor que la diastólica) sin guardar nada.
- Evaluar cada signo contra el **perfil de umbrales** vigente para la edad del paciente en la fecha de medición y calcular la severidad máxima (`warning` o `critical`).
- Generar **una alerta por registro** con valores fuera de umbral. La alerta se **deriva** del signo vital y del perfil; no se guarda en una tabla propia.
- Trazar la alerta en `local_access_audits` (HOSPITAL) en la misma transacción que el signo vital.
- **Notificar** la alerta en la aplicación (tiempo real) a los médicos con asignación activa en el episodio, con reintentos, y auditar la entrega.
- Consultar los signos vitales de un episodio con su evaluación de umbrales (médico tratante).

**Fuera de alcance**

- **Reconocer alertas y mantener su estado** (`open` / `acknowledged`, bandeja de alertas pendientes). Requiere una entidad de alerta, y la única prevista en el esquema es `critical_alert`, ubicada en los módulos de laboratorio. Queda pendiente de coordinación (ver [Coordinación pendiente con otros módulos](#coordinación-pendiente-con-otros-módulos)).
- Captura por enfermería o por dispositivos de monitoreo: el esquema actual solo admite como autor a un médico asignado (ver *Supuestos*).
- Escalamiento automático cuando una alerta no se atiende y canales externos (SMS, correo o buscapersonas). Dependen del reconocimiento.
- Umbrales personalizados por paciente o por servicio y perfiles pediátricos u obstétricos: en esta semana solo se define el perfil de referencia para adultos.
- Puntajes de alerta temprana compuestos (por ejemplo, NEWS2 completo con nivel de conciencia y oxígeno suplementario).
- Corregir un registro validado (nueva versión con `supersedes_id`) y reevaluarlo: el esquema ya soporta la corrección y se detalla en la Semana 5 (ver R5).
- Mantenimiento del perfil de umbrales (alta y edición de versiones).
- Publicar las alertas hacia CENTRAL: son un evento local del hospital.

## Supuestos y restricciones

- **Actor:** solo registra un médico con `care_assignment` activo en el episodio. El esquema ya lo exige con el trigger `vital_signs_author_assignment_trigger`. Para mantener la coherencia con la estructura actual, no se modela otro actor de captura.
- **Sin modificar entidades de otros módulos:** el módulo usa `vital_signs` (ya migrada) y `local_access_audits` (prevista en H8 y ya propuesta en Notas SOAP). No define ni altera `critical_alert`, que el esquema ubica en laboratorio (slice H7), ni agrega entidades al inventario HOSPITAL de 27 entidades.
- **Plausibilidad frente a umbrales:** los `CHECK` de `vital_signs` (por ejemplo, `heart_rate` entre 1 y 300) delimitan lo **posible**. Un valor imposible se rechaza con `422` antes de llegar a la base. Un valor posible pero anormal (por ejemplo, FC 140) **se guarda** y genera alerta.
- **Unidades:** `vital_signs` no tiene columnas de unidad. Se asumen las unidades que implican sus rangos: °C, lpm, rpm, mmHg, %, kg, cm y mg/dL. La API las documenta y no acepta otras.
- **Estado del registro:** en este flujo el médico confirma la medición al registrarla, así que se guarda directamente como `validated` (inmutable). El estado `draft` queda para una captura parcial futura.
- **Alerta derivada y reproducible:** el perfil de umbrales es configuración versionada con fecha de vigencia, detrás de `ThresholdPolicy`. Como el signo validado no cambia, evaluar el mismo registro con el perfil vigente en su `measured_at` siempre da la misma alerta. La consulta del episodio vuelve a calcularla; no lee la auditoría para obtener datos clínicos.
- **Sin perfil aplicable:** si no hay perfil para la edad del paciente, el signo se guarda, se audita `signo_vital.sin_evaluacion` y la respuesta incluye el aviso `THRESHOLDS_NOT_APPLICABLE`. La evaluación nunca se omite en silencio.
- **Ningún valor anormal sin traza:** el signo vital y la auditoría `signo_vital.alerta_generada` se escriben en **una sola transacción** de la conexión `hospital`. Si la auditoría falla, `ROLLBACK` y `500`: el médico vuelve a intentar y el valor no queda guardado sin traza.
- **Notificación fuera de la transacción:** se encola con `afterCommit`, así que solo se notifica un registro confirmado. Si el envío falla, se reintenta. Al agotar los reintentos se registra en el log; el médico que registró ya vio la alerta en pantalla y el signo anormal sigue marcado en la consulta del episodio.
- **La auditoría es traza, no estado:** `local_access_audits` registra qué pasó y cuándo, pero no se usa para decidir si una alerta está pendiente. Por eso el reconocimiento queda fuera de alcance hasta contar con una entidad de alerta acordada.
- **Sin transacciones entre bases:** todo ocurre en la base HOSPITAL del tenant. Las alertas no se publican a CENTRAL.
- Los signos vitales **no se borran** (`vital_signs_retention`).
- La auditoría guarda metadatos (médico, paciente, episodio, ids, severidad, signos afectados y versión del perfil), no notas clínicas.

## Trazabilidad entre los diagramas

Los números de la columna *Secuencia* corresponden a la numeración automática (`autonumber`) de [`03-secuencia.svg`](./03-secuencia.svg).

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-SV-01 | Registrar signos vitales del paciente | Partición A: "Médico abre el episodio…", "Capturar los signos medidos…", "¿Al menos un signo, valores numéricos y measured_at no futura?", "¿Episodio abierto y expediente…?" → "Medición aceptada" → (B). Partición B: "INSERT vital_signs (validated)" en las tres ramas | 1–2, 4–7, 15–18, 21, 30, 36 |
| UC-SV-02 | Evaluar umbrales clínicos | B: "Obtener el perfil de umbrales…", "¿Existe un perfil de umbrales aplicable?", "Comparar cada signo con su umbral…", "¿Algún valor fuera de umbral?" → "201 Signos registrados, sin alerta" o "201 Registrado con aviso THRESHOLDS_NOT_APPLICABLE" | 19–34 |
| UC-SV-03 | Rechazar medición no plausible | A: "¿Valores dentro de los rangos fisiológicos plausibles…?" → "Rechazar (UC-SV-03) 422 VITAL_SIGN_IMPLAUSIBLE" | 8–10 |
| UC-SV-04 | Generar alerta clínica | B: "Generar la alerta (UC-SV-04): severidad, hallazgos y versión del perfil", "BEGIN … INSERT local_access_audits signo_vital.alerta_generada", "¿Auditoría registrada?" → "ROLLBACK … 500" o "COMMIT · 201 … + alerta" → "Encolar la notificación…" → (C) | 35–50 |
| UC-SV-05 | Notificar alerta a médicos tratantes | Partición C completa: "Obtener los médicos con care_assignment activo…", "Enviar notificación…", "¿Envío exitoso?", "¿Quedan reintentos?" → "Alerta notificada" o "Registrar el fallo en el log…" | 47, 51–57 |
| UC-SV-06 | Validar acceso y médico tratante | A: "¿JWT, tenant y permiso signos_vitales.registrar?" y "¿Es médico asignado y activo en el episodio?" | 2–4, 11–14 |
| UC-SV-07 | Registrar evento de auditoría (`local_access_audits`, HOSPITAL) | B: "INSERT local_access_audits signo_vital.sin_evaluacion", "INSERT local_access_audits signo_vital.alerta_generada", "¿Auditoría registrada?" → ROLLBACK o COMMIT. C: "+ INSERT local_access_audits signo_vital.alerta_notificada" | 22–24, 37–46, 55–56 |
| UC-SV-08 | Consultar signos vitales y su evaluación | Reutiliza la validación de acceso (permiso `signos_vitales.ver`) y la evaluación de UC-SV-02 | `GET /care-episodes/{episode}/vital-signs` (se detalla en la Semana 5) |

## Flujo y resultados

### A · Capturar signos vitales y validar plausibilidad

1. **Acceso (UC-SV-06):** JWT, tenant y permiso `signos_vitales.registrar`; sin ellos, `401` o `403` con la traza de RBAC.
2. **Formato:** al menos un signo, valores numéricos y `measured_at` no futura; si no, `422` con los errores por campo.
3. **Plausibilidad (UC-SV-03):** cada valor dentro del rango fisiológico posible (los mismos límites de los `CHECK` de `vital_signs`) y sistólica ≥ diastólica; si no, `422 VITAL_SIGN_IMPLAUSIBLE` y no se guarda nada.
4. **Médico tratante (UC-SV-06):** si no tiene asignación activa en el episodio, `403`.
5. **Contexto clínico:** si el episodio o el expediente del paciente están cerrados, `409`.
6. **Resultado:** medición aceptada; continúa en B.

### B · Evaluar umbrales y generar alerta

1. **Perfil (UC-SV-02):** se obtiene el perfil de umbrales vigente según la edad del paciente (calculada con `local_patients.birth_date`) y la fecha de medición.
2. **Sin perfil aplicable:** en una transacción se guardan el signo vital `validated` y la auditoría `signo_vital.sin_evaluacion` (UC-SV-07). Respuesta `201` con el aviso `THRESHOLDS_NOT_APPLICABLE`: requiere evaluación clínica manual.
3. **Evaluación:** cada signo presente se compara con su umbral. Los hallazgos se clasifican en `warning` o `critical` y la alerta toma la severidad máxima. Peso y talla no tienen umbral.
4. **Valores normales:** se guarda el signo vital `validated` (`201`, sin alerta).
5. **Valores fuera de umbral (UC-SV-04):** se genera la alerta (severidad, hallazgos y versión del perfil). En una sola transacción de la conexión `hospital`: `INSERT vital_signs` e `INSERT local_access_audits` (`signo_vital.alerta_generada`). Si la auditoría falla, `ROLLBACK` y `500`.
6. **Resultado:** `COMMIT` y `201` con el `vital_sign_id`, la severidad y los hallazgos. La notificación se encola después del `COMMIT` y continúa en C.

### C · Notificar la alerta

Asíncrono; precondición: signo vital y auditoría confirmados con `COMMIT` en B.

1. **Destinatarios:** los médicos con `care_assignment` activo en el episodio.
2. **Envío (UC-SV-05):** notificación en la aplicación (tiempo real) con paciente, signo, valor y severidad. Las `critical` se muestran con prioridad.
3. **Fallo:** se reintenta con espera exponencial. Al agotar los reintentos se registra en el log.
4. **Resultado:** notificación entregada y auditada (`signo_vital.alerta_notificada`, UC-SV-07).

## Riesgos y pendientes detectados en el esquema

| # | Hallazgo | Impacto | Propuesta |
|---|---|---|---|
| R1 | La única entidad de alerta prevista, `critical_alert`, está en el slice H7 junto a `lab_result_version` (laboratorio), sin migración (tarea 3.6) ni columnas. | Sin una entidad de alerta propia, este módulo no puede guardar el estado de una alerta, y por eso el reconocimiento queda fuera de alcance. | No modificarla desde este módulo. Acordar con laboratorio y con la persona encargada del esquema si se comparte o si se crea otra entidad mediante un cambio de especificación (ver [Coordinación pendiente](#coordinación-pendiente-con-otros-módulos)). |
| R2 | No existe una entidad de umbrales clínicos. | Sin una fuente definida, la evaluación no es reproducible ni auditable. | Configuración versionada con fecha de vigencia detrás de `ThresholdPolicy` ([Anexo B](#anexo-b--perfil-de-umbrales-de-referencia-adulto)), con la versión registrada en la auditoría de cada alerta. |
| R3 | `vital_signs` no guarda unidades. | Un valor en °F o mmol/L se interpretaría mal y daría una alerta falsa o la ocultaría. | Fijar las unidades en el contrato de la API (Semana 5) y rechazar otras con `422`. |
| R4 | La tabla de auditoría local (`local_access_audit`, slice H8) aún no tiene migración (tarea 3.7). Es el mismo hallazgo R4 de Notas SOAP. | Sin ella no se garantiza "signo vital y traza en la misma transacción". | Reutilizar la propuesta del [Anexo A de Notas SOAP](../../notas-soap/semana-01/narrativa-alcance-trazabilidad.md#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital), con los eventos de este módulo ([Anexo A](#anexo-a--alerta-derivada-y-eventos-de-auditoría)). |
| R5 | Un signo vital validado se corrige con una nueva versión (`supersedes_id`), pero no está definido cómo se reevalúa. | La consulta podría mostrar la alerta de un dato ya corregido. | Definir en la Semana 5 que la consulta evalúe solo la última versión y que la corrección genere su propio evento de auditoría. |
| R6 | El proyecto aún no tiene configurados colas ni *broadcasting* para notificaciones. | La notificación activa depende de infraestructura que todavía no existe. | Mantener la interfaz `AlertNotifier`, con una implementación síncrona simple mientras se configuran la cola y el canal en tiempo real. |

## Coordinación pendiente con otros módulos

Este módulo deja **sin desarrollar** las partes que dependen de decisiones de otros módulos, para no modificar entidades que no le corresponden. Antes de ampliarlo (a más tardar en la Semana 5, cuando se formaliza el contrato API) hay que acordar lo siguiente:

| # | Con quién | Qué hay que acordar | Qué habilita para este módulo | Mientras tanto |
|---|---|---|---|---|
| C1 | Módulos de laboratorio: *Ingreso de resultados* (ASII-19) y *Validación de resultados* (ASII-20), y la persona encargada del esquema de base de datos (o, si no hay responsable asignado, el grupo) | Si `critical_alert` (slice H7) puede compartirse con signos vitales mediante un origen (`source_type`) o si conviene otra entidad, lo que exige cambiar la especificación de 27 entidades. También quién la define y la migra. | Reconocer alertas (con nota de conducta), su estado `open` / `acknowledged`, una bandeja de alertas pendientes y, después, el escalamiento. | La alerta se deriva del signo vital, se traza en la auditoría y se notifica. No hay reconocimiento. |
| C2 | Módulo de *Gobernanza y auditoría* y persona encargada del esquema (slice H8) | Las columnas de `local_access_audits` (propuesta del Anexo A de Notas SOAP) y los valores de `action` de este módulo. | La traza en la misma transacción que `vital_signs`. | Se usa la propuesta de Notas SOAP como referencia. |
| C3 | Responsable de la infraestructura del backend | Configuración de colas y del canal de notificaciones en tiempo real. | Notificación asíncrona con reintentos. | `AlertNotifier` con una implementación simple. |
| C4 | Módulo de *Médicos* y responsable del esquema | Si en el futuro enfermería u otro personal debe registrar signos vitales (hoy el trigger solo admite médicos asignados). | Ampliar los actores de captura. | Solo registra el médico tratante. |

**Insumos que este módulo aporta a la conversación C1.** Si se acuerda compartir `critical_alert`, signos vitales necesitaría: el origen de la alerta (el `vital_sign_id`), paciente y episodio, severidad (`warning` / `critical`), los hallazgos por signo, la versión del perfil de umbrales, la fecha de notificación y los datos del reconocimiento (quién, cuándo y la conducta tomada). Es una lista de necesidades, no una definición de tabla: la estructura final la deciden los módulos responsables.

## Dependencias con otros módulos

- **Depende de:**
  - *Expediente médico electrónico base* (expediente y episodio; las tablas ya existen).
  - *Médicos* (asignación activa del médico al episodio).
  - *RBAC* (permisos `signos_vitales.registrar` y `signos_vitales.ver`).
  - *Gobernanza y auditoría* y esquema HOSPITAL: la tabla `local_access_audits` (slice H8).
- **Coordinación futura:** *Ingreso y validación de resultados de laboratorio* (`critical_alert`, C1).
- **Dependen de este módulo:** *Notas SOAP* (muestra los signos vitales en la sección Objetivo, como lectura), *Expediente* (historial de signos vitales) y los reportes y analytics.

## Anexo A — Alerta derivada y eventos de auditoría

> **Estado:** decisión de diseño (Semana 1). No se crea ninguna tabla. Los eventos se escriben en `local_access_audits` con la estructura propuesta en el [Anexo A de Notas SOAP](../../notas-soap/semana-01/narrativa-alcance-trazabilidad.md#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital).

**Cómo se obtiene la alerta.** `alerta = evaluar(vital_sign, perfil vigente en measured_at)`. El resultado es un objeto en memoria que viaja en la respuesta `201`, en la notificación y en la consulta del episodio:

```json
{
  "vital_sign_id": "…",
  "severity": "critical",
  "threshold_profile_version": "adulto-2026.1",
  "findings": [
    { "sign": "heart_rate", "value": 138, "unit": "lpm", "rule": "≥ 131", "severity": "critical" },
    { "sign": "oxygen_saturation", "value": 94, "unit": "%", "rule": "92–95", "severity": "warning" }
  ]
}
```

**Eventos de auditoría de este módulo**

| `action` | `resource_type` / `resource_id` | `reason` | `metadata` |
|---|---|---|---|
| `signo_vital.alerta_generada` | `vital_sign` / id del signo vital | — | `{"severity": "critical", "signs": ["heart_rate", "oxygen_saturation"], "threshold_profile_version": "adulto-2026.1"}` |
| `signo_vital.sin_evaluacion` | `vital_sign` / id del signo vital | `THRESHOLDS_NOT_APPLICABLE` | `{"patient_age_years": 9}` |
| `signo_vital.alerta_notificada` | `vital_sign` / id del signo vital | — | `{"severity": "critical", "recipients": 2}` |

**Orden dentro de la transacción (conexión `hospital`):** `BEGIN` → `INSERT vital_signs` → `INSERT local_access_audits` (`signo_vital.alerta_generada`) → `COMMIT`. Si cualquier paso falla, `ROLLBACK` de ambos. La notificación se encola después del `COMMIT` y su auditoría (`signo_vital.alerta_notificada`) se escribe al entregarla, fuera de esa transacción.

## Anexo B — Perfil de umbrales de referencia (adulto)

> **Estado:** valores de referencia **ilustrativos** para modelar el flujo (Semana 1), inspirados en los rangos de la escala NEWS2 (Royal College of Physicians, 2017) y en los niveles de hipoglucemia de la ADA. **Deben ser validados por personal clínico** antes de usarse. Versión del perfil: `adulto-2026.1`; se aplica a pacientes de 18 años o más.

| Signo | Unidad | `warning` (anormal) | `critical` |
|---|---|---|---|
| Temperatura (`temperature`) | °C | 35.1–36.0 o 38.1–39.0 | ≤ 35.0 o ≥ 39.1 |
| Frecuencia cardiaca (`heart_rate`) | lpm | 41–50 o 91–130 | ≤ 40 o ≥ 131 |
| Frecuencia respiratoria (`respiratory_rate`) | rpm | 9–11 o 21–24 | ≤ 8 o ≥ 25 |
| PA sistólica (`systolic_bp`) | mmHg | 91–100 o 180–219 | ≤ 90 o ≥ 220 |
| PA diastólica (`diastolic_bp`) | mmHg | 110–119 | ≥ 120 |
| Saturación de oxígeno (`oxygen_saturation`) | % | 92–95 | ≤ 91 |
| Glucosa (`glucose`) | mg/dL | 54–69 o 181–399 | < 54 o ≥ 400 |
| Peso (`weight`) y talla (`height`) | kg / cm | — | — (no generan alerta) |

Ejemplo de evaluación: FC 138 lpm y SpO₂ 94 % generan **una** alerta `critical` con dos hallazgos (FC `critical`, SpO₂ `warning`).

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del módulo](./README.md)
