# Narrativa de alcance y trazabilidad — Alertas críticas y notificaciones internas

## Propósito

Que **ningún valor crítico de laboratorio quede sin respuesta**: el sistema lo detecta al validarse el resultado, avisa a la persona responsable, exige un acuse con lectura de verificación y, si nadie lo confirma a tiempo, escala la alerta hasta que alguien actúe. Cada paso queda auditado.

## Alcance

**Incluido:**
- consumir «resultado validado» (ASII-20) por `inbox_messages`, con entrega al menos una vez;
- detectar el valor crítico con los umbrales de **la versión de la prueba** con que se ordenó (ASII-17);
- una sola alerta por resultado (idempotencia);
- destinatario: médico con `care_assignment` activa (nivel 0) o jefatura de servicio (nivel 1);
- notificar en la bandeja interna, con reintentos;
- acusar con lectura de verificación;
- registrar la acción y cerrar;
- escalar las vencidas (tratante → jefatura → dirección médica);
- auditar cada paso.

**Fuera de alcance:**
- capturar o validar resultados (ASII-19/20);
- definir umbrales (ASII-17);
- alertas por signos vitales (ASII-13), que pueden sumarse como otra fuente con el mismo contrato;
- SMS, correo o llamada automática (solo queda el evento en `outbox_events`);
- turnos y guardias (ver R4).

## Supuestos

- **Fuente:** el resultado **validado**, para no alertar con valores que el bioquímico rechace.
- **Umbrales versionados:** la alerta guarda el `lab_test_id` y los umbrales superados.
- **Plazos:** 15 minutos por nivel. El plazo empieza al generar la alerta, no al entregarla.
- **Niveles:** 0 tratante, 1 jefatura, 2 dirección médica. Al agotarse el 2, la alerta sigue abierta y se pide una llamada registrada.
- **Acuse:** solo el destinatario o alguien con relación de cuidado. El primero gana; los demás reciben `409`.
- **Transacción local:** alerta, auditoría y outbox en la conexión `hospital`; la notificación sale después del `COMMIT`.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad | Secuencia (mensajes) |
|---|---|---|---|
| UC-ALR-01 | Detectar valor crítico | A: «¿Evento completo?», «¿La versión de la prueba tiene umbrales y el valor los supera?» | 1–4 |
| UC-ALR-02 | Generar alerta | A: «¿Ya hay alerta?», destinatario, «BEGIN: INSERT critical_alerts…», «¿Transacción?» | 5–12 |
| UC-ALR-03 | Notificar | A: «COMMIT · notificar a la bandeja». C: «… notificar» | 13–15, 33 |
| UC-ALR-04 | Acusar recibo | B: «BEGIN FOR UPDATE: status = acknowledged», «¿Falla de auditoría?» | 19–25 |
| UC-ALR-05 | Escalar vencida | C completa | 29–35 |
| UC-ALR-06 | Consultar bandeja | B: «Médico abre la bandeja» | 16 (el listado se detalla en la Semana 5) |
| UC-ALR-07 | Registrar acción y cerrar | B: «Registra la acción clínica → resolved» | 25–28 |
| UC-ALR-08 | Acceso y relación de cuidado | B: «¿JWT, tenant y alertas.acusar?», «¿Existe, hay relación de cuidado…?» | 16–18, 20–21 |
| UC-ALR-09 | Auditoría | A: «+ local_access_audits + outbox_events». B y C: «+ auditoría» | 9, 23, 27, 32, 34 |

## Riesgos y dependencias

| # | Hallazgo | Propuesta |
|---|---|---|
| R1 | `critical_alert` (H7) no tiene migración ni columnas | Validar el Anexo A con el grupo y con ASII-13, 19 y 20 |
| R2 | No hay tabla de historial de escalamiento ni de notificaciones | Guardarlo en `local_access_audits` y `outbox_events` |
| R3 | Los umbrales dependen de `lab_tests` (ASII-17), sin migración | Coordinar `critical_low` y `critical_high` con el Anexo A de ASII-17 |
| R4 | No hay modelo de turnos ni jefaturas por servicio | Un rol fijo por servicio hasta acordarlo con ASII-04 y ASII-02 |
| R5 | `outbox_events`, `inbox_messages` y `local_access_audits` (H8) sin migración | Acordar el formato del evento con ASII-20 |
| R6 | El canal en tiempo real no está configurado (`BROADCAST_CONNECTION=log`) | Empezar con consulta periódica de la bandeja |

**Depende de** ASII-20 (evento), ASII-17 (umbrales), ASII-04/10 (`care_assignments`) y ASII-02 (permisos). **Dependen de este módulo** el Expediente (ASII-10), que muestra las alertas abiertas; Dashboard y Reportes (tiempos de acuse), y Signos vitales (ASII-13), que puede reutilizar el flujo.

## Anexo A — Propuesta de `critical_alerts`

> **Estado:** propuesta (Semana 1), no implementada. Se valida con quien lleva el esquema. Sigue las convenciones HOSPITAL: PK `uuid`, FK locales `ON DELETE RESTRICT`, estados con `CHECK` y protección contra borrado.

```sql
CREATE TABLE critical_alerts (
    critical_alert_id             uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    lab_result_version_id         uuid NOT NULL REFERENCES lab_result_versions ON DELETE RESTRICT,
    lab_test_id                   uuid NOT NULL REFERENCES lab_tests ON DELETE RESTRICT,
    local_patient_id              uuid NOT NULL REFERENCES local_patients ON DELETE RESTRICT,
    care_episode_id               uuid NOT NULL REFERENCES care_episodes ON DELETE RESTRICT,
    measured_value                numeric NOT NULL,
    direction                     text NOT NULL CHECK (direction IN ('low', 'high')),
    status                        text NOT NULL DEFAULT 'open'
        CHECK (status IN ('open', 'notified', 'escalated', 'acknowledged', 'resolved')),
    escalation_level              smallint NOT NULL DEFAULT 0 CHECK (escalation_level BETWEEN 0 AND 2),
    current_recipient_user_ref_id uuid REFERENCES local_user_refs ON DELETE RESTRICT,
    due_at                        timestamptz NOT NULL,
    acknowledged_at               timestamptz,
    acknowledged_by_user_ref_id   uuid REFERENCES local_user_refs ON DELETE RESTRICT,
    resolution_note               text,
    created_at                    timestamptz NOT NULL DEFAULT now(),
    CHECK ((status IN ('acknowledged', 'resolved')) = (acknowledged_at IS NOT NULL))
);
-- Idempotencia: una alerta por resultado
CREATE UNIQUE INDEX critical_alerts_lab_result_unique ON critical_alerts (lab_result_version_id);
-- Lo que lee el planificador cada minuto
CREATE INDEX critical_alerts_pending_due_index ON critical_alerts (due_at)
    WHERE status IN ('open', 'notified', 'escalated');
```
