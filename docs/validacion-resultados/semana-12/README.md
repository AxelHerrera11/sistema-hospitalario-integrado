# Semana 12 | ASII-20: Defensa de arquitectura y UX

## Recorrido

`Bandeja -> Revisión -> Validación/Rechazo -> Firma -> Auditoría`

El bioquímico revisa el contexto y decide; el técnico recibe rechazo o resultado
validado. Los estados y decisiones quedan asociados a rol, tenant y trazabilidad.

## Matriz decisión -> RF/RNF -> evidencia

| Decisión | Requisito | Evidencia |
|---|---|---|
| Separar revisión y firma | RF: decisión de bioquímico autorizado | Flujo y Semana 9 |
| Exigir rango y unidad | RNF: contexto completo antes de decidir | Responsive y Semana 10 |
| Recuperar error de red | RNF: no duplicar decisión | Prototipo y Semana 11 |
| Registrar rechazo | RF: motivo, firma y estado nuevo | Auditoría documentada |
| Limitar acceso | RNF: tenant y unidad autorizada | Reglas de privacidad |

## Cambio solicitado y respuesta

**Cambio:** rechazar un resultado cuando el valor está fuera de rango, pero la
conexión falla durante la firma.

**Respuesta:** el prototipo conserva comentario, rango y contexto; mantiene el
resultado pendiente y permite reintentar una sola decisión con clave idempotente.
No se muestra `Rechazado` ni se registra firma hasta confirmar la operación.

## Criterios de defensa

- Aprobar exige resultado, unidad, rango, comentario y firma.
- Rechazar exige motivo y muestra la consecuencia antes de confirmar.
- El error conserva la decisión sin duplicar auditoría.
- La interfaz mantiene accesibilidad y protección de datos en desktop/móvil.

## Evidencia

- Requisito Odoo: `Semana 12: Segunda evaluación parcial`.
- Entregable: recorrido navegable, matriz decisión→RF/RNF→evidencia y cambio respondido.
- La defensa conecta validación bioquímica, componentes, UX, movilidad y trazabilidad.
