# Guion de la segunda evaluación parcial — Médicos y especialidades (ASII-04)

Doce minutos de exposición y cuatro minutos de preguntas. Cada diapositiva indica qué se abre y qué se dice.

## Diapositiva 1 — Tesis (1 min)

- El flujo «registro de médico, asociación de especialidad y consulta de disponibilidad» se recorre entero con un rol y sin backend.
- Tres decisiones: misma regla para los tres roles, la UI no decide reglas de negocio y el error es un estado de la pantalla.
- Se abre: `semana-11/prototipo/index.html`.

## Diapositiva 2 — Componentes (2 min)

- `AvailabilityPage.vue` pinta `status` y `slots`; el estado (`SCHEDULE_NOT_CONFIGURED`) lo decide `DoctorDayAvailability` en el dominio.
- `QueryAvailability` solo orquesta: especialidad → médicos vigentes → horario y horas ocupadas → dominio.
- `DbAppointmentReader` separado de `EloquentDoctorRepository`: un cambio en el esquema de Citas no toca el repositorio de médicos.
- `DoctorScheduleProvider` es un puerto porque `doctor_schedules` no existe todavía.
- Evidencia: `semana-07/README.md` y el diagrama de componentes.

## Diapositiva 3 — UX por rol (2 min)

- Administrador: alta en cuatro pasos, asociación de especialidad y ficha con las vigencias.
- Recepcionista: especialidad y fecha, tarjetas por médico, franjas libres.
- Médico: «Mi disponibilidad», sin filtro por especialidad.
- Lo que el rol no puede hacer no aparece; el acceso directo responde 403.
- Demostración: `?rol=admin#medicos`, `?rol=recepcion#disponibilidad`, `?rol=medico#disponibilidad` y `?rol=recepcion#asociar` (403).

## Diapositiva 4 — Estados (1 min)

- Vacío (`NO_DOCTORS_FOR_SPECIALTY`), médico sin horario (`SCHEDULE_NOT_CONFIGURED`), carga, confirmación y éxito.
- El médico sin horario **aparece**: ocultarlo haría creer a Citas que no existe.
- Demostración: especialidad Pediatría y el Dr. Beto Ruiz.

## Diapositiva 5 — Errores (2 min)

- 409 `LICENSE_ALREADY_REGISTERED`, 409 `PRIMARY_SPECIALTY_CONFLICT`, 409 `SPECIALTY_ALREADY_ASSOCIATED`, 422 `PAYLOAD_INVALID`.
- El alta no responde: «No sabemos si se guardó» y «Buscar licencia» antes de repetir.
- La consulta no responde: «No se pudo consultar» y «Reintentar»; el reintento automático es uno y el alta nunca se reintenta.
- Demostración: `?rol=admin&sim=licencia&demo=alta` y `?rol=admin&sim=sin-respuesta&demo=alta`.

## Diapositiva 6 — Error crítico: conflicto de agenda (2 min)

- La recepcionista elige una franja y, mientras decide, otra cita la toma.
- Qué hace el prototipo: vuelve a consultar al elegir, avisa «08:00 ya no está libre» con `role="alert"` y el `trace_id`, retira la franja de la lista y limpia la selección.
- Por qué es el error más grave: la recepcionista promete al paciente una hora que ya no existe (H2 de la Semana 9).
- Lo que queda abierto: que Citas confirme con el mismo código. El aviso de la UI no sustituye la garantía del servidor.
- Demostración: `?rol=recepcion&sim=tomada&demo=elegir#disponibilidad`.

## Diapositiva 7 — Accesibilidad (2 min)

- Las franjas son un `radiogroup` con nombre accesible por médico (H3); los errores se anuncian con `role="alert"` y el estado con `role="status"`.
- Foco visible de 3 px en todo control (H9) y objetivos táctiles de 44 px.
- Contraste **medido**: 16 pares de color, y el hallazgo H10 — el indicador de foco estaba en 2,01:1 y no cumplía 1.4.11 — se corrigió a `#b45309` (4,69:1) antes de la defensa.
- Zoom al 200 % y reflow a 640 y 320 px: sin desborde horizontal.
- Evidencia: `semana-12/evidencia-medicion.md` y los dos guiones de `semana-12/evidencia/`.

## Diapositiva 8 — Movilidad (1 min)

- Un dato clave por línea, sin tablas; alta en cuatro pasos con validación por paso; acción principal fija abajo; barra inferior en lugar de menú lateral.
- Sin conexión: se ve la última consulta con su hora y no se puede elegir franja.
- El diseño se hizo entre 320 y 430 px y el prototipo se verificó a 320 px sin desborde.

## Diapositiva 9 — Riesgos (1 min)

- Conflictos de agenda: mitigado en la UI, abierto en el servidor.
- `doctor_schedules` y el slice H8: dependen de otros módulos.
- Ausencias de médico (`DOCTOR_ON_LEAVE`): caso práctico ya defendido en la Semana 6.
- Permisos `medicos.*` en RBAC, cierre de asociaciones y RF-MED-06 de un solo día: abiertos y declarados.

## Diapositiva 10 — Qué demuestra la evidencia (1 min)

- 27 comprobaciones sobre el DOM real del prototipo: 27 cumplen (roles 8, estados 4, errores 6, accesibilidad 6, tamaño y reflow 3).
- Los guiones son reproducibles y leen el archivo del prototipo; no dependen de un backend ni de un paquete externo.

## Diapositiva 11 — Cierre (30 s)

- Entregado: componentes defendidos, flujo por rol, errores con acción, accesibilidad medida y movilidad verificada.
- Abierto y declarado: horario real, auditoría, permisos y ausencias.

## Preguntas que preparo

- **¿Por qué el prototipo no es otra pantalla del HIS?** El diseño de la Semana 7 y las reglas de la Semana 10 ya estaban decididos; faltaba poder mostrar el comportamiento sin backend. Con un solo HTML se defienden los tres roles, los estados y los errores en un minuto.
- **¿Cómo sé que el prototipo no inventa respuestas?** Cada estado y cada código salen del contrato `/api/v1` de la Semana 5; el selector de respuesta simulada incluye los 409 y los estados de dominio.
- **¿El aviso «ya no está libre» resuelve el conflicto de agenda?** No: es la recuperación en pantalla. La garantía es que Citas confirme con el mismo código, y por eso el riesgo queda abierto.
- **¿Por qué el color del foco se corrigió en la semana 12?** La Semana 9 se hizo con wireframes sin color final; la primera medición con color real es la de esta semana.
- **¿Qué pasa si cambia el tema del hospital?** La paleta está en variables del `:root` y la medición es un guion que se vuelve a ejecutar; el riesgo es de mantenimiento, no de diseño.
- **¿Qué no midieron?** El recorrido con un lector de pantalla instalado y el contraste con el tema institucional definitivo; ambos quedan para la Semana 13.
