# Médicos, especialidades y disponibilidad — Semana 12: segunda evaluación parcial

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Entrega:** defensa de componentes, UX, accesibilidad y movilidad, con demostración sobre el prototipo.

**Tarea individual:** Defienda las decisiones de componentes, UX, accesibilidad y movilidad de Médicos, especialidades y disponibilidad. Demuestre con el prototipo cómo «registro de médico, asociación de especialidad y consulta de disponibilidad» trata roles, estados, errores y riesgos asociados con médicos, especialidades, horarios, disponibilidad y conflictos de agenda.

> La evaluación presencial ya se realizó; este documento deja por escrito la defensa con la evidencia de las Semanas 7 a 11 y la medición ejecutada en esta semana.

## Tesis de diseño

**El flujo se puede recorrer entero con un solo rol y sin backend, y cada pantalla dice qué está pasando.** Las tres decisiones que sostengo son:

1. **La misma regla para los tres roles.** Alta, asociación y disponibilidad se comportan igual para todos: lo que el rol no puede hacer, no aparece; lo que puede hacer, se hace con los estados, los errores y los códigos del contrato `/api/v1` de la [Semana 5](../semana-05/contrato-api.md).
2. **La UI no decide nada de negocio.** La pantalla muestra `status` y `slots` tal como llegan del dominio; el conflicto de agenda, la principal única y la vigencia los resuelven `Period`, `SpecialtyAssignmentPolicy` y `SlotPlanner` de la [Semana 7](../semana-07/README.md), y la base con sus exclusiones GiST.
3. **El error es un estado de la pantalla, no una excepción.** Cada 409, 422 o falta de conexión produce un mensaje con el motivo, la acción que lo resuelve y el `trace_id`; ninguno deja al usuario frente a una pantalla en blanco.

## Hilo de la entrega defendida

| Semana | Qué aportó | Evidencia |
|---|---|---|
| 7 | Componentes backend/frontend y refactorización del punto de mayor acoplamiento (la consulta de disponibilidad) | `semana-07/` (PR #396) |
| 8 | Flujo UX por rol, wireframes, estados, ayuda contextual y protección de datos | `semana-08/` (PR #402) |
| 9 | Evaluación de usabilidad (10 heurísticas) y accesibilidad (WCAG 2.1 AA): 9 hallazgos priorizados | `semana-09/` (PR #401) |
| 10 | Diseño para movilidad de 320 a 430 px: jerarquía, navegación, formularios por pasos y recuperación ante conexión limitada | `semana-10/` (PR #400) |
| 11 | Prototipo navegable desktop y móvil, mapa de navegación y 9 capturas | `semana-11/` (PR #408) |
| 12 | Defensa de la segunda evaluación parcial y medición de contraste, zoom y reflow | `semana-12/` (este PR) |

## Decisiones defendidas

### Componentes

| Decisión | Por qué la defiendo | Evidencia verificable |
|---|---|---|
| `AvailabilityPage.vue` pinta, no decide | La UI cruza horario con citas si calcula franjas; el dominio ya lo hace y se prueba sin base | Refactor de la [Semana 7](../semana-07/README.md): la regla de estado vive en `DoctorDayAvailability`, no en la pantalla |
| `QueryAvailability` solo orquesta | Decir `SCHEDULE_NOT_CONFIGURED` desde la aplicación es una regla de negocio en la capa equivocada | `SlotPlanner` y `Period` se prueban sin Laravel |
| `DbAppointmentReader` separado de `EloquentDoctorRepository` | Un cambio en el esquema de Citas no debe obligar a tocar el repositorio de médicos | Separación de puertos propuesta en la [Semana 7](../semana-07/README.md) |
| `DoctorScheduleProvider` como puerto | El esquema de 27 entidades no tiene horario; el módulo no puede inventar la tabla | `ConfigScheduleProvider` hoy; `doctor_schedules` propuesta a Javier |
| Formularios en el `FormRequest`, reglas en el dominio | El 422 del contrato debe nombrar el campo, y las reglas de negocio no son validación de entrada | Códigos `422 PAYLOAD_INVALID` de la [Semana 5](../semana-05/contrato-api.md) y suite de la Semana 4 |
| Botón deshabilitado mientras corre el alta | El alta no es idempotente: un doble clic intentaría duplicar al médico | H1 de la [Semana 9](../semana-09/README.md) |

### UX

| Decisión | Por qué la defiendo | Evidencia verificable |
|---|---|---|
| Cada rol entra a su tarea | La recepcionista no necesita el alta y el médico no necesita el filtro por especialidad | Comprobaciones R1 a R8 de la [evidencia](./evidencia-medicion.md) |
| Confirmación antes de registrar o asociar | Registrar y asociar no se pueden deshacer desde la pantalla | Resumen con «Corregir» por línea (H6) y comprobación E3 |
| «Elegir no reserva» junto al botón | Prometer una hora que otro puede tomar es el riesgo H2 del flujo | Comprobación E4 y captura del error crítico en la [Semana 11](../semana-11/capturas/04-error-franja-tomada-desktop.png) |
| «Corregir» lleva al campo, no al formulario | Corregir un dato cuesta más pasos y se termina confirmando sin corregir | H6 de la Semana 9, implementado en el resumen del alta |
| Solo se ofrecen opciones válidas | Especialidades activas y franjas libres; lo inválido no se muestra deshabilitado | Reglas de interacción de la [Semana 8](../semana-08/README.md) |
| El mensaje dice qué pasó y qué hacer | Un error sin acción deja al usuario llamar al soporte | Comprobaciones X1 a X6 |
| Sin datos de pacientes en la lista | Médicos solo necesita saber qué horas están ocupadas (RNF-MED-03) | Comprobación R5: la recepcionista no ve la licencia del médico |

### Accesibilidad

| Decisión | Por qué la defiendo | Evidencia verificable |
|---|---|---|
| Las franjas son un `radiogroup` por médico | Con lector de pantalla hay que saber a qué médico pertenece cada hora (H3) | Comprobación A1: «Franjas de Dra. Ana López» |
| Los errores se anuncian con `role="alert"` y el estado con `role="status"` | Un mensaje que no se anuncia no existe para quien no ve la pantalla (H2) | Comprobaciones A2 y A3 |
| Contorno de foco de 3 px en todo control | Sin foco visible, quien usa teclado no sabe dónde está (H9) | Comprobaciones A4 y A5 |
| Objetivos táctiles de 44 px | El flujo se usa en teléfono en la recepción | Comprobación A6 |
| Contraste medido, no supuesto | «Alto contraste» no es un número; la Semana 9 dejó dos criterios sin medir | 16 pares de color medidos en la [evidencia](./evidencia-medicion.md) |
| El foco también cumple 1.4.11 | El indicador de foco es información no textual: necesita 3:1, no solo existir | H10: `#f59e0b` (2,01:1) se corrigió a `#b45309` (4,69:1) |

### Movilidad

| Decisión | Por qué la defiendo | Evidencia verificable |
|---|---|---|
| Un dato clave por línea y nada de tablas | En 360 px una tabla de varias columnas no se lee | [Semana 10](../semana-10/README.md) y capturas móviles de la Semana 11 |
| Alta en cuatro pasos con validación por paso | Un formulario largo en móvil se abandona a mitad | Pasos 1 a 4 del prototipo y «Siguiente» con validación del campo |
| Acción principal fija abajo | Es la acción que la recepcionista repite cientos de veces al día | Comprobación T3 a 320 px: la acción sigue dentro de la ventana |
| Barra inferior en lugar de menú lateral | Dos destinos no justifican un menú; el botón «+» solo aparece con permiso | Navegación de la Semana 10 y comprobación R4 |
| Sin conexión: se ve la última consulta, no se elige | Prometer una hora sin poder confirmarla es peor que no prometer | Comprobación X5 |
| La consulta se reintenta una vez, el alta nunca | Repetir un POST sin saber si llegó duplicaría al médico | Comprobaciones X2 y X6 |

## Demostración con el prototipo

El prototipo es un solo archivo, funciona sin servidor y permite elegir el rol y la respuesta simulada: `../semana-11/prototipo/index.html`.

| # | Qué demuestro | Cómo se reproduce | Qué tiene que verse |
|---|---|---|---|
| 1 | **Rol administrador** | `?rol=admin#medicos` | «+ Registrar médico», «Asociar especialidad» y el número de colegiado |
| 2 | **Rol recepcionista** | `?rol=recepcion#disponibilidad` | Especialidad y fecha, tarjetas por médico, franjas libres y «Elegir franja (se confirma en Citas)» |
| 3 | **Rol médico** | `?rol=medico#disponibilidad` | «Mi disponibilidad», sin filtro por especialidad y sin acción de alta |
| 4 | **Alta en cuatro pasos** | `?rol=admin#alta` | «paso 1 de 4», ayuda de cada campo y «Corregir» por línea en el resumen |
| 5 | **Alta correcta** | `?rol=admin&demo=alta&registrar#alta` | «Médico registrado» con enlace a la ficha |
| 6 | **Error: licencia repetida** | `?rol=admin&sim=licencia&demo=alta` | «Ese número de colegiado ya está registrado» con `LICENSE_ALREADY_REGISTERED` y enlace al médico existente |
| 7 | **Alta sin respuesta** | `?rol=admin&sim=sin-respuesta&demo=alta` | «No sabemos si se guardó» y «Buscar licencia» antes de permitir repetir |
| 8 | **Asociar especialidad** | `?rol=admin#medicos` y el botón «Asociar especialidad» | Especialidad, interruptor «Principal» y el aviso «Ya tiene principal: Neurología» |
| 9 | **Error: principal única** | `?rol=admin&demo=asociar&principal` | «Ya tiene una principal en ese periodo» con `PRIMARY_SPECIALTY_CONFLICT` |
| 10 | **Sin permiso** | `?rol=recepcion#asociar` | «No tiene permiso para esta acción» |
| 11 | **Estado vacío** | `?rol=recepcion#disponibilidad` y especialidad Pediatría | «No hay médicos vigentes para esta especialidad» |
| 12 | **Médico sin horario** | `?rol=recepcion#disponibilidad` | «Dr. Beto Ruiz» con «Sin horario configurado», visible y no oculto |
| 13 | **Error crítico: conflicto de agenda** | `?rol=recepcion&sim=tomada&demo=elegir#disponibilidad` | «08:00 ya no está libre», el `trace_id` y la franja desaparece de la lista |
| 14 | **Sin conexión** | `?rol=recepcion&sim=sin-conexion#disponibilidad` | Banda «Sin conexión» con la hora de la última consulta; franjas y acción deshabilitadas |
| 15 | **Consulta sin respuesta** | `?rol=recepcion&sim=sin-respuesta&demo=elegir#disponibilidad` | «No se pudo consultar» con botón «Reintentar» |
| 16 | **Móvil a 360 px** | El mismo archivo en el modo de dispositivo del navegador | Barra inferior, acción principal fija abajo y objetivos de 44 px |

Los caminos de la tabla están verificados sobre el DOM real por el guion [`verificar-prototipo.mjs`](./evidencia/verificar-prototipo.mjs): **27 comprobaciones, 27 cumplen** (roles 8, estados 4, errores 6, accesibilidad 6, tamaño y reflow 3). Ver [evidencia-medicion.md](./evidencia-medicion.md).

## Matriz de roles y permisos

| Pantalla o acción | Administrador | Recepcionista | Médico | Permiso |
|---|---|---|---|---|
| Ver lista de médicos | Sí | Sí | Sí | `medicos.ver_disponibilidad` |
| Ver el número de colegiado | Sí | No | No | RNF-MED-04 |
| Registrar médico | Sí | No | No | `medicos.registrar` |
| Asociar especialidad | Sí | No | No | `medicos.asociar_especialidad` |
| Consultar disponibilidad por especialidad | Sí | Sí | No | `medicos.ver_disponibilidad` |
| «Mi disponibilidad» | No | No | Sí | `medicos.ver_disponibilidad` |
| Elegir franja para Citas | Sí | Sí | No | `medicos.ver_disponibilidad` |

## Matriz de estados y errores

| Situación | Estado o código | Qué ve el usuario | Área afectada |
|---|---|---|---|
| Sin médicos para la especialidad | `NO_DOCTORS_FOR_SPECIALTY` (200) | «No hay médicos vigentes para esta especialidad» | Especialidades |
| Médico sin horario | `SCHEDULE_NOT_CONFIGURED` | El médico aparece con «Sin horario configurado», sin franjas | Horarios |
| Licencia repetida | `LICENSE_ALREADY_REGISTERED` (409) | «Ese número de colegiado ya está registrado» + enlace al médico existente | Médicos |
| Principal ya existente | `PRIMARY_SPECIALTY_CONFLICT` (409) | «Ya tiene una principal en ese periodo» y se desmarca «Principal» | Especialidades |
| Especialidad ya asociada | `SPECIALTY_ALREADY_ASSOCIATED` (409) | «Ya tiene esa especialidad en ese periodo» | Especialidades |
| Vigencia fuera del perfil | `PERIOD_OUTSIDE_PROFILE` (422) | Error junto al campo de vigencia | Médicos |
| Tipo o dato inválido | `PAYLOAD_INVALID` (422) | Error junto al campo que lo provocó | Médicos |
| La franja se tomó mientras se elegía | Conflicto de agenda | «08:00 ya no está libre» con `trace_id`; la franja desaparece | Conflictos de agenda |
| La consulta no responde | `503` o sin respuesta | «No se pudo consultar» + «Reintentar» | Disponibilidad |
| El alta no responde | Sin respuesta | «No sabemos si se guardó» + «Buscar licencia» | Médicos |
| Sin conexión | RNF-MED-05 | Última consulta con su hora; no se puede elegir | Disponibilidad |
| Sin permiso | `403` | «No tiene permiso para esta acción» | Todas |

## Riesgos: estado al cerrar la entrega

| # | Riesgo | Área | Impacto | Estado |
|---|---|---|---|---|
| R1 | La franja se ocupa entre la consulta y la confirmación de Citas | Conflictos de agenda | La recepcionista promete una hora que ya no existe | **Mitigado en la UI**: aviso «Elegir no reserva», reconsulta al elegir, mensaje con `trace_id` y retiro de la franja. Sigue abierto en el servidor: Citas debe confirmar con el mismo código de conflicto |
| R2 | La tabla `doctor_schedules` depende de Javier | Horarios | Sin horario real, la disponibilidad real no se puede calcular | **Abierto**: el módulo usa `ConfigScheduleProvider`; la tabla está propuesta en el `README` del módulo de base de datos |
| R3 | La ausencia de médico no está modelada | Médicos | Un médico en vacaciones seguiría ofreciendo franjas | **Abierto**: es el caso práctico ya defendido en la [Semana 6](../semana-06/README.md) (`DoctorAbsenceReader`, `DOCTOR_ON_LEAVE`, `409 ABSENCE_HAS_APPOINTMENTS`) |
| R4 | Los permisos `medicos.*` no están en el catálogo de RBAC | Todas | El 403 del prototipo no se puede reproducir contra el HIS real | **Abierto**: depende de ASII-02 |
| R5 | RF-MED-06 consulta un día, no el rango de 14 días | Disponibilidad | La recepcionista no puede ver la semana completa | **Abierto**: declarado en la Semana 2 |
| R6 | `doctor_specialties` no permite cerrar una asociación | Especialidades | Un alta equivocada no se puede corregir desde la ficha (heurística 3) | **Abierto**: el contrato v1 no tiene ese endpoint |
| R7 | El slice H8 (`local_access_audits`) no tiene migración | Médicos | El alta no deja la traza de auditoría que exige RNF-MED-02 | **Abierto**: bloqueante para la integración |
| R8 | El tema institucional definitivo puede cambiar la paleta | Todas | Habría que re-medir el contraste | **Abierto**: la medición de esta semana es sobre la paleta del módulo |

## Hallazgos de accesibilidad: de H1 a H10

| # | Severidad | Área | Hallazgo | Estado al cierre de la semana 12 |
|---|---|---|---|---|
| H1 | Alta | Médicos | Ante «sin respuesta» en el alta se puede repetir y duplicar el intento | **Resuelto**: «No sabemos si se guardó» + «Buscar licencia» (comprobación X2) |
| H2 | Alta | Conflictos de agenda | Una franja elegida puede parecer reservada | **Resuelto**: aviso «Elegir no reserva» y reconsulta al elegir (comprobaciones E4 y X4) |
| H3 | Media | Disponibilidad | Las franjas no eran un grupo de opciones con nombre | **Resuelto**: `radiogroup` con «Franjas de Dra. Ana López» (comprobación A1) |
| H4 | Media | Especialidades | El aviso de principal existente no tenía lugar en pantalla | **Resuelto**: «Ya tiene principal: Neurología» junto al interruptor |
| H5 | Media | Disponibilidad | Sin «hoy» por defecto | **Resuelto**: la fecha de hoy viene por defecto y se puede cambiar |
| H6 | Media | Médicos y especialidades | «Corregir» volvía al formulario completo | **Resuelto**: «Corregir» por línea del resumen |
| H7 | Baja | Horarios | «Sin horario configurado» no orienta | **Resuelto**: «pida a administración que lo cargue» |
| H8 | Baja | Especialidades | Faltaba el formato de fecha en la etiqueta | **Resuelto**: «AAAA-MM-DD» en la ayuda del campo |
| H9 | Media | Todas | Sin foco visible definido | **Resuelto**: contorno de 3 px medido (comprobaciones A4 y A5) |
| H10 | Media | Todas | El indicador de foco no cumplía 1.4.11 (2,01:1) | **Resuelto en esta semana**: `#b45309` (4,69:1), ver [evidencia](./evidencia-medicion.md) |

## Qué responde el prototipo y qué no

| Pregunta de la defensa | Respuesta del prototipo | Evidencia |
|---|---|---|
| ¿Cada rol ve solo lo que puede hacer? | Sí: la UI filtra por permiso y el 403 cubre el acceso directo | R1 a R8 |
| ¿La pantalla distingue estados que no son errores? | Sí: vacío, sin horario, carga y éxito tienen su propio estado | E1, E2, E3 |
| ¿Los errores dicen qué hacer? | Sí: cada 409 y cada falta de respuesta trae la acción que lo resuelve | X1 a X6 |
| ¿El error crítico es recuperable sin recargar? | Sí: la franja se retira, se avisa y se elige otra | X4 |
| ¿Se puede usar sin conexión? | Se ve la última consulta, pero no se puede elegir franja | X5 |
| ¿Funciona con teclado y con lector de pantalla? | Con teclado, verificado; con lector de pantalla, verificado sobre los atributos ARIA, no sobre un lector instalado | A1 a A5 |
| ¿El contraste está medido? | Sí, 16 pares, y el incumplimiento del foco se corrigió | [evidencia](./evidencia-medicion.md) |
| ¿El contenido se conserva al 200 % y a 320 px? | Sí: sin desborde horizontal y con la acción principal visible | T1, T2, T3 |

## Preguntas que preparo para el comité

- **¿Por qué el prototipo no es una pantalla más del HIS?** Porque el diseño de la Semana 7 y las reglas de la Semana 10 ya están decididos y probados; lo que faltaba era poder mostrar el comportamiento sin backend. Con el mismo HTML se defienden los tres roles, los estados y los errores en un minuto.
- **¿Cómo saben que el prototipo no se inventó lo que el servidor devuelve?** Cada estado y cada código salen del contrato `/api/v1` de la Semana 5; el selector de respuesta simulada incluye los 409 y los estados de dominio (`NO_DOCTORS_FOR_SPECIALTY`, `SCHEDULE_NOT_CONFIGURED`).
- **¿Qué pasa con el conflicto de agenda en el servidor?** La UI avisa y retira la franja, pero el respaldo real es que Citas confirme con el mismo código; por eso R1 queda abierto y no se da por resuelto.
- **¿Por qué no corrigieron el color del foco antes de la semana 12?** Porque la Semana 9 se hizo con wireframes sin color final; la primera medición con color real es la de esta semana, y por eso se corrigió antes de la defensa y no durante.
- **¿Qué pasa si el hospital cambia el tema?** La paleta se declara en una sola variable por color en el `:root`; la medición es un guion que se vuelve a ejecutar, y por eso el riesgo R8 es de mantenimiento, no de diseño.

## Pendientes declarados

1. La tabla `doctor_schedules` y el slice H8 siguen dependiendo de otros módulos.
2. El caso de la ausencia de médico (`DOCTOR_ON_LEAVE`) queda para cuando Citas defina el 409.
3. La medición con lector de pantalla real y con el tema institucional definitivo se hace en la Semana 13.

Guion de la exposición: [presentacion.md](./presentacion.md) · Evidencia de medición: [evidencia-medicion.md](./evidencia-medicion.md) · Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md)
