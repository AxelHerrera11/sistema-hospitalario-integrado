# Formato de hallazgos — revisión técnica formal (Semana 13)

Cada hallazgo se registra con la plantilla de abajo, uno por entrada, en el acta de la reunión. La reunión **clasifica** el hallazgo y asigna responsable; no se discute la solución.

## Plantilla

| Campo | Contenido |
|---|---|
| **ID** | `H-R-NN`, correlativo dentro de la revisión |
| **Ítem** | Número del checklist (`C-NN`) o «fuera del checklist» |
| **Ubicación** | `archivo:línea`, ruta del contrato o tabla; nunca «en general» |
| **Tipo** | Defecto · Omisión · Pregunta · Mejora |
| **Severidad** | Crítico · Mayor · Menor · Sugerencia |
| **Descripción** | Una o dos frases: qué está mal, no cómo arreglarlo |
| **Evidencia** | Cómo reproducirlo o qué línea lo demuestra; comando, entrada y resultado |
| **Impacto** | Qué le pasa al paciente, a la medición o al sistema si no se corrige |
| **Corrección propuesta** | Opcional; se acepta o no después de la reunión |
| **Responsable y fecha** | Quién corrige y para cuándo (obligatorio desde Mayor) |
| **Estado** | Abierto · En corrección · Verificado · Rechazado (con motivo) |
| **Reportado por** | Revisor que lo encontró |

## Severidad

| Nivel | Criterio | Plazo de corrección |
|---|---|---|
| **Crítico** | Puede inventar u ocultar una alerta, guardar un dato clínico incorrecto, perder trazabilidad o exponer una medición a quien no debe; o rompe una regla de dominio | Antes de aprobar la revisión |
| **Mayor** | El comportamiento es incorrecto o contradice el contrato, pero con rodeo o sin daño clínico directo | En el PR de corrección, con responsable y fecha |
| **Menor** | Defecto de claridad, mensaje o cobertura de pruebas sin riesgo clínico | Cuando convenga, sin bloquear |
| **Sugerencia** | Mejora que no es un defecto | Opcional |

## Tipo

- **Defecto:** el artefacto hace algo distinto de lo especificado.
- **Omisión:** falta algo que el contrato o los RF/RNF exigen.
- **Pregunta:** el revisor no puede decidir si es defecto; se convierte en otro tipo o se cierra con la respuesta del autor.
- **Mejora:** funciona, pero se puede simplificar (por ejemplo, una dependencia innecesaria).

## Estados y cierre

`Abierto` → `En corrección` (PR vinculado, de menos de 400 líneas) → `Verificado` (el moderador repite la evidencia y ya no falla). Un hallazgo `Rechazado` exige motivo escrito y la conformidad del revisor que lo reportó. Un Crítico no se cierra sin evidencia repetida.

## Ejemplo diligenciado

Es el punto de atención **P-2** del [checklist](./checklist.md#puntos-de-atención-declarados-por-el-autor), reproducido antes de la reunión con la sonda P-2.

| Campo | Contenido |
|---|---|
| **ID** | H-R-01 (ejemplo) |
| **Ítem** | C-11 (y C-06) |
| **Ubicación** | `src/Application/RecordVitalSigns.php`, llamada a `$this->notifier->notify(...)` después de `$this->vitalSigns->record(...)`; `src/Presentation/VitalSignController.php`, `respond()` |
| **Tipo** | Defecto |
| **Severidad** | Crítico |
| **Descripción** | Un fallo del canal de notificación después del `COMMIT` se traduce a `500 VITAL_SIGN_PERSISTENCE_ERROR`, aunque la medición ya quedó guardada y auditada. |
| **Evidencia** | `php docs/signos-vitales/semana-13/evidencia/sondas.php`: con un `AlertNotifier` que lanza `RuntimeException`, `POST …/vital-signs` con FC 138 responde `500 VITAL_SIGN_PERSISTENCE_ERROR` y el repositorio contiene 2 eventos de auditoría (`signo_vital.registrado` y `signo_vital.alerta_generada`). |
| **Impacto** | El médico lee «no se registró nada», repite el envío y la medición y la alerta quedan duplicadas; además, nadie sabe que la alerta del primer envío no se notificó. |
| **Corrección propuesta** | Capturar el fallo del notificador dentro del caso de uso, registrarlo y devolver `201` con la alerta; reintentar la notificación fuera de la petición (cola con reintentos, RF-SV-07). Sin entidad de base de datos nueva. |
| **Responsable y fecha** | Por asignar en la reunión |
| **Estado** | Abierto |
| **Reportado por** | Autor (punto de atención P-2) |

## Acta

El secretario completa, por cada sesión: fecha, asistentes y roles, artefactos y commits revisados, tiempo de preparación de cada revisor, hallazgos por severidad, decisión (**Aprobada**, **Aprobada con condiciones** o **Re-revisión**) y firmas del moderador y del autor.
