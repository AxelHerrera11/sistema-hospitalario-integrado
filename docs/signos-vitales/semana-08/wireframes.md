# Wireframes anotados — Signos vitales y alertas (Semana 8)

Seis pantallas en baja fidelidad del flujo «captura de signos vitales, evaluación de umbrales y generación de alerta». Cada wireframe anota **estados**, **mensajes**, **validaciones** y **reglas de interacción**. Los textos son los de la UI propuesta y los `code` son los del [contrato de la Semana 5](../semana-05/contrato-api.md). Los datos son ficticios.

## 1. Formulario de captura

**Rol:** médico tratante con `signos_vitales.registrar` y asignación activa en el episodio.

```text
┌ Signos vitales · Episodio EP-1 ───────────────── Paciente: María Gómez (45 años) ─
│ Fecha de medición*  [ 2026-10-14 08:30 ]      Marque al menos un signo
│
│ Frecuencia cardiaca   [ 138 ] lpm              Saturación O2  [ 94 ] %
│ Frec. respiratoria    [     ] rpm              Glucosa        [ 5,5 ] [ mmol/L ▾ ]
│ Presión arterial      [     ] / [     ] mmHg   Temperatura    [     ] [ °C ▾ ]
│ Peso                  [     ] kg               Talla          [     ] cm
│
│ Glucosa 5,5 mmol/L = 99,1 mg/dL (se evalúa en mg/dL)
│
│ [ Cancelar ]                                              [ Continuar ]
└─────────────────────────────────────────────────────────────────────────────────
```

| Anotación | Detalle |
|---|---|
| **Estados** | Vacío (sin valores, «Continuar» deshabilitado), con valores, error de validación (el campo se marca y se conserva lo escrito) y en curso. |
| **Mensajes** | Título: «Registre al menos un signo. El sistema lo compara con los umbrales y le avisa si hay un valor anormal.» · Vista previa: «Glucosa 5,5 mmol/L = 99,1 mg/dL». |
| **Validaciones** | Al menos un signo (`VITAL_SIGNS_INVALID`, 422) · números válidos (`PAYLOAD_INVALID`) · fecha `AAAA-MM-DD HH:MM:SS` y no futura (`MEASURED_AT_INVALID`) · valor fisiológicamente posible (`VITAL_SIGN_IMPLAUSIBLE`) · sistólica ≥ diastólica · unidad aceptada para ese signo (`UNIT_NOT_SUPPORTED`). El cliente solo orienta; decide el servidor. |
| **Reglas** | Solo temperatura y glucosa ofrecen selector de unidad; los demás muestran su unidad fija. La unidad por defecto es la canónica (°C, mg/dL). No hay selector de paciente ni de episodio. Los campos llevan `autocomplete="off"` y nada se guarda en `localStorage`. |

## 2. Confirmación antes de registrar

**Rol:** médico tratante, tras «Continuar».

```text
┌ Confirmar registro ───────────────────────────────────────────────────────────
│ Se guardará en el episodio EP-1 · Medido el 14/10/2026 08:30
│
│ Frecuencia cardiaca   138 lpm
│ Saturación O2          94 %
│ Glucosa               5,5 mmol/L  →  99,1 mg/dL
│
│ Se evaluará con el perfil «adulto-2026.1» (ilustrativo, pendiente de validación)
│ Si algún valor está fuera de umbral, se generará una alerta y se notificará
│ a los médicos tratantes del episodio. Esta acción no se puede deshacer.
│
│ [ Corregir ]                                               [ Registrar ]
└─────────────────────────────────────────────────────────────────────────────────
```

| Anotación | Detalle |
|---|---|
| **Estados** | Resumen, en curso («Registrando…» con ambos botones deshabilitados) y error (vuelve al formulario con lo escrito). |
| **Mensajes** | «Esta acción no se puede deshacer»: una medición validada es inmutable y se corrige con una versión nueva. |
| **Validaciones** | Ninguna nueva: es la última oportunidad de ver cada valor con su unidad antes de enviar. |
| **Reglas** | «Corregir» vuelve al formulario sin perder datos. «Registrar» se deshabilita mientras la petición está en curso; el `POST` nunca se reintenta solo. Si la petición no responde: «No sabemos si se guardó: no repita el envío; consulte las mediciones». |

## 3. Resultado con alerta

**Rol:** médico tratante, respuesta `201 ALERT_GENERATED`.

```text
┌ Medición registrada ──────────────────────────────────────────────────────────
│ ■ ALERTA CRÍTICA                                       Perfil: adulto-2026.1
│
│   Frecuencia cardiaca   138 lpm      ■ Crítica
│   Saturación O2          94 %        ▲ Advertencia
│   Glucosa                99,1 mg/dL  Sin alerta
│
│ Se notificó a los médicos tratantes del episodio. La notificación no incluye valores.
│
│ [ Ver medición ]   [ Registrar otra ]                           [ Cerrar ]
│ Código: ALERT_GENERATED · trace_id 3f2a…9c1
└─────────────────────────────────────────────────────────────────────────────────
```

| Anotación | Detalle |
|---|---|
| **Estados** | Éxito con alerta: la severidad global es la máxima de los hallazgos. Hay tres niveles: ■ Crítica, ▲ Advertencia y «Sin alerta». |
| **Mensajes** | «Se notificó a los médicos tratantes del episodio. La notificación no incluye valores.» · «Crítica» y «Advertencia» siempre con texto, no solo con color. |
| **Validaciones** | Los valores se presentan en la unidad canónica, la que usó el servidor para evaluar (glucosa en mg/dL aunque se ingresó en mmol/L). |
| **Reglas** | La alerta crítica no se cierra sola ni con un toast: queda hasta «Cerrar». «Cerrar» no es un reconocimiento (fuera del alcance del módulo). «Registrar otra» limpia el formulario; «Ver medición» navega a la consulta, así recargar no reenvía el `POST`. |

## 4. Resultado sin alerta y «no evaluado»

**Rol:** médico tratante, respuestas `201 OK` y `201 THRESHOLDS_NOT_APPLICABLE`.

```text
┌ Medición registrada ─────────────────────────────────────────────────────────
│ ✓ SIN VALORES FUERA DE UMBRAL                         Perfil: adulto-2026.1
│   FC 80 lpm · Peso 70 kg
│
├───────────────────────────────────────────────────────────────────────────────
│ ? NO EVALUADO                                          Sin perfil aplicable
│   Paciente de 9 años. La medición se guardó, pero NO se comparó con umbrales.
│   Revise los valores usted mismo.
│
│ [ Ver medición ]   [ Registrar otra ]                           [ Cerrar ]
└───────────────────────────────────────────────────────────────────────────────
```

| Anotación | Detalle |
|---|---|
| **Estados** | Dos resultados alternativos de la misma pantalla: `OK` (se evaluó y no hay hallazgos) y `THRESHOLDS_NOT_APPLICABLE` (no se evaluó). |
| **Mensajes** | «Sin valores fuera de umbral» frente a «NO EVALUADO … Revise los valores usted mismo». |
| **Validaciones** | Un signo sin umbral (peso y talla) nunca genera alerta ni error; se lista como «Sin alerta». |
| **Reglas** | «No evaluado» usa un ícono, un color y un texto distintos de «Sin valores fuera de umbral» y nunca se pinta en verde: la ausencia de alerta no significa que el valor sea normal. |

## 5. Errores recuperables y límites de acceso

**Rol:** médico tratante o cualquier rol que llegue a la pantalla.

```text
┌ No se pudo registrar ─────────────────────────────────────────────────────────
│ ⚠ La temperatura ingresada no es un valor posible.      Código: VITAL_SIGN_IMPLAUSIBLE
│   Revise el valor y la unidad. Lo que escribió se conservó.     trace_id 8b1…e07
│
│ [ Corregir ]
└─────────────────────────────────────────────────────────────────────────────────
```

| Código | Mensaje | Qué hace la UI |
|---|---|---|
| `VITAL_SIGN_IMPLAUSIBLE` (422) | «El valor no es posible para este signo. Revise el valor y la unidad.» | Marca el campo, conserva lo escrito y enfoca el campo. |
| `UNIT_NOT_SUPPORTED` (422) | «La unidad elegida no es válida para este signo.» | Devuelve el selector a la unidad canónica. |
| `VITAL_SIGNS_INVALID` / `MEASURED_AT_INVALID` (422) | «Ingrese al menos un signo» / «La fecha de medición no es válida o es futura.» | Error junto al campo. |
| `FORBIDDEN` (403) | «No tiene permiso para registrar signos vitales.» + permiso faltante | Sin botón de reintento. |
| `NOT_TREATING_PHYSICIAN` (403) | «Usted no atiende este episodio; pida al médico tratante.» | Sin reintento ni datos clínicos. |
| `EPISODE_CLOSED` (409) | «El episodio está cerrado: no se pueden registrar mediciones.» | Bloquea el formulario. |
| `VITAL_SIGN_PERSISTENCE_ERROR` (500) | «No se registró nada. Puede reintentar.» | Vuelve al formulario con lo escrito; el reintento es manual. |
| `AUTHORIZATION_UNAVAILABLE` (503) | «No se pudo verificar su permiso. No se registró nada.» | Reintento manual; nunca finge un resultado. |
| Sin respuesta | «No sabemos si se guardó: no repita el envío; consulte las mediciones.» | Ofrece abrir las mediciones; no reintenta. |

| Anotación | Detalle |
|---|---|
| **Estados** | Un panel de error por intento; el formulario conserva lo escrito. |
| **Validaciones** | El mensaje sale del `code`, nunca del texto de la excepción. |
| **Reglas** | `code` y `trace_id` visibles en letra pequeña para que soporte los encuentre en la bitácora. Los mensajes no incluyen valores del paciente. |

## 6. Consulta de una medición

**Rol:** médico tratante (y enfermería cuando tenga asignación) con `signos_vitales.ver`.

```text
┌ Medición · Episodio EP-1 ──────────────────────────────── Solo lectura ───────
│ Medida el 14/10/2026 08:30 · Registrada por: Dra. López
│
│   Frecuencia cardiaca   138 lpm
│   Saturación O2          94 %
│   Glucosa                99,1 mg/dL     (en unidad canónica)
│
│ [ Registrar otra medición ]  (solo médico tratante)
└─────────────────────────────────────────────────────────────────────────────────
```

| Anotación | Detalle |
|---|---|
| **Estados** | Carga (esqueleto gris sin valores), éxito, vacío («Este episodio aún no tiene mediciones registradas»), `404` («La medición no está disponible») y `403`. |
| **Mensajes** | `FORBIDDEN`: «No tiene permiso para ver signos vitales.» · `NOT_TREATING_PHYSICIAN`: «No atiende este episodio; pida al médico tratante.» · `AUTHORIZATION_UNAVAILABLE`: «No se pudo verificar su permiso.» |
| **Validaciones** | La consulta exige permiso y asignación activa; la UI no puede saltarse la segunda. |
| **Reglas** | Los valores se muestran solo en el `200`; en el esqueleto, en los errores y en las vistas previas no aparece ningún valor. El botón «Registrar otra medición» solo se dibuja si el rol tiene `signos_vitales.registrar`: no hay un camino que termine en `403`. Se muestra el autor con nombre legible, no el identificador interno. |
