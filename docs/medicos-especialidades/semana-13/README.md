# Médicos, especialidades y disponibilidad — Semana 13: revisión técnica formal

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Entrega:** plan de revisión técnica formal, checklist de 15 ítems, roles, criterios de entrada/salida y formato de hallazgos.

**Tarea individual:** Prepare una revisión técnica formal de Médicos, especialidades y disponibilidad, enfocada en reglas de dominio, contrato API, permisos/tenant, integridad de médicos, especialidades, horarios, disponibilidad y conflictos de agenda, y en las dependencias estrictamente necesarias para «registro de médico, asociación de especialidad y consulta de disponibilidad».

> La reunión de revisión se fija con los revisores; este documento es el plan y la preparación del autor. Los hallazgos de la preparación (ejecutada con el código real) están en [hallazgos.md](./hallazgos.md) y quedan en estado **Abierto** hasta que la reunión los confirme o los descarte.

## 1. Objeto y alcance

El objeto es el flujo de tres operaciones del contrato `/api/v1` de la [Semana 5](../semana-05/contrato-api.md):

| Operación | Ruta | Permiso |
|---|---|---|
| Registro de médico | `POST /api/v1/doctors` | `medicos.registrar` |
| Asociación de especialidad | `POST /api/v1/doctors/{id}/specialties` | `medicos.asociar_especialidad` |
| Consulta de disponibilidad | `GET /api/v1/availability` | `medicos.ver_disponibilidad` |

**Dentro del alcance** (solo lo que estas tres operaciones necesitan):

| Artefacto | Dónde está | Por qué es necesario |
|---|---|---|
| Dominio y casos de uso: `Period`, `SpecialtyAssignmentPolicy`, `SlotPlanner`, `RegisterDoctor`, `AssociateSpecialty`, `QueryAvailability` | `develop` | Reglas RF-MED-01 a RF-MED-07 |
| Persistencia: `PdoDoctorStore`, `ConfigScheduleProvider`, `InMemoryDoctorRepository` | PR #126 → #355 | Integridad y atomicidad (RNF-MED-02) |
| Entrada HTTP: `Router`, `DoctorController`, `Request` | PR #356 | Validación de entrada y códigos |
| Permisos y trazabilidad: `ApiKernel`, `PermissionChecker` | PR #372 | RNF-MED-01 |
| Contrato y OpenAPI | PR #371 | Fuente de verdad del contrato |

**Fuera del alcance:** la interfaz (revisada en las Semanas 8 a 12), las demás operaciones del módulo, la tabla `doctor_schedules` de la base (la propone ASII-09 en el PR #413; aquí solo importa como dependencia declarada del `DoctorScheduleProvider`) y el cliente HTTP.

## 2. Roles

| Rol | Responsabilidad | Quién |
|---|---|---|
| **Autor** | Entrega el paquete, responde preguntas, no defiende ni discute severidades, corrige lo aceptado | Josué Hicho García |
| **Moderador** | Conduce la reunión (60 min), vigila el tiempo, decide cuándo un tema pasa a fuera de reunión, firma la salida | Por confirmar: compañero de ASII-04 que no sea el autor |
| **Revisor A — dominio y contrato** | Ítems C01 a C08; consume el contrato como Citas | Propuesto: Lis (Citas) |
| **Revisor B — integridad y agenda** | Ítems C03, C12 a C14; conoce las restricciones de la base | Propuesto: Javier (ASII-09) |
| **Revisor C — permisos y tenant** | Ítems C09 a C11; define el catálogo RBAC | Propuesto: María (ASII-02) |
| **Escriba** | Registra cada hallazgo en el formato de la sección 5 | Por confirmar; no puede ser el autor |

Los nombres propuestos salen de los consumidores del contrato; no están confirmados. Nadie puede tener dos roles, salvo que el escriba sea uno de los revisores. El autor nunca modera ni escribe.

## 3. Criterios de entrada y salida

**Entrada** (el moderador no abre la reunión si falta alguno):

1. El paquete está publicado: contrato (#371), código de #126, #355, #356 y #372 y este plan.
2. Las suites pasan en la punta de la pila (#372): `php tests/run.php` y `php tests/api.php`, en 0 fallos.
3. Cada PR del paquete tiene menos de 400 líneas propias y su base declarada; los que dependen de otros están en borrador.
4. Los commits siguen `tipo(scope): descripción (ASII-04)` sin atribución de herramientas.
5. El autor entregó la autoverificación ([hallazgos.md](./hallazgos.md)) y los revisores prepararon sus ítems por separado antes de reunirse.

**Salida** (la revisión se cierra solo si se cumplen todos):

1. Los 15 ítems tienen resultado: `Cumple`, `No cumple` o `No aplica` con justificación.
2. Todo hallazgo tiene severidad, responsable y disposición (corregir, aceptar con motivo o diferir con fecha).
3. Cero hallazgos **Críticos** o **Mayores** sin corrección verificada o sin aceptación escrita del moderador.
4. Cada corrección se verifica con una prueba que fallaba antes y pasa después; no basta la palabra del autor.
5. Los hallazgos de otro módulo (RBAC, base de datos, Citas) quedan avisados al dueño; ninguno se corrige desde este módulo.

Resultado: **Aprobado**, **Aprobado con cambios** (se verifica sin nueva reunión) o **Repetir revisión** (un Crítico sin resolver, o más de 3 Mayores abiertos).

## 4. Checklist (15 ítems)

Resultado de la preparación del autor sobre la punta de la pila: ✔ cumple · ✖ no cumple · ⚠ cumple con reserva. El detalle y la evidencia de cada hallazgo (H-nn) están en [hallazgos.md](./hallazgos.md).

| # | Área | Pregunta de revisión | Evidencia que debe mirar | Prep. |
|---|---|---|---|---|
| C01 | Dominio | ¿La vigencia `[desde, hasta)` es coherente en perfil y asociaciones, y se rechaza lo que la rompe (RF-MED-04)? | `Period`, `SpecialtyAssignmentPolicy`; pruebas de vigencia | ✔ |
| C02 | Dominio | ¿Se rechaza la misma especialidad solapada y una segunda principal, también cuando la base es quien decide (RF-MED-05)? | Política + restricciones `EXCLUDE` de la migración 000100 | ⚠ H-06 |
| C03 | Dominio | ¿Licencia única y un solo perfil activo por usuario, con el código que dice el contrato (RF-MED-02)? | `RegisterDoctor`, índice único parcial del usuario | ✖ H-03 · ⚠ H-05 |
| C04 | Dominio | ¿Solo se usan especialidades activas al registrar, asociar y consultar (RF-MED-03)? | `SpecialtyCatalog`, prueba `SPECIALTY_INACTIVE` | ✔ |
| C05 | API | ¿Cada operación devuelve el estado y el `code` del contrato (201, 404, 409, 422, 500) en todos los caminos? | Tabla de códigos de la Semana 5 frente a las sondas | ⚠ H-08 |
| C06 | API | ¿Un campo con tipo incorrecto o un JSON malformado da `422 PAYLOAD_INVALID` sin guardar nada? | `Request`, pruebas de tipos | ✔ |
| C07 | API | ¿Toda respuesta, también la de error, lleva `trace_id` y `X-Trace-Id`, sin exponer detalle interno? | `ApiKernel::handle`, bitácora sin cuerpo | ✔ |
| C08 | API | ¿El contrato cubre lo que pide el requisito: rango de hasta 14 días y `generated_at` (RF-MED-06)? | RF-MED-06 frente a `availability?date=` | ✖ H-07 |
| C09 | Permisos | ¿Cada ruta exige su permiso `medicos.*` y una ruta nueva nace denegada, no permitida? | `ApiKernel::PERMISSIONS` frente a `Router` | ⚠ H-04 |
| C10 | Permisos | ¿Sin actor es 401, sin permiso 403 con el permiso faltante y con RBAC caído 503 (falla cerrada)? | Pruebas de `tests/api.php` | ✔ |
| C11 | Tenant | ¿Una petición con `X-Tenant-ID` ajeno recibe 401/403 y ninguna consulta cruza hospitales (RNF-MED-01)? | Búsqueda de `tenant` en el módulo | ✖ H-01 |
| C12 | Integridad | ¿Perfil, especialidad principal y auditoría son atómicos y un fallo revierte todo (RNF-MED-02)? | `PdoDoctorStore::transaction`, prueba de auditoría caída | ✔ |
| C13 | Agenda | ¿La disponibilidad es horario menos citas ocupadas, respetando la duración de la cita y sin datos del paciente (RNF-MED-03)? | `SlotPlanner`, `AppointmentReader`, columna `ends_at` | ✖ H-02 |
| C14 | Agenda | ¿Un médico sin horario se informa sin ocultarse y la dependencia de `doctor_schedules` está declarada (RF-MED-07)? | `ConfigScheduleProvider`, registro de coordinación | ⚠ H-09 |
| C15 | Proceso | ¿Los PR tienen menos de 400 líneas propias, commits limpios y pruebas ejecutadas? | `gh pr`, `git log` de las ramas | ⚠ H-10 · H-11 |

Resumen de la preparación: **6 ✔, 5 ⚠, 4 ✖**; ningún Crítico, **4 Mayores abiertos** (H-01, H-02, H-03, H-04) y 1 de proceso que requiere decisión (H-10).

## 5. Formato de hallazgos

Cada hallazgo se registra con estos campos; el escriba no lo da por cerrado hasta tener la verificación.

| Campo | Contenido |
|---|---|
| **ID** | `H-nn`, correlativo |
| **Ítem** | Código del checklist (C01 a C15) |
| **Severidad** | **Crítico** (pérdida o fuga de datos, o acceso sin permiso) · **Mayor** (incumple un requisito o miente al usuario) · **Medio** (código o mensaje incorrecto, sin pérdida) · **Menor** (claridad, orden, estilo) |
| **Ubicación** | Archivo y símbolo, o PR |
| **Evidencia** | Comando o prueba que lo reproduce y su salida |
| **Impacto** | Qué falla para qué rol y en qué momento |
| **Recomendación** | Cambio mínimo propuesto |
| **Responsable** | Persona y módulo dueño |
| **Estado** | Abierto → Confirmado / Descartado → Corregido → Verificado |
| **Disposición** | Corregir · Aceptar (con motivo y firma del moderador) · Diferir (con fecha) |

## 6. Procedimiento

| Paso | Quién | Producto |
|---|---|---|
| 1. Preparación | Autor | Paquete, autoverificación y sondas ([hallazgos.md](./hallazgos.md)) |
| 2. Revisión individual (45 min) | Revisores A, B y C | Hallazgos por ítem, sin comentarlos entre sí |
| 3. Reunión (60 min) | Moderador | Cada ítem se lee, se confirma o descarta; se asigna responsable |
| 4. Retrabajo | Autor y dueños | Una corrección por PR, con la prueba que antes fallaba |
| 5. Verificación | Moderador | Estado **Verificado** y resultado de salida |

Reglas de la reunión: se revisa el producto, no a la persona; no se rediseña en la reunión (se registra y se difiere); un hallazgo sin evidencia reproducible no se confirma.
