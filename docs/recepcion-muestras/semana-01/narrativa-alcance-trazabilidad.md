# Narrativa de alcance y trazabilidad — Recepción de muestras

## Propósito

Garantizar que toda muestra que llega al laboratorio corresponda **inequívocamente** a un paciente y a un ítem de una orden. Si cumple los requisitos, recibe un código identificador único. Si no, se rechaza con un motivo controlado, se registra quién y cuándo, y se avisa al solicitante para que se tome una nueva muestra.

## Alcance funcional

**Incluido**

- Localizar la orden y el ítem de laboratorio que esperan muestra (por escaneo o búsqueda).
- Verificar la identidad con **dos identificadores**: nombre completo y fecha de nacimiento o código de paciente, comparados con el paciente de la orden.
- Validar los requisitos de la prueba: tipo de muestra, contenedor, volumen mínimo, tiempo máximo desde la toma (estabilidad) y temperatura de transporte.
- Aceptar la muestra y generar el código identificador.
  - Formato propuesto: `<hospital>-<AAMMDD>-<secuencia>-<dígito verificador>`, por ejemplo `H01-261009-00042-7`.
  - El dígito verificador detecta errores de lectura o digitación.
  - El código es único por hospital y **nunca se reutiliza**.
- Imprimir y reimprimir la etiqueta. Una reimpresión no genera otro código.
- Rechazar con un motivo del catálogo:
  - hemolizada
  - coagulada
  - volumen insuficiente
  - contenedor incorrecto
  - sin etiqueta o identificación discordante
  - fuera de tiempo de estabilidad
  - temperatura inadecuada
  - derramada
- Notificar el rechazo al médico solicitante.
- Consultar una muestra por código, por ejemplo desde *Ingreso de resultados*.

**Fuera de alcance**

- La toma de la muestra y la creación de la orden: módulo *Órdenes de laboratorio desde EMR*.
- Definir los requisitos de cada prueba: módulo *Catálogo de pruebas de laboratorio*.
- Procesar la muestra, ingresar y validar resultados: módulos *Ingreso de resultados* y *Validación por bioquímico*.

## Supuestos y restricciones

- Solo se recibe contra un ítem en estado "pendiente de muestra". Un ítem cancelado o con muestra ya aceptada responde `409`.
- La identidad discordante **siempre** se rechaza: es el error más peligroso del laboratorio y no admite excepción.
- El código se genera solo al aceptar. El rechazo queda registrado contra el ítem de la orden, con motivo, técnico y hora.
- Si la notificación falla, el rechazo ya está guardado y la notificación queda en cola de reintento. El registro nunca depende del canal de aviso.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-MUE-01 | Recibir muestra | "Técnico escanea o busca la orden…" (inicio del flujo) | 1–2, 4–6 |
| UC-MUE-02 | Localizar orden e ítem | "¿Orden e ítem existen y esperan muestra?" | 7–12 |
| UC-MUE-03 | Verificar identidad (dos identificadores) | "Verificar identidad…" y "¿La etiqueta coincide con la orden?" | 13 |
| UC-MUE-04 | Validar requisitos de la muestra | "Validar requisitos contra el catálogo…" y "¿Cumple todos los requisitos?" | 14–16 |
| UC-MUE-05 | Aceptar y generar código | "Generar código…", "¿Código único?", "Guardar muestra accepted", "Imprimir etiqueta" | 26–35 |
| UC-MUE-06 | Rechazar muestra con motivo | "Elegir motivo…" / "Motivo obligatorio…" → "Registrar rechazo" | 17–18, 23–25 |
| UC-MUE-07 | Consultar muestra por código | Fuera del flujo de recepción: lo usa *Ingreso de resultados* | Nota final (`GET /lab/specimens/{código}`) |
| UC-MUE-08 | Notificar rechazo al solicitante | "Notificar al solicitante…" y "¿Notificación entregada?" | 19–22 |
| UC-MUE-09 | Validar acceso (rol y tenant) | "¿Rol con permiso muestras.recibir…?" | 2–3 |

## Flujo y resultados

1. **Acceso (UC-MUE-09):** JWT, tenant y permiso `muestras.recibir`; si faltan, `401` o `403` con la traza de RBAC.
2. **Orden (UC-MUE-02):** si el ítem no existe, `404`; si está cancelado o ya tiene muestra aceptada, `409`.
3. **Identidad (UC-MUE-03):** se comparan dos identificadores. Si no coinciden, se rechaza con el motivo "identificación discordante".
4. **Requisitos (UC-MUE-04):** el validador del tipo de muestra compara lo observado con lo que exige el catálogo. Si algo falla, se rechaza con el motivo correspondiente.
5. **Rechazo (UC-MUE-06/08):** se guarda el rechazo, el ítem vuelve a "pendiente de nueva muestra" y se notifica al solicitante. Respuesta: `201` con `estado = rejected` y el motivo.
6. **Aceptación (UC-MUE-05):** se genera el código, hasta 3 intentos si choca con uno existente. Se guarda la muestra `accepted` con técnico y hora, se marca el ítem como recibido y se imprime la etiqueta. Respuesta: `201` con `estado = accepted` y el código.
7. **Excepción:** sin código único tras 3 intentos se responde `500` y la muestra no se acepta.

## Dependencias con otros módulos

- **Depende de:** *Órdenes de laboratorio* (ítem y paciente), *Catálogo de pruebas* (requisitos), *Alertas críticas y notificaciones* (aviso de rechazo) y *RBAC*. Para avanzar sin esperar, se consumen a través de las interfaces `LabOrderGateway`, `LabTestRequirements` y `Notifier`, con implementación en memoria hasta la integración.
- **Dependen de este módulo:** *Ingreso de resultados* y *Validación por bioquímico*, que solo trabajan sobre muestras `accepted` identificadas por su código.

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del módulo](./README.md)
