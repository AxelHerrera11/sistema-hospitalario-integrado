# Narrativa de alcance y trazabilidad — Signos vitales y alertas por valores anormales

## Propósito

Que el médico tratante registre los **signos vitales** del paciente durante el episodio de atención y que el sistema los **compare con umbrales clínicos** para **generar una alerta** cuando un valor sea anormal. La alerta se notifica a los médicos tratantes y se cierra solo cuando alguien la **reconoce** y deja constancia de la conducta tomada. Así ningún valor anormal pasa desapercibido ni queda sin traza.

## Alcance funcional

**Incluido**

- Registrar signos vitales en un episodio de atención abierto (`vital_signs` con `status = 'validated'`): temperatura, frecuencia cardiaca, frecuencia respiratoria, presión arterial sistólica y diastólica, saturación de oxígeno, peso, talla y glucosa. Al menos un signo por registro.
- Rechazar mediciones **no plausibles** (fuera del rango fisiológico posible o con sistólica menor que la diastólica) sin guardar nada.
- Evaluar cada signo contra el **perfil de umbrales** vigente para la edad del paciente y calcular la severidad máxima (`warning` o `critical`).
- Generar **una alerta clínica por registro** con valores fuera de umbral, con sus hallazgos, en la misma transacción que el signo vital.
- **Notificar** la alerta en la aplicación (bandeja y tiempo real) a los médicos con asignación activa en el episodio, con reintentos.
- **Reconocer** la alerta con una nota obligatoria sobre la conducta tomada.
- Consultar los signos vitales y las alertas de un episodio (médico tratante).
- Auditar la generación de alertas, el registro sin evaluación y el reconocimiento en `local_access_audits` (HOSPITAL), en la misma transacción que el dato clínico.

**Fuera de alcance**

- Captura por enfermería o por dispositivos de monitoreo: el esquema actual solo admite como autor a un médico asignado (ver *Supuestos*).
- Escalamiento automático cuando nadie reconoce una alerta (por ejemplo, al jefe de servicio tras cierto tiempo) y canales externos (SMS, correo o buscapersonas). Son mejoras futuras.
- Umbrales personalizados por paciente o por servicio y perfiles pediátricos u obstétricos: en esta semana solo se define el perfil de referencia para adultos.
- Puntajes de alerta temprana compuestos (por ejemplo, NEWS2 completo con nivel de conciencia y oxígeno suplementario).
- Corregir un registro validado (nueva versión con `supersedes_id`) y el efecto sobre su alerta: el esquema ya soporta la corrección y se detalla en la Semana 5 (ver R5).
- Mantenimiento del perfil de umbrales (alta y edición de versiones).
- Publicar las alertas hacia CENTRAL: son un evento local del hospital.

## Supuestos y restricciones

- **Actor:** solo registra un médico con `care_assignment` activo en el episodio. El esquema ya lo exige con el trigger `vital_signs_author_assignment_trigger`. Para mantener la coherencia con la estructura actual, no se modela otro actor de captura.
- **Plausibilidad frente a umbrales:** los `CHECK` de `vital_signs` (por ejemplo, `heart_rate` entre 1 y 300) delimitan lo **posible**. Un valor imposible se rechaza con `422` antes de llegar a la base. Un valor posible pero anormal (por ejemplo, FC 140) **se guarda** y genera alerta.
- **Unidades:** `vital_signs` no tiene columnas de unidad. Se asumen las unidades que implican sus rangos: °C, lpm, rpm, mmHg, %, kg, cm y mg/dL. La API las documenta y no acepta otras.
- **Estado del registro:** en este flujo el médico confirma la medición al registrarla, así que se guarda directamente como `validated` (inmutable). El estado `draft` queda para una captura parcial futura.
- **Umbrales como configuración:** no se agrega una entidad de umbrales al esquema HOSPITAL (que fija exactamente 27 entidades). El perfil vive en configuración versionada detrás de la interfaz `ThresholdPolicy`, y la alerta guarda la versión usada (`threshold_profile_version`) para que la evaluación sea reproducible.
- **Sin perfil aplicable:** si no hay perfil para la edad del paciente, el signo se guarda, se audita `signo_vital.sin_evaluacion` y la respuesta incluye el aviso `THRESHOLDS_NOT_APPLICABLE`. La evaluación nunca se omite en silencio.
- **Ningún valor anormal sin alerta:** el signo vital, la alerta y la auditoría se escriben en **una sola transacción** de la conexión `hospital`. Si la alerta o la auditoría fallan, `ROLLBACK` y `500`: el médico vuelve a intentar y el valor no queda guardado sin su alerta.
- **Notificación fuera de la transacción:** se encola con `afterCommit`, así que solo se notifica una alerta confirmada. Si el envío falla, se reintenta. Al agotar los reintentos se registra en el log, pero la alerta **no se pierde**: sigue `open` en la bandeja de alertas.
- **Sin transacciones entre bases:** todo ocurre en la base HOSPITAL del tenant. Las alertas no se publican a CENTRAL.
- Los signos vitales **no se borran** (`vital_signs_retention`). Una alerta tampoco se borra: se reconoce (ver Anexo A).
- La auditoría guarda metadatos (médico, paciente, episodio, ids, severidad y signos afectados), no notas clínicas completas.

## Trazabilidad entre los diagramas

Los números de la columna *Secuencia* corresponden a la numeración automática (`autonumber`) de [`03-secuencia.svg`](./03-secuencia.svg).

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-SV-01 | Registrar signos vitales del paciente | Partición A: "Médico abre el episodio…", "Capturar los signos medidos…", "¿Al menos un signo, valores numéricos y measured_at no futura?", "¿Episodio abierto y expediente…?" → "Medición aceptada" → (B). Partición B: "INSERT vital_signs (validated)" en las tres ramas | 1–2, 4–7, 15–18, 21, 30, 35 |
| UC-SV-02 | Evaluar umbrales clínicos | B: "Obtener el perfil de umbrales…", "¿Existe un perfil de umbrales aplicable?", "Comparar cada signo con su umbral…", "¿Algún valor fuera de umbral?" → "201 Signos registrados, sin alerta" o "201 Registrado con aviso THRESHOLDS_NOT_APPLICABLE" | 19–34 |
| UC-SV-03 | Rechazar medición no plausible | A: "¿Valores dentro de los rangos fisiológicos plausibles…?" → "Rechazar (UC-SV-03) 422 VITAL_SIGN_IMPLAUSIBLE" | 8–10 |
| UC-SV-04 | Generar alerta clínica | B: "BEGIN … INSERT critical_alerts (open, severidad, hallazgos)", "¿Alerta y auditoría guardadas?" → "ROLLBACK … 500" o "COMMIT · 201 … + alerta" → "Encolar la notificación…" → (C) | 35–48 |
| UC-SV-05 | Notificar alerta a médicos tratantes | Partición C completa: "Obtener los médicos con care_assignment activo…", "Enviar notificación…", "¿Envío exitoso?", "¿Quedan reintentos?" → "Alerta notificada" o "Registrar el fallo en el log…" | 45, 49–54 |
| UC-SV-06 | Reconocer alerta clínica | Partición D completa: "¿Registró la conducta tomada?", "¿La alerta existe en el tenant?", "¿La alerta sigue abierta?" → "BEGIN … status = acknowledged…" → "200 Alerta reconocida" | 55–83 |
| UC-SV-07 | Validar acceso y médico tratante | A: "¿JWT, tenant y permiso signos_vitales.registrar?", "¿Es médico asignado y activo en el episodio?". D: "¿JWT, tenant y permiso alertas_clinicas.reconocer?", "¿Es médico asignado y activo en el episodio de la alerta?" | 2–4, 11–14, 56–58, 62–63, 66–67 |
| UC-SV-08 | Registrar evento de auditoría (`local_access_audits`, HOSPITAL) | B: "INSERT local_access_audits signo_vital.sin_evaluacion" y "INSERT local_access_audits" dentro de la transacción de la alerta. D: "+ INSERT local_access_audits", "¿Auditoría registrada?" → ROLLBACK o COMMIT | 22–24, 37–44, 71–80 |
| UC-SV-09 | Consultar signos vitales y alertas | Reutiliza la validación de acceso (permisos `signos_vitales.ver` y `alertas_clinicas.ver`) | `GET /care-episodes/{episode}/vital-signs` y `GET /clinical-alerts` (se detallan en la Semana 5) |

## Flujo y resultados

### A · Capturar signos vitales y validar plausibilidad

1. **Acceso (UC-SV-07):** JWT, tenant y permiso `signos_vitales.registrar`; sin ellos, `401` o `403` con la traza de RBAC.
2. **Formato:** al menos un signo, valores numéricos y `measured_at` no futura; si no, `422` con los errores por campo.
3. **Plausibilidad (UC-SV-03):** cada valor dentro del rango fisiológico posible (los mismos límites de los `CHECK` de `vital_signs`) y sistólica ≥ diastólica; si no, `422 VITAL_SIGN_IMPLAUSIBLE` y no se guarda nada.
4. **Médico tratante (UC-SV-07):** si no tiene asignación activa en el episodio, `403`.
5. **Contexto clínico:** si el episodio o el expediente del paciente están cerrados, `409`.
6. **Resultado:** medición aceptada; continúa en B.

### B · Evaluar umbrales y generar alerta

1. **Perfil (UC-SV-02):** se obtiene el perfil de umbrales vigente según la edad del paciente (calculada con `local_patients.birth_date`).
2. **Sin perfil aplicable:** en una transacción se guardan el signo vital `validated` y la auditoría `signo_vital.sin_evaluacion` (UC-SV-08). Respuesta `201` con el aviso `THRESHOLDS_NOT_APPLICABLE`: requiere evaluación clínica manual.
3. **Evaluación:** cada signo presente se compara con su umbral. Los hallazgos se clasifican en `warning` o `critical` y la alerta toma la severidad máxima. Peso y talla no tienen umbral.
4. **Valores normales:** se guarda el signo vital `validated` (`201`, sin alerta).
5. **Valores fuera de umbral (UC-SV-04):** en una sola transacción de la conexión `hospital`: `INSERT vital_signs`, `INSERT critical_alerts` (`open`, severidad, hallazgos y versión del perfil) e `INSERT local_access_audits` (`signo_vital.alerta_generada`). Si falla la alerta o la auditoría, `ROLLBACK` y `500`.
6. **Resultado:** `COMMIT`, `201` con `vital_sign_id`, `critical_alert_id` y severidad. La notificación se encola después del `COMMIT` y continúa en C.

### C · Notificar la alerta

Asíncrono; precondición: alerta confirmada con `COMMIT` en B.

1. **Destinatarios:** los médicos con `care_assignment` activo en el episodio de la alerta.
2. **Envío (UC-SV-05):** notificación en la aplicación (bandeja y tiempo real) con paciente, signo, valor y severidad. Las `critical` se muestran con prioridad.
3. **Fallo:** se reintenta con espera exponencial. Al agotar los reintentos se registra en el log y la alerta sigue `open` en la bandeja.
4. **Resultado:** alerta entregada, con `notified_at` registrado.

### D · Reconocer la alerta clínica

Escenario independiente; precondición: alerta abierta.

1. **Acceso:** permiso `alertas_clinicas.reconocer`; sin él, `401` o `403`.
2. **Nota:** la conducta tomada es obligatoria; si falta, `422`.
3. **Alerta:** `404` si no existe en el tenant; `403` si el usuario no es médico tratante del episodio; `409` si ya fue reconocida.
4. **Reconocimiento y auditoría (UC-SV-06/08):** en una transacción: `UPDATE critical_alerts` (`acknowledged`, `acknowledged_by_user_ref_id`, `acknowledged_at`, `acknowledgment_note`) e `INSERT local_access_audits` (`alerta_clinica.reconocida`). Si la auditoría falla, `ROLLBACK` y `500`.
5. **Resultado:** `200`, alerta cerrada en la bandeja e inmutable.

## Riesgos y pendientes detectados en el esquema

| # | Hallazgo | Impacto | Propuesta |
|---|---|---|---|
| R1 | `critical_alert` está entre las 27 entidades HOSPITAL, pero en el slice H7 junto a `lab_result_version`, sin migración (tarea 3.6) ni columnas definidas. | Si se define solo para laboratorio, las alertas de signos vitales no tendrían dónde guardarse. Crear otra tabla rompería el inventario exacto de 27 entidades. | Proponer `critical_alerts` con `source_type` para ambos orígenes ([Anexo A](#anexo-a--propuesta-de-la-tabla-critical_alerts-hospital)) y acordarla con los módulos de *Ingreso de resultados* y *Validación de resultados* de laboratorio y con la persona encargada del esquema, antes de implementar (Semanas 5–7). |
| R2 | No existe una entidad de umbrales clínicos. | Sin una fuente definida, la evaluación no es reproducible ni auditable. | Configuración versionada detrás de `ThresholdPolicy` ([Anexo B](#anexo-b--perfil-de-umbrales-de-referencia-adulto)), con la versión guardada en cada alerta. Si más adelante se necesitan umbrales por paciente, se propone un cambio de especificación. |
| R3 | `vital_signs` no guarda unidades. | Un valor en °F o mmol/L se interpretaría mal y daría una alerta falsa o la ocultaría. | Fijar las unidades en el contrato de la API (Semana 5) y rechazar otras con `422`. |
| R4 | La tabla de auditoría local (`local_access_audit`, slice H8) aún no tiene migración (tarea 3.7). Es el mismo hallazgo R4 de Notas SOAP. | Sin ella no se garantiza "alerta y auditoría en la misma transacción". | Reutilizar la propuesta del [Anexo A de Notas SOAP](../../notas-soap/semana-01/narrativa-alcance-trazabilidad.md#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital), con los valores de `action` de este módulo (Anexo A, ejemplos). |
| R5 | Un signo vital validado se corrige con una nueva versión (`supersedes_id`), pero no está definido qué pasa con la alerta de la versión anterior. | Podría quedar una alerta abierta sobre un dato corregido, o perderse una alerta real. | Definir en la Semana 5 que la corrección reevalúa umbrales y que la alerta anterior se reconozca con un motivo de corrección. |
| R6 | El proyecto aún no tiene configurados colas ni *broadcasting* para notificaciones. | La notificación activa depende de infraestructura que todavía no existe. | Mantener la interfaz `AlertNotifier`, con una implementación de solo bandeja mientras se configuran la cola y el canal en tiempo real. |

## Dependencias con otros módulos

- **Depende de:**
  - *Expediente médico electrónico base* (expediente y episodio; las tablas ya existen).
  - *Médicos* (asignación activa del médico al episodio).
  - *RBAC* (permisos `signos_vitales.*` y `alertas_clinicas.*`).
  - *Gobernanza y auditoría* y esquema HOSPITAL: la tabla `local_access_audits` (slice H8).
  - *Ingreso y validación de resultados de laboratorio*: comparten la entidad `critical_alert` (R1).
- **Dependen de este módulo:** *Notas SOAP* (muestra los signos vitales en la sección Objetivo, como lectura), *Expediente* (historial de signos vitales) y los reportes y analytics.

## Anexo A — Propuesta de la tabla `critical_alerts` (HOSPITAL)

> **Estado:** propuesta de documentación (Semana 1). No se implementa esta semana. Corresponde a la entidad `critical_alert` del slice H7 (tarea 3.6). Se debe acordar con los módulos de laboratorio que también la usan y con la persona encargada del esquema de base de datos o, si no hay responsable asignado, con el grupo.

Sigue las convenciones de las migraciones HOSPITAL existentes: tabla en plural, PK `uuid` con `gen_random_uuid()`, FK locales con `ON DELETE RESTRICT`, estados acotados con `CHECK`, `timestamptz` y `jsonb` con tipo verificado.

```sql
CREATE TABLE critical_alerts (
    critical_alert_id            uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    source_type                  text NOT NULL CHECK (source_type IN ('vital_sign', 'lab_result_version')),
    vital_sign_id                uuid REFERENCES vital_signs(vital_sign_id) ON DELETE RESTRICT,
    lab_result_version_id        uuid,                         -- FK a lab_result_versions, la define el slice H7
    local_patient_id             uuid NOT NULL REFERENCES local_patients(local_patient_id) ON DELETE RESTRICT,
    care_episode_id              uuid NOT NULL REFERENCES care_episodes(care_episode_id) ON DELETE RESTRICT,
    severity                     text NOT NULL CHECK (severity IN ('warning', 'critical')),
    findings                     jsonb NOT NULL CHECK (jsonb_typeof(findings) = 'array' AND jsonb_array_length(findings) > 0),
    threshold_profile_version    text,                         -- versión del perfil de umbrales usado
    status                       text NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'acknowledged')),
    notified_at                  timestamptz,
    acknowledged_by_user_ref_id  uuid REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
    acknowledged_at              timestamptz,
    acknowledgment_note          text,
    created_at                   timestamptz NOT NULL DEFAULT now(),
    updated_at                   timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT critical_alerts_source_check CHECK (
        (source_type = 'vital_sign' AND vital_sign_id IS NOT NULL AND lab_result_version_id IS NULL AND threshold_profile_version IS NOT NULL)
        OR (source_type = 'lab_result_version' AND lab_result_version_id IS NOT NULL AND vital_sign_id IS NULL)
    ),
    CONSTRAINT critical_alerts_acknowledgment_check CHECK (
        (status = 'open' AND acknowledged_by_user_ref_id IS NULL AND acknowledged_at IS NULL AND acknowledgment_note IS NULL)
        OR (status = 'acknowledged' AND acknowledged_by_user_ref_id IS NOT NULL AND acknowledged_at >= created_at
            AND acknowledgment_note IS NOT NULL AND btrim(acknowledgment_note) <> '')
    ),
    CONSTRAINT critical_alerts_notified_check CHECK (notified_at IS NULL OR notified_at >= created_at)
);

CREATE UNIQUE INDEX critical_alerts_vital_sign_unique ON critical_alerts (vital_sign_id) WHERE vital_sign_id IS NOT NULL;
CREATE INDEX critical_alerts_care_episode_status_index ON critical_alerts (care_episode_id, status);
CREATE INDEX critical_alerts_local_patient_id_index    ON critical_alerts (local_patient_id);

-- Integridad (trigger): el signo vital pertenece al mismo episodio y paciente; quien reconoce es médico
-- con care_assignment activo en el episodio; una alerta reconocida es inmutable; no se permite DELETE.
```

| Columna | Justificación |
|---|---|
| `source_type` + `vital_sign_id` / `lab_result_version_id` | Una sola entidad para alertas de signos vitales y de laboratorio, sin salir de las 27 entidades. El `CHECK` exige exactamente un origen coherente con el tipo. |
| `local_patient_id`, `care_episode_id` | Permiten la bandeja por episodio y por paciente sin tener que unir con la tabla de origen. El trigger verifica que coincidan con el signo vital. |
| `severity`, `findings` | Una alerta por registro, con la severidad máxima. `findings` detalla cada signo afectado, por ejemplo `[{"sign": "heart_rate", "value": 138, "unit": "lpm", "rule": "> 130", "severity": "critical"}]`. |
| `threshold_profile_version` | Hace reproducible la evaluación: se sabe con qué umbrales se generó la alerta. Es obligatoria para signos vitales. |
| `status`, `acknowledged_*` | Ciclo `open` → `acknowledged`. El reconocimiento exige quién, cuándo y la conducta tomada. |
| `notified_at` | Evidencia de entrega. Si queda vacío, la alerta sigue visible en la bandeja como no notificada. |
| Índice único en `vital_sign_id` | Impide duplicar la alerta de un mismo registro, por ejemplo si se reintenta la petición. |

**Eventos de auditoría de este módulo** (en `local_access_audits`, según el [Anexo A de Notas SOAP](../../notas-soap/semana-01/narrativa-alcance-trazabilidad.md#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital))

| `action` | `resource_type` / `resource_id` | `reason` | `metadata` |
|---|---|---|---|
| `signo_vital.alerta_generada` | `critical_alert` / id de la alerta | — | `{"vital_sign_id": "…", "severity": "critical", "signs": ["heart_rate"], "threshold_profile_version": "adulto-2026.1"}` |
| `signo_vital.sin_evaluacion` | `vital_sign` / id del signo vital | `THRESHOLDS_NOT_APPLICABLE` | `{"patient_age_years": 9}` |
| `alerta_clinica.reconocida` | `critical_alert` / id de la alerta | — | `{"severity": "critical", "minutes_to_acknowledge": 7}` |

**Orden dentro de la transacción (conexión `hospital`):** `BEGIN` → `INSERT vital_signs` → `INSERT critical_alerts` → `INSERT local_access_audits` → `COMMIT`. Si cualquier paso falla, `ROLLBACK` de los tres. La notificación se encola después del `COMMIT`.

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
