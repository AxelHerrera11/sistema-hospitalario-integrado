# Alergias clínicas y alerta visual en expediente — Semana 8: flujo UX por rol

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Entrega:** user flow, wireframes iniciales y reglas de interacción.

## Objetivo

Diseñar la experiencia del flujo «registro de alergia y emisión de alerta al consultar el expediente» para cada rol autorizado, con estados vacío, carga, éxito y error recuperable, y mensajes que usan los códigos reales del contrato API.

## Roles

| Rol | Objetivo en el flujo | Permiso |
|---|---|---|
| Médico | Ver el banner al abrir el expediente y registrar una alergia | `alergias.ver` · `alergias.registrar` |
| Enfermería | Ver el banner antes de administrar un medicamento | `alergias.ver` |
| Prescripciones (ASII-15) | Consultar el nivel antes de firmar una receta | `alergias.ver` |

## User flow

```mermaid
flowchart TD
  start([Abre el expediente del paciente]) --> load[Banner en estado de carga]
  load --> resp{Respuesta de allergy-alert}
  resp -- critical --> red[Banner rojo fijo: «ALERGIA GRAVE: verificar antes de prescribir»]
  resp -- warning --> amber[Banner ámbar: alergias registradas]
  resp -- none --> green[Texto discreto: «Sin alergias registradas»]
  resp -- unavailable o sin red --> grey[Banner gris: «Alergias no disponibles: verificar con el paciente»]
  red & amber & green & grey --> add{¿alergias.registrar?}
  add -- Sí --> form[Formulario «Registrar alergia»]
  form --> save[Guardar] --> r2{Respuesta}
  r2 -- 422 --> fix[Error junto al campo]
  fix --> form
  r2 -- 409 --> dup[«Ya hay una alergia activa equivalente»: ver la existente]
  r2 -- 201 --> ok[Banner actualizado sin recargar la página]
```

## Wireframes iniciales (baja fidelidad)

### 1. Expediente con banner crítico

```text
┌ Expediente · Paciente P-DEMO · 45 años ────────────────────────────────┐
│ ████ ALERGIA GRAVE: verificar antes de prescribir ████████████████████ │
│ ████ Penicilina · amenaza la vida · anafilaxia            [Ver todas] █ │
│ Resumen │ Notas │ Órdenes │ Alergias (1)                                 │
└────────────────────────────────────────────────────────────────────────┘
```

### 2. Banner cuando el servicio no responde

```text
┌────────────────────────────────────────────────────────────────────────┐
│ ▒▒ Alergias no disponibles: verificar con el paciente antes de        │
│ ▒▒ prescribir.                                         [Reintentar]  │
└────────────────────────────────────────────────────────────────────────┘
```

### 3. Formulario «Registrar alergia»

```text
┌ Registrar alergia ─────────────────────────────────────────────────────┐
│ Alérgeno* [Penicilina______________] (código opcional [RXN-7980])       │
│ Tipo* (•) Alergia ( ) Intolerancia ( ) Desconocido                     │
│ Severidad* [Amenaza la vida ▼]  Reacción [Anafilaxia_______]            │
│ ⚠ Ya hay una alergia activa equivalente (ALLERGY_ALREADY_ACTIVE)  [Ver] │
│                                               [Cancelar] [Guardar]     │
└────────────────────────────────────────────────────────────────────────┘
```

## Estados de la pantalla

| Estado | Qué ve el usuario |
|---|---|
| Carga | Banner neutro «Cargando alergias…»; **nunca** «Sin alergias» mientras carga |
| `none` | Texto discreto «Sin alergias registradas», sin color de alarma |
| `warning` | Banner ámbar con la lista |
| `critical` | Banner rojo fijo arriba del expediente, con `role="alert"` |
| `unavailable` o sin red | Banner gris «Alergias no disponibles…» con «Reintentar» |
| Duplicado (409) | Aviso con enlace a la alergia existente |
| Éxito (201) | El banner se actualiza en el momento |

## Reglas de interacción

- El banner crítico no se puede cerrar ni ocultar; queda fijo mientras se navega el expediente.
- El estado de carga y el de error **nunca** se muestran como «Sin alergias» (falla segura del contrato).
- La severidad se elige de una lista con textos clínicos («Amenaza la vida»), no con los códigos internos.
- El botón «Registrar alergia» solo aparece con `alergias.registrar`.

Los wireframes se evalúan en la Semana 9 (usabilidad y accesibilidad) y se adaptan a móvil en la Semana 10. Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
