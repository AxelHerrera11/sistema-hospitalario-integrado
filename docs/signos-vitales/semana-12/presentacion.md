# Presentación breve — Signos vitales y alertas (ASII-13), Semana 12

Seis diapositivas, unos 8 minutos más 4 de recorrido en el prototipo. Cada una indica lo que se proyecta y lo que se dice.

---

## Diapositiva 1 — Tesis

**Se proyecta.** «En un flujo que decide si hay una alerta, el diseño de la pantalla es parte de la regla clínica.» Tres garantías: *ninguna alerta se inventa*, *ninguna se pierde sin que la persona lo sepa*, *ningún dato sale de quien tiene derecho a verlo*.

**Se dice.** Una unidad mal elegida genera una alerta crítica falsa; una coma mal leída cambia la severidad; una conexión caída deja a un paciente crítico sin aviso. No son detalles de interfaz.

---

## Diapositiva 2 — Componentes

**Se proyecta.** Diagrama de la [Semana 7](../semana-07/componentes.md): `VitalSignsCapturePage` → formulario con `MeasurementField` → `AlertResult` → store Pinia → API v1; en el servidor, `Measurement`, `Unit`, `ThresholdProfile`, `VitalSignRule` y `VitalSignRepository`.

**Se dice.** El dominio no sabe de pantallas y la pantalla no sabe de umbrales: la pantalla solo traduce `code` a texto. Por eso se pudo cambiar de unidad sin tocar las reglas.

---

## Diapositiva 3 — UX y accesibilidad

**Se proyecta.** Los cuatro resultados: ■ Crítica, ▲ Advertencia, ✓ Sin valores fuera de umbral y ? No evaluado. Hallazgos críticos de la Semana 9 y su corrección: unidad sin valor por defecto y coma decimal.

**Se dice.** La severidad lleva ícono y texto, no solo color, y «no evaluado» nunca se pinta como «normal». Medí el contraste: 6 de 16 combinaciones típicas no cumplían, y el prototipo usa tonos que sí.

---

## Diapositiva 4 — Movilidad

**Se proyecta.** Captura en tres pasos, barra de acción inferior, confirmación a pantalla completa y tarjetas en lugar de tabla (Semana 10).

**Se dice.** Se diseñó para quien mide de pie, con una mano y señal irregular. Lo urgente va primero: la severidad antes que cualquier valor, y el paciente siempre a la vista.

---

## Diapositiva 5 — Demostración: roles, estados, errores y riesgos

**Se proyecta.** El [recorrido](./README.md#recorrido-navegable) de 11 paradas.

**Se dice.** Se recorre el prototipo: médico y enfermería, la unidad sin valor por defecto, el aviso de `5,5` en mg/dL, la alerta crítica, «no evaluado» y el error crítico: *«No sabemos si se guardó… avise usted directamente… no repita el envío»*. Es un `POST` no idempotente, así que la persona verifica antes de repetir.

---

## Diapositiva 6 — Cambio solicitado y cierre

**Se proyecta.** «Que la unidad de glucosa quede preseleccionada.» Decisión: **aceptado en parte**: se recuerda la última unidad **dentro de la sesión**, marcada «la última que usó: confírmela»; nunca en la primera captura ni entre sesiones. Ver [cambio-solicitado.md](./cambio-solicitado.md).

**Se dice.** Se acepta el objetivo (menos toques) y se rechaza lo que reabriría el riesgo (una unidad por defecto silenciosa). Sin entidad de base de datos nueva y sin cambio de contrato.

---

## Preguntas que preparo

- **¿Por qué no una unidad por defecto?** Porque una unidad asumida es el error que inventa una alerta crítica (glucosa 5,5 mmol/L leída como mg/dL). Recordar la última, con confirmación visible, reduce toques sin asumir.
- **¿Por qué la unidad se recuerda solo en la sesión?** Porque entre turnos o dispositivos otra persona podría heredar una unidad que no es la de su equipo.
- **¿Por qué «no evaluado» no es verde?** Porque no se comparó con umbrales: «sin alerta» sería una afirmación falsa.
- **¿Por qué el error no reintenta solo?** Registrar no es idempotente; reintentar duplicaría la medición y la alerta. La solución de fondo es `Idempotency-Key`, propuesta en la Semana 5.
- **¿Por qué enfermería no ve los controles de captura, ni deshabilitados?** Porque un botón que termina en `403` es una promesa rota. La UI refleja el rol; el servidor decide.
- **¿Qué falta?** Validar umbrales y conversión con un responsable clínico, probar con lector de pantalla y usuarios reales, y la auditoría H8 con Javier.
