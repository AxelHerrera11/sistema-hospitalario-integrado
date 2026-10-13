# Signos vitales y alertas por valores anormales — Semana 1

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales
- **Semana:** Semana 1 — Conceptos generales, orientación a objetos y UML
- **Tarea individual:** Modele el proceso «captura de signos vitales, evaluación de umbrales y generación de alerta». Casos de uso: delimitar actores y objetivo; el de actividad debe incluir decisiones, excepciones y resultado; el de secuencia debe mostrar participantes, mensajes, validaciones y respuesta. Mantenga trazabilidad entre los tres diagramas.
- **Entregable / evidencia:** UML de casos de uso y narrativa de alcance.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

---

## Contexto arquitectónico

- **Backend:** Laravel 12 (PHP 8.2+), JWT y RBAC por permisos (`signos_vitales.registrar`, `signos_vitales.ver`).
- **Base de datos HOSPITAL (PostgreSQL):** la tabla `vital_signs` ya existe en la migración `2026_08_11_000401_create_allergy_and_vital_sign_tables.php`:
  - Signos: `temperature`, `heart_rate`, `respiratory_rate`, `systolic_bp`, `diastolic_bp`, `oxygen_saturation`, `weight`, `height` y `glucose`. Se exige **al menos uno** (`vital_signs_measurement_check`).
  - **Rangos de plausibilidad** por `CHECK` (por ejemplo, temperatura entre 25 y 45, FC entre 1 y 300, sistólica ≥ diastólica). Rechazan valores **imposibles**; no son umbrales clínicos.
  - Estados `draft` / `validated` / `entered_in_error`. Un registro `validated` es **inmutable** y se corrige con una nueva versión (`version_no`, `supersedes_id`, `correction_reason`). No se borra (`vital_signs_retention`).
  - El trigger `enforce_vital_sign_integrity` exige que el expediente y el episodio sean del mismo paciente y que **quien registra sea médico con `care_assignment` activo** en el episodio (`vital_signs_author_assignment_trigger`). Por eso el actor principal es el **médico tratante**.
- **Sin tablas nuevas:** este módulo trabaja solo con entidades que ya existen o ya están previstas en el esquema HOSPITAL, sin modificar las de otros módulos:
  - **Umbrales clínicos:** configuración versionada (con fecha de vigencia) detrás de la interfaz `ThresholdPolicy`. El perfil de referencia para adultos está en el [Anexo B de la narrativa](./narrativa-alcance-trazabilidad.md#anexo-b--perfil-de-umbrales-de-referencia-adulto).
  - **Alerta:** se **deriva** del signo vital y del perfil vigente en la fecha de medición. Como el signo validado es inmutable y el perfil está versionado, la alerta siempre se puede reconstruir. No se guarda en una tabla propia.
  - **Traza:** la generación y la notificación de la alerta se registran en `local_access_audits` (HOSPITAL, slice H8), **en la misma conexión `hospital` y la misma transacción** que `vital_signs`. Se reutiliza la propuesta de columnas del [Anexo A de Notas SOAP](../../notas-soap/semana-01/narrativa-alcance-trazabilidad.md#anexo-a--propuesta-de-la-tabla-local_access_audits-hospital).
- **`critical_alert` no se toca:** el esquema la ubica en el slice H7 junto a `lab_result_version`, es decir, en los módulos de laboratorio. El **reconocimiento de alertas** y su estado (`open` / `acknowledged`) quedan fuera de alcance hasta acordar con esos módulos si la entidad puede compartirse. Ver [Coordinación pendiente con otros módulos](./narrativa-alcance-trazabilidad.md#coordinación-pendiente-con-otros-módulos).
- **Notificación:** asíncrona, mediante una cola de Laravel despachada **después del `COMMIT`** (`afterCommit`) detrás de la interfaz `AlertNotifier`.
- **Multitenancy:** el hospital activo llega en `X-Tenant-ID`; el signo vital vive en la base de ese hospital.
- **Frontend:** Vue 3, Pinia y Axios (formulario de signos vitales y centro de notificaciones).
- **Endpoints preliminares** (se formalizan en la Semana 5):
  - `POST /api/v1/care-episodes/{episode}/vital-signs`
  - `GET /api/v1/care-episodes/{episode}/vital-signs` (cada registro incluye su evaluación de umbrales)

---

## 1. Diagrama de casos de uso

![Diagrama de casos de uso](./01-casos-de-uso.svg)

[Abrir el diagrama de casos de uso en SVG](./01-casos-de-uso.svg)

### Descripción

El objetivo es que **todo valor anormal de un signo vital se detecte al registrarlo, quede trazado y llegue a los médicos tratantes**.

- **Actor principal:**
  - *Médico tratante* (con `care_assignment` activo): registra los signos vitales, recibe las alertas y consulta los signos con su evaluación. Se modela solo al médico porque el esquema actual exige que el autor de `vital_signs` sea médico asignado al episodio.
- **Actores secundarios (sistemas):** *Expediente médico* (episodio y expediente), *Perfil de umbrales* (configuración versionada), *Servicio de notificaciones*, *Bitácora de auditoría* y *RBAC*.
- **UC-SV-01 (Registrar signos vitales)** incluye `<<include>>` la evaluación de umbrales (UC-SV-02) y la validación de acceso y médico tratante (UC-SV-06). **UC-SV-03 (Rechazar medición no plausible)** extiende `<<extend>>` a UC-SV-01 cuando un valor está fuera del rango fisiológico posible.
- **UC-SV-04 (Generar alerta clínica)** extiende a UC-SV-02 cuando al menos un valor queda fuera de umbral, e incluye la notificación (UC-SV-05) y el registro de auditoría (UC-SV-07).
- **UC-SV-05 (Notificar alerta)** incluye la auditoría (UC-SV-07) para dejar constancia de la entrega.
- **UC-SV-08 (Consultar signos vitales y su evaluación)** incluye UC-SV-06 con el permiso `signos_vitales.ver`.

## 2. Diagrama de actividades

![Diagrama de actividades](./02-actividad.svg)

[Abrir el diagrama de actividades en SVG](./02-actividad.svg)

### Descripción

Recorre el proceso en tres particiones. Los conectores UML **(B)** y **(C)** muestran que una partición continúa en la siguiente.

- **A · Capturar y validar plausibilidad:** acceso (401/403), formato (422), plausibilidad fisiológica (UC-SV-03, `422 VITAL_SIGN_IMPLAUSIBLE`, no se guarda nada), médico tratante (403) y episodio y expediente abiertos (409). Resultado: medición aceptada, continúa en B.
- **B · Evaluar umbrales y generar alerta:** se busca el perfil de umbrales según la edad del paciente y la fecha de medición.
  - Sin perfil aplicable (por ejemplo, paciente pediátrico): se guarda el signo vital `validated` con la auditoría `signo_vital.sin_evaluacion` y se responde `201` con el aviso `THRESHOLDS_NOT_APPLICABLE`. Nunca se omite la evaluación en silencio.
  - Todos los valores dentro de umbral: `201` sin alerta.
  - Algún valor fuera de umbral: se genera la alerta (severidad, hallazgos y versión del perfil) y, **en una sola transacción** de la conexión `hospital`, se insertan el signo vital y la auditoría `signo_vital.alerta_generada`. Si la auditoría falla, `ROLLBACK` y `500`: **no existe valor anormal sin traza**. Si todo va bien, `COMMIT`, `201` con la alerta y la notificación se encola después del `COMMIT`.
- **C · Notificar:** el job obtiene a los médicos con asignación activa en el episodio y les envía la notificación en la aplicación. Si llega, se audita `signo_vital.alerta_notificada`. Si falla, reintenta con espera exponencial; al agotar los reintentos se registra en el log y el signo anormal sigue marcado en la consulta del episodio.

## 3. Diagrama de secuencia

![Diagrama de secuencia](./03-secuencia.svg)

[Abrir el diagrama de secuencia en SVG](./03-secuencia.svg)

### Descripción

- **Participantes:** Vue (`VitalSignsForm` y `NotificationCenter`), middlewares, `VitalSignController`, `VitalSignService`, `ThresholdPolicy` «interfaz», `VitalSignRepository` (HOSPITAL), `AuditLogger` (HOSPITAL · `local_access_audits`) y `AlertNotifier` «interfaz».
- **Dependencias como interfaz:** `ThresholdPolicy` se modela como interfaz para que el servicio no dependa de dónde vienen los umbrales (archivo de configuración hoy, tabla o servicio clínico después). `AlertNotifier` permite cambiar el canal sin tocar el servicio. Se desarrolla en la Semana 2 con el principio DIP.
- **Escenario A (capturar, evaluar y generar alerta):** `alt` para 401/403 y 422 de formato; `break` para medición no plausible (422), médico no tratante (403) y episodio o expediente cerrado (409); `alt` para perfil no aplicable (201 con aviso), valores normales (201) y valores fuera de umbral. En ese último caso, signo y auditoría van en una transacción: si la auditoría falla, `ROLLBACK` y 500; si no, `COMMIT`, se encola la notificación (`afterCommit`) y se devuelve 201 con la severidad y los hallazgos.
- **Escenario B (notificar):** asíncrono, después del `COMMIT` de A. `alt` para envío fallido (`loop` de reintentos; al agotarse, se registra en el log) y entrega correcta (auditoría `signo_vital.alerta_notificada`).

### Eventos de auditoría del módulo (resumen)

Se escriben en `local_access_audits` con la estructura propuesta en Notas SOAP. El detalle está en el [Anexo A de la narrativa](./narrativa-alcance-trazabilidad.md#anexo-a--alerta-derivada-y-eventos-de-auditoría).

| `action` | Cuándo | `metadata` (solo metadatos) |
|---|---|---|
| `signo_vital.alerta_generada` | Al guardar un registro con valores fuera de umbral | Severidad, hallazgos y versión del perfil |
| `signo_vital.sin_evaluacion` | Al guardar sin perfil aplicable | Edad del paciente |
| `signo_vital.alerta_notificada` | Al entregar la notificación | Destinatarios y severidad |

## 4. Narrativa de alcance y trazabilidad

La narrativa completa, con la tabla de trazabilidad UC ↔ actividad ↔ secuencia, los riesgos detectados en el esquema, la **coordinación pendiente con otros módulos** y el perfil de umbrales de referencia, está en [narrativa-alcance-trazabilidad.md](./narrativa-alcance-trazabilidad.md).

## Fuentes editables

- Casos de uso: el SVG es editable en draw.io o Inkscape.
- Actividad y secuencia: [`fuentes/02-actividad.mmd`](./fuentes/02-actividad.mmd) y [`fuentes/03-secuencia.mmd`](./fuentes/03-secuencia.mmd) (Mermaid; GitHub los renderiza y se pueden editar en mermaid.live).

## Declaración de uso de IA

Se usó Claude (Anthropic) para proponer la estructura de los diagramas, generar los SVG y redactar el primer borrador de este documento a partir del repositorio del proyecto (en particular, la migración de `vital_signs`, el inventario de entidades del esquema HOSPITAL y la entrega de Notas SOAP) y del enunciado de la tarea. El estudiante revisó los actores, las reglas clínicas (plausibilidad frente a umbrales, ningún valor anormal sin traza y falla segura), la decisión de no modificar entidades de otros módulos y la trazabilidad, y es responsable del contenido final.
