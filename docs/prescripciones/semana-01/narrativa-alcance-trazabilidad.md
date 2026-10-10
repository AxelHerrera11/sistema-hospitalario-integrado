# Narrativa de alcance y trazabilidad — Prescripciones electrónicas

## Propósito

Que el médico tratante pueda indicar un medicamento de forma electrónica y que el sistema **verifique, antes de guardar y antes de firmar**, si el paciente tiene una alergia activa al principio activo o a su grupo farmacológico. Si la hay, la prescripción se bloquea o, en casos no graves, solo continúa con una justificación clínica que queda auditada.

## Alcance funcional

**Incluido**

- Crear una prescripción en borrador para un paciente con episodio de atención activo: medicamento del catálogo, dosis, vía, frecuencia y duración.
- Verificar alergias activas (`allergies.status = 'active'`) comparando `allergen_code` (o `allergen_name` normalizado) con el principio activo y el grupo del medicamento.
- Bloquear cuando hay conflicto, con estas reglas de severidad:
  - `severe` y `life_threatening`: bloqueo sin excepción.
  - `mild`, `moderate`, `unknown` e intolerancias: excepción posible solo con el permiso `prescripciones.excepcion_alergia` y una justificación escrita.
- Firmar la prescripción: se vuelve a verificar y, una vez firmada, es inmutable (no se edita; se anula y se crea otra).
- Consultar las prescripciones de un paciente (médico y enfermería).
- Auditar bloqueos, excepciones y firmas.

**Fuera de alcance**

- Dispensación y control de inventario en farmacia.
- Interacciones medicamento-medicamento y ajuste de dosis por función renal o peso (posible mejora futura).
- Alta y edición del catálogo de medicamentos: módulo *Catálogo de medicamentos*.
- Registro y edición de alergias: módulo *Alergias clínicas y alerta visual en expediente*.

## Supuestos y restricciones

- Solo prescribe un médico con `care_assignment` activo en un episodio del paciente (la misma regla que el esquema aplica a los signos vitales).
- El catálogo de medicamentos y el registro de alergias deben usar **el mismo código de principio activo**. Si no, la comparación no encontraría coincidencias. Este acuerdo se coordina con los responsables de ambos módulos.
- **Falla segura:** si no se puede consultar el registro de alergias, no se guarda ni se firma (`503`). Nunca se asume que el paciente "no tiene alergias".
- La auditoría guarda metadatos (médico, paciente, medicamento, alergia que coincidió, decisión y justificación), no el expediente completo.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-PRE-01 | Crear prescripción (borrador) | Partición A: "Médico abre al paciente…", "¿Datos completos y válidos?" → "Guardar borrador" → "201 Borrador creado" | 1–2, 4–7, 20–23 |
| UC-PRE-02 | Seleccionar medicamento del catálogo | "Seleccionar medicamento e ingresar dosis…" y "¿Medicamento existe y está activo en el catálogo?" | 12–15 |
| UC-PRE-03 | Verificar alergias activas | A: "Consultar alergias activas…", "¿Registro de alergias disponible?", "¿Alguna alergia activa coincide…?". B: "Re-verificar alergias activas", "¿Registro de alergias disponible?", "¿Hay un conflicto nuevo no cubierto por la excepción registrada?" | 16–20, 24, 29, 51–55, 59 |
| UC-PRE-04 | Bloquear por conflicto alérgico | A: "Bloquear (UC-PRE-04) 422 ALLERGY_CONFLICT". B: "422 ALLERGY_CONFLICT + auditoría; el borrador queda sin firmar" | 24–33, 55–58 |
| UC-PRE-05 | Registrar excepción con justificación | "¿Tiene permiso… y escribió justificación?" → "Registrar excepción…" | 34–37 |
| UC-PRE-06 | Firmar prescripción | Partición B completa: recuperar borrador, validar estado, re-verificar y "Estado signed: inmutable + auditoría de la firma" | 38–64 |
| UC-PRE-07 | Consultar prescripciones del paciente | Reutiliza la validación de acceso (permiso `prescripciones.ver`) | `GET /patients/{patient}/prescriptions` (se detalla en la Semana 5) |
| UC-PRE-08 | Validar acceso y médico asignado | A: "¿JWT, tenant y permiso prescripciones.crear?" y "¿Es médico asignado…?". B: "¿JWT, tenant y permiso prescripciones.firmar?" y "¿Sigue siendo médico tratante…?" | 2–3, 8–11, 39–40, 49–50 |
| UC-PRE-09 | Registrar evento de auditoría | "+ auditoría" en bloqueo, excepción, firma bloqueada y firma | 25, 30, 34, 56, 61 |

## Flujo y resultados

### A · Crear borrador

1. **Acceso (UC-PRE-08):** JWT, tenant y permiso `prescripciones.crear`; sin ellos, `401` o `403` con la traza de RBAC.
2. **Datos de la indicación:** dosis, vía, frecuencia y duración completas y válidas; si no, `422` con los errores por campo y el flujo termina.
3. **Médico tratante (UC-PRE-08):** si no está asignado a un episodio activo del paciente, `403` y el flujo termina.
4. **Medicamento (UC-PRE-02):** debe existir y estar activo en el catálogo; si no, `422` y el flujo termina.
5. **Verificación de alergias (UC-PRE-03):** si el registro no responde, `503` y no se guarda nada.
6. **Sin coincidencias:** se guarda el borrador en estado `draft` (`201`).
7. **Coincidencia grave (UC-PRE-04):** `422 ALLERGY_CONFLICT` con `excepcion_permitida = false`. El frontend muestra una alerta roja que no depende solo del color.
8. **Coincidencia no grave (UC-PRE-04/05):** `422 ALLERGY_CONFLICT` con `excepcion_permitida = true`. Si el médico tiene el permiso y escribe la justificación, se registra la excepción, se audita y se guarda el borrador (`201`).

### B · Firmar un borrador existente

Precondición: existe un borrador en estado `draft`, creado en la partición A.

1. **Acceso (UC-PRE-08):** JWT, tenant y permiso `prescripciones.firmar`; sin ellos, `401` o `403`.
2. **Recuperación del borrador:** si no existe en el tenant, `404`; si ya está firmado o anulado, `409`; si el médico ya no es tratante del episodio, `403`.
3. **Re-verificación de alergias (UC-PRE-03):** las alergias pudieron cambiar desde que se creó el borrador. Si el registro no responde, `503` y **no se firma**.
4. **Conflicto nuevo (UC-PRE-04):** si aparece una alergia activa no cubierta por la excepción registrada, se audita y se responde `422 ALLERGY_CONFLICT`. El conflicto se resuelve sobre el mismo borrador: **sigue en `draft`, sin firmar**, y nunca se crea ni se guarda otro borrador desde la firma.
5. **Firma (UC-PRE-06/09):** sin conflicto nuevo, el estado pasa a `signed`, queda inmutable y se audita (`200`).

## Dependencias con otros módulos

- **Depende de:**
  - *Catálogo de medicamentos* (medicamento, principio activo y grupo).
  - *Alergias clínicas* (alergias activas del paciente; la tabla ya existe).
  - *RBAC* (permisos).
  - *Médicos* y *Expediente* (episodio y asignación; las tablas ya existen).

  Para avanzar sin esperar a los demás módulos, el catálogo y las alergias se consumen a través de las interfaces `MedicationCatalog` y `AllergyChecker`, con una implementación en memoria hasta la integración.
- **Dependen de este módulo:** *Expediente* (mostrar las prescripciones), *Gobernanza* (auditoría de excepciones) y los reportes.

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del módulo](./README.md)
