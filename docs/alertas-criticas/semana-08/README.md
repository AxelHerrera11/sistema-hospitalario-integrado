# Alertas críticas y notificaciones internas — Semana 8: flujo UX por rol

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-21 · **Entrega:** user flow, wireframes iniciales y reglas de interacción.

## Objetivo

Diseñar la experiencia del flujo «detección, envío, acuse y escalamiento de una alerta crítica» para cada rol autorizado, con estados vacío, carga, éxito y error recuperable, y mensajes que usan los códigos reales del contrato API.

## Roles

| Rol | Objetivo en el flujo | Permiso |
|---|---|---|
| Médico tratante | Ver la alerta, confirmar la lectura y acusar recibo; luego registrar la acción | `alertas.ver` · `alertas.acusar` · `alertas.cerrar` |
| Jefatura de servicio | Recibir las alertas escaladas o sin médico asignado | `alertas.ver` · `alertas.acusar` |
| Enfermería de sala | Ver la bandeja de la sala y avisar al médico | `alertas.ver` |

## User flow

```mermaid
flowchart TD
  start([Llega una alerta crítica]) --> badge[Contador rojo en el menú + aviso sonoro]
  badge --> inbox[Bandeja: pendientes ordenadas por vencimiento]
  inbox --> open[Abre la alerta: paciente, prueba, valor y minutos restantes]
  open --> rb[Diálogo de lectura de verificación: escribir el valor leído]
  rb --> ack[Acusar recibo] --> resp{Respuesta}
  resp -- 422 READ_BACK_MISMATCH --> again[«El valor no coincide: vuelva a leer la alerta»]
  again --> rb
  resp -- 403 NO_CARE_RELATION --> rel[«No tiene relación de cuidado con este paciente»]
  resp -- 409 --> done[«Ya la acusó otra persona» con quién y cuándo]
  resp -- 200 --> stop[Escalamiento detenido] --> close[Registrar la acción clínica y cerrar · propuesto]
  inbox -- vence sin acuse --> esc[La alerta sube a la jefatura y cambia a «escalada»]
```
