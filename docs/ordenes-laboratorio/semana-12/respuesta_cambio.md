# Respuesta documentada a un cambio solicitado

**Cambio:** «Desde la bandeja quiero seleccionar varias órdenes y cancelarlas de una vez para no
repetir el mismo motivo.»
**Solicitante:** supervisor de laboratorio (escenario de la semana 9).
**Fecha de análisis:** 22 de octubre de 2026.
**Decisión:** **diferir**. No se implementa en la semana 12.

## 1. Situación actual

El módulo permite cancelar **una orden** desde su detalle:

- `POST /api/v1/lab-orders/{labOrderId}/cancel` con `reason` de 5 a 250 caracteres;
- permiso `laboratorio.ordenes.cancelar`;
- transición validada por `LabOrder`; `VALIDATED` y `CANCELLED` no se pueden cancelar;
- cada cancelación agrega un evento con actor, fecha, motivo y `correlation_id`;
- el prototipo exige motivo en un diálogo modal y conserva el historial.

La solicitud nace de una tarea repetitiva real: cancelar varias órdenes del mismo episodio por un
estudio duplicado. Hacerlo una por una es lento y propenso a omitir el motivo.

## 2. Impacto si se aceptara

| Dimensión | Impacto |
|---|---|
| UX | Modo de selección en la bandeja, contador de seleccionadas, barra de acción fija, confirmación con motivo y resumen de resultados |
| Accesibilidad | Checkboxes con etiqueta, orden de foco, anuncio del conteo, confirmación accesible y protección contra activación accidental; objetivos de 44 px |
| Movilidad | La selección múltiple compite con el toque para abrir el detalle; hay que definir un modo explícito y una salida clara |
| Dominio | La máquina de estados no cambia, pero cada orden se valida individualmente; una orden validada no debe bloquear a las demás |
| Contrato | Nueva ruta de lote, validación de tamaño, semántica de éxito parcial y errores por ítem; requiere versión o adición compatible |
| Permisos | Nuevo permiso específico o reutilización de `cancelar`; el lote no puede ampliar el alcance del usuario |
| Persistencia | Una transacción por orden, no una transacción global; el éxito parcial debe ser explícito |
| Trazabilidad | Un evento por orden, todos con el mismo motivo; se propone un `batch_id` compartido, lo que exige migración |
| Riesgos | Cancelación accidental de varias órdenes, abuso de permisos y dificultad para reparar un lote | 

## 3. Opciones evaluadas

| Opción | Descripción | Valor | Riesgo | Resultado |
|---|---|---|---|---|
| A | Cancelación directa desde cada tarjeta, sin abrir el detalle | Menos clics | Alto: toque accidental, sin contexto ni motivo visible | Rechazada |
| B | Modo de selección + confirmación + motivo + resultados por orden | Resuelve la tarea repetitiva con control | Medio: complejidad de contrato, permiso y pruebas | Aceptable con condiciones; diferida |
| C | Importar un CSV de IDs para cancelar | Automatización | Muy alto: errores de archivo, permisos y auditoría | Rechazada para este alcance |

## 4. Por qué se difiere

1. **Riesgo clínico y operativo.** Cancelar es irreversible; un lote multiplica el daño de un error.
2. **Semántica de éxito parcial.** Si una orden está validada, las demás no deben revertirse; hay que
   definir y probar esa respuesta.
3. **Contrato y esquema.** El `batch_id` y el resultado por ítem requieren migración y una revisión
   del OpenAPI que no está en el alcance de esta semana.
4. **Permisos.** Falta confirmar con seguridad si el permiso de cancelación individual debe habilitar
   lotes o si se requiere uno nuevo.
5. **UX móvil.** La selección múltiple necesita un prototipo y una prueba de accesibilidad antes de
   llevarla a la bandeja.

## 5. Contrato propuesto si se acepta en una iteración futura

```http
POST /api/v1/lab-orders/bulk-cancel
X-Tenant-ID: hosp-a
X-User-Ref: medico-1
X-Permissions: laboratorio.ordenes.cancelar
Idempotency-Key: 9f1c...

{
  "order_ids": ["ORD-1042", "ORD-1039"],
  "reason": "Estudio duplicado por error de captura"
}
```

Respuesta `200` con resultado por orden:

```json
{
  "batch_id": "lote-7f3a",
  "results": [
    { "lab_order_id": "ORD-1042", "status": "CANCELLED", "code": null },
    { "lab_order_id": "ORD-1039", "status": "REJECTED", "code": "ORDER_ALREADY_VALIDATED" }
  ]
}
```

Reglas:

- `order_ids`: 1–20, sin duplicados; fuera de rango `422`.
- `reason`: 5–250 caracteres; se aplica a todas las órdenes.
- Permiso `laboratorio.ordenes.cancelar`; sin él, `403` y cero cambios.
- Cada orden se procesa en su propia transacción; no hay rollback global.
- Cada éxito agrega un evento `CANCELLED` con actor, fecha, motivo, `correlation_id` y `batch_id`.
- Cada rechazo devuelve un código estable (`ORDER_ALREADY_VALIDATED`, `INVALID_STATUS_TRANSITION`,
  `ORDER_NOT_FOUND`) sin revelar órdenes de otro tenant.
- El replay con la misma `Idempotency-Key` devuelve el mismo resultado sin duplicar eventos.

No se usa `207 Multi-Status`: un `200` con `results[]` es más simple para el cliente y conserva el
detalle por orden. Los errores de validación global siguen siendo `400/403/422`.

## 6. UX y accesibilidad propuestas

- Botón **Seleccionar** en la bandeja; cada tarjeta muestra un checkbox con etiqueta accesible.
- Barra inferior fija: «2 seleccionadas · Cancelar» y «Salir de selección».
- Confirmación con el número de órdenes, el motivo obligatorio y el aviso de que es irreversible.
- Resultado con dos grupos: canceladas y rechazadas, con enlace a cada orden.
- El foco se mueve al resumen; el conteo se anuncia con `aria-live="polite"`.
- En móvil, la selección es un modo explícito; no se activa con un toque largo ni con deslizar.
- No se permite «seleccionar todas» si el conjunto incluye órdenes validas o canceladas.

## 7. Criterios de aceptación futuros

1. Dos órdenes cancelables se cancelan y generan dos eventos con el mismo motivo.
2. Si una está validada, esa se rechaza y la otra se cancela; no hay rollback global.
3. Sin permiso, la respuesta es 403 y no cambia ninguna orden.
4. Un motivo de menos de 5 caracteres devuelve 422 y no cambia nada.
5. Más de 20 IDs o IDs duplicados devuelven 422.
6. Un ID de otro tenant no se revela y no se modifica.
7. El replay con la misma clave no duplica eventos.
8. El flujo de selección y confirmación es usable con teclado y a 390 px.

## 8. Condiciones para reconsiderarlo

- Aprobación de negocio sobre la política de cancelación en lote.
- Revisión de seguridad y permisos.
- Migración acordada para `batch_id` y versión del contrato.
- Prototipo de selección probado en escritorio y móvil, con revisión de accesibilidad.
- Pruebas de éxito parcial, tenant, permisos, límites e idempotencia.

## 9. Respuesta al solicitante

«Entendemos la necesidad y la consideramos válida. No la implementamos todavía porque una
cancelación masiva sin éxito parcial explícito, permiso propio y auditoría por lote puede causar más
daño que la tarea manual. Mientras tanto, la cancelación individual con motivo sigue disponible y
conserva la trazabilidad. Dejamos el contrato, los riesgos y los criterios de aceptación listos para
una siguiente iteración.»
