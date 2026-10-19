# Catálogo de pruebas de laboratorio — Semana 8: flujo UX por rol

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-17 · **Entrega:** user flow, wireframes iniciales y reglas de interacción.

## Objetivo

Diseñar la experiencia del flujo «alta y consulta de prueba con requisitos de muestra y vigencia» para cada rol autorizado, con estados vacío, carga, éxito y error recuperable, y mensajes que usan los códigos reales del contrato API.

## Roles

| Rol | Objetivo en el flujo | Permiso |
|---|---|---|
| Jefatura de laboratorio | Registrar una versión nueva de una prueba con su muestra, rangos y vigencia | `laboratorio.catalogo.administrar` |
| Médico / Órdenes (ASII-16) | Encontrar la prueba vigente y ver qué muestra se toma | `laboratorio.catalogo.ver` |
| Técnico de laboratorio | Consultar los requisitos de muestra antes de recibirla | `laboratorio.catalogo.ver` |

## User flow

```mermaid
flowchart TD
  start([Abre «Catálogo de pruebas»]) --> perm{¿laboratorio.catalogo.ver?}
  perm -- No --> d403[Pantalla 403: sin permiso, volver al inicio]
  perm -- Sí --> list[Lista de pruebas vigentes hoy + buscador]
  list --> search[Busca «gluc»] --> res{¿Resultados?}
  res -- No --> empty[Estado vacío: «Sin pruebas vigentes para “gluc”» + limpiar filtro]
  res -- Sí --> detail[Detalle: muestra, recipiente, ayuno, rangos y versión]
  list --> admin{¿laboratorio.catalogo.administrar?}
  admin -- Sí --> form[Formulario «Nueva versión»]
  form --> save[Guardar] --> resp{Respuesta}
  resp -- 422 --> fix[Error junto al campo, sin borrar lo escrito]
  fix --> form
  resp -- 409 --> overlap[«La versión 1 de GLU sigue vigente: cierre su vigencia primero»]
  resp -- 201 --> ok[«GLU v2 registrada, vigente desde 01/11»] --> list
```
