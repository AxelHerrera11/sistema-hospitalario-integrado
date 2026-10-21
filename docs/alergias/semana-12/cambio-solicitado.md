# Caso práctico de diseño — cambio solicitado (ASII-12)

**Pedido:** Farmacia pide que el banner muestre **cuándo se verificó por última vez** cada alergia, para desconfiar de registros antiguos.

## Impacto por capa

| Pieza | ¿Cambia? | Qué cambia |
|---|---|---|
| Domain | Sí, mínimo | `Allergy` expone la fecha de verificación (`recorded_at` ya existe en la migración) |
| Application | No | `GetAllergyAlert` ya devuelve las alergias activas |
| Contrato API | Sí, compatible | Campo `verified_at` en cada alergia de `alert.allergies` |
| UI | Sí | «verificada hace 3 años» en texto secundario; si pasa de un umbral, ícono de reloj con texto |
| Accesibilidad | Revisar | La fecha no debe alargar el `role="alert"`: se lee en «Ver todas» |
| Riesgo | Bajo | Que una alergia antigua parezca menos importante: el nivel del banner no cambia por la fecha |

## Cómo lo absorbe el diseño

El cambio entra por una extensión compatible del contrato y por la UI. Las reglas que protegen al paciente (las de la [matriz](./matriz-decision-rf-rnf.md)) no se tocan. Antes de implementarlo hay que actualizar el flujo UX (Semana 8) y repetir el recorrido del prototipo (Semana 11) con el estado nuevo.
