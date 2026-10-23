# Wireframes anotados — Notas SOAP y diagnósticos (Semana 8)

Seis pantallas en baja fidelidad del flujo «registro de nota SOAP y asociación de diagnóstico clínico». Cada wireframe anota **estados**, **mensajes**, **validaciones** y **reglas de interacción**. Los textos son los que se mapearían desde los `code` del [contrato API](../semana-05/contrato-api.md).

## 1. Editor de nota SOAP (alta en borrador)

**Rol:** médico tratante con `notas_soap.crear` y asignación activa en el episodio.

```text
┌ Nota SOAP · Episodio EP-1 ─────────────────────── Paciente: María Gómez (exp-7734) ┐
│ Estado: Borrador · Autor: usted · No firmado                                        │
│                                                                                     │
│ Subjetivo*  ¿Qué refiere el paciente?   [ Tos de 3 días, sin fiebre                  ]│
│              [                                        ] 0/4000                     │
│ Objetivo*   Hallazgos del examen         [ T 38.4 °C, FR 22/min                     ]│
│              [                                        ] 0/4000                     │
│ Análisis*   Qué se concluye              [ Neumonía del lóbulo inferior derecho      ]│
│              [                                        ] 0/4000                     │
│ Plan*       Qué se hará                  [ Amoxicilina 500 mg c/8h por 7 días      ]│
│              [                                        ] 0/4000                     │
│                                                                                     │
│ Diagnósticos (0)   [ + Buscar diagnóstico ]                                         │
│  (vacío)  «Sin diagnósticos asociados todavía. Una nota sin diagnóstico principal   │
│            no se puede firmar.»                                                    │
│                                                                                     │
│ [ Descartar borrador ]            (guardado automático: no, todavía)  [ Continuar ] │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Estados** | Vacío (formulario sin escribir), en curso («Registrando…», botón deshabilitado), éxito (navega a la vista de la nota), error (se conserva lo escrito) |
| **Mensajes** | «Esta nota documenta la atención del episodio. Queda en borrador hasta que se firme y no se puede editar después.» · vacío de diagnósticos: «Sin diagnósticos asociados todavía…» |
| **Validaciones** | Las cuatro secciones son obligatorias y de máximo 4000 caracteres (`SOAP_SECTION_INVALID`, 422). El contador pasa a ámbar a 3600 caracteres. El botón «Continuar» no se habilita hasta que las cuatro tengan contenido |
| **Reglas** | El texto vive en la memoria de la página: no se guarda en `localStorage` ni en la URL. «Descartar borrador» pide confirmación: «Se perderá lo escrito. Esta acción no se puede deshacer.» El paciente y el episodio salen del contexto: no hay selector que permita cambiarlos |

## 2. Buscador y asociación de diagnósticos (CIE-10)

**Rol:** médico tratante con `notas_soap.asociar_diagnostico`, autor de la nota en borrador.

```text
┌ Buscar diagnóstico ─────────────────────────────── Nota EP-1 (borrador) ──────────┐
│ Código o descripción  [ J18                ] [Buscar]                              │
│                                                                                    │
│  J18.9  Neumonía, no especificada         Activo      [ primary | secondary | diff ]│
│  J44.1  EPOC con exacerbación aguda       Activo      [ primary | secondary | diff ]│
│  E11.9  Diabetes mellitus tipo 2          Activo      [ primary | secondary | diff ]│
│                                                                                    │
│ Resultado seleccionado: J18.9 · Neumonía, no especificada · Tipo: ( ) Principal    │
│                                                      (•) Secundario  ( ) Diferencial│
│                                          [ Cancelar ]  [ Asociar a la nota ]        │
└────────────────────────────────────────────────────────────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Estados** | Buscando, sin resultados («Ningún código coincide»), catálogo caído, asociando, éxito («Diagnóstico asociado») y duplicado |
| **Mensajes** | ayuda del buscador: «Busque por código (J18.9) o por descripción. El tipo define el papel del diagnóstico: principal, secundario o diferencial.» · ayuda del tipo: «Solo puede haber un diagnóstico principal por nota.» |
| **Validaciones** | Código vacío o tipo fuera de los tres → `DIAGNOSIS_INVALID` (422). Código inexistente o inactivo en el catálogo → `DIAGNOSIS_CODE_INVALID` (422). Código ya asociado → `DIAGNOSIS_DUPLICATED` (409). Segundo principal → `PRIMARY_DIAGNOSIS_EXISTS` (409) |
| **Reglas** | La descripción la trae el catálogo, nunca el usuario. El tipo viene por defecto «secundario» y el principal se marca a mano. Si el catálogo está caído, el buscador se bloquea con «El catálogo CIE-10 no está disponible. Intente de nuevo.» y **no** se permite escribir el código a mano: nunca se muestra «sin diagnósticos» cuando lo que hay es «catálogo caído» |

## 3. Confirmación antes de registrar

**Rol:** médico tratante, después de completar las cuatro secciones.

```text
┌ Confirmar registro de nota ─────────────────────────────────────────────────────────┐
│ Se guardará en el episodio EP-1 como BORRADOR.                                      │
│                                                                                    │
│ Subjetivo   Tos de 3 días, sin fiebre                                              │
│ Objetivo    T 38.4 °C, FR 22/min                                                   │
│ Análisis    Neumonía del lóbulo inferior derecho                                   │
│ Plan        Amoxicilina 500 mg c/8h por 7 días                                    │
│ Diagnósticos  (ninguno todavía)                                                    │
│                                                                                    │
│ «Un borrador se puede seguir editando. Firmarla la vuelve inmutable.»              │
│                              [ Volver a editar ]        [ Registrar nota ]         │
└────────────────────────────────────────────────────────────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Estados** | Confirmación, y tras registrar: éxito con «Nota registrada (borrador)», `clinical_note_id` y `trace_id` |
| **Mensajes** | éxito: «Nota registrada como borrador» + `trace_id` en letra pequeña. Sin diagnósticos: «Todavía no tiene diagnósticos: una nota sin diagnóstico principal no se puede firmar» |
| **Validaciones** | Si el servidor responde `422`, el error vuelve al campo correspondiente del editor, no a esta pantalla. Si responde `409 NOTE_SIGNED` (alguien firmó la nota mientras tanto): «La nota fue firmada mientras la editaba: ya no se puede modificar» |
| **Reglas** | «Registrar nota» se deshabilita mientras la petición está en curso. El POST no se reintenta: si no hay respuesta, se muestra «No sabemos si se guardó: no repita el envío; busque la nota» |

## 4. Lectura de la nota en el expediente (solo lectura)

**Rol:** médico tratante o enfermería con `notas_soap.ver` **y** asignación activa.

```text
┌ Nota SOAP · EP-1 ──────────────────────────── Paciente: María Gómez (exp-7734) ───┐
│ ● Borrador · Autor: Dra. López · 2 diagnósticos        [ Continuar editando ]    │
│                                                                                    │
│ Subjetivo   Tos de 3 días, sin fiebre                                              │
│ Objetivo    T 38.4 °C, FR 22/min                                                   │
│ Análisis    Neumonía del lóbulo inferior derecho                                   │
│ Plan        Amoxicilina 500 mg c/8h por 7 días                                    │
│                                                                                    │
│ Diagnósticos                                                                     │
│  J18.9  Neumonía, no especificada            Principal                             │
│  E11.9  Diabetes mellitus tipo 2              Secundario                            │
└────────────────────────────────────────────────────────────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Estados** | Carga (esqueleto en gris, sin texto clínico provisional), con nota, vacío (sin nota en el episodio), error de autorización |
| **Mensajes** | vacío: «Este episodio aún no tiene nota SOAP» (es un `200` con lista vacía, no un error). Sin asignación: «No atiende este episodio; pida al médico tratante» (`NOT_TREATING_PHYSICIAN`). Sin permiso: «No tiene permiso para ver notas SOAP» |
| **Validaciones** | No aplica: esta pantalla no escribe. «Continuar editando» solo aparece si la nota está en borrador **y** el actor es el autor tratante |
| **Reglas** | Solo se listan diagnósticos `active`. El texto clínico no se copia al portapapeles por defecto ni se guarda en `localStorage`. La pantalla de un episodio sin asignación no muestra el texto clínico en ningún momento |

## 5. Errores recuperables (panel)

**Rol:** cualquiera, con el permiso de la acción que se está intentando.

```text
┌ No se pudo asociar el diagnóstico ─────────────────────────────────────────────────┐
│ El código J18.9 ya está asociado a esta nota.                                      │
│ [ DIAGNOSIS_DUPLICATED ]                                                            │
│ Acción sugerida: elija otro código o cambie el tipo a secundario.                  │
│                                                                                    │
│ Diagnósticos de la nota: J18.9 · Principal                                          │
│                              [ Ver la nota ]        [ Elegir otro código ]         │
│ Referencia de soporte: trace_id 7f3c1d92-0b41-4a7e-9c1f-2a5b7e0d4c33               │
└────────────────────────────────────────────────────────────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Estados** | Recuperable (el usuario reintenta con un cambio), no recuperable (500 o sin respuesta) |
| **Mensajes** | `DIAGNOSIS_DUPLICATED`: «El código ya está asociado a esta nota» · `PRIMARY_DIAGNOSIS_EXISTS`: «La nota ya tiene un diagnóstico principal» · `NOTE_SIGNED`: «La nota está firmada: para corregirla se crea una versión nueva con su motivo» · `ICD10_UNAVAILABLE`: «El catálogo CIE-10 no está disponible» · `AUTHORIZATION_UNAVAILABLE`: «No se pudo verificar su permiso. No se registró nada» · `SOAP_PERSISTENCE_ERROR`: «No se registró nada; reintente» |
| **Validaciones** | Los `422` (sección vacía o muy larga, código inválido, tipo inválido, tipo de dato incorrecto) se muestran **junto al campo**, no en este panel |
| **Reglas** | El panel nunca muestra el detalle interno de la excepción ni texto clínico. El `trace_id` se muestra siempre, en letra pequeña. En `500` y sin respuesta la UI **no** reintenta sola: el alta no es idempotente |

## 6. Autoría clínica, versionado y descarte (qué ve cada rol)

**Rol:** comparativa de la misma nota en tres situaciones.

```text
┌ Nota EP-1 · Firmada el 12/10/2026 ─────────────────────────────────────────────────┐
│ ● FIRMADA · Versión 1 · Autora: Dra. López                                        │
│ «Esta nota está firmada. No se puede editar: para corregirla se crea una versión  │
│  nueva con su motivo.»                                                             │
│ [ Sin acciones de edición ]                                                        │
└────────────────────────────────────────────────────────────────────────────────────┘

┌ Misma nota, vista por otro médico con notas_soap.ver ─────────────────────────────┐
│ ● Borrador · Autora: Dra. López (no usted)                                         │
│ «Solo lectura: usted no es el tratante de esta nota.»                             │
│ [ Sin acciones de edición ]                                                        │
└────────────────────────────────────────────────────────────────────────────────────┘

┌ Descartar borrador ───────────────────────────────────────────────────────────────┐
│ Se perderá lo escrito y no se puede deshacer.                                      │
│                                      [ Cancelar ]        [ Descartar ]              │
└────────────────────────────────────────────────────────────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Estados** | Borrador, firmada, vista ajena, confirmación de descarte |
| **Mensajes** | «Esta nota está firmada. No se puede editar: para corregirla se crea una versión nueva con su motivo.» · «Solo lectura: usted no es el tratante de esta nota.» · «Se perderá lo escrito y no se puede deshacer.» |
| **Validaciones** | No aplica: son mensajes de estado. El descarte solo aparece si hay algo escrito |
| **Reglas** | La autoría se muestra con nombre legible, nunca el `author_user_ref_id` crudo. Cuando exista el traspaso de autoría (petición a Javier de la Semana 6), la pantalla distinguirá «autora original» de «autor vigente», y ninguno de los dos se puede editar desde la UI. La versión se muestra como cadena («versión 2, corrección de la versión 1»), nunca como un campo editable |
