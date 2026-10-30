# Requisitos del flujo «apertura y consulta autorizada del expediente longitudinal»

Los identificadores se usan en todas las semanas (diagramas, código, pruebas y matrices).

## Requisitos funcionales

| Id | Requisito | Implementado en |
|---|---|---|
| RF-EXP-01 | Abrir el expediente de un paciente activo: uno solo por paciente y hospital; repetir la apertura devuelve el mismo expediente. | `OpenMedicalRecord`, `MedicalRecord::open`, `PdoMedicalRecordRepository::open` |
| RF-EXP-02 | Consultar el expediente longitudinal: datos mínimos del paciente, datos del expediente y episodios del más reciente al más antiguo. | `ConsultLongitudinalRecord`, `LongitudinalRecord`, `LongitudinalPresenter` |
| RF-EXP-03 | Mostrar el resumen (número de episodios y episodios abiertos) y el estado del expediente. | `LongitudinalRecord::openEpisodes`, `vista.js` |
| RF-EXP-04 | Autorizar cada operación con el permiso RBAC del hospital y, para consultar, con un vínculo asistencial activo. | `AccessGuard`, `RbacAccessAuthorizer`, `PdoCareRelationship` |
| RF-EXP-05 | Registrar en la bitácora de Gobernanza cada apertura, consulta y denegación. | `GobernanzaClinicalAudit` |
| RF-EXP-06 | Informar cada error con un código estable y, si es recuperable, con una acción de recuperación. | `MedicalRecordController::respond`, `estados.js` |

## Requisitos no funcionales

| Id | Atributo | Requisito medible |
|---|---|---|
| RNF-EXP-01 | Confidencialidad | Ninguna respuesta de error, evento de auditoría ni log contiene nombre, motivo ni dato clínico; las respuestas con datos llevan `Cache-Control: no-store`. |
| RNF-EXP-02 | Integridad | Un expediente por paciente (UNIQUE), estados y fechas coherentes, episodios del mismo paciente, sin borrado. Datos inconsistentes → 500 sin datos. |
| RNF-EXP-03 | Aislamiento por tenant | Cada hospital usa su propia base; un usuario solo actúa donde RBAC le reconoce una membresía. |
| RNF-EXP-04 | Seguridad de entrada | Código de paciente `^[A-Za-z0-9][A-Za-z0-9_-]{0,39}$`; 100 % de SQL con sentencias preparadas. |
| RNF-EXP-05 | Resiliencia | Toda falla de infraestructura termina en 503 explícito; la apertura es idempotente; la interfaz limita los reintentos a 3. |
| RNF-EXP-06 | Mantenibilidad | Capas con dependencias hacia adentro (DIP), ningún archivo > 400 líneas, pruebas sin base compartida. |
| RNF-EXP-07 | Usabilidad y accesibilidad | WCAG 2.1 AA en teclado, foco, contraste ≥ 4.5:1 y etiquetas; uso completo entre 320 y 430 px. |
| RNF-EXP-08 | Trazabilidad | Cada denegación RBAC devuelve `trace_id` y el mismo valor queda en la auditoría (`rbac_trace`). |

## Criterios de aceptación (Dado / Cuando / Entonces)

| Id | Criterio | Prueba que lo verifica |
|---|---|---|
| CA-01 | **Dado** un paciente activo sin expediente y un usuario con permiso de apertura, **cuando** abre el expediente, **entonces** recibe 201 con `EXP-<código>` y queda el evento «abierto». | A7, N-09 |
| CA-02 | **Dado** un paciente que ya tiene expediente, **cuando** se repite la apertura (o dos procesos abren a la vez), **entonces** recibe 200 `created=false` con el mismo id y hay una sola fila. | A3, A6, N-09, P-03 |
| CA-03 | **Dado** un paciente fallecido, **cuando** se intenta abrir, **entonces** recibe 409 `PACIENTE_NO_ACTIVO`. | U-02, A4, N-10 |
| CA-04 | **Dado** un médico con asignación activa, **cuando** consulta, **entonces** recibe 200 con los episodios del más reciente al más antiguo y el resumen. | C7, N-01, U-05 |
| CA-05 | **Dado** un usuario sin `expediente.ver` en el hospital, **cuando** consulta, **entonces** recibe 403 `ACCESO_DENEGADO` con `trace_id` y sin datos. | C1, N-03, N-04, N-04b |
| CA-06 | **Dado** un usuario con permiso pero sin vínculo (o un código inexistente), **cuando** consulta, **entonces** recibe 403 `SIN_VINCULO_ASISTENCIAL` y queda auditado. | C3, C4, N-02, N-07 |
| CA-07 | **Dado** que la bitácora no responde, **cuando** se consulta o se abre, **entonces** recibe 503 `AUDITORIA_NO_DISPONIBLE`, sin datos y sin expediente guardado. | C6, A5, N-12, P-04 |
| CA-08 | **Dado** un código con más de 40 caracteres o caracteres no permitidos, **cuando** se envía, **entonces** recibe 422 antes de consultar cualquier base. | U-07, N-07c, N-07d, V-01 |
| CA-09 | **Dado** el mismo código de paciente en dos hospitales, **cuando** la misma médica consulta en cada uno, **entonces** cada respuesta sale de la base de su hospital. | P-08, N-05 |
| CA-10 | **Dado** un error del almacén, **cuando** se consulta, **entonces** recibe 503 `EXPEDIENTE_NO_DISPONIBLE` con solo el código. | N-11, P-06, P-09 |
