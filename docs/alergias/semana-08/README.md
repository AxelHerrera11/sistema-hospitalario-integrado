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
