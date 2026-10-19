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

## Wireframes iniciales (baja fidelidad)

### 1. Bandeja de alertas

```text
┌ Alertas críticas pendientes (2) ──────────────────── Dra. López (ficticia) ┐
│ 🔴 GLU-v1 32 mg/dL (bajo el umbral crítico) · P-DEMO · vence en 12 min [Abrir]│
│ 🔴 K 7,1 mmol/L (sobre el umbral crítico) · P-0002 · vence en 3 min   [Abrir]│
└──────────────────────────────────────────────────────────────────────────────┘
```

### 2. Lectura de verificación

```text
┌ Confirmar lectura ───────────────────────────────────────────────────────┐
│ Paciente: P-DEMO   Prueba: Glucosa (GLU-v1)                              │
│ Escriba el valor que leyó*: [32___] mg/dL                                │
│ ⚠ El valor no coincide con la alerta (READ_BACK_MISMATCH)                │
│                                         [Cancelar] [Acusar recibo]      │
└──────────────────────────────────────────────────────────────────────────┘
```

### 3. Alerta ya acusada por otra persona

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ ℹ Esta alerta ya la acusó dr.ruiz a las 08:07 (ALERT_ALREADY_ACKNOWLEDGED).│
│                                                       [Volver a bandeja] │
└──────────────────────────────────────────────────────────────────────────┘
```
