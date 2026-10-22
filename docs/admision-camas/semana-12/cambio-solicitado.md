# Caso práctico de diseño — cambio solicitado (ASII-07)

**Pedido:** Jefatura pide **reservar una cama** durante 10 minutos mientras se termina el papeleo de la admisión.

## Impacto por capa

| Pieza | ¿Cambia? | Qué cambia |
|---|---|---|
| Domain | Sí | Estado nuevo de cama `reserved` con vencimiento; `Admission` acepta una cama reservada por la misma persona |
| Application | Sí | Caso de uso `ReserveBed` y liberación automática al vencer |
| Base HOSPITAL | Sí | Ampliar el `CHECK` de `beds.status` (dueña: Madelin, ASII-06) o una tabla de reservas: se acuerda en el grupo |
| Contrato API | Sí | `POST /api/v1/beds/{bed}/reservations` y `409 BED_RESERVED` |
| UI | Sí | Estado «reservada por usted · 8 min» en el tablero, con texto |
| Riesgo | Alto | Reservas olvidadas que bloquean camas: el vencimiento es obligatorio y el Dashboard (Javier) las muestra |

## Cómo lo absorbe el diseño

El cambio entra por una extensión compatible del contrato y por la UI. Las reglas que protegen al paciente (las de la [matriz](./matriz-decision-rf-rnf.md)) no se tocan. Antes de implementarlo hay que actualizar el flujo UX (Semana 8) y repetir el recorrido del prototipo (Semana 11) con el estado nuevo.
